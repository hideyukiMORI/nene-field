<?php

declare(strict_types=1);

/*
 * Phinx configuration. Mirrors the typed DatabaseConfig env boundary
 * (docs/development/backend-standards.md §9). Both SQLite (Tier A) and MySQL
 * (Tier B) are supported; default is SQLite so a fresh install migrates with no
 * external database.
 */

$env = static function (string $key, string $default): string {
    $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

    return is_string($value) && $value !== '' ? $value : $default;
};

$adapter = $env('DB_ADAPTER', 'sqlite');

$database = $adapter === 'sqlite'
    ? [
        'adapter' => 'sqlite',
        // DB_NAME may be ':memory:' or a file path without the .sqlite suffix.
        'name' => $env('DB_NAME', __DIR__ . '/var/nene-field'),
        'suffix' => '',
    ]
    : [
        'adapter' => $adapter,
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => (int) $env('DB_PORT', '3306'),
        'name' => $env('DB_NAME', 'nene_field'),
        'user' => $env('DB_USER', 'nene_field'),
        'pass' => $env('DB_PASSWORD', ''),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
    ];

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds',
    ],
    'migration_base_class' => 'Phinx\Migration\AbstractMigration',
    'templates' => [],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'app',
        'app' => $database,
    ],
    'version_order' => 'creation',
];
