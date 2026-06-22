<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class DmRequest
{
    private ?PDO $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connection();
    }

    public function migrate(): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS dm_requests (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                requester_id INT UNSIGNED NOT NULL,
                recipient_id INT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                room_id INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY dm_request_pair_unique (requester_id, recipient_id),
                INDEX recipient_status_idx (recipient_id, status),
                INDEX requester_status_idx (requester_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function createPending(int $requesterId, int $recipientId): bool
    {
        if (!$this->connection instanceof PDO || $requesterId <= 0 || $recipientId <= 0 || $requesterId === $recipientId) {
            return false;
        }

        $existing = $this->betweenUsers($requesterId, $recipientId);
        if ($existing !== null && ($existing['status'] ?? '') === 'accepted') {
            return true;
        }

        if ($existing !== null && ($existing['status'] ?? '') === 'pending') {
            return true;
        }

        if ($existing !== null) {
            $statement = $this->connection->prepare(
                "UPDATE dm_requests
                 SET requester_id = :requester_id, recipient_id = :recipient_id, status = 'pending', room_id = NULL
                 WHERE id = :id"
            );
            $statement->execute([
                'id' => (int) $existing['id'],
                'requester_id' => $requesterId,
                'recipient_id' => $recipientId,
            ]);

            return true;
        }

        $statement = $this->connection->prepare(
            "INSERT INTO dm_requests (requester_id, recipient_id, status)
             VALUES (:requester_id, :recipient_id, 'pending')"
        );

        return $statement->execute([
            'requester_id' => $requesterId,
            'recipient_id' => $recipientId,
        ]);
    }

    public function betweenUsers(int $userA, int $userB): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT *
             FROM dm_requests dm
             WHERE (requester_id = :user_a AND recipient_id = :user_b)
                OR (requester_id = :user_b AND recipient_id = :user_a)
             LIMIT 1'
        );
        $statement->execute([
            'user_a' => $userA,
            'user_b' => $userB,
        ]);

        $request = $statement->fetch();

        return is_array($request) ? $request : null;
    }

    public function inboxForUser(int $userId): array
    {
        return $this->requestsForUser($userId, 'recipient');
    }

    public function sentForUser(int $userId): array
    {
        return $this->requestsForUser($userId, 'requester');
    }

    public function pendingCountForUser(int $userId): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $statement = $this->connection->prepare(
            "SELECT COUNT(*) FROM dm_requests WHERE recipient_id = :user_id AND status = 'pending'"
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function respond(int $requestId, int $recipientId, string $status, ?int $roomId = null): bool
    {
        if (!$this->connection instanceof PDO || !in_array($status, ['accepted', 'declined'], true)) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE dm_requests
             SET status = :status, room_id = :room_id
             WHERE id = :id AND recipient_id = :recipient_id AND status = "pending"'
        );
        $statement->execute([
            'id' => $requestId,
            'recipient_id' => $recipientId,
            'status' => $status,
            'room_id' => $roomId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function findPendingForRecipient(int $requestId, int $recipientId): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            "SELECT r.*, u.name AS requester_name, u.email AS requester_email, u.class_name AS requester_class, u.avatar_path AS requester_avatar
             FROM dm_requests r
             INNER JOIN users u ON u.id = r.requester_id
             WHERE r.id = :id AND r.recipient_id = :recipient_id AND r.status = 'pending'
             LIMIT 1"
        );
        $statement->execute([
            'id' => $requestId,
            'recipient_id' => $recipientId,
        ]);

        $request = $statement->fetch();

        return is_array($request) ? $request : null;
    }

    public function statusMapForUser(int $viewerId): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT dm.requester_id, dm.recipient_id, dm.status, rooms.slug AS room_slug
             FROM dm_requests dm
             LEFT JOIN rooms ON rooms.id = dm.room_id
             WHERE dm.requester_id = :viewer_id OR dm.recipient_id = :viewer_id'
        );
        $statement->execute(['viewer_id' => $viewerId]);
        $map = [];

        foreach ($statement->fetchAll() ?: [] as $row) {
            $otherId = (int) $row['requester_id'] === $viewerId ? (int) $row['recipient_id'] : (int) $row['requester_id'];
            $direction = (int) $row['requester_id'] === $viewerId ? 'sent' : 'received';
            $map[$otherId] = [
                'status' => (string) $row['status'],
                'direction' => $direction,
                'room_slug' => (string) ($row['room_slug'] ?? ''),
            ];
        }

        return $map;
    }

    public function requestsForUser(int $userId, string $side): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $userColumn = $side === 'recipient' ? 'recipient_id' : 'requester_id';
        $joinColumn = $side === 'recipient' ? 'requester_id' : 'recipient_id';
        $aliasPrefix = $side === 'recipient' ? 'requester' : 'recipient';

        $statement = $this->connection->prepare(
            "SELECT r.*, u.name AS {$aliasPrefix}_name, u.email AS {$aliasPrefix}_email,
                    u.class_name AS {$aliasPrefix}_class, u.avatar_path AS {$aliasPrefix}_avatar
             FROM dm_requests r
             INNER JOIN users u ON u.id = r.{$joinColumn}
             WHERE r.{$userColumn} = :user_id AND r.status = 'pending'
             ORDER BY r.created_at DESC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll() ?: [];
    }
}
