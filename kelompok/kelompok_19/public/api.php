<?php
// public/api.php
// API Gateway: route endpoint REST ke Controller yang sesuai.
// Base URL: /api

require_once __DIR__ . "/../app/Config/Database.php";
require_once __DIR__ . "/../app/Controllers/LahanController.php";
require_once __DIR__ . "/../app/Controllers/SiklusController.php";
require_once __DIR__ . "/../app/Controllers/InventarisController.php";
require_once __DIR__ . "/../app/Controllers/PekerjaController.php";
require_once __DIR__ . "/../app/Controllers/PanenController.php";

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Hilangkan prefix /api
$uri = preg_replace('/^\/api/', '', $uri);

// Parse path segments
$segments = array_values(array_filter(explode('/', $uri), 'strlen'));

// Routing sederhana: /api/lahan -> LahanController
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

$controllers = [
    'lahan' => new LahanController(),
    'siklus' => new SiklusController(),
    'inventaris' => new InventarisController(),
    'pekerja' => new PekerjaController(),
    'panen' => new PanenController(),
];

if (!isset($controllers[$resource])) {
    http_response_code(404);
    \App\Config\Database::response(['error' => 'Endpoint tidak ditemukan']);
    exit;
}

$controller = $controllers[$resource];

// Endpoint transaksi inventaris memakai segmen path khusus, bukan id angka.
if ($resource === 'inventaris' && $method === 'GET' && $id === 'transaksi') {
    $controller->indexTransaksi();
    exit;
}
if ($resource === 'inventaris' && $method === 'POST' && $id === 'createTransaksi') {
    $controller->createTransaksi();
    exit;
}

// Presensi pekerja memakai sub-route, pola sama dengan transaksi inventaris.
if ($resource === 'pekerja' && $id === 'presensi') {
    $presensiId = $segments[2] ?? null;

    switch ($method) {
        case 'GET':
            $controller->indexPresensi();
            break;

        case 'POST':
            $controller->createPresensi();
            break;

        case 'PUT':
            if ($presensiId) {
                $controller->updatePresensi($presensiId);
            } else {
                http_response_code(400);
                \App\Config\Database::response(['error' => 'ID presensi diperlukan untuk update']);
            }
            break;

        case 'DELETE':
            if ($presensiId) {
                $controller->deletePresensi($presensiId);
            } else {
                http_response_code(400);
                \App\Config\Database::response(['error' => 'ID presensi diperlukan untuk delete']);
            }
            break;

        default:
            http_response_code(405);
            \App\Config\Database::response(['error' => 'Method tidak diizinkan']);
            break;
    }
    exit;
}

// Routing berdasarkan method HTTP.
switch ($method) {
    case 'GET':
        if ($id) {
            $controller->show($id);
        } else {
            $controller->index();
        }
        break;

    case 'POST':
        $controller->create();
        break;

    case 'PUT':
        if ($id) {
            $controller->update($id);
        } else {
            http_response_code(400);
            \App\Config\Database::response(['error' => 'ID diperlukan untuk update']);
        }
        break;

    case 'DELETE':
        if ($id) {
            $controller->delete($id);
        } else {
            http_response_code(400);
            \App\Config\Database::response(['error' => 'ID diperlukan untuk delete']);
        }
        break;

    default:
        http_response_code(405);
        \App\Config\Database::response(['error' => 'Method tidak diizinkan']);
        break;
}