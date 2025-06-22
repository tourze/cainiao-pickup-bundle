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

(new Dotenv())->bootEnv(dirname(__DIR__, 3).'/.env');

if (!empty($_SERVER['APP_DEBUG'])) {
    umask(0000);
}
