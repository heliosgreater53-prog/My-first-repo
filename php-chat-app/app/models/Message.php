<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class Message
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
            'CREATE TABLE IF NOT EXISTS messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                room_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                reply_to_id INT UNSIGNED DEFAULT NULL,
                body TEXT NOT NULL,
                attachment_path VARCHAR(255) DEFAULT NULL,
                attachment_type VARCHAR(40) DEFAULT NULL,
                attachment_name VARCHAR(190) DEFAULT NULL,
                is_pinned TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX room_id_idx (room_id),
                INDEX user_id_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->ensureColumn('reply_to_id', 'ALTER TABLE messages ADD reply_to_id INT UNSIGNED DEFAULT NULL AFTER user_id');
        $this->ensureColumn('attachment_path', 'ALTER TABLE messages ADD attachment_path VARCHAR(255) DEFAULT NULL AFTER body');
        $this->ensureColumn('attachment_type', 'ALTER TABLE messages ADD attachment_type VARCHAR(40) DEFAULT NULL AFTER attachment_path');
        $this->ensureColumn('attachment_name', 'ALTER TABLE messages ADD attachment_name VARCHAR(190) DEFAULT NULL AFTER attachment_type');
        $this->ensureColumn('is_pinned', 'ALTER TABLE messages ADD is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER attachment_name');
        $this->ensureColumn('deleted_at', 'ALTER TABLE messages ADD deleted_at DATETIME DEFAULT NULL AFTER body');
        $this->ensureColumn('post_type', "ALTER TABLE messages ADD post_type VARCHAR(20) NOT NULL DEFAULT 'message' AFTER is_pinned");
        $this->ensureColumn('assignment_title', 'ALTER TABLE messages ADD assignment_title VARCHAR(120) DEFAULT NULL AFTER post_type');
        $this->ensureColumn('due_at', 'ALTER TABLE messages ADD due_at DATETIME DEFAULT NULL AFTER assignment_title');
        $this->ensureColumn('scheduled_at', 'ALTER TABLE messages ADD scheduled_at DATETIME DEFAULT NULL AFTER due_at');

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS message_reactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                reaction VARCHAR(12) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY message_user_reaction_unique (message_id, user_id, reaction),
                INDEX message_idx (message_id),
                INDEX user_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS typing_indicators (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                room_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY room_user_unique (room_id, user_id),
                INDEX room_idx (room_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function latestForRoom(int $roomId, int $limit = 50): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT m.id,
                    m.body,
                    m.created_at,
                    m.deleted_at,
                    m.reply_to_id,
                    m.attachment_path,
                    m.attachment_type,
                    m.attachment_name,
                    m.is_pinned,
                    m.post_type,
                    m.assignment_title,
                    m.due_at,
                    u.id AS user_id,
                    u.name,
                    u.class_name,
                    u.avatar_path,
                    u.role AS user_role,
                    r.name AS room_name,
                    r.slug AS room_slug,
                    (
                        SELECT COUNT(*)
                        FROM messages replies
                        WHERE replies.reply_to_id = m.id AND replies.deleted_at IS NULL
                    ) AS reply_count,
                    rp.body AS reply_body,
                    ru.name AS reply_name
             FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN rooms r ON r.id = m.room_id
             LEFT JOIN messages rp ON rp.id = m.reply_to_id
             LEFT JOIN users ru ON ru.id = rp.user_id
             WHERE m.room_id = :room_id
               AND m.deleted_at IS NULL
               AND (m.scheduled_at IS NULL OR m.scheduled_at <= NOW())
             ORDER BY m.id DESC
             LIMIT :message_limit'
        );
        $statement->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $statement->bindValue(':message_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $messages = array_reverse($statement->fetchAll() ?: []);
        return $this->withReactions($messages);
    }

    public function publishDueScheduled(): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $this->connection->exec(
            'UPDATE messages SET scheduled_at = NULL WHERE scheduled_at IS NOT NULL AND scheduled_at <= NOW()'
        );
    }

    public function allPublicLatest(int $limit = 50, string $classFilter = '', string $feedFilter = 'all'): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $this->publishDueScheduled();

        $sql = "SELECT m.id,
                    m.room_id,
                    m.body,
                    m.created_at,
                    m.deleted_at,
                    m.reply_to_id,
                    m.attachment_path,
                    m.attachment_type,
                    m.attachment_name,
                    m.is_pinned,
                    m.post_type,
                    m.assignment_title,
                    m.due_at,
                    u.id AS user_id,
                    u.name,
                    u.class_name,
                    u.avatar_path,
                    u.role AS user_role,
                    r.name AS room_name,
                    r.slug AS room_slug,
                    (
                        SELECT COUNT(*)
                        FROM messages replies
                        WHERE replies.reply_to_id = m.id AND replies.deleted_at IS NULL
                    ) AS reply_count,
                    rp.body AS reply_body,
                    ru.name AS reply_name
             FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN rooms r ON r.id = m.room_id
             LEFT JOIN messages rp ON rp.id = m.reply_to_id
             LEFT JOIN users ru ON ru.id = rp.user_id
             WHERE r.scope = 'public'
               AND m.deleted_at IS NULL
               AND (m.scheduled_at IS NULL OR m.scheduled_at <= NOW())";
        $params = [];

        if ($classFilter !== '') {
            $sql .= ' AND u.class_name = :class_filter';
            $params['class_filter'] = $classFilter;
        }

        if ($feedFilter === 'announcements') {
            $sql .= " AND (m.post_type = 'announcement' OR m.body LIKE '[Announcement]%' OR r.slug = 'notice-board')";
        } elseif ($feedFilter === 'assignments') {
            $sql .= " AND m.post_type = 'assignment'";
        }

        $sql .= ' ORDER BY m.is_pinned DESC, m.id DESC LIMIT :message_limit';

        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':message_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $messages = $statement->fetchAll() ?: [];
        return $this->withReactions($messages);
    }

    public function pinnedForRoom(int $roomId, int $limit = 5): array
    {
        if (!$this->connection instanceof PDO || $roomId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT m.id, m.body, m.created_at, u.name
             FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.room_id = :room_id AND m.is_pinned = 1 AND m.deleted_at IS NULL
             ORDER BY m.id DESC
             LIMIT :lim'
        );
        $statement->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $statement->bindValue(':lim', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function search(string $term, int $userId, string $className, bool $isAdmin, int $limit = 40): array
    {
        if (!$this->connection instanceof PDO || trim($term) === '') {
            return [];
        }

        $this->publishDueScheduled();
        $like = '%' . trim($term) . '%';

        $statement = $this->connection->prepare(
            "SELECT m.id, m.body, m.created_at, m.room_id, u.name, u.class_name, r.name AS room_name, r.slug AS room_slug
             FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN rooms r ON r.id = m.room_id
             LEFT JOIN room_memberships rm ON rm.room_id = r.id AND rm.user_id = :viewer_id
             WHERE m.deleted_at IS NULL
               AND (m.scheduled_at IS NULL OR m.scheduled_at <= NOW())
               AND m.body LIKE :term
               AND (
                   :is_admin = 1
                   OR r.scope = 'public'
                   OR (r.scope = 'class' AND r.class_name = :class_name)
                   OR rm.user_id IS NOT NULL
               )
             ORDER BY m.created_at DESC
             LIMIT :lim"
        );
        $statement->bindValue(':viewer_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':is_admin', $isAdmin ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(':class_name', $className);
        $statement->bindValue(':term', $like);
        $statement->bindValue(':lim', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function sinceForRoom(int $roomId, int $afterId, int $limit = 50): array
    {
        if (!$this->connection instanceof PDO || $roomId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT m.id FROM messages m
             WHERE m.room_id = :room_id AND m.id > :after_id AND m.deleted_at IS NULL
             ORDER BY m.id ASC
             LIMIT :lim'
        );
        $statement->bindValue(':room_id', $roomId, PDO::PARAM_INT);
        $statement->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $statement->bindValue(':lim', $limit, PDO::PARAM_INT);
        $statement->execute();

        if ((int) $statement->rowCount() === 0) {
            return [];
        }

        return $this->latestForRoom($roomId, $limit);
    }

    public function recentForAdmin(int $limit = 100, int $roomId = 0, int $offset = 0, string $className = ''): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $sql = 'SELECT m.id,
                       m.body,
                       m.created_at,
                       m.deleted_at,
                       m.attachment_path,
                       m.attachment_type,
                       m.attachment_name,
                       m.is_pinned,
                       u.id AS user_id,
                       u.name,
                       u.email,
                       u.class_name,
                       u.role,
                       u.is_active,
                       u.muted_until,
                       CASE
                           WHEN u.muted_until IS NOT NULL AND u.muted_until > NOW() THEN 1
                           ELSE 0
                       END AS is_muted,
                       r.id AS room_id,
                       r.name AS room_name,
                       r.slug AS room_slug,
                       r.scope
                FROM messages m
                INNER JOIN users u ON u.id = m.user_id
                INNER JOIN rooms r ON r.id = m.room_id';
        $params = [];

        $where = [];
        if ($roomId > 0) {
            $where[] = 'm.room_id = :room_id';
            $params['room_id'] = $roomId;
        }
        if ($className !== '') {
            $where[] = 'u.class_name = :class_name';
            $params['class_name'] = $className;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT :message_limit OFFSET :message_offset';

        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':message_limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':message_offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function create(
        int $roomId,
        int $userId,
        string $body,
        ?int $replyToId = null,
        array $attachment = [],
        string $postType = 'message',
        ?string $assignmentTitle = null,
        ?string $dueAt = null,
        ?string $scheduledAt = null,
    ): int {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO messages (room_id, user_id, reply_to_id, body, attachment_path, attachment_type, attachment_name, post_type, assignment_title, due_at, scheduled_at)
             VALUES (:room_id, :user_id, :reply_to_id, :body, :attachment_path, :attachment_type, :attachment_name, :post_type, :assignment_title, :due_at, :scheduled_at)'
        );
        $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
            'reply_to_id' => $replyToId,
            'body' => $body,
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_type' => $attachment['type'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'post_type' => in_array($postType, ['message', 'announcement', 'assignment'], true) ? $postType : 'message',
            'assignment_title' => $assignmentTitle,
            'due_at' => $dueAt,
            'scheduled_at' => $scheduledAt,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findInRoom(int $roomId, int $messageId): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT m.id, m.body, m.user_id, m.deleted_at, u.name
             FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.room_id = :room_id AND m.id = :id
             LIMIT 1'
        );
        $statement->execute([
            'room_id' => $roomId,
            'id' => $messageId,
        ]);
        $message = $statement->fetch();

        return is_array($message) ? $message : null;
    }

    public function updateIfAllowed(int $messageId, int $roomId, int $userId, string $body): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE messages
             SET body = :body
             WHERE id = :id
               AND room_id = :room_id
               AND user_id = :user_id
               AND deleted_at IS NULL'
        );

        $statement->execute([
            'id' => $messageId,
            'room_id' => $roomId,
            'user_id' => $userId,
            'body' => $body,
        ]);

        return $statement->rowCount() > 0;
    }

    public function deleteIfAllowed(int $messageId, int $roomId, int $userId, bool $isAdmin = false): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $sql = 'UPDATE messages SET deleted_at = NOW(), body = "[deleted]" WHERE id = :id AND room_id = :room_id';
        $params = [
            'id' => $messageId,
            'room_id' => $roomId,
        ];

        if (!$isAdmin) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->connection->prepare($sql);

        return $statement->execute($params);
    }

    public function deleteAsAdmin(int $messageId, string $className = ''): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $sql = 'UPDATE messages m';
        $params = ['id' => $messageId];
        if ($className !== '') {
            $sql .= ' INNER JOIN users u ON u.id = m.user_id';
            $params['class_name'] = $className;
        }
        $sql .= ' SET m.deleted_at = NOW(), m.body = "[deleted]" WHERE m.id = :id AND m.deleted_at IS NULL';
        if ($className !== '') {
            $sql .= ' AND u.class_name = :class_name';
        }
        $statement = $this->connection->prepare($sql);

        return $statement->execute($params);
    }

    public function togglePin(int $messageId, int $roomId, int $userId, bool $isAdmin = false): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $sql = 'UPDATE messages SET is_pinned = CASE WHEN is_pinned = 1 THEN 0 ELSE 1 END WHERE id = :id AND room_id = :room_id AND deleted_at IS NULL';
        $params = ['id' => $messageId, 'room_id' => $roomId];

        if (!$isAdmin) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function toggleReaction(int $messageId, int $userId, string $reaction): bool
    {
        if (!$this->connection instanceof PDO || !in_array($reaction, ['like', 'heart', 'laugh'], true)) {
            return false;
        }

        $exists = $this->connection->prepare('SELECT id FROM message_reactions WHERE message_id = :message_id AND user_id = :user_id AND reaction = :reaction LIMIT 1');
        $exists->execute(['message_id' => $messageId, 'user_id' => $userId, 'reaction' => $reaction]);

        if ($exists->fetch() !== false) {
            $delete = $this->connection->prepare('DELETE FROM message_reactions WHERE message_id = :message_id AND user_id = :user_id AND reaction = :reaction');
            return $delete->execute(['message_id' => $messageId, 'user_id' => $userId, 'reaction' => $reaction]);
        }

        $insert = $this->connection->prepare('INSERT INTO message_reactions (message_id, user_id, reaction) VALUES (:message_id, :user_id, :reaction)');
        return $insert->execute(['message_id' => $messageId, 'user_id' => $userId, 'reaction' => $reaction]);
    }

    public function markTyping(int $roomId, int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO typing_indicators (room_id, user_id, updated_at)
             VALUES (:room_id, :user_id, NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()'
        );
        $statement->execute(['room_id' => $roomId, 'user_id' => $userId]);
    }

    public function typingUsers(int $roomId, int $viewerId): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT u.name
             FROM typing_indicators t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.room_id = :room_id
               AND t.user_id != :viewer_id
               AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 8 SECOND)
             ORDER BY u.name ASC
             LIMIT 3'
        );
        $statement->execute(['room_id' => $roomId, 'viewer_id' => $viewerId]);

        return array_column($statement->fetchAll() ?: [], 'name');
    }

    private function withReactions(array $messages): array
    {
        if (!$this->connection instanceof PDO || $messages === []) {
            return $messages;
        }

        $ids = array_map(static fn (array $message): int => (int) $message['id'], $messages);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare(
            "SELECT message_id, reaction, COUNT(*) AS count
             FROM message_reactions
             WHERE message_id IN ($placeholders)
             GROUP BY message_id, reaction"
        );
        $statement->execute($ids);

        $grouped = [];
        foreach ($statement->fetchAll() ?: [] as $row) {
            $grouped[(int) $row['message_id']][(string) $row['reaction']] = (int) $row['count'];
        }

        foreach ($messages as &$message) {
            $message['reactions'] = $grouped[(int) $message['id']] ?? [];
        }

        return $messages;
    }

    public function seedRoomIfEmpty(int $roomId, array $user): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $countStatement = $this->connection->prepare('SELECT COUNT(*) FROM messages WHERE room_id = :room_id');
        $countStatement->execute(['room_id' => $roomId]);

        if ((int) $countStatement->fetchColumn() > 0) {
            return;
        }

        $samples = [
            'Welcome in. Use this room to catch up with everyone.',
            'Drop quick updates, reminders, or just say hi.',
            'This room is live now, so your next message will show here.',
        ];

        foreach ($samples as $sample) {
            $this->create($roomId, (int) $user['id'], $sample);
        }
    }

    private function ensureColumn(string $column, string $sql): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare('SHOW COLUMNS FROM messages LIKE :column');
        $statement->execute(['column' => $column]);

        if ($statement->fetch() === false) {
            $this->connection->exec($sql);
        }
    }
}
