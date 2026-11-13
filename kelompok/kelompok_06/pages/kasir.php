<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

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

// ===============================
// PENGATURAN
// ===============================
$PPN_PERSEN     = 0;   // contoh: 11 untuk PPN 11%
$SERVICE_PERSEN = 0;   // contoh: 5 untuk service charge 5%

// Login & role sudah dijaga oleh index.php
$role       = $_SESSION['role'] ?? '';
$boleh_void = in_array($role, ['admin', 'owner'], true);
$user_id    = (int) ($_SESSION['user_id'] ?? 0);

// Token CSRF untuk semua form di halaman kasir
if (empty($_SESSION['csrf_kasir'])) {
    $_SESSION['csrf_kasir'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_kasir'];

$error = '';

$post_ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = (string) ($_POST['csrf_token'] ?? '');
    $post_ok = hash_equals($csrf, $token);

    if (!$post_ok) {
        $error = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    }
}

// Redirect menggunakan JavaScript karena layout sudah mengeluarkan HTML.
function redirectKasir($url) {
    echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
    exit;
}

// ===============================
// FUNGSI BANTU
// ===============================

function hitungTotal($subtotal, $diskon, $svc_persen, $ppn_persen) {
    $diskon  = min(max(0, $diskon), $subtotal);
    $dasar   = $subtotal - $diskon;
    $service = round($dasar * $svc_persen / 100);
    $pajak   = round(($dasar + $service) * $ppn_persen / 100);

    return [
        'subtotal' => (float) $subtotal,
        'diskon'   => (float) $diskon,
        'service'  => (float) $service,
        'pajak'    => (float) $pajak,
        'total'    => (float) ($dasar + $service + $pajak),
    ];
}

function buatInvoice($conn) {
    do {
        $invoice = 'INV-' . date('YmdHis') . '-' . random_int(100, 999);
        $stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE no_invoice = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $invoice);
        mysqli_stmt_execute($stmt);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } while ($ada);

    return $invoice;
}

function hitungPembayaran($conn, $order_id) {
    $stmt = mysqli_prepare($conn,
        "SELECT o.total_tagihan, o.status_order,
                COALESCE((SELECT SUM(nominal_bayar)
                          FROM payments WHERE order_id = o.id), 0) AS dibayar
         FROM orders o
         WHERE o.id = ?
         FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$row) {
        throw new Exception('Transaksi tidak ditemukan.');
    }
    if ($row['status_order'] === 'batal') {
        throw new Exception('Transaksi ini sudah dibatalkan.');
    }

    $total   = (float) $row['total_tagihan'];
    $dibayar = (float) $row['dibayar'];

    return [
        'total'   => $total,
        'dibayar' => $dibayar,
        'sisa'    => max(0, round($total - $dibayar, 2)),
    ];
}

