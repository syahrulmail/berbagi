<?php

declare(strict_types=1);

use App\Database;

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'berbagi',
        'username' => getenv('DB_USER') ?: 'berbagi',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Berbagi.or.id',
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
        'env' => APP_ENV,
        'debug' => APP_ENV === 'development',
    ],
];
