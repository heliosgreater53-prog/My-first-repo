<?php
declare(strict_types=1);

namespace App\Models;

use Framework\Core\Database;
use PDO;

class AppSetting
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
            'CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        if ($this->get('flagged_terms') === null) {
            $this->set(
                'flagged_terms',
                json_encode(['bully', 'stupid', 'idiot', 'hate', 'ugly', 'fool', 'dumb', 'kill', 'worthless', 'shut up', 'loser'], JSON_THROW_ON_ERROR)
            );
        }

        if ($this->get('signup_requires_invite') === null) {
            $this->set('signup_requires_invite', '0');
        }
    }

    public function get(string $key): ?string
    {
        if (!$this->connection instanceof PDO) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT setting_value FROM app_settings WHERE setting_key = :key LIMIT 1'
        );
        $statement->execute(['key' => $key]);
        $row = $statement->fetch();

        return is_array($row) ? (string) $row['setting_value'] : null;
    }

    public function set(string $key, string $value): void
    {
        if (!$this->connection instanceof PDO) {
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO app_settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $statement->execute([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /** @return list<string> */
    public function getFlaggedTerms(): array
    {
        $raw = $this->get('flagged_terms');
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), static fn(string $t): bool => trim($t) !== ''));
    }

    /** @param list<string> $terms */
    public function setFlaggedTerms(array $terms): void
    {
        $clean = array_values(array_unique(array_filter(array_map(
            static fn(string $t): string => trim(mb_strtolower($t)),
            $terms
        ), static fn(string $t): bool => $t !== '')));

        $this->set('flagged_terms', json_encode($clean, JSON_THROW_ON_ERROR));
    }

    public function signupRequiresInvite(): bool
    {
        return $this->get('signup_requires_invite') === '1';
    }

    public function setSignupRequiresInvite(bool $required): void
    {
        $this->set('signup_requires_invite', $required ? '1' : '0');
    }
}
