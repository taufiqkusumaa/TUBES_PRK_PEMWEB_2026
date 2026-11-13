<?php
// File: kelompok/kelompok_13/public/api.php
// Matikan tampilan error HTML agar tidak merusak format JSON frontend
ini_set('display_errors', 0);
error_reporting(0);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/../app/Models/' . $className . '.php',
        __DIR__ . '/../app/Controllers/' . $className . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$module = $_REQUEST['module'] ?? $_REQUEST['controller'] ?? $_REQUEST['endpoint'] ?? $_REQUEST['page'] ?? '';
$moduleKey = strtolower(trim($module));

$moduleMap = [
    'transaksi'  => 'TransaksiController',
    'servis'     => 'TransaksiController',
    'kendaraan'  => 'KendaraanController',
    'layanan'    => 'LayananController',
    'kategori'   => 'LayananController',
    'mekanik'    => 'MekanikController',
    'work_order' => 'MekanikController',
    'teknisi'    => 'MekanikController',
    'sparepart'  => 'SparepartController',
    'komponen'   => 'SparepartController',
    'pelanggan'  => 'PelangganController',
    'users'      => 'PelangganController',
    'auth'       => 'PelangganController',
];

if (!isset($moduleMap[$moduleKey])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan.']);
    exit;
}

$action = (string) ($_REQUEST['action'] ?? '');
$publicActions = ['login', 'register', 'logout'];
$authenticated = isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']);
$isPublicAction = in_array($moduleKey, ['auth', 'pelanggan'], true) &&
    in_array($action, $publicActions, true);
if (!$authenticated && !$isPublicAction) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
    exit;
}

try {
    require_once __DIR__ . '/../app/config/Database.php';
    $pdo = Database::getConnection();
    $controllerClass = $moduleMap[$moduleKey];
    $controller = new $controllerClass($pdo);
    if (method_exists($controller, 'handle')) {
        if ($moduleKey === 'transaksi' && empty($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $_GET['action'] = 'list';
        }
        $controller->handle();
        exit;
    }
    if (method_exists($controller, 'handleApiRequest')) {
        $controller->handleApiRequest($_GET['action'] ?? 'list');
        exit;
    }
    if (method_exists($controller, 'index')) {
        $controller->index();
        exit;
    }
} catch (Throwable $e) {
    error_log('[API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.'
    ]);
}