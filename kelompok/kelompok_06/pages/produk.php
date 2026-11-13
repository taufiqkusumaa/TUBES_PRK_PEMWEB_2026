<?php

// Cadangan jika ekstensi mbstring tidak aktif di PHP.
if (!function_exists('mb_substr')) {
    function mb_substr($teks, $mulai, $panjang = null) {
        return $panjang === null ? substr($teks, $mulai) : substr($teks, $mulai, $panjang);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($teks) {
        return strtolower($teks);
    }
}

$pesan = '';
$jenis_pesan = '';

if (empty($_SESSION['csrf_produk'])) {
    $_SESSION['csrf_produk'] = bin2hex(random_bytes(32));
}

function prefixProduk($nama_kategori) {
    $nama = strtolower($nama_kategori);

    // "Non-Kopi" harus dicek lebih dulu, karena mengandung kata "kopi".
    if (strpos($nama, 'non-kopi') !== false ||
        strpos($nama, 'non kopi') !== false ||
        strpos($nama, 'teh') !== false) {
        return 'NON';
    } elseif (strpos($nama, 'kopi') !== false) {
        return 'KOP';
    } elseif (strpos($nama, 'makanan') !== false) {
        return 'MKN';
    } elseif (strpos($nama, 'snack') !== false ||
              strpos($nama, 'pastry') !== false) {
        return 'SNK';
    }

    return 'KAT';
}

// Menyimpan gambar produk ke uploads/produk/. Mengembalikan nama file,
// atau null jika tidak ada file yang diunggah. Melempar Exception jika tidak valid.
function simpanGambarProduk($file) {

    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload gambar gagal. Coba lagi.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Ukuran gambar maksimal 2 MB.');
    }

    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';

    $ekstensi = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($ekstensi[$mime])) {
        throw new Exception('Format gambar harus JPG, PNG, atau WEBP.');
    }

    $folder = __DIR__ . '/../uploads/produk/';

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        throw new Exception('Folder uploads/produk tidak bisa dibuat.');
    }

    $nama_file = 'produk_' . bin2hex(random_bytes(8)) . '.' . $ekstensi[$mime];

    if (!move_uploaded_file($file['tmp_name'], $folder . $nama_file)) {
        throw new Exception('Gagal menyimpan file gambar.');
    }

    return $nama_file;
}

function hapusFileGambar($nama_file) {
    if (!$nama_file) {
        return;
    }

    $path = __DIR__ . '/../uploads/produk/' . basename($nama_file);

    if (is_file($path)) {
        @unlink($path);
    }
}

function urlGambarProduk($nama_file) {
    if (!$nama_file) {
        return '';
    }

    $nama_file = basename($nama_file);

    return is_file(__DIR__ . '/../uploads/produk/' . $nama_file)
        ? 'uploads/produk/' . rawurlencode($nama_file)
        : '';
}

