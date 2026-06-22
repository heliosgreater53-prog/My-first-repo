<?php
declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Core\Request;

class RateLimitMiddleware
{
    private const LIMITS = [
        'login' => ['max' => 10, 'window' => 300],
        'signup' => ['max' => 5, 'window' => 600],
        'message' => ['max' => 60, 'window' => 60],
        'report' => ['max' => 15, 'window' => 300],
    ];

    public function handle(Request $request): void
    {
        $bucket = $this->bucketFor($request);
        if ($bucket === null) {
            return;
        }

        $config = self::LIMITS[$bucket];
        $key = '_rate_' . $bucket;
        $now = time();
        $data = session()->get($key, ['count' => 0, 'reset' => $now + $config['window']]);

        if ($now >= (int) ($data['reset'] ?? 0)) {
            $data = ['count' => 0, 'reset' => $now + $config['window']];
        }

        if ((int) $data['count'] >= $config['max']) {
            http_response_code(429);
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Too many requests. Please wait a moment.']);
            } else {
                session()->flash('toast', 'Too many requests. Please wait a moment.');
                session()->flash('toast_type', 'error');
                echo 'Too many requests.';
            }
            exit;
        }

        $data['count'] = (int) $data['count'] + 1;
        session()->put($key, $data);
    }

    private function bucketFor(Request $request): ?string
    {
        $path = $request->path();
        $method = $request->method();

        if ($method === 'POST' && $path === '/login') {
            return 'login';
        }
        if ($method === 'POST' && $path === '/signup') {
            return 'signup';
        }
        if ($method === 'POST' && $path === '/chat/messages') {
            return 'message';
        }
        if ($method === 'POST' && $path === '/chat/messages/report') {
            return 'report';
        }

        return null;
    }
}
