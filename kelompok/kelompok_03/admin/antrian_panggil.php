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
    $pdo->beginTransaction();

    // Kunci baris antrean yang akan dipanggil
    $stmt = $pdo->prepare(
        "SELECT id, dokter_id, status FROM antrian
         WHERE id = ? AND tanggal = ? FOR UPDATE"
    );
    $stmt->execute([$id, $tanggal]);
    $antrian = $stmt->fetch();

    if (!$antrian) {
        $pdo->rollBack();
        kembali('warning', 'Antrian tidak ditemukan pada tanggal tersebut.');
    }

    if ($antrian['status'] !== 'menunggu') {
        $pdo->rollBack();
        kembali('warning', 'Antrian ini tidak berstatus "menunggu".');
    }

    // Cegah memanggil pasien lain bila masih ada yang sedang dipanggil untuk dokter ini
    $cek = $pdo->prepare(
        "SELECT nomor FROM antrian
         WHERE dokter_id = ? AND tanggal = ? AND status = 'dipanggil'
         LIMIT 1 FOR UPDATE"
    );
    $cek->execute([$antrian['dokter_id'], $tanggal]);
    $dipanggil = $cek->fetch();

    if ($dipanggil) {
        $pdo->rollBack();
        kembali(
            'warning',
            'Masih ada pasien yang sedang dipanggil (nomor ' . $dipanggil['nomor'] . '). '
            . 'Selesaikan dulu sebelum memanggil pasien berikutnya.'
        );
    }

    $upd = $pdo->prepare(
        "UPDATE antrian SET status = 'dipanggil'
         WHERE id = ? AND tanggal = ? AND status = 'menunggu'"
    );
    $upd->execute([$id, $tanggal]);

    if ($upd->rowCount() === 1) {
        $pdo->commit();
        kembali('success', 'Pasien berhasil dipanggil.');
    }

    $pdo->rollBack();
    kembali('warning', 'Gagal memanggil pasien. Silakan muat ulang halaman.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[antrian_panggil] ' . $e->getMessage());
    kembali('danger', 'Terjadi kesalahan sistem.');
}
