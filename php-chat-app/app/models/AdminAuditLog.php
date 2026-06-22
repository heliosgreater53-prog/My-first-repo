<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class AdminAuditLog
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
            'CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_user_id INT UNSIGNED NOT NULL,
                target_user_id INT UNSIGNED DEFAULT NULL,
                action VARCHAR(80) NOT NULL,
                details TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX admin_user_idx (admin_user_id),
                INDEX target_user_idx (target_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function record(int $adminUserId, string $action, ?int $targetUserId = null, string $details = ''): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO admin_audit_logs (admin_user_id, target_user_id, action, details)
             VALUES (:admin_user_id, :target_user_id, :action, :details)'
        );
        $statement->execute([
            'admin_user_id' => $adminUserId,
            'target_user_id' => $targetUserId,
            'action' => $action,
            'details' => $details !== '' ? $details : null,
        ]);
    }

    public function recent(int $limit = 20): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT l.*,
                    au.name AS admin_name,
                    tu.name AS target_name
             FROM admin_audit_logs l
             INNER JOIN users au ON au.id = l.admin_user_id
             LEFT JOIN users tu ON tu.id = l.target_user_id
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT :audit_limit'
        );
        $statement->bindValue(':audit_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }
}
