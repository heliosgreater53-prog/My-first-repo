<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/framework/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => BASE_PATH . '/app/',
        'Framework\\' => BASE_PATH . '/framework/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

use Framework\Core\App;
use Framework\Core\Router;
use Framework\Core\Session;

migrate_app_schema();

$router = new Router();
require BASE_PATH . '/routes/web.php';

$app = new App($router, new Session());
$app->boot();