function simpanPembayaran($conn, $order_id, $metode, $nominal, $uang, $kembalian) {
    $cek = mysqli_query($conn,
        "SELECT COALESCE(MAX(urutan_split), 0) + 1 AS urutan
         FROM payments WHERE order_id = " . (int) $order_id
    );
    $urutan = (int) mysqli_fetch_assoc($cek)['urutan'];

    $payment = mysqli_prepare($conn,
        "INSERT INTO payments
        (order_id, urutan_split, metode_bayar, nominal_bayar, uang_diterima, kembalian)
        VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($payment, "iisddd",
        $order_id, $urutan, $metode, $nominal, $uang, $kembalian);
    mysqli_stmt_execute($payment);

    $p = hitungPembayaran($conn, $order_id);
    $status = ($p['sisa'] <= 0) ? 'lunas' : 'sebagian';

    $upd = mysqli_prepare($conn, "UPDATE orders SET status_order = ?, tipe_pembayaran = 'split' WHERE id = ?");
    mysqli_stmt_bind_param($upd, "si", $status, $order_id);
    mysqli_stmt_execute($upd);

    if ($status === 'lunas') {
        $updItem = mysqli_prepare($conn, "UPDATE order_items SET status_bayar_item = 'lunas' WHERE order_id = ?");
        mysqli_stmt_bind_param($updItem, "i", $order_id);
        mysqli_stmt_execute($updItem);
    }

    return $status;
}

function urlGambar($nama_file) {
    if (!$nama_file) return '';
    $nama_file = basename($nama_file);
    if (!is_file(__DIR__ . '/../uploads/produk/' . $nama_file)) return '';
    return 'uploads/produk/' . rawurlencode($nama_file);
}

function warnaProduk($nama) {
    return 'hsl(' . (abs(crc32($nama)) % 360) . ' 55% 52%)';
}

function inisialProduk($nama) {
    return strtoupper(mb_substr(trim($nama), 0, 1)) ?: '?';
}

function ikonKategori($nama) {
    $n = strtolower($nama);
    if (strpos($n, 'non') !== false || strpos($n, 'teh') !== false) return 'cup-soda';
    if (strpos($n, 'kopi') !== false)                               return 'coffee';
    if (strpos($n, 'makan') !== false)                              return 'utensils';
    if (strpos($n, 'snack') !== false || strpos($n, 'pastry') !== false) return 'croissant';
    return 'tag';
}

$res = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'gambar'");
$ada_gambar = $res && mysqli_num_rows($res) > 0;
$res = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'deskripsi'");
$ada_deskripsi = $res && mysqli_num_rows($res) > 0;

// ===============================
// CHECKOUT TRANSAKSI
// ===============================
if ($post_ok && isset($_POST['checkout'])) {
    mysqli_begin_transaction($conn);
    try {
        $input = json_decode($_POST['keranjang_json'] ?? '[]', true);
        if (!is_array($input) || empty($input) || count($input) > 100) {
            throw new Exception('Keranjang masih kosong.');
        }

        $qty_map = [];
        foreach ($input as $row) {
            $pid = (int) ($row['id'] ?? 0);
            $q   = (int) ($row['qty'] ?? 0);
            if ($pid > 0 && $q > 0) {
                $qty_map[$pid] = ($qty_map[$pid] ?? 0) + $q;
            }
        }

        if (empty($qty_map)) throw new Exception('Keranjang masih kosong.');

        $keranjang = [];
        $subtotal  = 0;

        foreach ($qty_map as $pid => $q) {
            $stmt = mysqli_prepare($conn, "SELECT id, nama_produk, harga, stok FROM products WHERE id = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, "i", $pid);
            mysqli_stmt_execute($stmt);
            $pr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$pr) throw new Exception('Ada produk yang sudah tidak tersedia.');
            if ((int) $pr['stok'] < $q) throw new Exception('Stok produk ' . $pr['nama_produk'] . ' tidak mencukupi.');

            $harga = (float) $pr['harga'];
            $keranjang[] = [
                'id'    => (int) $pr['id'],
                'qty'   => $q,
                'harga' => $harga,
            ];
            $subtotal += $harga * $q;
        }

        $diskon_in = (float) ($_POST['diskon'] ?? 0);
        $hitung    = hitungTotal($subtotal, $diskon_in, $SERVICE_PERSEN, $PPN_PERSEN);
        $invoice = buatInvoice($conn);

        $pelanggan = trim($_POST['nama_pelanggan'] ?? '');
        $pelanggan = $pelanggan !== '' ? mb_substr($pelanggan, 0, 100) : 'Umum';
        $meja = trim($_POST['nomor_meja'] ?? '');
        $meja = $meja !== '' ? mb_substr($meja, 0, 10) : '-';

        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders
            (no_invoice, user_id, nama_pelanggan, nomor_meja,
             subtotal, diskon, service_charge, pajak,
             total_tagihan, tipe_pembayaran, status_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'full', 'pending')"
        );

        mysqli_stmt_bind_param(
            $stmt, "sissddddd",
            $invoice, $user_id, $pelanggan, $meja,
            $hitung['subtotal'], $hitung['diskon'],
            $hitung['service'], $hitung['pajak'], $hitung['total']
        );
        mysqli_stmt_execute($stmt);

        $new_order_id = mysqli_insert_id($conn);

        foreach ($keranjang as $item) {
            $sub_item = $item['harga'] * $item['qty'];
            $detail = mysqli_prepare($conn,
                "INSERT INTO order_items (order_id, product_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($detail, "iiidd", $new_order_id, $item['id'], $item['qty'], $item['harga'], $sub_item);
            mysqli_stmt_execute($detail);

            $update = mysqli_prepare($conn,
                "UPDATE products
                 SET status = IF(stok - ? <= 0, 'habis', status), stok = stok - ?
                 WHERE id = ?"
            );
            mysqli_stmt_bind_param($update, "iii", $item['qty'], $item['qty'], $item['id']);
            mysqli_stmt_execute($update);
        }

        mysqli_commit($conn);
        redirectKasir('index.php?page=kasir&order=' . $new_order_id . '&baru=1');

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = 'Checkout gagal: ' . $e->getMessage();
    }
}

// ===============================
// PEMBAYARAN NOMINAL (CICILAN)
// ===============================
if ($post_ok && isset($_POST['bayar_nominal'])) {
    $order_id_post = (int) $_POST['order_id'];
    $uang   = (float) ($_POST['uang_diterima'] ?? 0);
    $metode = $_POST['metode_bayar'] ?? 'tunai';

    if (!in_array($metode, ['tunai', 'qris', 'debit'], true)) $metode = 'tunai';

    mysqli_begin_transaction($conn);
    try {
        if ($uang <= 0) throw new Exception('Nominal pembayaran harus lebih dari 0.');
        $p = hitungPembayaran($conn, $order_id_post);
        if ($p['sisa'] <= 0) throw new Exception('Transaksi ini sudah lunas.');
        if ($metode !== 'tunai' && $uang > $p['sisa']) {
            throw new Exception('Pembayaran QRIS/debit tidak boleh melebihi sisa tagihan.');
        }

        $nominal   = min($uang, $p['sisa']);
        $kembalian = $uang - $nominal;

        simpanPembayaran($conn, $order_id_post, $metode, $nominal, $uang, $kembalian);
        mysqli_commit($conn);
        redirectKasir('index.php?page=kasir&order=' . $order_id_post . '&sukses=bayar');
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// ===============================
// PEMBAYARAN SPLIT BILL
// ===============================
if ($post_ok && isset($_POST['bayar_split'])) {
    $order_id_post = (int) $_POST['order_id'];
    $item_ids = array_map('intval', $_POST['item_ids'] ?? []);
    $metode = $_POST['metode_bayar'] ?? 'tunai';
    $uang   = (float) ($_POST['uang_diterima'] ?? 0);

    if (!in_array($metode, ['tunai', 'qris', 'debit'], true)) $metode = 'tunai';

    if (empty($item_ids)) {
        $error = 'Pilih minimal satu item yang ingin dibayar.';
    } else {
        mysqli_begin_transaction($conn);
        try {
            $p = hitungPembayaran($conn, $order_id_post);
            if ($p['sisa'] <= 0) throw new Exception('Transaksi ini sudah lunas.');

            $stmt = mysqli_prepare($conn, "SELECT id, subtotal, status_bayar_item FROM order_items WHERE order_id = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, "i", $order_id_post);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            $semua_subtotal = 0; $belum_ids = []; $pilih_subtotal = 0; $valid_ids = [];
            while ($it = mysqli_fetch_assoc($res)) {
                $semua_subtotal += (float) $it['subtotal'];
                if ($it['status_bayar_item'] === 'belum') {
                    $belum_ids[] = (int) $it['id'];
                    if (in_array((int) $it['id'], $item_ids, true)) {
                        $pilih_subtotal += (float) $it['subtotal'];
                        $valid_ids[]     = (int) $it['id'];
                    }
                }
            }

            if (empty($valid_ids)) throw new Exception('Item tidak tersedia atau sudah lunas.');

            $faktor  = $semua_subtotal > 0 ? $p['total'] / $semua_subtotal : 1;
            $nominal = round($pilih_subtotal * $faktor);
            if (count($valid_ids) === count($belum_ids)) $nominal = $p['sisa'];
            $nominal = min($nominal, $p['sisa']);

            if ($metode !== 'tunai' && abs($uang - $nominal) > 0.001) {
                throw new Exception('Pembayaran QRIS/debit harus pas: ' . number_format($nominal, 0, ',', '.') . '.');
            }
            if ($uang < $nominal) throw new Exception('Uang yang diterima kurang dari total pembayaran.');

            $kembalian = $uang - $nominal;
            simpanPembayaran($conn, $order_id_post, $metode, $nominal, $uang, $kembalian);

            foreach ($valid_ids as $item_id) {
                $update = mysqli_prepare($conn, "UPDATE order_items SET status_bayar_item = 'lunas' WHERE id = ? AND order_id = ?");
                mysqli_stmt_bind_param($update, "ii", $item_id, $order_id_post);
                mysqli_stmt_execute($update);
            }
            mysqli_commit($conn);
            redirectKasir('index.php?page=kasir&order=' . $order_id_post . '&sukses=bayar');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// ===============================
// BATALKAN ORDER (VOID)
// ===============================
if ($post_ok && isset($_POST['void_order'])) {
    $order_id_post = (int) $_POST['order_id'];
    mysqli_begin_transaction($conn);
    try {
        if (!$boleh_void) throw new Exception('Anda tidak memiliki izin untuk membatalkan order.');

        $stmt = mysqli_prepare($conn, "SELECT id, status_order FROM orders WHERE id = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, "i", $order_id_post);
        mysqli_stmt_execute($stmt);
        $ord = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$ord) throw new Exception('Transaksi tidak ditemukan.');
        if ($ord['status_order'] === 'batal') throw new Exception('Transaksi sudah dibatalkan sebelumnya.');

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jml FROM payments WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $order_id_post);
        mysqli_stmt_execute($stmt);
        if ((int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['jml'] > 0) {
            throw new Exception('Order sudah ada pembayaran, tidak bisa dibatalkan. Lakukan refund secara manual.');
        }

        $stmt = mysqli_prepare($conn, "SELECT product_id, qty FROM order_items WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $order_id_post);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        while ($it = mysqli_fetch_assoc($res)) {
            $upd = mysqli_prepare($conn, "UPDATE products SET stok = stok + ?, status = 'tersedia' WHERE id = ?");
            $q   = (int) $it['qty'];
            $pid = (int) $it['product_id'];
            mysqli_stmt_bind_param($upd, "ii", $q, $pid);
            mysqli_stmt_execute($upd);
        }

        $upd = mysqli_prepare($conn, "UPDATE orders SET status_order = 'batal' WHERE id = ?");
        mysqli_stmt_bind_param($upd, "i", $order_id_post);
        mysqli_stmt_execute($upd);

        mysqli_commit($conn);
        redirectKasir('index.php?page=kasir&order=' . $order_id_post . '&sukses=void');
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// ===============================
// DATA KATEGORI & PRODUK
// ===============================
$kategori_list = [];
$res = mysqli_query($conn,
    "SELECT c.id, c.nama_kategori, COUNT(p.id) AS jml
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.stok > 0
     GROUP BY c.id, c.nama_kategori
     ORDER BY c.nama_kategori ASC"
);
while ($r = mysqli_fetch_assoc($res)) {
    $kategori_list[] = $r;
}

$produk_list = [];
$produk_js   = [];
$res = mysqli_query($conn,
    "SELECT p.*, c.nama_kategori
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.stok > 0
     ORDER BY p.nama_produk ASC"
);
while ($r = mysqli_fetch_assoc($res)) {
    $r['img']    = urlGambar($r['gambar'] ?? '');
    $r['warna']  = warnaProduk($r['nama_produk']);
    $r['inisial'] = inisialProduk($r['nama_produk']);

    $produk_list[] = $r;

    $produk_js[(int) $r['id']] = [
        'id'      => (int) $r['id'],
        'nama'    => $r['nama_produk'],
        'harga'   => (float) $r['harga'],
        'stok'    => (int) $r['stok'],
        'img'     => $r['img'],
        'warna'   => $r['warna'],
        'inisial' => $r['inisial'],
    ];
}

// ===============================
// DAFTAR ORDER BELUM LUNAS
// ===============================
$order_belum_lunas = [];
$res = mysqli_query($conn,
    "SELECT o.id, o.no_invoice, o.nama_pelanggan, o.nomor_meja,
            o.total_tagihan, o.status_order,
            COALESCE((SELECT SUM(nominal_bayar)
                      FROM payments WHERE order_id = o.id), 0) AS dibayar
     FROM orders o
     WHERE o.status_order IN ('pending', 'sebagian')
     ORDER BY o.id DESC
     LIMIT 20"
);
while ($r = mysqli_fetch_assoc($res)) {
    $order_belum_lunas[] = $r;
}

// ===============================
// LAPORAN HARIAN
// ===============================
$laporan = [];
$laporan_total = 0;
$res = mysqli_query($conn,
    "SELECT metode_bayar, COUNT(*) AS jumlah, COALESCE(SUM(nominal_bayar), 0) AS total
     FROM payments
     WHERE DATE(created_at) = CURDATE()
     GROUP BY metode_bayar"
);
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $laporan[] = $r;
        $laporan_total += (float) $r['total'];
    }
}
$res = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM orders WHERE DATE(created_at) = CURDATE() AND status_order <> 'batal'");
$order_hari_ini = $res ? (int) mysqli_fetch_assoc($res)['jml'] : 0;

// ===============================
// DETAIL ORDER YANG SEDANG DIBUKA
// ===============================
$order_id = (int) ($_GET['order'] ?? $_POST['order_id'] ?? 0);
$order = null;
$items = [];
$riwayat = [];
$total_lunas = 0;
$total_belum = 0;
$faktor_split = 1;

if ($order_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($order) {
        $sel_gambar = $ada_gambar ? ', p.gambar' : ', NULL AS gambar';
        $stmt = mysqli_prepare($conn,
            "SELECT oi.*, p.nama_produk $sel_gambar
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC"
        );
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $semua_subtotal = 0;

        while ($item = mysqli_fetch_assoc($result)) {
            $item['img']     = urlGambar($item['gambar'] ?? '');
            $item['warna']   = warnaProduk($item['nama_produk']);
            $item['inisial'] = inisialProduk($item['nama_produk']);
            $items[] = $item;
            $semua_subtotal += (float) $item['subtotal'];
        }

        $faktor_split = $semua_subtotal > 0 ? (float) $order['total_tagihan'] / $semua_subtotal : 1;

        $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(nominal_bayar), 0) AS dibayar FROM payments WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $total_lunas = (float) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['dibayar'];
        $total_belum = max(0, round((float) $order['total_tagihan'] - $total_lunas, 2));

        $stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE order_id = ? ORDER BY urutan_split ASC");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) {
            $riwayat[] = $r;
        }
    }
}

$order_batal = $order && $order['status_order'] === 'batal';
$kembalian_terakhir = 0;
if ($order && !empty($riwayat)) {
    $kembalian_terakhir = (float) end($riwayat)['kembalian'];
}

$badge_status = [
    'pending'  => 'bg-amber-50 text-amber-700',
    'sebagian' => 'bg-blue-50 text-blue-600',
    'lunas'    => 'bg-emerald-50 text-emerald-700',
    'batal'    => 'bg-red-50 text-red-700',
];

$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>

<style>
    #struk { display: none; }

    @media print {
        html, body, .h-screen { height: auto !important; overflow: visible !important; }
        body * { visibility: hidden; }
        #struk, #struk * { visibility: visible; }
        #struk {
            display: block;
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
            padding: 4mm;
            font-family: monospace;
            font-size: 12px;
            color: #000;
        }
        #struk .garis { border-top: 1px dashed #000; margin: 4px 0; }
        #struk .baris { display: flex; justify-content: space-between; }
    }

    .produk-card.dalam-keranjang { box-shadow: 0 0 0 2px #3b6ef5; }
</style>

<div class="h-full flex flex-col xl:flex-row overflow-y-auto xl:overflow-hidden">

    <!-- ========================================================= -->
    <!-- KOLOM KIRI : PENCARIAN, KATEGORI, MENU                    -->
    <!-- ========================================================= -->
    <div class="flex-1 min-w-0 xl:overflow-y-auto scroll-slim p-6 space-y-6 no-print">

        <!-- Pencarian -->
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
            <input type="text" id="cariProduk" autocomplete="off"
                   placeholder="Cari menu atau kode produk..."
                   class="w-full bg-white rounded-2xl pl-11 pr-4 py-3.5 text-sm shadow-sm border border-transparent focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
        </div>

        <!-- Notifikasi -->
        <?php if ($error): ?>
            <div class="flex items-start gap-2 p-3.5 rounded-xl bg-red-50 text-red-700 text-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (($_GET['sukses'] ?? '') === 'void'): ?>
            <div class="p-3.5 rounded-xl bg-amber-50 text-amber-800 text-sm">
                Order berhasil dibatalkan dan stok produk dikembalikan.
            </div>
        <?php elseif (($_GET['sukses'] ?? '') === 'bayar'): ?>
            <div class="p-3.5 rounded-xl bg-emerald-50 text-emerald-700 text-sm">
                Pembayaran berhasil dicatat.
                <?php if ($kembalian_terakhir > 0): ?>
                    <strong class="block text-xl mt-1">Kembalian: <?= formatRupiah($kembalian_terakhir) ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Kategori -->
        <div class="grid grid-cols-2 md:grid-cols-3 2xl:grid-cols-5 gap-3" id="listKategori">
            <button type="button" data-kat="all"
                    class="tile-kat flex items-center gap-3 rounded-2xl px-4 py-3 text-left transition bg-white shadow-sm">
                <span class="tile-ikon w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 text-slate-600 shrink-0">
                    <i data-lucide="layout-grid" class="w-[18px] h-[18px]"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-bold truncate">Semua Menu</span>
                    <span class="tile-sub block text-[11px] text-slate-400"><?= count($produk_list) ?> menu tersedia</span>
                </span>
            </button>

            <?php foreach ($kategori_list as $k): ?>
                <button type="button" data-kat="<?= (int) $k['id'] ?>"
                        data-nama="<?= htmlspecialchars($k['nama_kategori']) ?>"
                        class="tile-kat flex items-center gap-3 rounded-2xl px-4 py-3 text-left transition bg-white shadow-sm">
                    <span class="tile-ikon w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 text-slate-600 shrink-0">
                        <i data-lucide="<?= ikonKategori($k['nama_kategori']) ?>" class="w-[18px] h-[18px]"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold truncate"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                        <span class="tile-sub block text-[11px] text-slate-400"><?= (int) $k['jml'] ?> menu tersedia</span>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Daftar menu -->
        <section>
            <h3 class="text-lg font-extrabold mb-4" id="judulMenu">Semua Menu</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4" id="listProduk">
                <?php foreach ($produk_list as $pr): ?>
                    <?php
                    $deskripsi = trim((string) ($pr['deskripsi'] ?? ''));
                    if ($deskripsi === '') $deskripsi = $pr['nama_kategori'] . ' · ' . $pr['kode_produk'];
                    ?>
                    <div class="produk-card bg-white rounded-2xl p-3 flex gap-3 shadow-sm transition"
                         data-id="<?= (int) $pr['id'] ?>" data-kat="<?= (int) $pr['category_id'] ?>"
                         data-cari="<?= htmlspecialchars(mb_strtolower($pr['nama_produk'] . ' ' . $pr['kode_produk'] . ' ' . $pr['nama_kategori'])) ?>">

                        <!-- Gambar -->
                        <div class="w-[88px] h-[88px] rounded-xl overflow-hidden shrink-0">
                            <?php if ($pr['img']): ?>
                                <img src="<?= htmlspecialchars($pr['img']) ?>" alt="<?= htmlspecialchars($pr['nama_produk']) ?>" loading="lazy" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-3xl font-extrabold text-white" style="background: <?= $pr['warna'] ?>"><?= htmlspecialchars($pr['inisial']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0 flex flex-col">
                            <h4 class="font-bold text-sm leading-snug truncate"><?= htmlspecialchars($pr['nama_produk']) ?></h4>
                            <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5"><?= htmlspecialchars($deskripsi) ?></p>

                            <div class="mt-auto pt-2 flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-extrabold text-[15px] leading-none"><?= formatRupiah($pr['harga']) ?></p>
                                    <p class="text-[10px] text-slate-400 mt-1">Stok <?= (int) $pr['stok'] ?></p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" data-act="dec" data-id="<?= (int) $pr['id'] ?>" class="btn-dec w-7 h-7 rounded-full border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed"><i data-lucide="minus" class="w-3.5 h-3.5"></i></button>
                                    <span class="qty-val w-5 text-center text-sm font-bold">0</span>
                                    <button type="button" data-act="inc" data-id="<?= (int) $pr['id'] ?>" class="btn-inc w-7 h-7 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 disabled:opacity-40 disabled:cursor-not-allowed"><i data-lucide="plus" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p id="produkKosong" class="<?= empty($produk_list) ? '' : 'hidden' ?> text-sm text-slate-400 py-10 text-center">Menu tidak ditemukan.</p>
        </section>

        <!-- Laporan harian -->
        <details class="bg-white rounded-2xl shadow-sm p-5">
            <summary class="font-bold cursor-pointer text-sm">Ringkasan Hari Ini (<?= date('d/m/Y') ?>)</summary>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Jumlah order</span><strong><?= $order_hari_ini ?></strong>
                </div>
                <?php if (empty($laporan)): ?>
                    <p class="text-slate-400">Belum ada pembayaran hari ini.</p>
                <?php else: ?>
                    <?php foreach ($laporan as $l): ?>
                        <div class="flex justify-between border-b border-slate-100 py-1.5">
                            <span><?= strtoupper(htmlspecialchars($l['metode_bayar'])) ?> <span class="text-xs text-slate-400">(<?= (int) $l['jumlah'] ?> transaksi)</span></span>
                            <span><?= formatRupiah($l['total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="flex justify-between font-extrabold text-base pt-2">
                        <span>Total Pemasukan</span><span><?= formatRupiah($laporan_total) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    </div>

    <!-- ========================================================= -->
    <!-- KOLOM KANAN : INVOICE                                     -->
    <!-- ========================================================= -->
    <aside class="xl:w-[410px] shrink-0 xl:overflow-y-auto scroll-slim p-6 xl:pl-0 no-print">
        <div class="bg-white rounded-3xl shadow-sm p-5">

        <?php if (!$order): ?>
            <!-- ================== MODE: PESANAN BARU ================== -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-extrabold">Invoice</h3>
                <span class="text-xs text-slate-400"><?= date('d M Y') ?></span>
            </div>

            <div class="grid grid-cols-2 gap-1 bg-slate-100 rounded-xl p-1 mb-5 text-xs font-bold">
                <button type="button" data-ptab="keranjang" class="ptab rounded-lg py-2 transition">
                    Keranjang <span id="badgeCart" class="ml-1 px-1.5 rounded-full bg-blue-500 text-white">0</span>
                </button>
                <button type="button" data-ptab="belum" class="ptab rounded-lg py-2 transition">
                    Belum Lunas <span class="ml-1 px-1.5 rounded-full bg-slate-300 text-slate-700"><?= count($order_belum_lunas) ?></span>
                </button>
            </div>

            <!-- Tab: keranjang -->
            <div data-ppane="keranjang">
                <div id="cartList" class="space-y-4 mb-5"></div>
                <p id="cartKosong" class="text-center text-sm text-slate-400 py-8">
                    <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                    Pilih menu di sebelah kiri untuk memulai pesanan.
                </p>

                <form method="POST" id="formCheckout" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="keranjang_json" id="keranjangJson" value="[]">

                    <div class="bg-slate-50 rounded-2xl p-4 space-y-2 text-sm">
                        <p class="font-bold mb-1">Ringkasan Pembayaran</p>
                        <div class="flex justify-between text-slate-500">
                            <span>Sub Total</span><span id="outSubtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500 gap-2">
                            <span>Diskon (Rp)</span>
                            <input type="number" name="diskon" id="inpDiskon" min="0" step="1" value="0" class="w-28 bg-white border border-slate-200 rounded-lg px-2 py-1 text-right text-slate-800 text-sm">
                        </div>
                        <?php if ($SERVICE_PERSEN > 0): ?>
                            <div class="flex justify-between text-slate-500">
                                <span>Service (<?= $SERVICE_PERSEN ?>%)</span><span id="outService">Rp 0</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($PPN_PERSEN > 0): ?>
                            <div class="flex justify-between text-slate-500">
                                <span>PPN (<?= $PPN_PERSEN ?>%)</span><span id="outPajak">Rp 0</span>
                            </div>
                        <?php endif; ?>
                        <div class="border-t border-dashed border-slate-300 pt-3 mt-1 flex justify-between items-center">
                            <span class="font-bold">Total Tagihan</span>
                            <span class="text-lg font-extrabold" id="outTotal">Rp 0</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <input name="nama_pelanggan" maxlength="100" placeholder="Nama pelanggan" class="bg-slate-50 rounded-xl px-3 py-2.5 text-sm border border-transparent focus:outline-none focus:border-blue-500">
                        <input name="nomor_meja" maxlength="10" placeholder="No. meja" class="bg-slate-50 rounded-xl px-3 py-2.5 text-sm border border-transparent focus:outline-none focus:border-blue-500">
                    </div>

                    <button name="checkout" id="btnCheckout" class="w-full bg-blue-500 hover:bg-blue-600 text-white rounded-xl py-3.5 font-bold transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Buat Pesanan
                    </button>
                    <button type="button" id="btnKosongkan" class="w-full text-xs text-red-500 hover:text-red-700 py-1">
                        Kosongkan keranjang
                    </button>
                </form>
            </div>

            <!-- Tab: order belum lunas -->
            <div data-ppane="belum" class="hidden space-y-3">
                <?php if (empty($order_belum_lunas)): ?>
                    <p class="text-center text-sm text-slate-400 py-8">Tidak ada order yang belum lunas.</p>
                <?php endif; ?>
                <?php foreach ($order_belum_lunas as $o): ?>
                    <?php $sisa_o = max(0, $o['total_tagihan'] - $o['dibayar']); ?>
                    <a href="index.php?page=kasir&order=<?= (int) $o['id'] ?>" class="block rounded-2xl border border-slate-200 hover:border-blue-500 p-4 transition">
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-sm truncate"><?= htmlspecialchars($o['no_invoice']) ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($o['nama_pelanggan']) ?> · Meja <?= htmlspecialchars($o['nomor_meja']) ?></p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full <?= $badge_status[$o['status_order']] ?? '' ?>">
                                <?= strtoupper(htmlspecialchars($o['status_order'])) ?>
                            </span>
                        </div>
                        <div class="flex justify-between mt-3 text-sm">
                            <span class="text-slate-400">Sisa tagihan</span>
                            <strong class="text-amber-600"><?= formatRupiah($sisa_o) ?></strong>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ================== MODE: DETAIL ORDER ================== -->
            <a href="index.php?page=kasir" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-500 hover:text-blue-700 mb-3">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Pesanan baru
            </a>

            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h3 class="text-lg font-extrabold truncate"><?= htmlspecialchars($order['no_invoice']) ?></h3>
                    <p class="text-xs text-slate-400"><?= htmlspecialchars($order['nama_pelanggan']) ?> · Meja <?= htmlspecialchars($order['nomor_meja']) ?></p>
                </div>
                <span class="text-[11px] font-bold px-3 py-1 rounded-full <?= $badge_status[$order['status_order']] ?? 'bg-slate-100 text-slate-600' ?>">
                    <?= strtoupper(htmlspecialchars($order['status_order'])) ?>
                </span>
            </div>

            <!-- Item pesanan -->
            <div class="space-y-3 mb-5">
                <?php foreach ($items as $it): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0">
                            <?php if ($it['img']): ?>
                                <img src="<?= htmlspecialchars($it['img']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center font-extrabold text-white" style="background: <?= $it['warna'] ?>"><?= htmlspecialchars($it['inisial']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate"><?= htmlspecialchars($it['nama_produk']) ?></p>
                            <p class="text-xs text-slate-400"><?= (int) $it['qty'] ?> × <?= formatRupiah($it['harga_satuan']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold"><?= formatRupiah($it['subtotal']) ?></p>
                            <?php if ($it['status_bayar_item'] === 'lunas'): ?>
                                <span class="text-[10px] font-bold text-emerald-600">LUNAS</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-amber-600">BELUM</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Ringkasan -->
            <div class="bg-slate-50 rounded-2xl p-4 space-y-2 text-sm mb-4">
                <?php if ((float) $order['diskon'] > 0 || (float) $order['service_charge'] > 0 || (float) $order['pajak'] > 0): ?>
                    <div class="flex justify-between text-slate-500">
                        <span>Sub Total</span><span><?= formatRupiah($order['subtotal']) ?></span>
                    </div>
                    <?php if ((float) $order['diskon'] > 0): ?>
                        <div class="flex justify-between text-slate-500">
                            <span>Diskon</span><span>- <?= formatRupiah($order['diskon']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ((float) $order['service_charge'] > 0): ?>
                        <div class="flex justify-between text-slate-500">
                            <span>Service</span><span><?= formatRupiah($order['service_charge']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ((float) $order['pajak'] > 0): ?>
                        <div class="flex justify-between text-slate-500">
                            <span>PPN</span><span><?= formatRupiah($order['pajak']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="border-t border-dashed border-slate-300 my-1"></div>
                <?php endif; ?>
                <div class="flex justify-between"><span class="font-bold">Total Tagihan</span><strong><?= formatRupiah($order['total_tagihan']) ?></strong></div>
                <div class="flex justify-between text-emerald-700"><span>Sudah Dibayar</span><strong><?= formatRupiah($total_lunas) ?></strong></div>
                <div class="flex justify-between text-amber-700"><span>Sisa Tagihan</span><strong class="text-base"><?= formatRupiah($order_batal ? 0 : $total_belum) ?></strong></div>
            </div>

            <!-- Riwayat pembayaran -->
            <?php if ($riwayat): ?>
                <div class="mb-4">
                    <p class="text-sm font-bold mb-2">Riwayat Pembayaran</p>
                    <ul class="text-xs space-y-1.5">
                        <?php foreach ($riwayat as $r): ?>
                            <li class="flex justify-between border-b border-slate-100 pb-1.5">
                                <span class="font-semibold">#<?= (int) $r['urutan_split'] ?> · <?= strtoupper(htmlspecialchars($r['metode_bayar'])) ?></span>
                                <span class="text-right">
                                    <?= formatRupiah($r['nominal_bayar']) ?>
                                    <span class="block text-[10px] text-slate-400">Diterima <?= formatRupiah($r['uang_diterima']) ?> · Kembali <?= formatRupiah($r['kembalian']) ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Area pembayaran -->
            <?php if ($order_batal): ?>
                <div class="bg-red-50 text-red-700 text-sm p-4 rounded-xl">Transaksi ini sudah dibatalkan.</div>
            <?php elseif ($total_belum > 0): ?>
                <div class="grid grid-cols-2 gap-1 bg-slate-100 rounded-xl p-1 mb-4 text-xs font-bold">
                    <button type="button" data-paytab="nominal" class="paytab rounded-lg py-2 transition">Bayar Nominal</button>
                    <button type="button" data-paytab="split" class="paytab rounded-lg py-2 transition">Bayar Per Item</button>
                </div>

                <!-- Bayar nominal / cicilan -->
                <form method="POST" data-paypane="nominal" class="form-bayar space-y-3" data-mode="nominal" data-sisa="<?= (float) $total_belum ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <p class="text-xs text-slate-400">Masukkan nominal berapa pun. Kurang dari sisa = status SEBAGIAN. QRIS/debit tidak boleh melebihi sisa tagihan.</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="quick-pas border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">Uang Pas</button>
                        <button type="button" class="quick-uang border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50" data-nilai="20000">20.000</button>
                        <button type="button" class="quick-uang border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50" data-nilai="50000">50.000</button>
                        <button type="button" class="quick-uang border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50" data-nilai="100000">100.000</button>
                    </div>
                    <select name="metode_bayar" class="inp-metode w-full bg-slate-50 rounded-xl px-3 py-2.5 text-sm">
                        <option value="tunai">Tunai</option><option value="qris">QRIS</option><option value="debit">Debit</option>
                    </select>
                    <input type="number" name="uang_diterima" min="1" step="1" required placeholder="Nominal dibayar (sisa <?= (int) $total_belum ?>)" class="inp-uang w-full bg-slate-50 rounded-xl px-3 py-2.5 text-sm">
                    <p class="out-kembalian text-sm font-bold"></p>
                    <button name="bayar_nominal" class="w-full bg-blue-500 hover:bg-blue-600 text-white rounded-xl py-3 font-bold">Bayar Nominal</button>
                </form>

                <!-- Bayar per item / split bill -->
                <form method="POST" data-paypane="split" class="form-bayar space-y-3 hidden" data-mode="split" data-sisa="<?= (float) $total_belum ?>" data-faktor="<?= (float) $faktor_split ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <p class="text-xs text-slate-400">Centang item yang dibayar sekarang. Sisanya tetap menjadi tagihan.</p>
                    <div class="space-y-1.5">
                        <?php foreach ($items as $it): ?>
                            <?php if ($it['status_bayar_item'] !== 'belum') continue; ?>
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 cursor-pointer hover:border-blue-500">
                                <input type="checkbox" name="item_ids[]" class="chk-item accent-[#3b6ef5] w-4 h-4" data-subtotal="<?= (float) $it['subtotal'] ?>" value="<?= (int) $it['id'] ?>">
                                <span class="flex-1 min-w-0 text-sm font-semibold truncate"><?= htmlspecialchars($it['nama_produk']) ?> <span class="text-xs text-slate-400 font-normal">× <?= (int) $it['qty'] ?></span></span>
                                <span class="text-sm font-bold"><?= formatRupiah($it['subtotal']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-sm">Total item dipilih: <strong class="out-nominal-split">Rp 0</strong></p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="quick-pas border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">Uang Pas</button>
                        <button type="button" class="quick-uang border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50" data-nilai="50000">50.000</button>
                        <button type="button" class="quick-uang border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-slate-50" data-nilai="100000">100.000</button>
                    </div>
                    <select name="metode_bayar" class="inp-metode w-full bg-slate-50 rounded-xl px-3 py-2.5 text-sm">
                        <option value="tunai">Tunai</option><option value="qris">QRIS</option><option value="debit">Debit</option>
                    </select>
                    <input type="number" name="uang_diterima" min="0" step="1" required placeholder="Uang diterima" class="inp-uang w-full bg-slate-50 rounded-xl px-3 py-2.5 text-sm">
                    <p class="out-kembalian text-sm font-bold"></p>
                    <button name="bayar_split" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl py-3 font-bold">Proses Pembayaran</button>
                </form>

            <?php else: ?>
                <div class="bg-emerald-50 text-emerald-700 text-sm p-4 rounded-xl text-center font-semibold">Transaksi sudah lunas. Terima kasih!</div>
            <?php endif; ?>

            <!-- Aksi tambahan -->
            <div class="flex gap-2 mt-5 pt-4 border-t border-slate-100">
                <?php if (!$order_batal): ?>
                    <button type="button" onclick="window.print()" class="flex-1 inline-flex items-center justify-center gap-2 border border-slate-200 rounded-xl py-2.5 text-sm font-semibold hover:bg-slate-50">
                        <i data-lucide="printer" class="w-4 h-4"></i> Cetak Struk
                    </button>
                <?php endif; ?>
                <?php if (!$order_batal && $boleh_void && empty($riwayat)): ?>
                    <form method="POST" class="flex-1" onsubmit="return confirm('Batalkan order ini? Stok produk akan dikembalikan.');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <button name="void_order" class="w-full border border-red-200 text-red-600 hover:bg-red-50 rounded-xl py-2.5 text-sm font-semibold">Batalkan Order</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </aside>

    <!-- STRUK (hanya tampil saat dicetak) -->
    <?php if ($order && !$order_batal): ?>
        <div id="struk">
            <div style="text-align:center">
                <strong>STRUK PEMBAYARAN</strong><br>
                <?= htmlspecialchars($order['no_invoice']) ?><br>
                <?= htmlspecialchars($order['created_at'] ?? date('Y-m-d H:i:s')) ?><br>
                Meja <?= htmlspecialchars($order['nomor_meja']) ?> | <?= htmlspecialchars($order['nama_pelanggan']) ?>
            </div>
            <div class="garis"></div>
            <?php foreach ($items as $it): ?>
                <div><?= htmlspecialchars($it['nama_produk']) ?></div>
                <div class="baris">
                    <span><?= (int) $it['qty'] ?> x <?= formatRupiah($it['harga_satuan']) ?></span>
                    <span><?= formatRupiah($it['subtotal']) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="garis"></div>
            <?php if ((float) $order['diskon'] > 0 || (float) $order['service_charge'] > 0 || (float) $order['pajak'] > 0): ?>
                <div class="baris"><span>Subtotal</span><span><?= formatRupiah($order['subtotal']) ?></span></div>
                <?php if ((float) $order['diskon'] > 0): ?><div class="baris"><span>Diskon</span><span>-<?= formatRupiah($order['diskon']) ?></span></div><?php endif; ?>
                <?php if ((float) $order['service_charge'] > 0): ?><div class="baris"><span>Service</span><span><?= formatRupiah($order['service_charge']) ?></span></div><?php endif; ?>
                <?php if ((float) $order['pajak'] > 0): ?><div class="baris"><span>PPN</span><span><?= formatRupiah($order['pajak']) ?></span></div><?php endif; ?>
            <?php endif; ?>
            <div class="baris"><strong>TOTAL</strong><strong><?= formatRupiah($order['total_tagihan']) ?></strong></div>
            <div class="garis"></div>
            <?php foreach ($riwayat as $r): ?>
                <div class="baris">
                    <span>Bayar <?= strtoupper(htmlspecialchars($r['metode_bayar'])) ?></span>
                    <span><?= formatRupiah($r['uang_diterima']) ?></span>
                </div>
                <?php if ((float) $r['kembalian'] > 0): ?>
                    <div class="baris"><span>Kembalian</span><span><?= formatRupiah($r['kembalian']) ?></span></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="baris"><span>Sudah Dibayar</span><span><?= formatRupiah($total_lunas) ?></span></div>
            <div class="baris"><span>Sisa</span><span><?= formatRupiah($total_belum) ?></span></div>
            <div class="garis"></div>
            <div style="text-align:center">Terima kasih!</div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    'use strict';

    const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const ikon = () => { if (window.lucide) lucide.createIcons(); };

    const PRODUK = <?= json_encode($produk_js, $js_flags) ?>;
    const SVC = <?= (float) $SERVICE_PERSEN ?>;
    const PPN = <?= (float) $PPN_PERSEN ?>;
    const KEY = 'flashpos_cart_v1';
    const ADA_ORDER = <?= $order ? 'true' : 'false' ?>;
    const BARU = <?= isset($_GET['baru']) ? 'true' : 'false' ?>;

    /* TAB PANEL */
    function pilihTab(nama) {
        document.querySelectorAll('[data-ppane]').forEach(el => el.classList.toggle('hidden', el.dataset.ppane !== nama));
        document.querySelectorAll('.ptab').forEach(el => {
            const aktif = el.dataset.ptab === nama;
            el.classList.toggle('bg-white', aktif); el.classList.toggle('shadow-sm', aktif);
            el.classList.toggle('text-slate-900', aktif); el.classList.toggle('text-slate-500', !aktif);
        });
    }
    document.querySelectorAll('.ptab').forEach(el => el.addEventListener('click', () => pilihTab(el.dataset.ptab)));
    if (document.querySelector('.ptab')) pilihTab('keranjang');

    /* TAB PEMBAYARAN */
    function pilihPayTab(nama) {
        document.querySelectorAll('[data-paypane]').forEach(el => el.classList.toggle('hidden', el.dataset.paypane !== nama));
        document.querySelectorAll('.paytab').forEach(el => {
            const aktif = el.dataset.paytab === nama;
            el.classList.toggle('bg-white', aktif); el.classList.toggle('shadow-sm', aktif);
            el.classList.toggle('text-slate-900', aktif); el.classList.toggle('text-slate-500', !aktif);
        });
    }
    document.querySelectorAll('.paytab').forEach(el => el.addEventListener('click', () => pilihPayTab(el.dataset.paytab)));
    if (document.querySelector('.paytab')) pilihPayTab('nominal');

    /* FILTER KATEGORI + PENCARIAN */
    let katAktif = 'all';
    function terapkanFilter() {
        const q = (document.getElementById('cariProduk').value || '').trim().toLowerCase();
        let tampil = 0;
        document.querySelectorAll('.produk-card').forEach(el => {
            const cocokKat = katAktif === 'all' || el.dataset.kat === katAktif;
            const cocokCari = el.dataset.cari.includes(q);
            const ok = cocokKat && cocokCari;
            el.style.display = ok ? '' : 'none';
            if (ok) tampil++;
        });
        document.getElementById('produkKosong').classList.toggle('hidden', tampil > 0);
    }
    function pilihKategori(id, nama) {
        katAktif = id;
        document.getElementById('judulMenu').textContent = id === 'all' ? 'Semua Menu' : nama;
        document.querySelectorAll('.tile-kat').forEach(el => {
            const aktif = el.dataset.kat === id;
            el.classList.toggle('bg-blue-500', aktif); el.classList.toggle('text-white', aktif);
            el.classList.toggle('bg-white', !aktif);
            const ik = el.querySelector('.tile-ikon');
            ik.classList.toggle('bg-white/20', aktif); ik.classList.toggle('text-white', aktif);
            ik.classList.toggle('bg-slate-100', !aktif); ik.classList.toggle('text-slate-600', !aktif);
            const sub = el.querySelector('.tile-sub');
            sub.classList.toggle('text-white/80', aktif); sub.classList.toggle('text-slate-400', !aktif);
        });
        terapkanFilter();
    }
    document.querySelectorAll('.tile-kat').forEach(el => el.addEventListener('click', () => pilihKategori(el.dataset.kat, el.dataset.nama || '')));
    if(document.getElementById('cariProduk')) document.getElementById('cariProduk').addEventListener('input', terapkanFilter);
    pilihKategori('all', '');

    /* KERANJANG */
    let cart = {};
    function muatCart() {
        try {
            const raw = JSON.parse(localStorage.getItem(KEY) || '{}');
            Object.keys(raw).forEach(id => {
                const p = PRODUK[id];
                const q = parseInt(raw[id], 10);
                if (p && q > 0) cart[id] = Math.min(q, p.stok);
            });
        } catch (e) { cart = {}; }
    }
    function simpanCart() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }
    function setQty(id, q) {
        const p = PRODUK[id];
        if (!p) return;
        q = Math.max(0, Math.min(q, p.stok));
        if (q === 0) delete cart[id]; else cart[id] = q;
        simpanCart(); render();
    }
    function thumb(p, ukuran) {
        if (p.img) return '<img src="' + esc(p.img) + '" alt="" class="w-full h-full object-cover">';
        return '<div class="w-full h-full flex items-center justify-center font-extrabold text-white" style="background:' + esc(p.warna) + ';font-size:' + ukuran + 'px">' + esc(p.inisial) + '</div>';
    }
    function hitungTotal(subtotal, diskon) {
        diskon = Math.min(Math.max(0, diskon || 0), subtotal);
        const dasar = subtotal - diskon;
        const service = Math.round(dasar * SVC / 100);
        const pajak = Math.round((dasar + service) * PPN / 100);
        return { subtotal, diskon, service, pajak, total: dasar + service + pajak };
    }
    function render() {
        document.querySelectorAll('.produk-card').forEach(card => {
            const id = card.dataset.id; const p = PRODUK[id]; const q = cart[id] || 0;
            card.querySelector('.qty-val').textContent = q;
            card.querySelector('.btn-dec').disabled = q <= 0;
            card.querySelector('.btn-inc').disabled = q >= p.stok;
            card.classList.toggle('dalam-keranjang', q > 0);
        });

        const list = document.getElementById('cartList');
        if (!list) return;

        const ids = Object.keys(cart);
        let subtotal = 0; let jumlahItem = 0;

        list.innerHTML = ids.map(id => {
            const p = PRODUK[id]; const q = cart[id]; subtotal += p.harga * q; jumlahItem += q;
            return '<div class="flex items-center gap-3">' +
              '<div class="w-14 h-14 rounded-xl overflow-hidden shrink-0">' + thumb(p, 20) + '</div>' +
              '<div class="flex-1 min-w-0">' +
                '<p class="text-sm font-bold truncate">' + esc(p.nama) + '</p>' +
                '<p class="text-xs text-slate-400">' + q + '× · ' + rupiah(p.harga) + '</p>' +
                '<div class="flex items-center gap-1.5 mt-1.5">' +
                  '<button type="button" data-act="dec" data-id="' + p.id + '" class="w-6 h-6 rounded-full border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-100"><i data-lucide="minus" class="w-3 h-3"></i></button>' +
                  '<span class="w-5 text-center text-xs font-bold">' + q + '</span>' +
                  '<button type="button" data-act="inc" data-id="' + p.id + '" ' + (q >= p.stok ? 'disabled' : '') + ' class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 disabled:opacity-40"><i data-lucide="plus" class="w-3 h-3"></i></button>' +
                  '<button type="button" data-act="del" data-id="' + p.id + '" class="ml-1 text-slate-300 hover:text-red-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>' +
                '</div>' +
              '</div>' +
              '<p class="text-sm font-extrabold">' + rupiah(p.harga * q) + '</p>' +
            '</div>';
        }).join('');

        document.getElementById('cartKosong').classList.toggle('hidden', ids.length > 0);
        document.getElementById('badgeCart').textContent = jumlahItem;
        document.getElementById('btnCheckout').disabled = ids.length === 0;
        document.getElementById('btnKosongkan').classList.toggle('hidden', ids.length === 0);

        const diskon = parseFloat(document.getElementById('inpDiskon').value) || 0;
        const h = hitungTotal(subtotal, diskon);

        document.getElementById('outSubtotal').textContent = rupiah(h.subtotal);
        document.getElementById('outTotal').textContent = rupiah(h.total);
        if(document.getElementById('outService')) document.getElementById('outService').textContent = rupiah(h.service);
        if(document.getElementById('outPajak')) document.getElementById('outPajak').textContent = rupiah(h.pajak);

        document.getElementById('keranjangJson').value = JSON.stringify(ids.map(id => ({ id: parseInt(id, 10), qty: cart[id] })));
        ikon();
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const id = btn.dataset.id; const q = cart[id] || 0;
        if (btn.dataset.act === 'inc') setQty(id, q + 1);
        if (btn.dataset.act === 'dec') setQty(id, q - 1);
        if (btn.dataset.act === 'del') setQty(id, 0);
    });

    const inpDiskon = document.getElementById('inpDiskon');
    if (inpDiskon) inpDiskon.addEventListener('input', render);

    const btnKosong = document.getElementById('btnKosongkan');
    if (btnKosong) btnKosong.addEventListener('click', function () {
        if (confirm('Kosongkan seluruh keranjang?')) { cart = {}; simpanCart(); render(); }
    });

    const formCheckout = document.getElementById('formCheckout');
    if (formCheckout) formCheckout.addEventListener('submit', function (e) {
        if (Object.keys(cart).length === 0) e.preventDefault();
    });

    if (BARU) { try { localStorage.removeItem(KEY); } catch (e) {} }

    muatCart(); render();

    /* FORM PEMBAYARAN */
    document.querySelectorAll('.form-bayar').forEach(function (form) {
        const mode = form.dataset.mode;
        const sisa = parseFloat(form.dataset.sisa);
        const faktor = parseFloat(form.dataset.faktor || '1');
        const uang = form.querySelector('.inp-uang');
        const metode = form.querySelector('.inp-metode');
        const out = form.querySelector('.out-kembalian');
        const outSplit = form.querySelector('.out-nominal-split');
        const checks = form.querySelectorAll('.chk-item');

        function nominalSekarang() {
            if (mode === 'nominal') return Math.min(parseFloat(uang.value) || 0, sisa);
            let sub = 0, dipilih = 0;
            checks.forEach(function (c) { if (c.checked) { sub += parseFloat(c.dataset.subtotal); dipilih++; } });
            if (dipilih > 0 && dipilih === checks.length) return sisa;
            return Math.min(Math.round(sub * faktor), sisa);
        }

        function tampil(teks, merah) {
            out.className = 'out-kembalian text-sm font-bold ' + (merah ? 'text-red-600' : 'text-emerald-700');
            out.textContent = teks;
        }

        function update() {
            const nominal = nominalSekarang();
            const diterima = parseFloat(uang.value) || 0;
            if (outSplit) outSplit.textContent = rupiah(nominal);
            if (diterima <= 0 || (mode === 'split' && nominal === 0)) { out.textContent = ''; return; }
            if (metode.value !== 'tunai') {
                if (mode === 'nominal' && diterima > sisa) tampil('QRIS/debit tidak boleh melebihi sisa tagihan.', true);
                else if (mode === 'split' && diterima !== nominal) tampil('QRIS/debit harus pas: ' + rupiah(nominal), true);
                else tampil('Pembayaran pas, tanpa kembalian.', false);
                return;
            }
            if (diterima < nominal) tampil('Uang kurang ' + rupiah(nominal - diterima), true);
            else tampil('Kembalian: ' + rupiah(diterima - nominal), false);
        }

        form.querySelectorAll('.quick-pas').forEach(b => b.addEventListener('click', function () { uang.value = mode === 'nominal' ? sisa : nominalSekarang(); update(); }));
        form.querySelectorAll('.quick-uang').forEach(b => b.addEventListener('click', function () { uang.value = b.dataset.nilai; update(); }));
        uang.addEventListener('input', update);
        metode.addEventListener('change', update);
        checks.forEach(c => c.addEventListener('change', update));
    });

    ikon();
})();
</script>