<?php
/**
 * File: admin/obat/gambar_helper.php
 * Helper upload gambar obat (validasi, simpan, hapus) - Kelompok 22 KlikObat
 */

const GAMBAR_DIR       = __DIR__ . '/../../assets/images/obat/';
const GAMBAR_DEFAULT   = 'default.jpg';
const GAMBAR_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

/**
 * Validasi file upload. Mengembalikan pesan error, atau null jika valid.
 * Dipanggil hanya jika admin memilih file.
 */
function validasiGambar(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran gambar maksimal 2 MB.';
        }
        return 'Upload gambar gagal, silakan coba lagi.';
    }

    if ($file['size'] > GAMBAR_MAX_BYTES) {
        return 'Ukuran gambar maksimal 2 MB.';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        return 'Format gambar harus JPG atau PNG.';
    }

    // Cek isi file sebenarnya, bukan hanya ekstensi
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
        return 'File yang diunggah bukan gambar JPG/PNG yang valid.';
    }

    return null;
}

/**
 * Simpan file ke assets/images/obat/ dengan nama acak.
 * Mengembalikan nama file, atau null jika gagal.
 */
function simpanGambar(array $file): ?string
{
    if (!is_dir(GAMBAR_DIR) && !mkdir(GAMBAR_DIR, 0755, true)) {
        return null;
    }

    $info = getimagesize($file['tmp_name']);
    $ext  = ($info['mime'] === 'image/png') ? 'png' : 'jpg';
    $nama = bin2hex(random_bytes(8)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], GAMBAR_DIR . $nama)) {
        return null;
    }
    return $nama;
}

/**
 * Hapus file gambar lama dari folder (default.jpg tidak pernah dihapus).
 */
function hapusFileGambar(?string $nama): void
{
    if ($nama === null || $nama === '' || $nama === GAMBAR_DEFAULT) {
        return;
    }
    $path = GAMBAR_DIR . basename($nama);
    if (is_file($path)) {
        @unlink($path);
    }
}