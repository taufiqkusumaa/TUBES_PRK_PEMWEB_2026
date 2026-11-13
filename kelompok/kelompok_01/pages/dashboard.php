<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Cek hak akses berdasarkan role
$role = $_SESSION['user']['role'] ?? '';

if ($role === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

// Dashboard dapat diakses Kepala Keluarga dan Anggota Keluarga
if (!in_array($role, ['kepala_keluarga', 'anggota_keluarga'], true)) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ringkasan pemasukan, pengeluaran, anggaran, dan tabungan keluarga di SaveSmart.">
    <meta name="theme-color" content="#f4f1e9">

    <title>SaveSmart — Ringkasan keluarga</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>

<body class="dashboard-page">
    <div class="app-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content" id="main-dashboard">
        <?php include __DIR__ . '/../components/navbar.php'; ?>
        <div class="dashboard-content">
            <section class="dashboard-overview" id="dashboard-overview" aria-labelledby="dashboardTitle">
                <div class="wrap dashboard-overview__wrap">
                    <div class="hero-text">
                        <div class="dashboard-overview__body">
                            <header class="dashboard-intro">
                                <p class="dashboard-eyebrow">CATATAN KELUARGA</p>
                                <h1 id="dashboardTitle">Ringkasan keluarga</h1>
                                <p>Uang yang masuk, keluar, dan tersimpan dirangkum apa adanya.</p>
                            </header>

                        <article class="balance-card card" aria-labelledby="balanceTitle">
                            <div class="balance-card__header">
                                <div class="balance-card__label">
                                    <span class="balance-card__dot" aria-hidden="true"></span>
                                    <p id="balanceTitle">Saldo keseluruhan</p>
                                </div>
                                
                            </div>

                            <div class="balance-card__main">
                                <p class="balance-card__amount" id="cardSaldo" aria-live="polite">Memuat...</p>
                                <p class="balance-card__note">Pemasukan dikurangi pengeluaran dan setoran tabungan.</p>
                            </div>

                            <div class="balance-card__footer">
                                <p>Catatan keuangan keluarga</p>
                                <div class="balance-actions">
                                    <a class="btn balance-action balance-action--primary" href="pengeluaran.php">
                                        Catat pengeluaran
                                        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M8 3l5 5-5 5" /></svg>
                                    </a>
                                </div>
                            </div>
                        </article>

                        <div class="summary-grid grid" aria-label="Ringkasan total keluarga">
                            <article class="summary-card card" aria-label="Total pemasukan">
                                <div class="summary-card__top">
                                    <p class="summary-card__label">Total pemasukan</p>
                                    <span class="ic-tile ic-tile--glyph ic-tile--40 summary-icon income" aria-hidden="true">
                                        <svg class="ic ic-svg ic-24" viewBox="0 0 24 24" fill="none">
                                            <path d="M7 17 17 7M7 7h10v10" />
                                        </svg>
                                    </span>
                                </div>
                                <p class="summary-card__amount" id="cardPemasukan" aria-live="polite">Memuat...</p>
                                <small>Sejak pencatatan dimulai</small>
                            </article>

                            <article class="summary-card card" aria-label="Total pengeluaran">
                                <div class="summary-card__top">
                                    <p class="summary-card__label">Total pengeluaran</p>
                                    <span class="ic-tile ic-tile--glyph ic-tile--40 summary-icon expense" aria-hidden="true">
                                        <svg class="ic ic-svg ic-24" viewBox="0 0 24 24" fill="none">
                                            <path d="M7 7 17 17M7 17h10V7" />
                                        </svg>
                                    </span>
                                </div>
                                <p class="summary-card__amount" id="cardPengeluaran" aria-live="polite">Memuat...</p>
                                <small>Sejak pencatatan dimulai</small>
                            </article>

                            <article class="summary-card card" aria-label="Total tabungan">
                                <div class="summary-card__top">
                                    <p class="summary-card__label">Tabungan terkumpul</p>
                                    <span class="ic-tile ic-tile--glyph ic-tile--40 summary-icon saving" aria-hidden="true">
                                        <svg class="ic ic-svg ic-24" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="8" />
                                            <path d="M12 7v5l3 2" />
                                        </svg>
                                    </span>
                                </div>
                                <p class="summary-card__amount" id="cardTabungan" aria-live="polite">Memuat...</p>
                                <small>Akumulasi setoran</small>
                            </article>
                        </div>

                        <p class="dashboard-error" id="dashboardError" role="alert" hidden></p>
                    </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-analytics" id="dashboard-analytics" aria-labelledby="analyticsTitle">
                <div class="wrap dashboard-analytics__wrap">
                    <header class="dashboard-section-head">
                        <h2 id="analyticsTitle">Pergerakan uang</h2>
                        <p>Bandingkan pemasukan dan pengeluaran selama enam bulan terakhir.</p>
                        <a class="btn balance-action balance-action--primary analytics-action" href="laporan.php">
                            Lihat laporan
                            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M8 3l5 5-5 5" /></svg>
                        </a>
                    </header>

                    <div class="analytics-panel">
                        <section class="chart-panel" aria-labelledby="chartTitle">
                            <div class="panel-heading">
                                <div>
                                    <h3 id="chartTitle">Pemasukan &amp; pengeluaran</h3>
                                    <p>Enam bulan terakhir</p>
                                </div>
                                <span class="chart-period">6 bulan</span>
                            </div>

                            <div class="chart-frame">
                                <canvas id="financeChart" hidden role="img" aria-label="Grafik batang pemasukan dan pengeluaran keluarga selama enam bulan terakhir"></canvas>
                                <p class="chart-message" id="financeChartMessage" role="status">Memuat grafik enam bulan terakhir...</p>
                            </div>
                        </section>

                        <aside class="finance-summary" aria-labelledby="financeSummaryTitle">
                            <div class="finance-summary__head">
                                <div class="summary-mark" aria-hidden="true">
                                    <svg class="ic ic-svg ic-24" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7" />
                                        <path d="M16 6h3v3" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 id="financeSummaryTitle">Rincian akumulasi</h3>
                                    <p>Total catatan keluarga</p>
                                </div>
                            </div>

                            <dl class="info-list">
                                <div class="info-item">
                                    <dt><span class="info-dot info-dot--income" aria-hidden="true"></span>Pemasukan</dt>
                                    <dd id="infoPemasukan">Memuat...</dd>
                                </div>
                                <div class="info-item">
                                    <dt><span class="info-dot info-dot--expense" aria-hidden="true"></span>Pengeluaran</dt>
                                    <dd id="infoPengeluaran">Memuat...</dd>
                                </div>
                                <div class="info-item">
                                    <dt><span class="info-dot info-dot--saving" aria-hidden="true"></span>Setoran tabungan</dt>
                                    <dd id="infoSetoran">Memuat...</dd>
                                </div>
                                <div class="info-item info-item--balance">
                                    <dt>Saldo</dt>
                                    <dd id="infoSaldo">Memuat...</dd>
                                </div>
                            </dl>
                        </aside>
                    </div>
                </div>
            </section>
        </div>
    </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
