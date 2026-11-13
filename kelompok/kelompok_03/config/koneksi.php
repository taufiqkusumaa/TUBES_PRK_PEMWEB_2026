
<?php

date_default_timezone_set('Asia/Jakarta');

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbName = getenv('DB_NAME') ?: 'klinik_antrian';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    // Fallback otomatis untuk lingkungan MAMP (port 8889, user 'root', password 'root')
    if ($dbPort === 3306 && $dbPass === '') {
        try {
            $dsnFallback = "mysql:host={$dbHost};port=8889;dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsnFallback, 'root', 'root', $options);
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e2) {
            error_log('[Database Klinik] ' . $e2->getMessage());
            http_response_code(500);
            exit('Koneksi database gagal. Periksa konfigurasi database.');
        }
    } else {
        error_log('[Database Klinik] ' . $e->getMessage());
        http_response_code(500);
        exit('Koneksi database gagal. Periksa konfigurasi database.');
    }
}
