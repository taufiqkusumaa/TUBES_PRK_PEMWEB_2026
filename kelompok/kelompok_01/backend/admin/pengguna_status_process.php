<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/admin_pengguna.php');
    exit;
}

$id = (int) ($_POST['id_pengguna'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['aktif', 'nonaktif'], true)) {
    $_SESSION['error'] = 'Data status pengguna tidak valid.';
    header('Location: ../../pages/admin_pengguna.php');
    exit;
}

if ($id === (int) $_SESSION['user']['id_pengguna']) {
    $_SESSION['error'] = 'Admin tidak dapat mengubah status akunnya sendiri.';
    header('Location: ../../pages/admin_pengguna.php');
    exit;
}

try {
    $stmt = $pdo->prepare('CALL sp_admin_status_pengguna(?, ?)');
    $stmt->execute([$id, $status]);
    $stmt->closeCursor();

    $_SESSION['success'] = 'Status pengguna berhasil diperbarui.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal memperbarui status pengguna.';
}

header('Location: ../../pages/admin_pengguna.php');
exit;