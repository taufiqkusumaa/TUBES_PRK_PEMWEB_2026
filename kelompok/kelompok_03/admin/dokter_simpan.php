
<?php

require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dokter_list.php');
    exit;
}

$id = $_POST['id'] ?? null;
$nama = trim($_POST['nama'] ?? '');
$spesialis = trim($_POST['spesialis'] ?? '');
$kuota_per_hari = trim($_POST['kuota_per_hari'] ?? '');
$hari_praktik = $_POST['hari_praktik'] ?? [];

$isEdit = !empty($id);

if (empty($nama)) {
    $error = 'Nama dokter wajib diisi.';
} elseif (empty($spesialis)) {
    $error = 'Spesialis wajib diisi.';
} elseif (empty($kuota_per_hari) || !is_numeric($kuota_per_hari) || $kuota_per_hari < 1) {
    $error = 'Kuota per hari harus angka positif.';
} elseif (empty($hari_praktik)) {
    $error = 'Pilih minimal satu hari praktik.';
} else {
    $hari_praktik_str = implode(',', $hari_praktik);

    try {
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE dokter SET nama = ?, spesialis = ?, kuota_per_hari = ?, hari_praktik = ? WHERE id = ?');
            $stmt->execute([$nama, $spesialis, $kuota_per_hari, $hari_praktik_str, $id]);
            $success = 'Data dokter berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO dokter (nama, spesialis, kuota_per_hari, hari_praktik) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nama, $spesialis, $kuota_per_hari, $hari_praktik_str]);
            $success = 'Dokter berhasil ditambahkan.';
        }
        
        header('Location: dokter_list.php?success=' . urlencode($success));
        exit;
    } catch (PDOException $e) {
        error_log('[Dokter Simpan] ' . $e->getMessage());
        $error = 'Gagal menyimpan data dokter.';
    }
}

$redirectUrl = $isEdit ? "dokter_form.php?id=$id" : 'dokter_form.php';
header("Location: $redirectUrl?error=" . urlencode($error));
exit;
