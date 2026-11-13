<?php
// File: kelompok/kelompok_13/public/index.php
session_start();

require_once __DIR__ . '/../app/config/Database.php';

$page = $_GET['page'] ?? 'dashboard';
$user = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : null;
$role = strtolower((string) ($user['role'] ?? ''));
$role = ['pelanggan' => 'customer', 'mekanik' => 'teknisi'][$role] ?? $role;

$views = [
    'dashboard'  => 'dashboard.php',
    'transaksi'  => 'transaksi.php',
    'kendaraan'  => 'kendaraan.php',
    'work_order' => 'mekanik.php',
    'mekanik'    => 'mekanik.php',
    'sparepart'  => 'sparepart.php',
    'layanan'    => 'layanan.php',
    'pelanggan'  => 'pelanggan.php',
    'users'      => 'pelanggan.php',
];

if (!isset($views[$page])) {
    $page = 'dashboard';
}

if ($user === null) {
    if ($page !== 'pelanggan') {
        header('Location: index.php?page=pelanggan&mode=login');
        exit;
    }
} elseif ($page === 'pelanggan' && in_array($_GET['mode'] ?? '', ['login', 'register'], true)) {
    header('Location: index.php?page=' . ($role === 'admin' ? 'dashboard' : ($role === 'teknisi' ? 'mekanik' : 'transaksi')));
    exit;
}

$allowedRoles = [
    'dashboard' => ['admin'],
    'transaksi' => ['admin', 'customer'],
    'kendaraan' => ['admin'],
    'work_order' => ['admin', 'teknisi'],
    'mekanik' => ['admin', 'teknisi'],
    'sparepart' => ['admin', 'teknisi'],
    'layanan' => ['admin'],
    'pelanggan' => ['admin', 'customer', 'teknisi'],
    'users' => ['admin'],
];
if ($user !== null && !in_array($role, $allowedRoles[$page], true)) {
    http_response_code(403);
    echo 'Anda tidak memiliki akses ke halaman ini.';
    exit;
}
if ($user !== null && $page === 'pelanggan' && ($_GET['mode'] ?? '') === 'users' && $role !== 'admin') {
    http_response_code(403);
    exit('Hanya admin yang dapat mengelola pengguna.');
}

if ($page === 'transaksi') {
    require_once __DIR__ . '/../app/Controllers/TransaksiController.php';
    (new TransaksiController(Database::getConnection()))->index();
    exit;
}

$fileView = __DIR__ . '/../app/Views/' . $views[$page];

if (file_exists($fileView)) {
    require_once $fileView;
} else {
    require_once __DIR__ . '/../app/Views/dashboard.php';
}