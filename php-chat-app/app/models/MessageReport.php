<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class MessageReport
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
            'CREATE TABLE IF NOT EXISTS message_reports (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message_id INT UNSIGNED NOT NULL,
                reporter_user_id INT UNSIGNED NOT NULL,
                reason VARCHAR(190) NOT NULL DEFAULT "Reported for review",
                status VARCHAR(40) NOT NULL DEFAULT "open",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY message_reporter_unique (message_id, reporter_user_id),
                INDEX message_idx (message_id),
                INDEX reporter_idx (reporter_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->ensureColumn('status', 'ALTER TABLE message_reports ADD status VARCHAR(40) NOT NULL DEFAULT "open" AFTER reason');
    }

    public function create(int $messageId, int $reporterUserId, string $reason = 'Reported for review'): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'INSERT IGNORE INTO message_reports (message_id, reporter_user_id, reason)
             VALUES (:message_id, :reporter_user_id, :reason)'
        );

        return $statement->execute([
            'message_id' => $messageId,
            'reporter_user_id' => $reporterUserId,
            'reason' => trim($reason) !== '' ? trim($reason) : 'Reported for review',
        ]);
    }

    public function countOpen(string $className = ''): int
    {
        if (!$this->connection instanceof PDO) {
            return 0;
        }

        if ($className === '') {
            return (int) $this->connection->query(
                'SELECT COUNT(*) FROM message_reports WHERE status = "open"'
            )->fetchColumn();
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM message_reports mr
             INNER JOIN messages m ON m.id = mr.message_id
             INNER JOIN users author ON author.id = m.user_id
             WHERE mr.status = "open" AND author.class_name = :class_name'
        );
        $statement->execute(['class_name' => $className]);

        return (int) $statement->fetchColumn();
    }

    public function recent(int $limit = 30, string $statusFilter = '', string $className = ''): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT mr.*,
                    reporter.name AS reporter_name,
                    author.name AS author_name,
                    author.is_active AS author_is_active,
                    m.body,
                    m.deleted_at,
                    r.name AS room_name
             FROM message_reports mr
             INNER JOIN messages m ON m.id = mr.message_id
             INNER JOIN users reporter ON reporter.id = mr.reporter_user_id
             INNER JOIN users author ON author.id = m.user_id
             INNER JOIN rooms r ON r.id = m.room_id
             WHERE (:status_filter = "" OR mr.status = :status_filter)
               AND (:class_name = "" OR author.class_name = :class_name)
             ORDER BY CASE WHEN mr.status = "open" THEN 0 ELSE 1 END, mr.created_at DESC, mr.id DESC
             LIMIT :report_limit'
        );
        $statement->bindValue(':status_filter', $statusFilter);
        $statement->bindValue(':class_name', $className);
        $statement->bindValue(':report_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function updateStatus(int $reportId, string $status, string $className = ''): bool
    {
        if (!$this->connection instanceof PDO || !in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            return false;
        }

        $sql = 'UPDATE message_reports mr';
        $params = [
            'id' => $reportId,
            'status' => $status,
        ];
        if ($className !== '') {
            $sql .= ' INNER JOIN messages m ON m.id = mr.message_id INNER JOIN users author ON author.id = m.user_id';
            $params['class_name'] = $className;
        }
        $sql .= ' SET mr.status = :status WHERE mr.id = :id';
        if ($className !== '') {
            $sql .= ' AND author.class_name = :class_name';
        }
        $statement = $this->connection->prepare($sql);

        return $statement->execute($params);
    }

    private function ensureColumn(string $column, string $sql): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare('SHOW COLUMNS FROM message_reports LIKE :column');
        $statement->execute(['column' => $column]);

        if ($statement->fetch() === false) {
            $this->connection->exec($sql);
        }
    }
}
