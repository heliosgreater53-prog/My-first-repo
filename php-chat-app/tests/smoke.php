<?php
declare(strict_types=1);

/**
 * Quick smoke checks — run: php tests/smoke.php
 */
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/framework/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefixes = ['App\\' => BASE_PATH . '/app/', 'Framework\\' => BASE_PATH . '/framework/'];
    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

migrate_app_schema();

$settings = new \App\Models\AppSetting();
assert(count($settings->getFlaggedTerms()) > 0, 'flagged terms seeded');

$user = new \App\Models\User();
$peers = $user->peersForChat(1);
assert(is_array($peers), 'peersForChat returns array');

echo "OK: smoke tests passed\n";
