<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

ss4_require_login();
$pdo = ss4_db();

$today = date('Y-m-d');
$defaultFrom = date('Y-m-d', strtotime('-29 days'));
$dateFrom = ss4_valid_date(isset($_GET['date_from']) ? (string) $_GET['date_from'] : null, $defaultFrom);
$dateTo = ss4_valid_date(isset($_GET['date_to']) ? (string) $_GET['date_to'] : null, $today);

if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$error = '';
$summary = [];
$trend = [];
$bestSellers = [];
$slowMoving = [];
$lowStock = [];
$expiring = [];

try {
    $summary = ss4_get_summary($pdo, $dateFrom, $dateTo);
    $trend = ss4_get_sales_trend($pdo, $dateFrom, $dateTo);
    $bestSellers = ss4_get_best_sellers($pdo, $dateFrom, $dateTo, 10);
    $slowMoving = ss4_get_slow_moving($pdo, 30, 20);
    $lowStock = ss4_get_low_stock($pdo);
    $expiring = ss4_get_expiring_batches($pdo, 30);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$maxTrend = 0.0;
foreach ($trend as $point) {
    $maxTrend = max($maxTrend, (float) $point['omzet']);
}
$chartLabels = array_map(static function ($point) {
    return date('d/m', strtotime($point['date']));
}, $trend);
$chartValues = array_map(static function ($point) {
    return (float) $point['omzet'];
}, $trend);

$headerPath = dirname(__DIR__) . '/includes/header.php';
$footerPath = dirname(__DIR__) . '/includes/footer.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Analitik - SisaSense</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .an-wrap{max-width:1280px;margin:24px auto;padding:0 18px;color:#1f2937}
        .an-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
        .an-muted{color:#64748b;font-size:13px}
        .an-filter,.an-panel{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin:18px 0}
        .an-filter form{display:flex;gap:12px;align-items:end;flex-wrap:wrap}
        .an-filter label{display:block;font-size:13px;margin-bottom:5px}
        .an-filter input{padding:9px;border:1px solid #cbd5e1;border-radius:7px}
        .an-btn{display:inline-block;background:#b42318;color:#fff;padding:10px 15px;border:0;border-radius:7px;text-decoration:none;cursor:pointer}
        .an-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin:18px 0}
        .an-stat{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px}
        .an-stat span{display:block;color:#64748b;font-size:13px;margin-bottom:8px}
        .an-stat strong{font-size:22px}
        .an-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .an-table-wrap{overflow-x:auto}
        .an-table{width:100%;border-collapse:collapse;min-width:450px}
        .an-table th,.an-table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:13px}
        .an-table th{background:#f8fafc}
        .an-alert{padding:11px 13px;border-radius:8px;background:#fff7ed;color:#9a3412;margin:8px 0}
        .an-danger{color:#b91c1c;font-weight:600}
        .an-ok{color:#166534;font-weight:600}
        .an-chart{width:100%;height:260px;display:block}
        .an-chart text{font-size:10px;fill:#64748b}
        .an-legend{font-size:12px;color:#64748b}
        .an-empty{padding:18px;text-align:center;color:#64748b}
        @media(max-width:800px){.an-columns{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php if (is_file($headerPath)) require $headerPath; ?>

<main class="an-wrap">
    <div class="an-head">
        <div>
            <h1>Dashboard Analisis Penjualan</h1>
            <p class="an-muted">Ringkasan performa penjualan, produk, dan kondisi persediaan SisaSense.</p>
        </div>
    </div>

    <section class="an-filter">
        <form method="get">
            <div>
                <label for="date_from">Tanggal mulai</label>
                <input type="date" id="date_from" name="date_from" value="<?= ss4_h($dateFrom) ?>" required>
            </div>
            <div>
                <label for="date_to">Tanggal akhir</label>
                <input type="date" id="date_to" name="date_to" value="<?= ss4_h($dateTo) ?>" required>
            </div>
            <button class="an-btn" type="submit">Terapkan filter</button>
            <a class="an-muted" href="index.php">Reset periode</a>
        </form>
        <p class="an-muted">Metrik omzet, transaksi, laba kotor, tren, dan produk terlaris mengikuti rentang tanggal yang dipilih. Slow-moving menggunakan jendela 30 hari berjalan; peringatan stok dan kedaluwarsa menunjukkan kondisi saat ini.</p>
    </section>

    <?php if ($error !== ''): ?>
        <section class="an-alert">
            Dashboard belum dapat dimuat: <?= ss4_h($error) ?>
            <br><small>Pastikan nama kolom database dan helper koneksi sesuai dengan skema tim.</small>
        </section>
    <?php else: ?>
        <section class="an-grid">
            <div class="an-stat"><span>Omzet</span><strong><?= ss4_rupiah($summary['omzet']) ?></strong></div>
            <div class="an-stat"><span>Total Transaksi</span><strong><?= number_format($summary['transactions'], 0, ',', '.') ?></strong></div>
            <div class="an-stat"><span>Produk Aktif</span><strong><?= number_format($summary['active_products'], 0, ',', '.') ?></strong></div>
            <div class="an-stat"><span>Laba Kotor</span><strong><?= ss4_rupiah($summary['gross_profit']) ?></strong><small class="an-muted">Omzet dikurangi HPP snapshot</small></div>
        </section>

        <section class="an-panel">
            <h2>Tren omzet harian</h2>
            <p class="an-muted">Grafik mengikuti rentang tanggal yang dipilih dan hanya memasukkan transaksi completed.</p>
            <?php if (!$trend): ?>
                <div class="an-empty">Tidak ada data pada rentang tanggal ini.</div>
            <?php else: ?>
                <svg class="an-chart" viewBox="0 0 1000 260" preserveAspectRatio="none" role="img" aria-label="Grafik tren omzet harian">
                    <?php for ($i = 0; $i <= 4; $i++): ?>
                        <?php $y = 220 - ($i * 48); ?>
                        <line x1="55" y1="<?= $y ?>" x2="980" y2="<?= $y ?>" stroke="#e5e7eb" stroke-width="1"/>
                    <?php endfor; ?>
                    <?php
                    $count = count($trend);
                    $points = [];
                    foreach ($trend as $i => $point) {
                        $x = $count <= 1 ? 55 : 55 + ($i * (925 / ($count - 1)));
                        $ratio = $maxTrend > 0 ? ((float) $point['omzet'] / $maxTrend) : 0;
                        $y = 220 - ($ratio * 190);
                        $points[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                    }
                    ?>
                    <polyline points="<?= ss4_h(implode(' ', $points)) ?>" fill="none" stroke="#b42318" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <?php foreach ($trend as $i => $point): ?>
                        <?php
                        $x = $count <= 1 ? 55 : 55 + ($i * (925 / ($count - 1)));
                        $ratio = $maxTrend > 0 ? ((float) $point['omzet'] / $maxTrend) : 0;
                        $y = 220 - ($ratio * 190);
                        ?>
                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="3.5" fill="#b42318">
                            <title><?= ss4_h($point['date']) ?>: <?= ss4_h(ss4_rupiah($point['omzet'])) ?></title>
                        </circle>
                    <?php endforeach; ?>
                    <text x="55" y="250"><?= ss4_h($chartLabels[0] ?? '') ?></text>
                    <text x="980" y="250" text-anchor="end"><?= ss4_h($chartLabels[count($chartLabels)-1] ?? '') ?></text>
                    <text x="55" y="15"><?= ss4_h(ss4_rupiah($maxTrend)) ?></text>
                </svg>
                <p class="an-legend">Nilai tertinggi per hari pada periode: <?= ss4_rupiah($maxTrend) ?>.</p>
            <?php endif; ?>
        </section>

        <div class="an-columns">
            <section class="an-panel">
                <h2>Produk terlaris</h2>
                <p class="an-muted">Diurutkan berdasarkan total kuantitas terjual pada rentang tanggal terpilih.</p>
                <div class="an-table-wrap">
                    <table class="an-table">
                        <thead><tr><th>Produk</th><th>Terjual</th><th>Omzet item</th></tr></thead>
                        <tbody>
                        <?php if (!$bestSellers): ?>
                            <tr><td colspan="3" class="an-empty">Belum ada penjualan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($bestSellers as $product): ?>
                            <tr>
                                <td><?= ss4_h($product['name']) ?><br><small class="an-muted"><?= ss4_h($product['sku']) ?></small></td>
                                <td><?= number_format((int) $product['total_qty'], 0, ',', '.') ?> <?= ss4_h($product['unit']) ?></td>
                                <td><?= ss4_rupiah($product['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="an-panel">
                <h2>Produk slow-moving</h2>
                <p class="an-muted">Produk aktif tanpa transaksi completed selama 30 hari terakhir, termasuk yang belum pernah terjual.</p>
                <div class="an-table-wrap">
                    <table class="an-table">
                        <thead><tr><th>Produk</th><th>Penjualan terakhir</th></tr></thead>
                        <tbody>
                        <?php if (!$slowMoving): ?>
                            <tr><td colspan="2" class="an-empty">Tidak ada produk slow-moving.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($slowMoving as $product): ?>
                            <tr>
                                <td><?= ss4_h($product['name']) ?><br><small class="an-muted"><?= ss4_h($product['sku']) ?></small></td>
                                <td><?= $product['last_sold_at'] ? ss4_h(date('d/m/Y', strtotime($product['last_sold_at']))) : '<span class="an-danger">Belum pernah terjual</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="an-columns">
            <section class="an-panel">
                <h2>Alert stok minimum</h2>
                <p class="an-muted">Produk aktif dengan stok batch valid kurang dari atau sama dengan stok minimum.</p>
                <?php if (!$lowStock): ?>
                    <p class="an-ok">Tidak ada produk yang berada di bawah batas stok minimum.</p>
                <?php else: ?>
                    <?php foreach ($lowStock as $item): ?>
                        <div class="an-alert">
                            <strong><?= ss4_h($item['name']) ?></strong> (<?= ss4_h($item['sku']) ?>)<br>
                            Stok: <?= (int) $item['available_stock'] ?> <?= ss4_h($item['unit']) ?> · Minimum: <?= (int) $item['min_stock'] ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="an-panel">
                <h2>Batch mendekati kedaluwarsa</h2>
                <p class="an-muted">Batch dengan stok tersisa dan tanggal kedaluwarsa dalam 30 hari ke depan.</p>
                <?php if (!$expiring): ?>
                    <p class="an-ok">Tidak ada batch yang mendekati kedaluwarsa.</p>
                <?php else: ?>
                    <?php foreach ($expiring as $batch): ?>
                        <div class="an-alert">
                            <strong><?= ss4_h($batch['product_name']) ?></strong>
                            (<?= ss4_h($batch['batch_code'] ?: 'Tanpa kode batch') ?>)<br>
                            Sisa: <?= (int) $batch['qty_remaining'] ?> <?= ss4_h($batch['unit']) ?> · Kedaluwarsa: <?= ss4_h(date('d/m/Y', strtotime($batch['expiry_date']))) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</main>

<?php if (is_file($footerPath)) require $footerPath; ?>
</body>
</html>
