<?php
include '../../config/koneksi.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$id_raw = $_POST['id'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';
$id = is_string($id_raw)
    ? filter_var($id_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id === false
    || !is_string($csrf_token) || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    header('Location: index.php?status=danger&pesan=' . urlencode('Permintaan tidak valid atau sesi telah berakhir.'));
    exit;
}

$transaksi_dimulai = false;
$berhasil = false;
try {
    if (!mysqli_begin_transaction($koneksi)) {
        throw new RuntimeException('Transaksi tidak dapat dimulai.');
    }
    $transaksi_dimulai = true;

    // Ambil data log, kunci barisnya
    $st = mysqli_prepare($koneksi,
        "SELECT id_pakan, jumlah_kg FROM log_pemberian_pakan WHERE id_log_pakan = ? FOR UPDATE");
    mysqli_stmt_bind_param($st, 'i', $id);
    if (!mysqli_stmt_execute($st)) {
        throw new RuntimeException('Pembacaan log gagal.');
    }
    $log = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

    if (!$log) {
        throw new DomainException('Data log tidak ditemukan.');
    }

    // Kembalikan stok
    $st = mysqli_prepare($koneksi, "UPDATE pakan SET stok_kg = stok_kg + ? WHERE id_pakan = ?");
    mysqli_stmt_bind_param($st, 'di', $log['jumlah_kg'], $log['id_pakan']);
    if (!mysqli_stmt_execute($st)) {
        throw new RuntimeException('Pengembalian stok gagal.');
    }
    if (mysqli_stmt_affected_rows($st) !== 1) {
        throw new RuntimeException('Stok pakan tidak berhasil diperbarui.');
    }

    // Hapus log
    $st = mysqli_prepare($koneksi, "DELETE FROM log_pemberian_pakan WHERE id_log_pakan = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    if (!mysqli_stmt_execute($st)) {
        throw new RuntimeException('Penghapusan log gagal.');
    }
    if (mysqli_stmt_affected_rows($st) !== 1) {
        throw new RuntimeException('Log tidak berhasil dihapus.');
    }

    if (!mysqli_commit($koneksi)) {
        throw new RuntimeException('Transaksi tidak berhasil disimpan.');
    }
    $pesan = 'Log dihapus dan stok pakan dikembalikan.';
    $berhasil = true;
} catch (DomainException $e) {
    if ($transaksi_dimulai) mysqli_rollback($koneksi);
    $pesan = $e->getMessage();
} catch (Throwable $e) {
    if ($transaksi_dimulai) mysqli_rollback($koneksi);
    error_log('Gagal menghapus log pemberian pakan: ' . $e->getMessage());
    $pesan = 'Log gagal dihapus. Silakan coba lagi.';
}

header('Location: index.php?status=' . ($berhasil ? 'success' : 'danger')
    . '&pesan=' . urlencode($pesan));
exit;