<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__, 3).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
