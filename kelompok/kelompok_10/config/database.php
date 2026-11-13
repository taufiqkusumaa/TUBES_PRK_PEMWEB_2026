<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    // Mendukung override database via file database.local.php (opsional untuk InfinityFree / hosting)
    $localConfig = [];
    $localConfigFile = __DIR__ . '/database.local.php';
    if (file_exists($localConfigFile)) {
        $loaded = require $localConfigFile;
        if (is_array($loaded)) {
            $localConfig = $loaded;
        }
    }

    $host = $localConfig['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
    $port = (string) ($localConfig['port'] ?? (getenv('DB_PORT') ?: '3306'));
    $database = $localConfig['database'] ?? (getenv('DB_NAME') ?: 'sisasense_db');
    $username = $localConfig['username'] ?? (getenv('DB_USER') ?: 'root');
    $password = $localConfig['password'] ?? (getenv('DB_PASSWORD') ?: 'fadhlan100');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