function generateKodeProduk($conn, $prefix) {

    $nama_lock = 'flashpos_produk_' . $prefix;

    $lock = mysqli_prepare(
        $conn,
        "SELECT GET_LOCK(?, 5)"
    );

    mysqli_stmt_bind_param($lock, "s", $nama_lock);
    mysqli_stmt_execute($lock);
    mysqli_stmt_bind_result($lock, $lock_result);
    mysqli_stmt_fetch($lock);
    mysqli_stmt_close($lock);

    if ((int)$lock_result !== 1) {
        throw new Exception('Sistem sedang memproses produk lain. Silakan coba lagi.');
    }

    try {

        $query = mysqli_prepare(
            $conn,
            "SELECT kode_produk
             FROM products
             WHERE kode_produk LIKE CONCAT(?, '-%')"
        );

        mysqli_stmt_bind_param($query, "s", $prefix);
        mysqli_stmt_execute($query);
        mysqli_stmt_bind_result($query, $kode_lama);

        $nomor_terakhir = 0;

        while (mysqli_stmt_fetch($query)) {

            if (preg_match(
                '/^' . preg_quote($prefix, '/') . '-(\d+)$/',
                $kode_lama,
                $hasil
            )) {
                $nomor_terakhir = max(
                    $nomor_terakhir,
                    (int)$hasil[1]
                );
            }
        }

        mysqli_stmt_close($query);

        $nomor_baru = $nomor_terakhir + 1;

        return $prefix . '-' . str_pad(
            $nomor_baru,
            3,
            '0',
            STR_PAD_LEFT
        );

    } finally {

        $release = mysqli_prepare(
            $conn,
            "SELECT RELEASE_LOCK(?)"
        );

        mysqli_stmt_bind_param($release, "s", $nama_lock);
        mysqli_stmt_execute($release);
        mysqli_stmt_close($release);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['simpan_produk'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_produk'], $token)) {

        $pesan = 'Permintaan tidak valid. Muat ulang halaman.';
        $jenis_pesan = 'error';

    } else {

        $id = (int)($_POST['id_produk'] ?? 0);
        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = (int)($_POST['category_id'] ?? 0);
        $harga_input = $_POST['harga'] ?? '';
        $stok_input = $_POST['stok'] ?? '';

        if (
            $nama === '' ||
            strlen($nama) > 100 ||
            $kategori <= 0 ||
            !is_numeric($harga_input) ||
            (float)$harga_input <= 0 ||
            filter_var($stok_input, FILTER_VALIDATE_INT) === false ||
            (int)$stok_input < 0
        ) {

            $pesan = 'Mohon isi semua data dengan benar.';
            $jenis_pesan = 'error';

        } else {

            $harga = (float)$harga_input;
            $stok = (int)$stok_input;
            $status = $stok > 0 ? 'tersedia' : 'habis';

            $deskripsi = mb_substr(trim($_POST['deskripsi'] ?? ''), 0, 160);
            $gambar_baru = null;
            $upload_gagal = false;

            try {
                $gambar_baru = simpanGambarProduk($_FILES['gambar'] ?? null);
            } catch (Throwable $e) {
                $pesan = $e->getMessage();
                $jenis_pesan = 'error';
                $upload_gagal = true;
            }

            $cek_kategori = mysqli_prepare(
                $conn,
                "SELECT nama_kategori
                 FROM categories
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $cek_kategori,
                "i",
                $kategori
            );

            mysqli_stmt_execute($cek_kategori);
            mysqli_stmt_bind_result(
                $cek_kategori,
                $nama_kategori
            );

            $kategori_ada = mysqli_stmt_fetch($cek_kategori);

            mysqli_stmt_close($cek_kategori);

            if (!$kategori_ada) {

                $pesan = 'Kategori tidak ditemukan.';
                $jenis_pesan = 'error';

            } else {

                if ($upload_gagal) {

                    // Pesan error upload sudah diisi di atas.

                } elseif ($id > 0) {

                    $cek_produk = mysqli_prepare(
                        $conn,
                        "SELECT id FROM products WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $cek_produk,
                        "i",
                        $id
                    );

                    mysqli_stmt_execute($cek_produk);
                    mysqli_stmt_store_result($cek_produk);

                    $produk_ada = mysqli_stmt_num_rows($cek_produk) > 0;

                    mysqli_stmt_close($cek_produk);

                    if (!$produk_ada) {

                        hapusFileGambar($gambar_baru);

                        $pesan = 'Produk tidak ditemukan.';
                        $jenis_pesan = 'error';

                    } else {

                        // Simpan nama gambar lama supaya bisa dihapus jika diganti.
                        $q_lama = mysqli_prepare(
                            $conn,
                            "SELECT gambar FROM products WHERE id = ?"
                        );
                        mysqli_stmt_bind_param($q_lama, "i", $id);
                        mysqli_stmt_execute($q_lama);
                        mysqli_stmt_bind_result($q_lama, $gambar_lama);
                        mysqli_stmt_fetch($q_lama);
                        mysqli_stmt_close($q_lama);

                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE products
                             SET category_id = ?,
                                 nama_produk = ?,
                                 harga = ?,
                                 stok = ?,
                                 status = ?,
                                 deskripsi = ?,
                                 gambar = COALESCE(?, gambar)
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "isdisssi",
                            $kategori,
                            $nama,
                            $harga,
                            $stok,
                            $status,
                            $deskripsi,
                            $gambar_baru,
                            $id
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            mysqli_stmt_close($stmt);

                            // Gambar diganti: hapus file lama.
                            if ($gambar_baru && $gambar_lama) {
                                hapusFileGambar($gambar_lama);
                            }

                            echo "<script>
                                window.location.replace(
                                    'index.php?page=produk&status=edit'
                                );
                            </script>";
                            exit;

                        } else {

                            hapusFileGambar($gambar_baru);

                            $pesan = 'Gagal mengubah produk.';
                            $jenis_pesan = 'error';
                        }

                        mysqli_stmt_close($stmt);
                    }

                } else {

                    try {

                        $prefix = prefixProduk($nama_kategori);

                        $kode = generateKodeProduk(
                            $conn,
                            $prefix
                        );

                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO products
                            (category_id, kode_produk, nama_produk,
                             gambar, deskripsi, harga, stok, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "issssdis",
                            $kategori,
                            $kode,
                            $nama,
                            $gambar_baru,
                            $deskripsi,
                            $harga,
                            $stok,
                            $status
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            mysqli_stmt_close($stmt);

                            echo "<script>
                                window.location.replace(
                                    'index.php?page=produk&status=tambah'
                                );
                            </script>";
                            exit;

                        } else {

                            hapusFileGambar($gambar_baru);

                            $pesan = 'Gagal menyimpan produk.';
                            $jenis_pesan = 'error';
                        }

                        mysqli_stmt_close($stmt);

                    } catch (Throwable $e) {

                        hapusFileGambar($gambar_baru);

                        $pesan = $e->getMessage();
                        $jenis_pesan = 'error';
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['hapus_produk'])) {

    $token = $_POST['csrf_token'] ?? '';
    $id = (int)($_POST['id_produk'] ?? 0);

    if (!hash_equals($_SESSION['csrf_produk'], $token)) {

        $pesan = 'Permintaan tidak valid.';
        $jenis_pesan = 'error';

    } elseif ($id <= 0) {

        $pesan = 'ID produk tidak valid.';
        $jenis_pesan = 'error';

    } else {

        $cek = mysqli_prepare(
            $conn,
            "SELECT id
             FROM order_items
             WHERE product_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($cek, "i", $id);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {

            $pesan = 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi.';
            $jenis_pesan = 'error';

        } else {

            $q_img = mysqli_prepare(
                $conn,
                "SELECT gambar FROM products WHERE id = ?"
            );
            mysqli_stmt_bind_param($q_img, "i", $id);
            mysqli_stmt_execute($q_img);
            mysqli_stmt_bind_result($q_img, $gambar_hapus);
            mysqli_stmt_fetch($q_img);
            mysqli_stmt_close($q_img);

            $hapus = mysqli_prepare(
                $conn,
                "DELETE FROM products WHERE id = ?"
            );

            mysqli_stmt_bind_param($hapus, "i", $id);

            if (
                mysqli_stmt_execute($hapus) &&
                mysqli_stmt_affected_rows($hapus) > 0
            ) {

                hapusFileGambar($gambar_hapus);

                mysqli_stmt_close($hapus);
                mysqli_stmt_close($cek);

                echo "<script>
                    window.location.replace(
                        'index.php?page=produk&status=hapus'
                    );
                </script>";
                exit;

            } else {

                $pesan = 'Produk gagal dihapus atau tidak ditemukan.';
                $jenis_pesan = 'error';
            }

            mysqli_stmt_close($hapus);
        }

        mysqli_stmt_close($cek);
    }
}

if (isset($_GET['status'])) {

    $notifikasi = [
        'tambah' => 'Produk berhasil ditambahkan!',
        'edit' => 'Produk berhasil diperbarui!',
        'hapus' => 'Produk berhasil dihapus!'
    ];

    if (isset($notifikasi[$_GET['status']])) {
        $pesan = $notifikasi[$_GET['status']];
        $jenis_pesan = 'success';
    }
}

$q_statistik = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_produk,
        COALESCE(SUM(stok), 0) AS total_stok
     FROM products"
);

$statistik = mysqli_fetch_assoc($q_statistik);

$q_kategori_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM categories"
);

$total_kategori = mysqli_fetch_assoc($q_kategori_total)['total'];

$q_kategori = mysqli_query(
    $conn,
    "SELECT * FROM categories ORDER BY nama_kategori ASC"
);

$kategori_list = [];

while ($k = mysqli_fetch_assoc($q_kategori)) {
    $kategori_list[] = $k;
}

$q_produk = mysqli_query(
    $conn,
    "SELECT p.*, c.nama_kategori
     FROM products p
     JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC"
);

$produk_list = [];

while ($row = mysqli_fetch_assoc($q_produk)) {
    $produk_list[] = $row;
}
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <div>
            <h2 class="text-xl font-bold text-slate-900">
                Manajemen Produk
            </h2>

            <p class="text-sm text-slate-500 mt-1">
                Kelola katalog menu dan persediaan FlashPOS.
            </p>
        </div>

        <button type="button"
            onclick="bukaModal()"
            class="px-4 py-2.5 bg-slate-900 text-white rounded-lg text-sm font-medium hover:bg-slate-800">
            + Tambah Produk
        </button>

    </div>

    <?php if ($pesan): ?>

        <div class="p-4 rounded-lg text-sm
            <?= $jenis_pesan === 'success'
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-red-50 text-red-700' ?>">

            <?= htmlspecialchars($pesan) ?>

        </div>

    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white p-5 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">Total Produk</p>

            <h3 class="text-2xl font-bold mt-2">
                <?= (int)$statistik['total_produk'] ?>
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">Total Kategori</p>

            <h3 class="text-2xl font-bold mt-2">
                <?= (int)$total_kategori ?>
            </h3>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">Total Stok</p>

            <h3 class="text-2xl font-bold mt-2">
                <?= (int)$statistik['total_stok'] ?>
            </h3>
        </div>

    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

        <div class="p-5 border-b border-slate-100">

            <h3 class="font-semibold text-slate-900">
                Daftar Menu / Produk
            </h3>

            <p class="text-xs text-slate-400 mt-1">
                Seluruh produk yang tersimpan di database.
            </p>

        </div>

        <div class="p-4">

            <input type="text"
                id="cariProduk"
                placeholder="Cari kode atau nama produk..."
                class="w-full sm:w-80 border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-300">

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">

                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Nama Produk</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Harga</th>
                        <th class="px-5 py-3 text-right">Stok</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php foreach ($produk_list as $row): ?>

                        <tr class="baris-produk hover:bg-slate-50">

                            <td class="px-5 py-4 font-mono text-xs">
                                <?= htmlspecialchars($row['kode_produk']) ?>
                            </td>

                            <td class="px-5 py-4 font-medium">

                                <?php $img_row = urlGambarProduk($row['gambar'] ?? ''); ?>

                                <div class="flex items-center gap-3">

                                    <?php if ($img_row): ?>
                                        <img src="<?= htmlspecialchars($img_row) ?>"
                                            alt=""
                                            class="w-10 h-10 rounded-lg object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center text-xs">
                                            Foto
                                        </div>
                                    <?php endif; ?>

                                    <span><?= htmlspecialchars($row['nama_produk']) ?></span>

                                </div>

                            </td>

                            <td class="px-5 py-4">
                                <?= htmlspecialchars($row['nama_kategori']) ?>
                            </td>

                            <td class="px-5 py-4">
                                <?= formatRupiah($row['harga']) ?>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <?= (int)$row['stok'] ?>
                            </td>

                            <td class="px-5 py-4 text-center">

                                <span class="px-2 py-1 rounded-md text-xs
                                    <?= $row['status'] === 'tersedia'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-red-50 text-red-700' ?>">

                                    <?= htmlspecialchars($row['status']) ?>

                                </span>

                            </td>

                            <td class="px-5 py-4">

                                <div class="flex justify-center gap-2">

                                    <button type="button"
                                        onclick="editProduk(this)"

                                        data-id="<?= (int)$row['id'] ?>"
                                        data-kode="<?= htmlspecialchars($row['kode_produk'], ENT_QUOTES) ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_produk'], ENT_QUOTES) ?>"
                                        data-kategori="<?= (int)$row['category_id'] ?>"
                                        data-harga="<?= htmlspecialchars($row['harga'], ENT_QUOTES) ?>"
                                        data-stok="<?= (int)$row['stok'] ?>"
                                        data-deskripsi="<?= htmlspecialchars($row['deskripsi'] ?? '', ENT_QUOTES) ?>"
                                        data-gambar="<?= htmlspecialchars($img_row, ENT_QUOTES) ?>"

                                        class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs hover:bg-blue-100">

                                        Edit

                                    </button>

                                    <form method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?')">

                                        <input type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_produk']) ?>">

                                        <input type="hidden"
                                            name="id_produk"
                                            value="<?= (int)$row['id'] ?>">

                                        <button type="submit"
                                            name="hapus_produk"
                                            class="px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs hover:bg-red-100">

                                            Hapus

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($produk_list)): ?>

                        <tr>
                            <td colspan="7"
                                class="p-6 text-center text-slate-400">
                                Belum ada produk.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<div id="modalProduk"
    class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center p-4 z-50">

    <div class="bg-white rounded-xl w-full max-w-lg p-6">

        <div class="flex justify-between items-center mb-5">

            <h3 id="judulModal" class="font-bold text-lg">
                Tambah Produk
            </h3>

            <button type="button"
                onclick="tutupModal()"
                class="text-slate-400 hover:text-slate-900 text-xl">
                &times;
            </button>

        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">

            <input type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_produk']) ?>">

            <input type="hidden"
                name="id_produk"
                id="id_produk"
                value="">

            <div>

                <label class="block text-sm font-medium mb-1">
                    Kode Produk
                </label>

                <input type="text"
                    id="kode_produk"
                    readonly
                    placeholder="Dibuat otomatis oleh sistem"
                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm bg-slate-100 text-slate-500">

                <p class="text-xs text-slate-400 mt-1">
                    Kode akan dibuat otomatis setelah produk disimpan.
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium mb-1">
                    Nama Produk
                </label>

                <input type="text"
                    name="nama_produk"
                    id="nama_produk"
                    maxlength="100"
                    required
                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm">

            </div>

            <div>

                <label class="block text-sm font-medium mb-1">
                    Deskripsi Singkat
                </label>

                <input type="text"
                    name="deskripsi"
                    id="deskripsi"
                    maxlength="160"
                    placeholder="Contoh: Espresso dengan susu dan gula aren"
                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm">

            </div>

            <div>

                <label class="block text-sm font-medium mb-1">
                    Foto Produk
                </label>

                <div class="flex items-center gap-3">

                    <img id="previewGambar"
                        src=""
                        alt=""
                        class="w-16 h-16 rounded-lg object-cover bg-slate-100 hidden">

                    <input type="file"
                        name="gambar"
                        id="gambar"
                        accept="image/jpeg,image/png,image/webp"
                        class="text-sm w-full">

                </div>

                <p class="text-xs text-slate-400 mt-1">
                    JPG, PNG, atau WEBP. Maksimal 2 MB. Kosongkan jika tidak ingin mengubah foto.
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium mb-1">
                    Kategori
                </label>

                <select name="category_id"
                    id="category_id"
                    required
                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm">

                    <option value="">
                        Pilih Kategori
                    </option>

                    <?php foreach ($kategori_list as $k): ?>

                        <option value="<?= (int)$k['id'] ?>">
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="grid grid-cols-2 gap-3">

                <div>

                    <label class="block text-sm font-medium mb-1">
                        Harga (Rp)
                    </label>

                    <input type="number"
                        name="harga"
                        id="harga"
                        min="0.01"
                        step="0.01"
                        required
                        class="w-full border border-slate-200 rounded-lg p-2.5 text-sm">

                </div>

                <div>

                    <label class="block text-sm font-medium mb-1">
                        Stok
                    </label>

                    <input type="number"
                        name="stok"
                        id="stok"
                        min="0"
                        step="1"
                        required
                        class="w-full border border-slate-200 rounded-lg p-2.5 text-sm">

                </div>

            </div>

            <div class="flex justify-end gap-2 pt-3">

                <button type="button"
                    onclick="tutupModal()"
                    class="px-4 py-2 border border-slate-200 rounded-lg text-sm">

                    Batal

                </button>

                <button type="submit"
                    name="simpan_produk"
                    class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm hover:bg-slate-800">

                    Simpan Produk

                </button>

            </div>

        </form>

    </div>

</div>

<script>

function bukaModal() {

    document.getElementById('judulModal').textContent = 'Tambah Produk';

    document.getElementById('id_produk').value = '';
    document.getElementById('kode_produk').value = '';
    document.getElementById('nama_produk').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('harga').value = '';
    document.getElementById('stok').value = '';
    document.getElementById('deskripsi').value = '';
    document.getElementById('gambar').value = '';

    setPreview('');

    tampilkanModal();
}

function editProduk(tombol) {

    document.getElementById('judulModal').textContent = 'Edit Produk';

    document.getElementById('id_produk').value = tombol.dataset.id;
    document.getElementById('kode_produk').value = tombol.dataset.kode;
    document.getElementById('nama_produk').value = tombol.dataset.nama;
    document.getElementById('category_id').value = tombol.dataset.kategori;
    document.getElementById('harga').value = tombol.dataset.harga;
    document.getElementById('stok').value = tombol.dataset.stok;
    document.getElementById('deskripsi').value = tombol.dataset.deskripsi || '';
    document.getElementById('gambar').value = '';

    setPreview(tombol.dataset.gambar || '');

    tampilkanModal();
}

function setPreview(src) {

    const img = document.getElementById('previewGambar');

    if (src) {
        img.src = src;
        img.classList.remove('hidden');
    } else {
        img.removeAttribute('src');
        img.classList.add('hidden');
    }
}

document.getElementById('gambar').addEventListener('change', function () {

    if (this.files && this.files[0]) {
        setPreview(URL.createObjectURL(this.files[0]));
    }

});

function tampilkanModal() {

    const modal = document.getElementById('modalProduk');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function tutupModal() {

    const modal = document.getElementById('modalProduk');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('cariProduk').addEventListener('input', function () {

    const kataKunci = this.value.toLowerCase();

    document.querySelectorAll('.baris-produk').forEach(function (baris) {

        baris.style.display =
            baris.textContent.toLowerCase().includes(kataKunci)
            ? ''
            : 'none';

    });

});

</script>