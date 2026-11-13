<?php
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$pageTitle = 'Dashboard';

require_once 'config/database.php';
require_once 'includes/helper.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$conn->query("SET time_zone = '+07:00'");

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$startDate = date('Y-m-d', strtotime('-6 days'));

$totalProduk = 0;
$transaksiHariIni = 0;
$omzetHariIni = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total_produk
    FROM products
");

if ($result) {
    $totalProduk = (int) $result->fetch_assoc()['total_produk'];
}

$result = $conn->query("
    SELECT
        COUNT(*) AS total_transaksi,
        COALESCE(SUM(total), 0) AS omzet
    FROM transactions
    WHERE transaction_date >= '{$today} 00:00:00'
      AND transaction_date < '{$tomorrow} 00:00:00'
");

if ($result) {
    $statistikHariIni = $result->fetch_assoc();

    $transaksiHariIni = (int) $statistikHariIni['total_transaksi'];
    $omzetHariIni = (float) $statistikHariIni['omzet'];
}

$labelOmzet = [];
$dataOmzet = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-{$i} days"));

    $labelOmzet[] = date('d M', strtotime($tanggal));
    $dataOmzet[$tanggal] = 0;
}

$result = $conn->query("
    SELECT
        DATE(transaction_date) AS tanggal,
        COALESCE(SUM(total), 0) AS omzet
    FROM transactions
    WHERE transaction_date >= '{$startDate} 00:00:00'
      AND transaction_date < '{$tomorrow} 00:00:00'
    GROUP BY DATE(transaction_date)
    ORDER BY tanggal ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tanggal = $row['tanggal'];

        if (isset($dataOmzet[$tanggal])) {
            $dataOmzet[$tanggal] = (float) $row['omzet'];
        }
    }
}

$dataOmzet = array_values($dataOmzet);
$maxOmzet = max($dataOmzet ?: [0]);

$stokNormal = 0;
$stokMenipis = 0;
$stokHabis = 0;

$result = $conn->query("
    SELECT
        SUM(CASE
            WHEN stock > min_stock THEN 1
            ELSE 0
        END) AS normal,

        SUM(CASE
            WHEN stock > 0 AND stock <= min_stock THEN 1
            ELSE 0
        END) AS menipis,

        SUM(CASE
            WHEN stock = 0 THEN 1
            ELSE 0
        END) AS habis

    FROM products
");

if ($result) {
    $stok = $result->fetch_assoc();

    $stokNormal = (int) $stok['normal'];
    $stokMenipis = (int) $stok['menipis'];
    $stokHabis = (int) $stok['habis'];
}

$stokRendah = [];

$result = $conn->query("
    SELECT id, sku, name, stock, min_stock
    FROM products
    WHERE stock <= min_stock
    ORDER BY stock ASC, name ASC, id ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stokRendah[] = $row;
    }
}

?>

