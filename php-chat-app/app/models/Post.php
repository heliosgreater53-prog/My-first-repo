<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class Post
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
            'CREATE TABLE IF NOT EXISTS posts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                post_type VARCHAR(20) NOT NULL DEFAULT \'message\',
                assignment_title VARCHAR(120) DEFAULT NULL,
                due_at DATETIME DEFAULT NULL,
                scheduled_at DATETIME DEFAULT NULL,
                attachment_path VARCHAR(255) DEFAULT NULL,
                attachment_type VARCHAR(40) DEFAULT NULL,
                attachment_name VARCHAR(190) DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX user_id_idx (user_id),
                INDEX post_type_idx (post_type),
                INDEX scheduled_idx (scheduled_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function allLatest(int $limit = 50, string $classFilter = '', string $feedFilter = 'all'): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $this->publishDueScheduled();

        $sql = "SELECT p.id,
                    p.body,
                    p.created_at,
                    p.deleted_at,
                    p.post_type,
                    p.assignment_title,
                    p.due_at,
                    p.attachment_path,
                    p.attachment_type,
                    p.attachment_name,
                    u.id AS user_id,
                    u.name,
                    u.class_name,
                    u.avatar_path,
                    u.role AS user_role
                 FROM posts p
                 INNER JOIN users u ON u.id = p.user_id
                 WHERE p.deleted_at IS NULL
                   AND p.body != '[expired]'
                   AND p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                   AND (p.scheduled_at IS NULL OR p.scheduled_at <= NOW())";

        $params = [];

        if ($classFilter !== '') {
            $sql .= ' AND u.class_name = :class_filter';
            $params['class_filter'] = $classFilter;
        }

        if ($feedFilter === 'announcements') {
            $sql .= " AND p.post_type = 'announcement'";
        } elseif ($feedFilter === 'assignments') {
            $sql .= " AND p.post_type = 'assignment'";
        }

        $sql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT :message_limit';

        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':message_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function create(
        int $userId,
        string $body,
        string $postType = 'message',
        ?string $assignmentTitle = null,
        ?string $dueAt = null,
        ?string $scheduledAt = null,
        array $attachment = []
    ): int {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO posts (user_id, body, post_type, assignment_title, due_at, scheduled_at, attachment_path, attachment_type, attachment_name)
             VALUES (:user_id, :body, :post_type, :assignment_title, :due_at, :scheduled_at, :attachment_path, :attachment_type, :attachment_name)'
        );
        $statement->execute([
            'user_id' => $userId,
            'body' => $body,
            'post_type' => in_array($postType, ['message', 'announcement', 'assignment'], true) ? $postType : 'message',
            'assignment_title' => $assignmentTitle,
            'due_at' => $dueAt,
            'scheduled_at' => $scheduledAt,
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_type' => $attachment['type'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function publishDueScheduled(): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        // 1) After scheduled posts are due, remove scheduled_at so they become visible.
        $this->connection->exec(
            'UPDATE posts SET scheduled_at = NULL WHERE scheduled_at IS NOT NULL AND scheduled_at <= NOW()'
        );

        // 2) Expire feed posts automatically after 24 hours.
        // Soft-expire by setting deleted_at + body marker.
        $this->connection->exec(
            'UPDATE posts SET deleted_at = NOW(), body = "[expired]" '
            . 'WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND deleted_at IS NULL'
        );
    }

    // Hard cleanup helper (optional). Not used on hot paths.
    public function purgeExpired(int $batchSize = 500): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $stmt = $this->connection->prepare(
            'SELECT id FROM posts WHERE deleted_at IS NOT NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY id ASC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($ids) || $ids === []) {
            return;
        }

        $in = implode(',', array_map('intval', $ids));
        $this->connection->exec('DELETE FROM posts WHERE id IN (' . $in . ')');
    }

    public function deleteIfAllowed(int $postId, int $userId, bool $isAdmin = false): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $sql = 'UPDATE posts SET deleted_at = NOW(), body = "[deleted]" WHERE id = :id';
        $params = ['id' => $postId];

        if (!$isAdmin) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->connection->prepare($sql);
        return $statement->execute($params);
    }
}