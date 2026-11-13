<?php
// Unggah atau hapus foto profil (dari kotak profil di navbar). Foto disimpan di uploads/foto/, namanya di users.foto.
require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$kembali = (string) ($_POST['kembali'] ?? '');
// Hanya boleh kembali ke halaman di situs ini.
$tujuan = (BASE_URL !== '' && str_starts_with($kembali, BASE_URL . '/') && !str_contains($kembali, '//')) ? $kembali : url(dashboard_path($user['role']));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    redirect($tujuan);
}

$pdo = db();
$dir = ROOT_PATH . '/uploads/foto';
$st = $pdo->prepare('SELECT foto FROM users WHERE id = ?');
$st->execute([$user['id']]);
$lama = (string) $st->fetchColumn();

function hapus_foto_lama(string $dir, string $lama): void
{
    if ($lama !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $lama) === 1 && is_file($dir . '/' . $lama)) {
        @unlink($dir . '/' . $lama);
    }
}

if (($_POST['aksi'] ?? '') === 'hapus') {
    hapus_foto_lama($dir, $lama);
    $pdo->prepare('UPDATE users SET foto = NULL WHERE id = ?')->execute([$user['id']]);
    flash('success', 'Foto profil dihapus.');
    redirect($tujuan);
}

$f = $_FILES['foto'] ?? null;
if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $kode = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    flash('danger', in_array($kode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Foto terlalu besar. Maksimal 2 MB.' : 'Pilih foto terlebih dahulu.');
    redirect($tujuan);
}
if ($f['size'] > 2 * 1024 * 1024) {
    flash('danger', 'Foto terlalu besar. Maksimal 2 MB.');
    redirect($tujuan);
}
$info = @getimagesize($f['tmp_name']);
$ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
if (!$info || !$ekstensi) {
    flash('danger', 'File harus berupa gambar JPG, PNG, atau WebP.');
    redirect($tujuan);
}
if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
    flash('danger', 'Folder uploads/foto tidak bisa dibuat. Periksa izin folder.');
    redirect($tujuan);
}
$nama = 'u' . (int) $user['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi;
if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nama)) {
    flash('danger', 'Foto gagal disimpan. Coba lagi.');
    redirect($tujuan);
}
hapus_foto_lama($dir, $lama);
$pdo->prepare('UPDATE users SET foto = ? WHERE id = ?')->execute([$nama, $user['id']]);
flash('success', 'Foto profil diperbarui.');
redirect($tujuan);
