<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class UserNotification
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
            'CREATE TABLE IF NOT EXISTS user_notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                type VARCHAR(40) NOT NULL DEFAULT "mention",
                message_id INT UNSIGNED DEFAULT NULL,
                from_user_id INT UNSIGNED DEFAULT NULL,
                body VARCHAR(255) NOT NULL,
                room_slug VARCHAR(120) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX user_read_idx (user_id, is_read),
                INDEX created_idx (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function create(int $userId, string $type, string $body, ?int $messageId = null, ?int $fromUserId = null, ?string $roomSlug = null): void
    {
        if (!$this->connection instanceof PDO || $userId <= 0) {
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO user_notifications (user_id, type, message_id, from_user_id, body, room_slug)
             VALUES (:user_id, :type, :message_id, :from_user_id, :body, :room_slug)'
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'message_id' => $messageId,
            'from_user_id' => $fromUserId,
            'body' => mb_substr($body, 0, 255),
            'room_slug' => $roomSlug,
        ]);
    }

    public function unreadCount(int $userId): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM user_notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function recentForUser(int $userId, int $limit = 20): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT n.*, u.name AS from_name
             FROM user_notifications n
             LEFT JOIN users u ON u.id = n.from_user_id
             WHERE n.user_id = :user_id
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT :lim'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':lim', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function markAllRead(int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'UPDATE user_notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);
    }
}
