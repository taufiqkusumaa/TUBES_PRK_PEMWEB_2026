<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../reports/nav.php';

require_login();
require_role('admin');

$pageTitle = 'Rekomendasi Restock';
$activePage = 'restock';

$navItems = reports_nav_items(true);

$pdo = db();

// Mengambil data produk, stok tersedia, dan penjualan 30 hari terakhir.
$productQuery = $pdo->query("
    SELECT
        p.id,
        p.sku,
        p.name,
        p.min_stock,

        COALESCE((
            SELECT SUM(sb.qty_remaining)
            FROM stock_batches sb
            WHERE sb.product_id = p.id
            AND sb.qty_remaining > 0
             AND (sb.expiry_date IS NULL OR sb.expiry_date >= CURDATE())
        ), 0) AS available_stock,

        COALESCE((
            SELECT SUM(si.qty)
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            WHERE si.product_id = p.id
              AND s.status = 'completed'
              AND s.sold_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND s.sold_at <= NOW()
        ), 0) AS sold_qty

    FROM products p
    WHERE p.is_active = 1
    ORDER BY p.name ASC
");

$products = $productQuery->fetchAll();

// Mengambil input perhitungan dari form.
$leadTimes = $_GET['lead_time'] ?? [];
$safetyStocks = $_GET['safety_stock'] ?? [];
$targetStocks = $_GET['target_stock'] ?? [];

if (!is_array($leadTimes)) {
    $leadTimes = [];
}

if (!is_array($safetyStocks)) {
    $safetyStocks = [];
}

if (!is_array($targetStocks)) {
    $targetStocks = [];
}

// Mengambil nilai input dengan aman.
$getInput = static function (array $values, int $id): string {
    $value = $values[$id] ?? '';

    return is_scalar($value) ? (string) $value : '';
};

// Validasi input bilangan bulat nonnegatif.
$parseInteger = static function (string $value): ?int {
    if ($value === '') {
        return null;
    }

    $result = filter_var($value, FILTER_VALIDATE_INT);

    if ($result === false || $result < 0) {
        return null;
    }

    return $result;
};

$hasSubmitted = isset($_GET['calculate']);
$results = [];
$anyFilled = false;

$periodStart = date('d-m-Y', strtotime('-30 days'));
$periodEnd = date('d-m-Y');

foreach ($products as $product) {
    $id = (int) $product['id'];

    $leadTimeInput = $getInput($leadTimes, $id);
    $safetyStockInput = $getInput($safetyStocks, $id);
    $targetStockInput = $getInput($targetStocks, $id);

    $leadTime = $parseInteger($leadTimeInput);
    $safetyStock = $parseInteger($safetyStockInput);
    $targetStock = $parseInteger($targetStockInput);

    $availableStock = (int) $product['available_stock'];
    $soldQty = (int) $product['sold_qty'];

    // ADS dihitung dari penjualan 30 hari terakhir.
    $ads = $soldQty / 30;

    // Baris dianggap diisi jika minimal satu kolom input terisi.
    $rowFilled = $leadTimeInput !== '' || $safetyStockInput !== '' || $targetStockInput !== '';

    if ($rowFilled) {
        $anyFilled = true;
    }

    $rop = null;
    $suggestedQty = null;
    $needsRestock = null;
    $error = '';

    if ($hasSubmitted && $rowFilled) {
        if (
            $leadTime === null ||
            $leadTime < 1 ||
            $safetyStock === null ||
            $targetStock === null ||
            $targetStock < 1
        ) {
            $error = 'Isi lead time, safety stock, dan target stok dengan benar.';
        } elseif ($soldQty === 0) {
            $error = 'Riwayat penjualan 30 hari tidak mencukupi. Periksa data sebelum membuat rekomendasi.';
        } else {
            $rop = ($ads * $leadTime) + $safetyStock;

            $suggestedQty = max(
                0,
                $targetStock - $availableStock
            );

            $needsRestock = $availableStock <= $rop;
        }
    }

    $results[] = [
        'id' => $id,
        'sku' => $product['sku'],
        'name' => $product['name'],
        'available_stock' => $availableStock,
        'sold_qty' => $soldQty,
        'ads' => $ads,
        'lead_time' => $leadTimeInput,
        'safety_stock' => $safetyStockInput,
        'target_stock' => $targetStockInput,
        'rop' => $rop,
        'suggested_qty' => $suggestedQty,
        'needs_restock' => $needsRestock,
        'error' => $error,
    ];
}

$noRowFilled = $hasSubmitted && !$anyFilled;

require_once __DIR__ . '/../includes/header.php';
?>

<link
    rel="stylesheet"
    href="<?= e(app_url('assets/css/recommendations-reports.css')) ?>"
>

<section class="reports-container">

    <h1>Rekomendasi Restock</h1>

    <p class="reports-description">
        Hitung kebutuhan pembelian stok berdasarkan penjualan,
        waktu tunggu pemasok, dan stok pengaman.
    </p>

    <section class="analytics-section">

        <h2>Perhitungan Kebutuhan Stok</h2>

        <p>
            Periode data penjualan:
            <strong><?= e($periodStart) ?> sampai <?= e($periodEnd) ?></strong>
            (30 hari terakhir, hanya transaksi selesai).
            Isi lead time, safety stock, dan target stok hanya untuk produk
            yang ingin dihitung.
        </p>

        <p>
            ADS = Total penjualan 30 hari terakhir / 30.
            ROP = (ADS × Lead Time) + Safety Stock.
        </p>

        <p>
            Jumlah pembelian = Maksimum (0, Target Stok - Stok Tersedia).
        </p>

        <?php if ($noRowFilled): ?>
            <p class="report-error">
                Isi lead time, safety stock, dan target stok minimal untuk satu produk.
            </p>
        <?php endif; ?>

        <form method="GET" data-form="restock" action="<?= e(app_url('recommendations/restock.php')) ?>">

            <div class="table-responsive">

                <table>
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th>Terjual (30 Hari)</th>
                            <th>ADS</th>
                            <th>Stok Tersedia</th>
                            <th>Lead Time (Hari)</th>
                            <th>Safety Stock</th>
                            <th>Target Stok</th>
                            <th>ROP</th>
                            <th>Status</th>
                            <th>Rekomendasi Beli</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($results as $result): ?>

                            <tr>
                                <td><?= e($result['sku']) ?></td>

                                <td><?= e($result['name']) ?></td>

                                <td>
                                    <?= number_format($result['sold_qty']) ?>
                                </td>

                                <td>
                                    <?= number_format($result['ads'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?= number_format($result['available_stock']) ?>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="lead_time[<?= $result['id'] ?>]"
                                        min="1"
                                        step="1"
                                        value="<?= e($result['lead_time']) ?>"
                                        aria-label="Lead time <?= e($result['name']) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="safety_stock[<?= $result['id'] ?>]"
                                        min="0"
                                        step="1"
                                        value="<?= e($result['safety_stock']) ?>"
                                        aria-label="Safety stock <?= e($result['name']) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="target_stock[<?= $result['id'] ?>]"
                                        min="1"
                                        step="1"
                                        value="<?= e($result['target_stock']) ?>"
                                        aria-label="Target stok <?= e($result['name']) ?>"
                                    >
                                </td>

                                <td>
                                    <?php if ($result['rop'] !== null): ?>
                                        <?= number_format(
                                            $result['rop'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($result['needs_restock'] === true): ?>
                                        <span class="stock-status stock-low">Perlu restock</span>
                                    <?php elseif ($result['needs_restock'] === false): ?>
                                        <span class="stock-status stock-ok">Stok cukup</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($result['suggested_qty'] !== null): ?>
                                        <strong>
                                            <?= number_format($result['suggested_qty']) ?>
                                        </strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if ($result['error'] !== ''): ?>
                                <tr>
                                    <td colspan="11">
                                        <span class="report-error">
                                            <?= e($result['name']) ?>:
                                            <?= e($result['error']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

            <?php if (empty($results)): ?>
                <p>Belum ada produk aktif untuk dihitung.</p>
            <?php else: ?>
                <button type="submit" name="calculate" value="1">
                    Hitung Rekomendasi
                </button>
            <?php endif; ?>

        </form>

    </section>

    <p class="reports-description">
        Catatan: Perhitungan menggunakan data penjualan 30 hari terakhir.
        Jika tidak ada penjualan dalam periode tersebut, sistem tidak
        membuat rekomendasi otomatis.
    </p>

</section>
<script src="<?= e(app_url('assets/js/recommendations-reports.js')) ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
