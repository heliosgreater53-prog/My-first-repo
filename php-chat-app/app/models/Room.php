<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class Room
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
            "CREATE TABLE IF NOT EXISTS rooms (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(120) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL,
                scope VARCHAR(20) NOT NULL DEFAULT 'public',
                class_name VARCHAR(20) DEFAULT NULL,
                accent_color VARCHAR(20) NOT NULL DEFAULT '#2563eb',
                password_hash VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->ensureColumn('password_hash', "ALTER TABLE rooms ADD password_hash VARCHAR(255) DEFAULT NULL AFTER accent_color");

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS room_memberships (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                room_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                last_read_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY room_user_unique (room_id, user_id),
                INDEX user_idx (user_id),
                INDEX room_idx (room_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->seedDefaults();
    }

    public function accessibleForUser(array $user, bool $isAdmin = false): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            "SELECT r.*,
                    rm.last_read_at,
                    rm.user_id IS NOT NULL AS is_member,
                    (
                        SELECT m.body
                        FROM messages m
                        WHERE m.room_id = r.id AND m.deleted_at IS NULL
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message_body,
                    (
                        SELECT m.created_at
                        FROM messages m
                        WHERE m.room_id = r.id AND m.deleted_at IS NULL
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message_at,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.room_id = r.id
                          AND m.user_id != :viewer_id
                          AND m.deleted_at IS NULL
                          AND (
                              rm.last_read_at IS NULL
                              OR m.created_at > rm.last_read_at
                          )
                    ) AS unread_count,
                    (
                        SELECT COUNT(*)
                        FROM room_memberships rm_count
                        WHERE rm_count.room_id = r.id
                    ) AS member_count
              FROM rooms r
              LEFT JOIN room_memberships rm
                ON rm.room_id = r.id AND rm.user_id = :viewer_id
              WHERE :is_admin = 1
                 OR r.scope = 'public'
                 OR (r.scope = 'class' AND r.class_name = :class_name)
                 OR EXISTS (
                     SELECT 1 FROM room_memberships drm
                     WHERE drm.room_id = r.id AND drm.user_id = :viewer_id
                 )
              ORDER BY
                 (unread_count > 0) DESC,
                 CASE WHEN r.scope = 'direct' THEN 0 WHEN r.scope = 'class' THEN 1 ELSE 2 END,
                 COALESCE(last_message_at, r.created_at) DESC,
                 r.name ASC"
        );
        $statement->execute([
            'viewer_id' => $user['id'] ?? 0,
            'class_name' => $user['class_name'] ?? 'JSS1',
            'is_admin' => $isAdmin ? 1 : 0,
        ]);

        return $statement->fetchAll() ?: [];
    }

    public function findAccessibleBySlug(array $user, string $slug): ?array
    {
        foreach ($this->accessibleForUser($user, has_admin_privileges()) as $room) {
            if (($room['slug'] ?? '') === $slug) {
                return $this->canLoadRoom($room, $user, has_admin_privileges()) ? $room : null;
            }
        }

        return null;
    }

    public function firstAccessible(array $user): ?array
    {
        $rooms = $this->accessibleForUser($user, has_admin_privileges());
        foreach ($rooms as $room) {
            if ($this->canLoadRoom($room, $user, has_admin_privileges())) {
                return $room;
            }
        }

        return null;
    }

    public function findVisibleBySlug(array $user, string $slug, bool $isAdmin = false): ?array
    {
        foreach ($this->accessibleForUser($user, $isAdmin) as $room) {
            if (($room['slug'] ?? '') === $slug) {
                return $room;
            }
        }

        return null;
    }

    public function requiresPasswordForUser(array $room, array $user, bool $isAdmin = false): bool
    {
        return !$this->canLoadRoom($room, $user, $isAdmin);
    }

    private function canLoadRoom(array $room, array $user, bool $isAdmin = false): bool
    {
        if ($isAdmin || empty($room['password_hash'])) {
            return true;
        }

        return !empty($room['is_member']) || $this->isMember((int) ($room['id'] ?? 0), (int) ($user['id'] ?? 0));
    }

    public function markRead(int $roomId, int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $this->ensureMembership($roomId, $userId);

        $statement = $this->connection->prepare(
            'UPDATE room_memberships SET last_read_at = NOW() WHERE room_id = :room_id AND user_id = :user_id'
        );
        $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);
    }

    public function createRoom(array $owner, array $data): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $name = trim((string) ($data['name'] ?? ''));
        $scope = (string) ($data['scope'] ?? 'public');
        $className = $scope === 'class' ? (string) ($data['class_name'] ?? ($owner['class_name'] ?? 'JSS1')) : null;
        $password = trim((string) ($data['password'] ?? ''));

        if ($name === '') {
            return null;
        }

        $slug = $this->uniqueSlug($name);
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

        $statement = $this->connection->prepare(
            "INSERT INTO rooms (name, slug, description, scope, class_name, accent_color, password_hash)
             VALUES (:name, :slug, :description, :scope, :class_name, :accent_color, :password_hash)"
        );
        $statement->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? 'Custom room created inside ChatApp.',
            'scope' => $scope,
            'class_name' => $className,
            'accent_color' => $data['accent_color'] ?? '#2563eb',
            'password_hash' => $passwordHash,
        ]);

        $roomId = (int) $this->connection->lastInsertId();
        $this->ensureMembership($roomId, (int) $owner['id']);

        return $this->findById($roomId);
    }

    public function createDirectRoom(array $owner, array $peer): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $existing = $this->findDirectRoom((int) $owner['id'], (int) $peer['id']);
        if ($existing !== null) {
            return $existing;
        }

        $name = $owner['name'] . ' & ' . $peer['name'];
        $slug = $this->uniqueSlug('direct-' . $owner['id'] . '-' . $peer['id']);

        $statement = $this->connection->prepare(
            "INSERT INTO rooms (name, slug, description, scope, class_name, accent_color)
             VALUES (:name, :slug, :description, 'direct', NULL, :accent_color)"
        );
        $statement->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => 'Private conversation between two users.',
            'accent_color' => '#0ea5e9',
        ]);

        $roomId = (int) $this->connection->lastInsertId();
        $this->ensureMembership($roomId, (int) $owner['id']);
        $this->ensureMembership($roomId, (int) $peer['id']);

        return $this->findById($roomId);
    }

    public function findById(int $id): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare('SELECT * FROM rooms WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return is_array($room) ? $room : null;
    }

    public function findBySlug(string $slug): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare('SELECT * FROM rooms WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $room = $statement->fetch();

        return is_array($room) ? $room : null;
    }

    public function allForAdmin(): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->query(
            "SELECT r.*,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.room_id = r.id
                    ) AS message_count,
                    (
                        SELECT m.created_at
                        FROM messages m
                        WHERE m.room_id = r.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message_at
             FROM rooms r
             ORDER BY COALESCE(last_message_at, r.created_at) DESC, r.name ASC"
        );

        return $statement->fetchAll() ?: [];
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $fields = ['name = :name', 'description = :description', 'accent_color = :accent_color'];
        $params = [
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'accent_color' => trim((string) ($data['accent_color'] ?? '#2563eb')),
        ];

        if (isset($data['password'])) {
            $password = trim((string) $data['password']);
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
        }

        $statement = $this->connection->prepare(
            "UPDATE rooms SET " . implode(', ', $fields) . " WHERE id = :id"
        );

        return $statement->execute($params);
    }

    public function delete(int $id): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $messageIds = [];
        if ($this->tableExists('messages')) {
            $messageStatement = $this->connection->prepare('SELECT id FROM messages WHERE room_id = :id');
            $messageStatement->execute(['id' => $id]);
            foreach ($messageStatement->fetchAll() ?: [] as $message) {
                $messageIds[] = (int) $message['id'];
            }
        }

        try {
            $this->connection->beginTransaction();

            if ($messageIds !== [] && $this->tableExists('message_reports')) {
                $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
                $deleteReports = $this->connection->prepare("DELETE FROM message_reports WHERE message_id IN ($placeholders)");
                $deleteReports->execute($messageIds);
            }

            if ($messageIds !== [] && $this->tableExists('message_reactions')) {
                $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
                $deleteReactions = $this->connection->prepare("DELETE FROM message_reactions WHERE message_id IN ($placeholders)");
                $deleteReactions->execute($messageIds);
            }

            if ($this->tableExists('typing_indicators')) {
                $this->connection->prepare('DELETE FROM typing_indicators WHERE room_id = :id')->execute(['id' => $id]);
            }
            $this->connection->prepare('DELETE FROM room_memberships WHERE room_id = :id')->execute(['id' => $id]);
            if ($this->tableExists('messages')) {
                $this->connection->prepare('DELETE FROM messages WHERE room_id = :id')->execute(['id' => $id]);
            }

            $statement = $this->connection->prepare('DELETE FROM rooms WHERE id = :id');
            $statement->execute(['id' => $id]);
            $deleted = $statement->rowCount() > 0;

            $this->connection->commit();

            return $deleted;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return false;
        }
    }

    public function verifyPassword(int $roomId, string $password): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare('SELECT password_hash FROM rooms WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $roomId]);
        $row = $statement->fetch();

        if (!is_array($row) || $row['password_hash'] === null) {
            return true; // No password set = no restriction
        }

        return password_verify($password, $row['password_hash']);
    }

    public function hasPassword(int $roomId): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare('SELECT password_hash FROM rooms WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $roomId]);
        $row = $statement->fetch();

        return is_array($row) && $row['password_hash'] !== null;
    }

    public function joinWithPassword(int $roomId, int $userId, string $password): bool
    {
        if (!$this->verifyPassword($roomId, $password)) {
            return false;
        }

        $this->ensureMembership($roomId, $userId);

        return true;
    }

    private function isMember(int $roomId, int $userId): bool
    {
        if (!$this->connection instanceof PDO || $roomId <= 0 || $userId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT id FROM room_memberships WHERE room_id = :room_id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() !== false;
    }

    private function findDirectRoom(int $userA, int $userB): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            "SELECT r.*
             FROM rooms r
             INNER JOIN room_memberships rm1 ON rm1.room_id = r.id AND rm1.user_id = :user_a
             INNER JOIN room_memberships rm2 ON rm2.room_id = r.id AND rm2.user_id = :user_b
             WHERE r.scope = 'direct'
             LIMIT 1"
        );
        $statement->execute([
            'user_a' => $userA,
            'user_b' => $userB,
        ]);
        $room = $statement->fetch();

        return is_array($room) ? $room : null;
    }

    public function ensureMembership(int $roomId, int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            "INSERT IGNORE INTO room_memberships (room_id, user_id, last_read_at) VALUES (:room_id, :user_id, NOW())"
        );
        $statement->execute([
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        $slug = $baseSlug !== '' ? $baseSlug : 'room';
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare('SELECT id FROM rooms WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);

        return $statement->fetch() !== false;
    }

    private function seedDefaults(): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $defaultRooms = [
            ['General Hub', 'general-hub', 'Whole-school conversation and daily gist.', 'public', null, '#f97316', null],
            ['Notice Board', 'notice-board', 'Announcements, reminders, and pinned updates.', 'public', null, '#0f766e', null],
            ['After Class', 'after-class', 'Relaxed conversations once the books are closed.', 'public', null, '#7c3aed', null],
        ];

        foreach ($defaultRooms as [$name, $slug, $description, $scope, $className, $accentColor, $passwordHash]) {
            $statement = $this->connection->prepare('SELECT id FROM rooms WHERE slug = :slug LIMIT 1');
            $statement->execute(['slug' => $slug]);

            if ($statement->fetch() !== false) {
                continue;
            }

            $insert = $this->connection->prepare(
                "INSERT INTO rooms (name, slug, description, scope, class_name, accent_color, password_hash)
                 VALUES (:name, :slug, :description, :scope, :class_name, :accent_color, :password_hash)"
            );
            $insert->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'scope' => $scope,
                'class_name' => $className,
                'accent_color' => $accentColor,
                'password_hash' => $passwordHash,
            ]);
        }

        foreach (['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'] as $className) {
            $slug = strtolower($className) . '-studio';
            $name = $className . ' Studio';
            $statement = $this->connection->prepare('SELECT id FROM rooms WHERE slug = :slug LIMIT 1');
            $statement->execute(['slug' => $slug]);

            if ($statement->fetch() !== false) {
                continue;
            }

            $insert = $this->connection->prepare(
                "INSERT INTO rooms (name, slug, description, scope, class_name, accent_color, password_hash)
                 VALUES (:name, :slug, :description, :scope, :class_name, :accent_color, :password_hash)"
            );
            $insert->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => 'Private room for ' . $className . ' students.',
                'scope' => 'class',
                'class_name' => $className,
                'accent_color' => '#2563eb',
                'password_hash' => null,
            ]);
        }
    }

    private function ensureColumn(string $column, string $sql): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare('SHOW COLUMNS FROM rooms LIKE :column');
        $statement->execute(['column' => $column]);

        if ($statement->fetch() === false) {
            $this->connection->exec($sql);
        }
    }

    private function tableExists(string $table): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare('SHOW TABLES LIKE :table_name');
        $statement->execute(['table_name' => $table]);

        return $statement->fetch() !== false;
    }
}