<div class="app-layout">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content dashboard-main">

        <div class="dashboard-page">
            <div class="container-fluid">

                <div class="dashboard-heading">

                    <div>
                        <h2>Dashboard</h2>

                        <p>
                            Selamat datang,
                            <strong>
                                <?= htmlspecialchars(
                                    $_SESSION['name'] ??
                                    $_SESSION['username'] ??
                                    'Admin'
                                ) ?>
                            </strong>
                            👋
                        </p>
                    </div>

                    <div class="dashboard-date">
                        <i class="bi bi-calendar3"></i>
                        <span id="dashboardDate">
                            <?= date('d F Y') ?>
                        </span>
                    </div>

                </div>

                <?php showFlash(); ?>

                <?php if ($stokHabis > 0 || $stokMenipis > 0): ?>

                    <div class="alert alert-warning dashboard-alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>

                        <strong>Perhatian stok:</strong>

                        <?php if ($stokHabis > 0): ?>
                            <?= $stokHabis ?> produk habis
                        <?php endif; ?>

                        <?php if ($stokHabis > 0 && $stokMenipis > 0): ?>
                            dan
                        <?php endif; ?>

                        <?php if ($stokMenipis > 0): ?>
                            <?= $stokMenipis ?> produk memiliki stok menipis
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <div class="card dashboard-stat-card h-100">
                            <div class="card-body d-flex align-items-center">

                                <div class="dashboard-stat-icon purple">
                                    <i class="bi bi-box-seam"></i>
                                </div>

                                <div>
                                    <div class="dashboard-stat-label">
                                        Total Produk
                                    </div>

                                    <div class="dashboard-stat-value">
                                        <?= number_format($totalProduk, 0, ',', '.') ?>
                                    </div>

                                    <small class="text-muted">
                                        Produk terdaftar di sistem
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card dashboard-stat-card h-100">
                            <div class="card-body d-flex align-items-center">

                                <div class="dashboard-stat-icon orange">
                                    <i class="bi bi-receipt"></i>
                                </div>

                                <div>
                                    <div class="dashboard-stat-label">
                                        Transaksi Hari Ini
                                    </div>

                                    <div class="dashboard-stat-value">
                                        <?= number_format($transaksiHariIni, 0, ',', '.') ?>
                                    </div>

                                    <small class="text-muted">
                                        Transaksi yang tercatat hari ini
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card dashboard-stat-card h-100">
                            <div class="card-body d-flex align-items-center">

                                <div class="dashboard-stat-icon green">
                                    <i class="bi bi-cash-stack"></i>
                                </div>

                                <div>
                                    <div class="dashboard-stat-label">
                                        Omzet Hari Ini
                                    </div>

                                    <div class="dashboard-stat-value dashboard-omzet-value">
                                        <?= formatRupiah($omzetHariIni) ?>
                                    </div>

                                    <small class="text-muted">
                                        Total pendapatan hari ini
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-3 mb-4">

                    <div class="col-lg-8">
                        <div class="card dashboard-panel h-100">

                            <div class="card-body p-3 p-md-4">

                                <div class="dashboard-panel-heading">
                                    <div>
                                        <h5>
                                            <i class="bi bi-graph-up-arrow me-2"></i>
                                            Perkembangan Omzet
                                        </h5>

                                        <small>
                                            Omzet penjualan selama 7 hari terakhir
                                        </small>
                                    </div>

                                    <span class="dashboard-period">
                                        7 Hari
                                    </span>
                                </div>

                                <div class="dashboard-chart">
                                    <canvas id="omzetChart"></canvas>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card dashboard-panel h-100">

                            <div class="card-body p-3 p-md-4">

                                <div class="dashboard-panel-heading">
                                    <div>
                                        <h5>
                                            <i class="bi bi-pie-chart me-2"></i>
                                            Kondisi Stok
                                        </h5>

                                        <small>
                                            Ringkasan ketersediaan produk
                                        </small>
                                    </div>
                                </div>

                                <div class="dashboard-stock-chart">
                                    <canvas id="stockChart"></canvas>
                                </div>

                                <div class="dashboard-stock-legend">

                                    <div>
                                        <span class="stock-dot normal"></span>
                                        Stok Normal
                                        <strong><?= $stokNormal ?></strong>
                                    </div>

                                    <div>
                                        <span class="stock-dot menipis"></span>
                                        Stok Menipis
                                        <strong><?= $stokMenipis ?></strong>
                                    </div>

                                    <div>
                                        <span class="stock-dot habis"></span>
                                        Stok Habis
                                        <strong><?= $stokHabis ?></strong>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="card dashboard-panel mb-4">

                    <div class="card-body p-3 p-md-4">

                        <div class="dashboard-panel-heading">
                            <div>
                                <h5>
                                    <i class="bi bi-boxes me-2"></i>
                                    Monitoring Stok
                                </h5>

                                <small>
                                    Daftar produk dengan stok menipis atau habis
                                </small>
                            </div>

                            <span class="dashboard-period">
                                <?= count($stokRendah) ?> Produk
                            </span>
                        </div>

                        <?php if (!empty($stokRendah)): ?>

                            <div class="table-responsive">

                                <table class="table table-hover align-middle dashboard-stock-table">

                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th class="text-center">Stok</th>
                                            <th class="text-center">Minimal Stok</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($stokRendah as $produk): ?>

                                            <?php
                                            $stock = (int) $produk['stock'];
                                            $minStock = (int) $produk['min_stock'];

                                            $status = $stock === 0 ? 'Habis' : 'Menipis';
                                            $statusClass = $stock === 0 ? 'danger' : 'warning';
                                            ?>

                                            <tr>
                                                <td>
                                                    <span class="dashboard-sku">
                                                        <?= htmlspecialchars($produk['sku']) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <strong>
                                                        <?= htmlspecialchars($produk['name']) ?>
                                                    </strong>
                                                </td>

                                                <td class="text-center">
                                                    <?= $stock ?>
                                                </td>

                                                <td class="text-center">
                                                    <?= $minStock ?>
                                                </td>

                                                <td class="text-center">
                                                    <span class="dashboard-status <?= $statusClass ?>">
                                                        <?= $status ?>
                                                    </span>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="dashboard-empty">
                                <i class="bi bi-check-circle"></i>

                                <p>
                                    Semua stok produk dalam kondisi aman.
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function updateDashboardDate() {
    const dateElement = document.getElementById('dashboardDate');

    if (!dateElement) return;

    dateElement.textContent = new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta'
    }).format(new Date());
}

updateDashboardDate();
setInterval(updateDashboardDate, 60000);

document.addEventListener('DOMContentLoaded', function () {

    const labelOmzet = <?= json_encode($labelOmzet) ?>;
    const dataOmzet = <?= json_encode($dataOmzet) ?>;
    const maxOmzet = <?= json_encode($maxOmzet) ?>;

    const omzetCanvas = document.getElementById('omzetChart');

    if (omzetCanvas) {
        new Chart(omzetCanvas, {
            type: 'line',
            data: {
                labels: labelOmzet,
                datasets: [{
                    label: 'Omzet',
                    data: dataOmzet,
                    borderColor: '#5142a5',
                    backgroundColor: 'rgba(81, 66, 165, 0.10)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#5142a5',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Omzet: Rp ' +
                                    new Intl.NumberFormat('id-ID')
                                    .format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: maxOmzet === 0 ? 1 : undefined,
                        ticks: {
                            count: maxOmzet === 0 ? 1 : 5,
                            callback: function (value) {
                                if (maxOmzet === 0) {
                                    return value === 0 ? 'Rp 0' : '';
                                }

                                return 'Rp ' +
                                    new Intl.NumberFormat('id-ID', {
                                        notation: 'compact',
                                        maximumFractionDigits: 1
                                    }).format(value);
                            }
                        },
                        grid: {
                            color: '#eeeeee'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    const stockCanvas = document.getElementById('stockChart');

    if (stockCanvas) {
        new Chart(stockCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Menipis', 'Habis'],
                datasets: [{
                    data: [
                        <?= $stokNormal ?>,
                        <?= $stokMenipis ?>,
                        <?= $stokHabis ?>
                    ],
                    backgroundColor: [
                        '#5142a5',
                        '#ffad42',
                        '#dc3545'
                    ],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' +
                                    context.raw + ' produk';
                            }
                        }
                    }
                }
            }
        });
    }

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>