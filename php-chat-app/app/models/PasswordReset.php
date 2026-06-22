<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class PasswordReset
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
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function create(string $email, string $plainToken): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $this->deleteByEmail($email);

        $statement = $this->connection->prepare(
            'INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        );
        $statement->execute([
            'email' => strtolower(trim($email)),
            'token' => password_hash($plainToken, PASSWORD_DEFAULT),
        ]);
    }

    public function findValid(string $email, string $plainToken): bool
    {
        if (!$this->connection instanceof PDO) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT token, expires_at FROM password_resets WHERE email = :email ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $record = $statement->fetch();

        if (!is_array($record)) {
            return false;
        }

        if (strtotime((string) $record['expires_at']) < time()) {
            return false;
        }

        return password_verify($plainToken, (string) $record['token']);
    }

    public function deleteByEmail(string $email): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare('DELETE FROM password_resets WHERE email = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
    }
}
