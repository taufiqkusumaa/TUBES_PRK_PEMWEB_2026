<?php
require_once __DIR__ . '/../includes/cek_login.php';

// Sesuaikan dengan nama halaman buatan Anggota 4 (FE Admin)
const HALAMAN_ANTRIAN = 'antrian_list.php';

function kembali($tipe, $pesan)
{
    $_SESSION['flash_tipe']  = $tipe;
    $_SESSION['flash_pesan'] = $pesan;
    header('Location: ' . HALAMAN_ANTRIAN);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$id = filter_input(INPUT_POST, 'antrian_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    kembali('danger', 'Data antrian tidak valid.');
}

$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}

try {
    $stmt = $pdo->prepare(
        "UPDATE antrian SET status = 'selesai'
         WHERE id = ? AND tanggal = ? AND status = 'dipanggil'"
    );
    $stmt->execute([$id, $tanggal]);

    if ($stmt->rowCount() === 1) {
        kembali('success', 'Antrian berhasil diselesaikan.');
    }
    kembali('warning', 'Antrian tidak ditemukan atau belum dipanggil.');
} catch (PDOException $e) {
    error_log('[antrian_selesai] ' . $e->getMessage());
    kembali('danger', 'Terjadi kesalahan sistem.');
}