<?php
// KONFIGURASI UTAMA - ORANG 5
define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Pena Kampus');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'pena_kampus');
define('DB_USER', 'root');
define('DB_PASS', '');

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT);
$appRoot = realpath(APP_ROOT);
$basePath = '';
if ($documentRoot && $appRoot && str_starts_with($appRoot, $documentRoot)) {
    $basePath = str_replace('\\', '/', substr($appRoot, strlen($documentRoot)));
}
define('BASE_URL', rtrim($basePath, '/'));

date_default_timezone_set('Asia/Jakarta');
