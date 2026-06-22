<?php
declare(strict_types=1);

namespace Framework\Core;

use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;
    private array $config = [];

    public function __construct()
    {
        $this->config = config('db');
    }

    public function connection(): ?PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        if ($this->config === []) {
            return null;
        }

        $this->ensureDatabaseExists();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['host'] ?? '127.0.0.1',
            $this->config['port'] ?? '3306',
            $this->config['dbname'] ?? '',
            $this->config['charset'] ?? 'utf8mb4'
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? '',
                $this->config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException) {
            $this->connection = null;
        }

        return $this->connection;
    }

    private function ensureDatabaseExists(): void
    {
        $databaseName = $this->config['dbname'] ?? '';

        if ($databaseName === '') {
            return;
        }

        try {
            $serverConnection = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;charset=%s',
                    $this->config['host'] ?? '127.0.0.1',
                    $this->config['port'] ?? '3306',
                    $this->config['charset'] ?? 'utf8mb4'
                ),
                $this->config['username'] ?? '',
                $this->config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $charset = $this->config['charset'] ?? 'utf8mb4';

            $serverConnection->exec(
                sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci',
                    $databaseName,
                    $charset,
                    $charset
                )
            );
        } catch (PDOException) {
            return;
        }
    }
}
