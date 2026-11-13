<?php

date_default_timezone_set('Asia/Jakarta');
$ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR'];

session_start();

if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';

    if ($role === 'admin') {
        header('Location: pages/admin_dashboard.php');
    } elseif ($role === 'kepala_keluarga' || $role === 'anggota_keluarga') {
        header('Location: pages/dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}
header('Location: login.php');
exit;

?>

