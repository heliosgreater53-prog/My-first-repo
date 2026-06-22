<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class PostActions
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
            'CREATE TABLE IF NOT EXISTS post_likes (
                post_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (post_id, user_id),
                INDEX user_id_idx (user_id),
                INDEX post_id_idx (post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS post_comments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                post_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                parent_id INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL,
                INDEX post_id_idx (post_id),
                INDEX parent_id_idx (parent_id),
                INDEX user_id_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function toggleLike(int $postId, int $userId): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $stmt = $this->connection->prepare('SELECT 1 FROM post_likes WHERE post_id = :post_id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['post_id' => $postId, 'user_id' => $userId]);
        $exists = (bool) $stmt->fetchColumn();

        if ($exists) {
            $del = $this->connection->prepare('DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id');
            return $del->execute(['post_id' => $postId, 'user_id' => $userId]);
        }

        $ins = $this->connection->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)');
        return $ins->execute(['post_id' => $postId, 'user_id' => $userId]);
    }

    public function likeCount(int $postId): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
        $stmt->execute(['post_id' => $postId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function createComment(int $postId, int $userId, string $body, ?int $parentId = null): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $body = trim($body);
        if ($body === '') {
            return 0;
        }

        $parentId = $parentId !== null && $parentId > 0 ? $parentId : null;

        $stmt = $this->connection->prepare(
            'INSERT INTO post_comments (post_id, user_id, body, parent_id)
             VALUES (:post_id, :user_id, :body, :parent_id)'
        );

        $ok = $stmt->execute([
            'post_id' => $postId,
            'user_id' => $userId,
            'body' => $body,
            'parent_id' => $parentId,
        ]);

        if (!$ok) {
            return 0;
        }

        return (int) $this->connection->lastInsertId();
    }

    public function commentsCount(int $postId): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM post_comments WHERE post_id = :post_id AND deleted_at IS NULL');
        $stmt->execute(['post_id' => $postId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function commentsTree(int $postId): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }


        $stmt = $this->connection->prepare(
            'SELECT c.id, c.post_id, c.user_id, c.body, c.parent_id, c.created_at,
                    u.name, u.avatar_path, u.role AS user_role
             FROM post_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :post_id
               AND c.deleted_at IS NULL
             ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute(['post_id' => $postId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byId = [];
        $roots = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $row['id'] = $id;
            $row['parent_id'] = $parentId;
            $row['children'] = [];
            $byId[$id] = $row;
        }

        foreach ($byId as $id => $node) {
            $parentId = $node['parent_id'];
            if ($parentId === null || !isset($byId[$parentId])) {
                $roots[] = $node;
            } else {
                $byId[$parentId]['children'][] = $node;
            }
        }

        return $roots;
    }
}

