<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../reports/nav.php';
require_login();
require_role('admin');

$pageTitle = 'Simulasi Diskon';
$activePage = 'discount';

$navItems = reports_nav_items(true);

$pdo = db();

// Mengambil produk aktif dan HPP rata-rata tertimbang.
$products = $pdo->query("
    SELECT
        p.id, p.sku, p.name, p.selling_price,
        (
            SELECT SUM(sb.qty_remaining * sb.cost_per_unit) /
                   NULLIF(SUM(sb.qty_remaining), 0)
            FROM stock_batches sb
            WHERE sb.product_id = p.id
                AND sb.qty_remaining > 0
                AND (sb.expiry_date IS NULL OR sb.expiry_date >= CURDATE())
        ) AS average_hpp
    FROM products p
    WHERE p.is_active = 1
    ORDER BY p.name
")->fetchAll();

// Mengambil input simulasi.
$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$discountInput = is_string($_GET['discount_percent'] ?? null)
    ? trim($_GET['discount_percent'])
    : '';

$errorMessage = null;
$warningMessage = null;
$selectedProduct = null;
$simulation = null;

if (isset($_GET['calculate'])) {

    // Validasi input.
    if (!$productId || $productId < 1) {
        $errorMessage = 'Silakan pilih produk terlebih dahulu.';
    } elseif ($discountInput === '' || !is_numeric($discountInput)) {
        $errorMessage = 'Persentase diskon harus berupa angka.';
    } else {
        $discount = (float) $discountInput;

        if (!is_finite($discount) || $discount < 0 || $discount > 100) {
            $errorMessage = 'Persentase diskon harus berada antara 0 sampai 100%.';
        }
    }

    // Mencari produk yang dipilih.
    if ($errorMessage === null) {
        $productsById = array_column($products, null, 'id');
        $selectedProduct = $productsById[$productId] ?? null;

        if ($selectedProduct === null) {
            $errorMessage = 'Produk tidak ditemukan atau sudah tidak aktif.';
        }
    }

    // Menghitung simulasi.
    if ($errorMessage === null && $selectedProduct !== null) {
        if ($selectedProduct['average_hpp'] === null) {
            $warningMessage = 'HPP tidak dapat dihitung karena produk tidak memiliki stok batch yang tersedia.';
        } else {
            $price = (float) $selectedProduct['selling_price'];
            $hpp = (float) $selectedProduct['average_hpp'];
            $discountAmount = $price * ($discount / 100);
            $discountedPrice = $price - $discountAmount;
            $profit = $discountedPrice - $hpp;

            $simulation = [
                'price' => $price,
                'hpp' => $hpp,
                'discount' => $discount,
                'discount_amount' => $discountAmount,
                'discounted_price' => $discountedPrice,
                'profit' => $profit,
                'margin' => $discountedPrice > 0
                    ? ($profit / $discountedPrice) * 100
                    : null,
            ];

            if ($profit < 0) {
                $warningMessage = 'Harga setelah diskon berada di bawah HPP. Produk berpotensi mengalami kerugian.';
            }
        }
    }
}

// Format rupiah.
$rupiah = static fn(float $value): string =>
    'Rp ' . number_format($value, 0, ',', '.');

require __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>">

<div class="reports-container">

    <h1>Simulasi Diskon Produk</h1>
    <p class="reports-description">
        Perkirakan harga setelah diskon dan keuntungan tanpa mengubah harga asli produk.
    </p>

    <section class="analytics-section">
        <h2>Pengaturan Simulasi</h2>

        <?php if ($errorMessage !== null): ?>
            <p class="report-error"><?= e($errorMessage) ?></p>
        <?php endif; ?>

        <form method="GET" class="report-filters" data-form="discount">

            <div>
                <label for="product_id">Pilih Produk</label>
                <select name="product_id" id="product_id" required>
                    <option value="">-- Pilih Produk --</option>

                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"
                            <?= $productId === (int) $product['id'] ? 'selected' : '' ?>>
                            <?= e($product['sku'] . ' - ' . $product['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="discount_percent">Persentase Diskon (%)</label>
                <input
                    type="number"
                    name="discount_percent"
                    id="discount_percent"
                    min="0"
                    max="100"
                    step="0.01"
                    value="<?= e($discountInput) ?>"
                    placeholder="Contoh: 10"
                    required
                >
            </div>

            <button type="submit" name="calculate" value="1">
                Hitung Simulasi
            </button>
        </form>
    </section>

    <?php if ($warningMessage !== null): ?>
        <section class="analytics-section">
            <h2>Perhatian</h2>
            <p class="report-error"><?= e($warningMessage) ?></p>
        </section>
    <?php endif; ?>

    <?php if ($simulation !== null): ?>

        <section class="analytics-section">
            <h2>Hasil Simulasi Diskon</h2>

            <div class="report-detail-info">
                <p><strong>SKU:</strong> <?= e($selectedProduct['sku']) ?></p>
                <p><strong>Nama Produk:</strong> <?= e($selectedProduct['name']) ?></p>
            </div>

            <div class="analytics-cards">
                <?php
                $cards = [
                    ['Harga Jual Asli', $rupiah($simulation['price'])],
                    ['HPP Rata-rata', $rupiah($simulation['hpp'])],
                    ['Potongan Diskon', $rupiah($simulation['discount_amount'])],
                    ['Harga Setelah Diskon', $rupiah($simulation['discounted_price'])],
                ];
                ?>

                <?php foreach ($cards as [$label, $value]): ?>
                    <div class="analytics-card">
                        <h3><?= e($label) ?></h3>
                        <p><?= e($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="analytics-section">
                <h2>Analisis Keuntungan</h2>

                <div class="report-detail-info">
                    <p><strong>Diskon:</strong> <?= e(number_format($simulation['discount'], 2, ',', '.')) ?>%</p>
                    <p><strong>Keuntungan per Unit:</strong> <?= e($rupiah($simulation['profit'])) ?></p>
                    <p>
                        <strong>Margin:</strong>
                        <?= $simulation['margin'] !== null
                            ? e(number_format($simulation['margin'], 2, ',', '.') . '%')
                            : 'Tidak dapat dihitung' ?>
                    </p>
                </div>

                <?php if ($simulation['profit'] >= 0): ?>
                    <span class="stock-status stock-ok">
                        Harga setelah diskon masih menutupi HPP.
                    </span>
                <?php endif; ?>
            </div>
        </section>

    <?php endif; ?>

    <section class="analytics-section">
        <h2>Informasi Perhitungan</h2>
        <p>
            HPP menggunakan rata-rata tertimbang biaya batch yang masih memiliki
            stok tersedia. Keuntungan per unit dihitung dari harga setelah diskon
            dikurangi HPP rata-rata.
        </p>
        <p>
            Simulasi hanya sebagai bahan pertimbangan dan tidak mengubah harga
            jual produk di database.
        </p>
    </section>

</div>

<script src="<?= e(app_url('assets/js/recommendations-reports.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
