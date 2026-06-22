<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class UserBlock
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
            'CREATE TABLE IF NOT EXISTS user_blocks (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                blocker_id INT UNSIGNED NOT NULL,
                blocked_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY block_pair (blocker_id, blocked_id),
                INDEX blocker_idx (blocker_id),
                INDEX blocked_idx (blocked_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function block(int $blockerId, int $blockedId): bool
    {
        if ($blockerId <= 0 || $blockedId <= 0 || $blockerId === $blockedId || !$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (:blocker_id, :blocked_id)'
        );

        return $statement->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
        ]);
    }

    public function unblock(int $blockerId, int $blockedId): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'DELETE FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id'
        );

        return $statement->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
        ]);
    }

    public function isBlocked(int $blockerId, int $blockedId): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT 1 FROM user_blocks WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id LIMIT 1'
        );
        $statement->execute([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
        ]);

        return (bool) $statement->fetch();
    }

    /** @return list<int> */
    public function blockedIdsFor(int $userId): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT blocked_id FROM user_blocks WHERE blocker_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map('intval', array_column($statement->fetchAll() ?: [], 'blocked_id'));
    }
}
