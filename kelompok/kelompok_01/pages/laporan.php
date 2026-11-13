<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Silakan login terlebih dahulu.';
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Laporan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaveSmart - Laporan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/reports.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../components/navbar.php'; ?>

            <div class="reports-content">
                <section class="welcome-section">
                    <div>
                        <h1>Laporan keuangan</h1>
                        <p>Pantau dan analisis kondisi keuangan keluarga Anda.</p>
                    </div>
                </section>

                <section class="report-filter">
                    <div class="filter-heading">
                        <h3>Filter Periode</h3>
                        <p>Pilih rentang tanggal laporan yang ingin ditampilkan.</p>
                    </div>

                    <div class="filter-form">
                        <div class="filter-field">
                            <label for="tanggalDari">Dari Tanggal</label>
                            <input type="date" id="tanggalDari">
                        </div>

                        <div class="filter-field">
                            <label for="tanggalSampai">Sampai Tanggal</label>
                            <input type="date" id="tanggalSampai">
                        </div>

                        <button type="button" id="filterButton">Tampilkan</button>
                    </div>
                </section>

                <section class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-icon income">↙</div>
                        <p>Total Pemasukan</p>
                        <h3 id="totalPemasukan">Rp 0</h3>
                        <small>Periode terpilih</small>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon expense">↗</div>
                        <p>Total Pengeluaran</p>
                        <h3 id="totalPengeluaran">Rp 0</h3>
                        <small>Periode terpilih</small>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon balance">▣</div>
                        <p>Selisih Keuangan</p>
                        <h3 id="saldoLaporan">Rp 0</h3>
                        <small>Pemasukan - Pengeluaran</small>
                    </div>
                </section>

                <section class="report-grid">
                    <div class="report-panel">
                        <div class="panel-heading">
                            <h3>Grafik Keuangan</h3>
                            <p>Perbandingan pemasukan dan pengeluaran</p>
                        </div>
                        <div class="report-chart">
                            <canvas id="reportChart"></canvas>
                        </div>
                    </div>

                    <div class="report-panel">
                        <div class="panel-heading">
                            <h3>Rincian Kategori</h3>
                            <p>Jumlah transaksi berdasarkan kategori</p>
                        </div>
                        <div id="categoryList" class="category-list">
                            <p class="empty-message">Memuat data kategori...</p>
                        </div>
                    </div>
                </section>

                <section class="report-panel transaction-panel">
                    <div class="panel-heading">
                        <h3>Riwayat Transaksi</h3>
                        <p>Daftar transaksi pada periode terpilih</p>
                    </div>

                    <div class="table-wrapper">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody id="transactionList">
                                <tr>
                                    <td colspan="5" class="empty-message">Memuat transaksi...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/reports.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>