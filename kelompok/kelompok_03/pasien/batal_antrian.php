<?php
require_once __DIR__ . '/../includes/cek_login.php';

const HALAMAN = 'pasien.php';

function kembali($tipe, $pesan)
{
    $_SESSION['flash_tipe']  = $tipe;
    $_SESSION['flash_pesan'] = $pesan;
    header('Location: ' . HALAMAN);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

if ($_SESSION['role'] !== 'pasien') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$userId    = (int) $_SESSION['user_id'];
$antrianId = filter_input(INPUT_POST, 'antrian_id', FILTER_VALIDATE_INT);
$tanggal   = date('Y-m-d');

if (!$antrianId || $antrianId < 1) {
    kembali('danger', 'Data antrian tidak valid.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT a.id, a.nomor, a.status, d.nama AS nama_dokter
         FROM antrian a
         JOIN dokter d ON d.id = a.dokter_id
         WHERE a.id = ? AND a.user_id = ? AND a.tanggal = ?
         FOR UPDATE'
    );
    $stmt->execute([$antrianId, $userId, $tanggal]);
    $antrian = $stmt->fetch();

    if (!$antrian) {
        $pdo->rollBack();
        kembali('warning', 'Antrian tidak ditemukan atau bukan milik Anda hari ini.');
    }

    if ($antrian['status'] !== 'menunggu') {
        $pdo->rollBack();
        kembali('warning', 'Antrian yang sudah dipanggil atau selesai tidak dapat dibatalkan.');
    }

    $stmt = $pdo->prepare(
        "DELETE FROM antrian WHERE id = ? AND user_id = ? AND status = 'menunggu'"
    );
    $stmt->execute([$antrianId, $userId]);

    if ($stmt->rowCount() < 1) {
        $pdo->rollBack();
        kembali('warning', 'Antrian gagal dibatalkan. Status mungkin sudah berubah.');
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[batal_antrian] ' . $e->getMessage());
    kembali('danger', 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.');
}

kembali(
    'success',
    'Antrian #' . (int) $antrian['nomor'] . ' ke ' . $antrian['nama_dokter'] . ' berhasil dibatalkan.'
);
