import { Effect, Schedule, Runtime, Console, Schema } from "effect";
import { WebSocketServer, WebSocket } from "ws";
import { pack, unpack } from "msgpackr";
import * as http from "http";

// 1. Define a Schema for validation and type safety
const RealtimeEvent = Schema.Struct({
  room: Schema.String,
  type: Schema.Literal("new_message", "typing", "presence_update"),
  data: Schema.Unknown,
});

type RealtimeEvent = Schema.Schema.Type<typeof RealtimeEvent>;

/**
 * Utility to read the body of an HTTP request as an Effect.
 */
const readBody = (req: http.IncomingMessage) =>
  Effect.callback<string, Error>((resume) => {
    let body = "";
    req.on("data", (chunk) => (body += chunk));
    req.on("error", (err) => resume(Effect.fail(err)));
    req.on("end", () => resume(Effect.succeed(body)));
  });

/**
 * Creates and manages the WebSocket lifecycle using Effect.
 * This handles room subscriptions and broadcasts.
 */
const makeWss = (port: number) =>
  Effect.acquireRelease(
    Effect.sync(() => {
      const server = http.createServer();
      const wss = new WebSocketServer({ server });

      // Map to track room subscriptions: roomSlug -> Set of WebSockets
      const roomSubscriptions = new Map<string, Set<WebSocket>>();

      wss.on("connection", (ws) => {
        let subscribedRoom: string | null = null;

        ws.on("message", (raw) => {
          try {
            const event = unpack(raw as Buffer);
            if (event.type === "subscribe" && typeof event.room === "string") {
              subscribedRoom = event.room;
              let subs = roomSubscriptions.get(subscribedRoom);
              if (!subs) {
                subs = new Set();
                roomSubscriptions.set(subscribedRoom, subs);
              }
              subs.add(ws);
            }
          } catch (e) {
            console.error("Invalid binary payload received");
          }
        });

        ws.on("close", () => {
          if (subscribedRoom) {
            const subs = roomSubscriptions.get(subscribedRoom);
            if (subs) {
              subs.delete(ws);
              if (subs.size === 0) {
                roomSubscriptions.delete(subscribedRoom);
              }
            }
          }
        });
      });

      // Internal notify endpoint: PHP backend calls this when a message is saved
      server.on("request", (req, res) => {
        if (req.method === "POST" && req.url === "/notify") {
          let body = "";
          req.on("data", (chunk) => (body += chunk));
          req.on("end", () => {
            try {
              const payload = JSON.parse(body) as RealtimeEvent;
              const subscribers = roomSubscriptions.get(payload.room);
              if (subscribers) {
                const packed = pack(payload);
                subscribers.forEach((s) => {
                  if (s.readyState === WebSocket.OPEN) s.send(packed);
                });
              }
              res.writeHead(200);
              res.end("ok");
            } catch (err) {
              res.writeHead(400);
              res.end("invalid json");
            }
          });
        } else {
          res.writeHead(404);
          res.end();
        }
      });

      server.listen(port, () => {
        Console.log(
          `[LetsChat Realtime] Server active on ws://localhost:${port}`,
        );
      });

      return { wss, server };
    }),
    ({ wss, server }) =>
      Effect.sync(() => {
        wss.close();
        server.close();
      }),
  );

const program = Effect.gen(function* () {
  yield* makeWss(8080);
  yield* Effect.never; // Keep alive
}).pipe(
  Effect.tapError((e) =>
    Console.error(`[LetsChat Realtime] Fatal error: ${e}`),
  ),
  Effect.retry(Schedule.spaced("2 seconds")),
  Effect.scoped,
);

Runtime.runMain(program);
