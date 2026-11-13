<?php
require_once __DIR__ . '/../includes/cek_login.php';

// Sesuaikan dengan nama halaman buatan Anggota 5 (FE Pasien)
const HALAMAN_SUKSES = 'pasien.php';
const HALAMAN_GAGAL  = 'pasien.php';

class AturanAntrianException extends Exception {}

function kembali($halaman, $tipe, $pesan)
{
    $_SESSION['flash_tipe']  = $tipe;
    $_SESSION['flash_pesan'] = $pesan;
    header('Location: ' . $halaman);
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

$userId   = (int) $_SESSION['user_id'];
$dokterId = filter_input(INPUT_POST, 'dokter_id', FILTER_VALIDATE_INT);

if (!$dokterId || $dokterId < 1) {
    kembali(HALAMAN_GAGAL, 'danger', 'Dokter tidak valid.');
}

$namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
             5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hariIni  = $namaHari[(int) date('N')];
$tanggal  = date('Y-m-d');
$nomor    = 0;

try {
    $pdo->beginTransaction();

    // Kunci baris dokter agar dua pasien tidak mendapat nomor yang sama
    $stmt = $pdo->prepare(
        'SELECT id, nama, kuota_per_hari, hari_praktik
         FROM dokter WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$dokterId]);
    $dokter = $stmt->fetch();

    if (!$dokter) {
        throw new AturanAntrianException('Dokter tidak ditemukan.');
    }

    // 1. Hari ini termasuk hari praktik?
    $hariPraktik = array_map(
        fn($h) => mb_strtolower(trim($h)),
        explode(',', $dokter['hari_praktik'])
    );
    if (!in_array(mb_strtolower($hariIni), $hariPraktik, true)) {
        throw new AturanAntrianException(
            $dokter['nama'] . ' tidak praktik pada hari ' . $hariIni . '.'
        );
    }

    // 2. Pasien sudah punya antrian ke dokter ini hari ini?
    $stmt = $pdo->prepare(
        'SELECT nomor FROM antrian
         WHERE user_id = ? AND dokter_id = ? AND tanggal = ? LIMIT 1'
    );
    $stmt->execute([$userId, $dokterId, $tanggal]);
    $sudah = $stmt->fetch();
    if ($sudah) {
        throw new AturanAntrianException(
            'Anda sudah mengambil antrian ke dokter ini hari ini (nomor '
            . $sudah['nomor'] . ').'
        );
    }

    // 3. Kuota masih tersedia?
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS jumlah, COALESCE(MAX(nomor), 0) AS terakhir
         FROM antrian WHERE dokter_id = ? AND tanggal = ?'
    );
    $stmt->execute([$dokterId, $tanggal]);
    $rekap = $stmt->fetch();

    if ((int) $rekap['jumlah'] >= (int) $dokter['kuota_per_hari']) {
        throw new AturanAntrianException(
            'Kuota ' . $dokter['nama'] . ' hari ini sudah penuh.'
        );
    }

    // Terbitkan nomor
    $nomor = (int) $rekap['terakhir'] + 1;

    $stmt = $pdo->prepare(
        "INSERT INTO antrian (user_id, dokter_id, tanggal, nomor, status)
         VALUES (?, ?, ?, ?, 'menunggu')"
    );
    $stmt->execute([$userId, $dokterId, $tanggal, $nomor]);

    $pdo->commit();
} catch (AturanAntrianException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    kembali(HALAMAN_GAGAL, 'warning', $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 1062 = duplicate entry (UNIQUE constraint menolak)
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        kembali(HALAMAN_GAGAL, 'warning',
            'Nomor antrian bentrok atau Anda sudah terdaftar. Silakan coba lagi.');
    }
    error_log('[ambil_antrian] ' . $e->getMessage());
    kembali(HALAMAN_GAGAL, 'danger',
        'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.');
}

kembali(HALAMAN_SUKSES, 'success',
    'Berhasil! Nomor antrian Anda: ' . $nomor);