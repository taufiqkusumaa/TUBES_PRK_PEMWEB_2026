<?php
/**
 * File: public/api.php
 * Deskripsi: Endpoint API khusus untuk menangani request AJAX Fetch dari
 * public/assets/js/transaksi.js (Submit Booking, Upload Foto, Ambil Riwayat, Update Stepper).
 */

declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoloader kelas App\*
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\TransaksiController;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$controller = new TransaksiController();

switch ($action) {
    case 'create_booking':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
            exit;
        }
        echo json_encode($controller->storeBooking());
        break;

    case 'get_riwayat':
        echo json_encode($controller->getRiwayatJson());
        break;

    case 'update_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
            exit;
        }
        echo json_encode($controller->updateStatusJson());
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Action API tidak dikenali.'
        ]);
        break;
}