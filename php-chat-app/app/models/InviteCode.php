<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class InviteCode
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
            'CREATE TABLE IF NOT EXISTS invite_codes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(32) NOT NULL UNIQUE,
                class_name VARCHAR(20) DEFAULT NULL,
                max_uses INT UNSIGNED NOT NULL DEFAULT 1,
                uses_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_by INT UNSIGNED DEFAULT NULL,
                expires_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function create(string $code, ?string $className, int $maxUses, ?string $expiresAt, int $createdBy): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO invite_codes (code, class_name, max_uses, expires_at, created_by)
             VALUES (:code, :class_name, :max_uses, :expires_at, :created_by)'
        );

        return $statement->execute([
            'code' => strtoupper(trim($code)),
            'class_name' => $className !== '' ? $className : null,
            'max_uses' => max(1, $maxUses),
            'expires_at' => $expiresAt,
            'created_by' => $createdBy > 0 ? $createdBy : null,
        ]);
    }

    public function isValid(string $code): bool
    {
        $row = $this->findByCode($code);

        if ($row === null) {
            return false;
        }

        if ((int) ($row['uses_count'] ?? 0) >= (int) ($row['max_uses'] ?? 1)) {
            return false;
        }

        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
            return false;
        }

        return true;
    }

    public function consume(string $code): ?array
    {
        if (!$this->isValid($code)) {
            return null;
        }

        $row = $this->findByCode($code);
        if ($row === null || !$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'UPDATE invite_codes SET uses_count = uses_count + 1 WHERE id = :id'
        );
        $statement->execute(['id' => $row['id']]);

        return $row;
    }

    public function findByCode(string $code): ?array
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT * FROM invite_codes WHERE code = :code LIMIT 1'
        );
        $statement->execute(['code' => strtoupper(trim($code))]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function all(): array
    {
        if (!$this->connection instanceof PDO) {
            return [];
        }

        return $this->connection->query(
            'SELECT ic.*, u.name AS created_by_name
             FROM invite_codes ic
             LEFT JOIN users u ON u.id = ic.created_by
             ORDER BY ic.created_at DESC'
        )->fetchAll() ?: [];
    }
}
