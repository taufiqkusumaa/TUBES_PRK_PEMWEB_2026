<?php
/**
 * File: admin/obat/ubah.php
 * Ubah data obat (Update) - Kelompok 22 KlikObat
 */

require_once '../auth.php'; // halaman ini hanya boleh dibuka admin yang sudah login

require_once '../../config/koneksi.php';
require_once '../../config/helper.php';
require_once __DIR__ . '/gambar_helper.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data obat yang akan diubah
$stmt = mysqli_prepare($conn,
    "SELECT id_obat, nama_obat, id_kategori, harga, stok, indikasi, aturan_pakai, peringatan, gambar
     FROM obat WHERE id_obat = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$obat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$obat) {
    header('Location: index.php?status=gagal');
    exit;
}

$gambar_lama = $obat['gambar'] ?: GAMBAR_DEFAULT;

// Ambil semua kategori untuk dropdown
$kategori = [];
$q = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
while ($k = mysqli_fetch_assoc($q)) {
    $kategori[] = $k;
}

$errors = [];
$data = [
    'nama_obat'    => $obat['nama_obat'],
    'id_kategori'  => (string)$obat['id_kategori'],
    'harga'        => (string)(int)$obat['harga'],
    'stok'         => (string)$obat['stok'],
    'indikasi'     => $obat['indikasi'],
    'aturan_pakai' => $obat['aturan_pakai'],
    'peringatan'   => $obat['peringatan'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $v) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    // Validasi
    if ($data['nama_obat'] === '') {
        $errors[] = 'Nama obat wajib diisi.';
    } elseif (mb_strlen($data['nama_obat']) > 150) {
        $errors[] = 'Nama obat maksimal 150 karakter.';
    }

    $ada_kategori = false;
    foreach ($kategori as $k) {
        if ((string)$k['id_kategori'] === $data['id_kategori']) {
            $ada_kategori = true;
        }
    }
    if (!$ada_kategori) {
        $errors[] = 'Pilih kategori yang valid.';
    }

    if ($data['stok'] === '' || !ctype_digit($data['stok'])) {
        $errors[] = 'Stok harus berupa angka bulat 0 atau lebih.';
    }

    if ($data['harga'] === '' || !ctype_digit($data['harga']) || (int)$data['harga'] > 99999999) {
        $errors[] = 'Harga harus berupa angka bulat 0 sampai 99.999.999 (tanpa titik atau koma).';
    }

    if ($data['indikasi'] === '')    $errors[] = 'Deskripsi & indikasi wajib diisi.';
    if ($data['aturan_pakai'] === '') $errors[] = 'Aturan pakai & dosis wajib diisi.';
    if ($data['peringatan'] === '')   $errors[] = 'Peringatan / kontraindikasi wajib diisi.';

    // Gambar opsional: kalau tidak memilih file, gambar lama dipertahankan
    $ada_file = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($ada_file) {
        $err_gambar = validasiGambar($_FILES['gambar']);
        if ($err_gambar !== null) {
            $errors[] = $err_gambar;
        }
    }

    // Simpan file baru hanya jika seluruh validasi lolos
    $gambar_baru = null;
    $gambar_pakai = $gambar_lama;
    if (!$errors && $ada_file) {
        $gambar_baru = simpanGambar($_FILES['gambar']);
        if ($gambar_baru === null) {
            $errors[] = 'Gagal menyimpan file gambar ke server.';
        } else {
            $gambar_pakai = $gambar_baru;
        }
    }

    if (!$errors) {
        $id_kat = (int)$data['id_kategori'];
        $harga  = (int)$data['harga'];
        $stok   = (int)$data['stok'];

        $stmt = mysqli_prepare($conn,
            "UPDATE obat
             SET nama_obat = ?, id_kategori = ?, harga = ?, stok = ?, indikasi = ?, aturan_pakai = ?, peringatan = ?, gambar = ?
             WHERE id_obat = ?");
        mysqli_stmt_bind_param($stmt, "siiissssi",
            $data['nama_obat'], $id_kat, $harga, $stok,
            $data['indikasi'], $data['aturan_pakai'], $data['peringatan'], $gambar_pakai, $id);

        if (mysqli_stmt_execute($stmt)) {
            // Database sudah memakai gambar baru, file lama boleh dihapus
            if ($gambar_baru !== null) {
                hapusFileGambar($gambar_lama);
            }
            header('Location: index.php?status=ubah');
            exit;
        }
        // Gagal update: buang file baru supaya tidak jadi sampah
        if ($gambar_baru !== null) {
            hapusFileGambar($gambar_baru);
        }
        $errors[] = 'Gagal memperbarui data di database.';
    }
}

// Layout bersama (Rachel): header.php memuat <head>, sidebar, dan header halaman
$judulHalaman = 'Ubah Obat';
require_once '../../backend/admin/layout/header.php';
?>
<!-- CSS dipasang ulang dengan path relatif supaya tetap termuat walau path di header.php beda -->
<link rel="stylesheet" href="../../frontend/assets/css/style.css">
<link rel="stylesheet" href="../../frontend/assets/css/admin.css">
<style>
    .obat-notif { padding: 12px 15px; margin-bottom: 20px; border-radius: 10px; font-weight: 600; font-size: 14px; }
    .obat-notif.ok { background: #d1e7dd; color: #0f5132; }
    .obat-notif.err { background: #fee2e2; color: #b91c1c; }
    .obat-notif ul { margin-left: 18px; }
    .obat-link { color: var(--admin-primary); font-weight: 600; text-decoration: none; }
    .obat-link-hapus { background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font: inherit; font-weight: 600; }
    .obat-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
    .obat-badge { color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .obat-form label { display: block; font-weight: 600; margin: 18px 0 8px; color: var(--admin-text); }
    .obat-form label:first-child { margin-top: 0; }
    .obat-form textarea { min-height: 90px; }
    .obat-hint { font-size: 12px; color: var(--admin-secondary); font-weight: 400; }
    .obat-preview { display: block; width: 120px; height: 120px; object-fit: cover; border: 1px solid var(--admin-border); border-radius: 10px; }
    .obat-aksi { display: flex; gap: 12px; align-items: center; margin-top: 24px; }
    .obat-batal { color: var(--admin-secondary); text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 19px; }
</style>

<div class="admin-content">
    <h2>Form Ubah Obat</h2>

    <?php if ($errors): ?>
        <div class="obat-notif err">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="admin-table-container" style="max-width: 680px; padding: 25px;">
        <form method="post" enctype="multipart/form-data" class="obat-form">
            <label for="nama_obat">Nama Obat</label>
                <input type="text" id="nama_obat" name="nama_obat" maxlength="150"
                       value="<?= htmlspecialchars($data['nama_obat']) ?>">

                <label for="id_kategori">Kategori</label>
                <select id="id_kategori" name="id_kategori">
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= (int)$k['id_kategori'] ?>"
                            <?= (string)$k['id_kategori'] === $data['id_kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <label for="harga">Harga (Rp)</label>
            <input type="number" id="harga" name="harga" min="0" step="100"
                   value="<?= htmlspecialchars($data['harga']) ?>">

            <label for="stok">Stok</label>
                <input type="number" id="stok" name="stok" min="0"
                       value="<?= htmlspecialchars($data['stok']) ?>">


                <label>Gambar Saat Ini</label>
                <img class="obat-preview" src="../../assets/images/obat/<?= htmlspecialchars($gambar_lama) ?>"
                     alt="Gambar <?= htmlspecialchars($obat['nama_obat']) ?>">

                <label for="gambar">Ganti Gambar <span class="obat-hint">(opsional, JPG/PNG, maks. 2 MB; kosongkan jika tidak diganti)</span></label>
                <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png">

                <label for="indikasi">Deskripsi &amp; Indikasi</label>
                <textarea id="indikasi" name="indikasi"><?= htmlspecialchars($data['indikasi']) ?></textarea>

                <label for="aturan_pakai">Aturan Pakai &amp; Dosis</label>
                <textarea id="aturan_pakai" name="aturan_pakai"><?= htmlspecialchars($data['aturan_pakai']) ?></textarea>

                <label for="peringatan">Peringatan / Kontraindikasi</label>
                <textarea id="peringatan" name="peringatan"><?= htmlspecialchars($data['peringatan']) ?></textarea>

            <div class="obat-aksi">
                <button type="submit" class="admin-btn">Simpan Perubahan</button>
                <a href="index.php" class="obat-batal">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../backend/admin/layout/footer.php'; ?>