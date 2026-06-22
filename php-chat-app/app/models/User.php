<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class User
{
    public const PRESENCE_TIMEOUT_MINUTES = 3;

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
            'CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                class_name VARCHAR(20) NOT NULL DEFAULT "JSS1",
                role VARCHAR(20) NOT NULL DEFAULT "student",
                avatar_path VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                status VARCHAR(50) NOT NULL DEFAULT "Offline",
                headline VARCHAR(140) NOT NULL DEFAULT "",
                bio TEXT DEFAULT NULL,
                room_name VARCHAR(120) NOT NULL DEFAULT "General Room",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->ensureColumn('class_name', 'ALTER TABLE users ADD class_name VARCHAR(20) NOT NULL DEFAULT "JSS1" AFTER password');
        $this->ensureColumn('role', 'ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT "student" AFTER class_name');
        $this->ensureColumn('avatar_path', 'ALTER TABLE users ADD avatar_path VARCHAR(255) DEFAULT NULL AFTER role');
        $this->ensureColumn('is_active', 'ALTER TABLE users ADD is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER avatar_path');
        $this->ensureColumn('status', 'ALTER TABLE users ADD status VARCHAR(50) NOT NULL DEFAULT "Offline" AFTER is_active');
        $this->ensureColumn('headline', 'ALTER TABLE users ADD headline VARCHAR(140) NOT NULL DEFAULT "" AFTER status');
        $this->ensureColumn('bio', 'ALTER TABLE users ADD bio TEXT DEFAULT NULL AFTER headline');
        $this->ensureColumn('room_name', 'ALTER TABLE users ADD room_name VARCHAR(120) NOT NULL DEFAULT "General Room" AFTER bio');
        $this->ensureColumn('last_activity', 'ALTER TABLE users ADD last_activity TIMESTAMP NULL DEFAULT NULL AFTER room_name');
        $this->ensureColumn('theme_preference', 'ALTER TABLE users ADD theme_preference VARCHAR(20) NOT NULL DEFAULT "system" AFTER last_activity');
        $this->ensureColumn('reduce_motion', 'ALTER TABLE users ADD reduce_motion TINYINT(1) NOT NULL DEFAULT 0 AFTER theme_preference');
        $this->ensureColumn('notifications_enabled', 'ALTER TABLE users ADD notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER reduce_motion');
        $this->ensureColumn('browser_notifications_enabled', 'ALTER TABLE users ADD browser_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notifications_enabled');
        $this->ensureColumn('mention_notifications_enabled', 'ALTER TABLE users ADD mention_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER browser_notifications_enabled');
        $this->ensureColumn('dm_notifications_enabled', 'ALTER TABLE users ADD dm_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER mention_notifications_enabled');
        $this->ensureColumn('compact_ui', 'ALTER TABLE users ADD compact_ui TINYINT(1) NOT NULL DEFAULT 0 AFTER dm_notifications_enabled');
        $this->ensureColumn('muted_until', 'ALTER TABLE users ADD muted_until TIMESTAMP NULL DEFAULT NULL AFTER compact_ui');
        $this->ensureColumn('muted_by', 'ALTER TABLE users ADD muted_by INT UNSIGNED DEFAULT NULL AFTER muted_until');
        $this->ensureColumn('muted_reason', 'ALTER TABLE users ADD muted_reason VARCHAR(190) NOT NULL DEFAULT "" AFTER muted_by');
    }

    public function create(array $data): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO users (name, email, password, class_name, role, avatar_path, is_active, status, headline, bio, room_name)
             VALUES (:name, :email, :password, :class_name, :role, :avatar_path, :is_active, :status, :headline, :bio, :room_name)'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => strtolower(trim((string) $data['email'])),
            'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'class_name' => $data['class_name'] ?? 'JSS1',
            'role' => $data['role'] ?? 'student',
            'avatar_path' => $data['avatar_path'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'status' => $data['status'] ?? 'Offline',
            'headline' => trim((string) ($data['headline'] ?? '')),
            'bio' => trim((string) ($data['bio'] ?? '')) ?: null,
            'room_name' => $data['room_name'] ?? 'General Room',
        ]);

        return $this->find((int) $this->connection->lastInsertId());
    }

    public function find(int $id): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT id, name, email, class_name, role, avatar_path, is_active, status, headline, bio, room_name, last_activity,
                    theme_preference, reduce_motion, notifications_enabled, browser_notifications_enabled,
                    mention_notifications_enabled, dm_notifications_enabled, compact_ui, muted_until, muted_by, muted_reason, created_at,
                    CASE
                        WHEN muted_until IS NOT NULL AND muted_until > NOW() THEN 1
                        ELSE 0
                    END AS is_muted,
                    CASE
                        WHEN last_activity IS NOT NULL AND last_activity >= DATE_SUB(NOW(), INTERVAL ' . self::PRESENCE_TIMEOUT_MINUTES . ' MINUTE) THEN 1
                        ELSE 0
                    END AS is_online
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findByEmail(string $email): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);

        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function emailBelongsToAnotherUser(string $email, int $userId): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1'
        );
        $statement->execute([
            'email' => strtolower(trim($email)),
            'id' => $userId,
        ]);

        return is_array($statement->fetch());
    }

    public function update(int $id, array $data): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $fields = [
            'name = :name',
            'email = :email',
            'class_name = :class_name',
            'avatar_path = :avatar_path',
            'status = :status',
            'headline = :headline',
            'bio = :bio',
            'room_name = :room_name',
        ];

        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => strtolower(trim((string) $data['email'])),
            'class_name' => $data['class_name'] ?? 'JSS1',
            'avatar_path' => $data['avatar_path'] ?? null,
            'status' => $data['status'] ?? 'Online',
            'headline' => trim((string) ($data['headline'] ?? '')),
            'bio' => trim((string) ($data['bio'] ?? '')) ?: null,
            'room_name' => $data['room_name'] ?? 'General Room',
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $statement->execute($params);

        return $this->find($id);
    }

    public function updatePreferences(int $id, array $data): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'UPDATE users
             SET theme_preference = :theme_preference,
                 reduce_motion = :reduce_motion,
                 notifications_enabled = :notifications_enabled,
                 browser_notifications_enabled = :browser_notifications_enabled,
                 mention_notifications_enabled = :mention_notifications_enabled,
                 dm_notifications_enabled = :dm_notifications_enabled,
                 compact_ui = :compact_ui
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'theme_preference' => $data['theme_preference'] ?? 'system',
            'reduce_motion' => (int) ($data['reduce_motion'] ?? 0),
            'notifications_enabled' => (int) ($data['notifications_enabled'] ?? 1),
            'browser_notifications_enabled' => (int) ($data['browser_notifications_enabled'] ?? 1),
            'mention_notifications_enabled' => (int) ($data['mention_notifications_enabled'] ?? 1),
            'dm_notifications_enabled' => (int) ($data['dm_notifications_enabled'] ?? 1),
            'compact_ui' => (int) ($data['compact_ui'] ?? 0),
        ]);

        return $this->find($id);
    }

    public function count(): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        return (int) $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function adminStats(): array
    {
        if (!$this->connection instanceof PDO) {
            return [
                'total_users' => 0,
                'admins' => 0,
                'students' => 0,
                'banned' => 0,
                'unbanned' => 0,
            ];
        }

        $stats = $this->connection->query(
            'SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN role = "admin" THEN 1 ELSE 0 END) AS admins,
                SUM(CASE WHEN role != "admin" THEN 1 ELSE 0 END) AS students,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS banned,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS unbanned
             FROM users'
        )->fetch();

        return is_array($stats) ? $stats : [];
    }

    public function searchPaginated(string $term = '', string $className = '', string $banState = '', int $page = 1, int $perPage = 20): array
    {
        $all = $this->search($term, $className, $banState);
        $offset = max(0, ($page - 1) * $perPage);

        return array_slice($all, $offset, $perPage);
    }

    public function searchCount(string $term = '', string $className = '', string $banState = ''): int
    {
        return count($this->search($term, $className, $banState));
    }

    public function findIdsByNames(array $names): array
    {
        if (!$this->connection instanceof PDO || $names === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $statement = $this->connection->prepare(
            "SELECT id, name FROM users WHERE is_active = 1 AND name IN ($placeholders)"
        );
        $statement->execute(array_values($names));

        $map = [];
        foreach ($statement->fetchAll() ?: [] as $row) {
            $map[(string) $row['name']] = (int) $row['id'];
        }

        return $map;
    }

    public function search(string $term = '', string $className = '', string $banState = ''): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $sql = 'SELECT id, name, email, class_name, role, avatar_path, is_active, status, headline, bio, room_name, last_activity, muted_until, muted_by, muted_reason, created_at,
                    CASE
                        WHEN muted_until IS NOT NULL AND muted_until > NOW() THEN 1
                        ELSE 0
                    END AS is_muted,
                    CASE
                        WHEN last_activity IS NOT NULL AND last_activity >= DATE_SUB(NOW(), INTERVAL ' . self::PRESENCE_TIMEOUT_MINUTES . ' MINUTE) THEN 1
                        ELSE 0
                    END AS is_online
                FROM users
                WHERE 1=1';
        $params = [];

        if ($term !== '') {
            $sql .= ' AND (name LIKE :term OR email LIKE :term)';
            $params['term'] = '%' . $term . '%';
        }

        if ($className !== '') {
            $sql .= ' AND class_name = :class_name';
            $params['class_name'] = $className;
        }

        if ($banState === 'banned') {
            $sql .= ' AND is_active = 0';
        } elseif ($banState === 'unbanned') {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY created_at DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function updateAdminFields(int $id, array $data): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'UPDATE users
             SET role = :role,
                 is_active = :is_active,
                 status = :status,
                 class_name = :class_name,
                 room_name = :room_name,
                 headline = :headline,
                 bio = :bio
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'status' => $data['status'],
            'class_name' => $data['class_name'],
            'room_name' => $data['room_name'],
            'headline' => trim((string) ($data['headline'] ?? '')),
            'bio' => trim((string) ($data['bio'] ?? '')) ?: null,
        ]);

        if (!empty($data['password'])) {
            $passwordStatement = $this->connection->prepare(
                'UPDATE users SET password = :password WHERE id = :id'
            );
            $passwordStatement->execute([
                'id' => $id,
                'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            ]);
        }

        return $this->find($id);
    }

    public function updatePasswordByEmail(string $email, string $password): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET password = :password WHERE email = :email'
        );

        return $statement->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => strtolower(trim($email)),
        ]);
    }

    public function setBanState(int $id, int $isActive): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET is_active = :is_active WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'is_active' => $isActive,
        ]);
    }

    public function muteUntil(int $id, int $moderatorId, string $until, string $reason = ''): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE users
             SET muted_until = :muted_until, muted_by = :muted_by, muted_reason = :muted_reason
             WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'muted_until' => $until,
            'muted_by' => $moderatorId,
            'muted_reason' => mb_substr(trim($reason), 0, 190),
        ]);
    }

    public function unmute(int $id): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET muted_until = NULL, muted_by = NULL, muted_reason = "" WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function isMuted(array $user): bool
    {
        $mutedUntil = (string) ($user['muted_until'] ?? '');

        return $mutedUntil !== '' && strtotime($mutedUntil) > time();
    }

    public function peersForChat(int $currentUserId): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT u.id, u.name, u.class_name, u.avatar_path, u.role, u.headline, u.status, u.last_activity,
                    CASE
                        WHEN u.last_activity IS NOT NULL AND u.last_activity >= DATE_SUB(NOW(), INTERVAL ' . self::PRESENCE_TIMEOUT_MINUTES . ' MINUTE) THEN 1
                        ELSE 0
                    END AS is_online
             FROM users u
             WHERE u.is_active = 1 AND u.id != :id
               AND NOT EXISTS (
                   SELECT 1 FROM user_blocks b
                   WHERE (b.blocker_id = :id AND b.blocked_id = u.id)
                      OR (b.blocker_id = u.id AND b.blocked_id = :id)
               )
             ORDER BY u.name ASC'
        );
        $statement->execute(['id' => $currentUserId]);

        return $statement->fetchAll() ?: [];
    }

    public function updateLastActivity(int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET status = "Online", last_activity = CURRENT_TIMESTAMP WHERE id = :id AND is_active = 1'
        );
        $statement->execute(['id' => $userId]);
    }

    public function markOnline(int $userId): void
    {
        $this->updateLastActivity($userId);
    }

    public function markOffline(int $userId): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET status = "Offline", last_activity = NULL WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }

    public function isOnline(int $userId, int $timeoutMinutes = 5): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT last_activity FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        if (!is_array($user) || empty($user['last_activity'])) {
            return false;
        }

        $lastActivity = new \DateTime($user['last_activity']);
        $now = new \DateTime();
        $interval = $now->diff($lastActivity);
        $minutesSinceLastActivity = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        return $minutesSinceLastActivity <= $timeoutMinutes;
    }

    public function getOnlineStatusForUsers(array $userIds, int $timeoutMinutes = 5): array
    {
        if (!$this->connection instanceof PDO || empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT id,
                CASE
                    WHEN last_activity IS NULL THEN 0
                    WHEN last_activity >= DATE_SUB(NOW(), INTERVAL ? MINUTE) THEN 1
                    ELSE 0
                END as is_online
                FROM users
                WHERE id IN ($placeholders)";

        $params = array_merge([$timeoutMinutes], $userIds);
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        $results = [];
        while ($row = $statement->fetch()) {
            $results[$row['id']] = (bool) $row['is_online'];
        }

        return $results;
    }

    private function ensureColumn(string $column, string $sql): void
     {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare('SHOW COLUMNS FROM users LIKE :column');
        $statement->execute(['column' => $column]);

        if ($statement->fetch() === false) {
            $this->connection->exec($sql);
        }
    }

    public function getOnlineInRoom(int $roomId, int $excludeUserId = 0): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $sql = 'SELECT u.id, u.name, u.class_name, u.avatar_path, u.last_activity,
                    CASE
                        WHEN u.last_activity IS NOT NULL AND u.last_activity >= DATE_SUB(NOW(), INTERVAL ' . self::PRESENCE_TIMEOUT_MINUTES . ' MINUTE) THEN 1
                        ELSE 0
                    END AS is_online
             FROM users u
             INNER JOIN room_memberships rm ON rm.user_id = u.id
             WHERE rm.room_id = :room_id
               AND u.is_active = 1
               AND u.last_activity >= DATE_SUB(NOW(), INTERVAL ' . self::PRESENCE_TIMEOUT_MINUTES . ' MINUTE)';
        
        if ($excludeUserId > 0) {
            $sql .= ' AND u.id != :exclude_id';
        }
        
        $sql .= ' ORDER BY u.name ASC';

        $params = ['room_id' => $roomId];
        if ($excludeUserId > 0) {
            $params['exclude_id'] = $excludeUserId;
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $users = $statement->fetchAll() ?: [];

        // Ensure is_online is boolean
        foreach ($users as &$user) {
            $user['is_online'] = (bool)$user['is_online'];
        }

        return $users;
    }
}
