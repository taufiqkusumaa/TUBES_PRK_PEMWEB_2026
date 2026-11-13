<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Silakan login terlebih dahulu.';
    header('Location: ../login.php');
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Pesan dari backend (flash message)
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle = 'Pemasukan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SaveSmart - Pemasukan</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/transaksi.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>

<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="transaction-content" data-jenis="pemasukan">

            <section class="welcome-section">
                <div>
                    <h1>Pemasukan</h1>
                    <p>Catat dan pantau seluruh pemasukan keuangan keluarga.</p>
                </div>
            </section>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Ringkasan -->
            <section class="transaction-summary">

                <div class="summary-card">
                    <div class="summary-icon income">↙</div>
                    <p>Total Pemasukan</p>
                    <h3 id="totalBulanIni">Rp 0</h3>
                    <small>Bulan ini</small>
                </div>

                <div class="summary-card">
                    <div class="summary-icon balance">▣</div>
                    <p>Jumlah Transaksi</p>
                    <h3 id="jumlahTransaksi">0</h3>
                    <small>Transaksi tercatat</small>
                </div>

            </section>

            <!-- Form (dipakai untuk tambah & ubah) -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3 id="formTitle">Tambah pemasukan</h3>
                    <p>Masukkan informasi pemasukan keluarga.</p>
                </div>

                <form id="transaksiForm"
                      action="../backend/transaksi/pemasukan_process.php"
                      method="POST">

                    <input type="hidden" name="aksi" id="formAksi" value="tambah">
                    <input type="hidden" name="id" id="formId" value="">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="id_kategori" required>
                                <option value="">Memuat kategori...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jumlah">Nominal</label>
                            <input type="number" id="jumlah" name="jumlah"
                                   placeholder="Contoh: 5000000"
                                   min="1" step="any" required>
                        </div>

                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="keterangan">Keterangan</label>
                            <textarea id="keterangan" name="keterangan"
                                      placeholder="Contoh: Gaji bulanan"
                                      maxlength="255" rows="3"></textarea>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-reset">Reset</button>
                        <button type="submit" class="btn-primary" id="btnSubmit">
                            + Simpan Pemasukan
                        </button>
                    </div>

                </form>

            </section>

            <!-- Riwayat -->
            <section class="transaction-panel">

                <div class="panel-heading">
                    <h3>Riwayat Pemasukan</h3>
                    <p>Daftar pemasukan yang telah dicatat.</p>
                </div>

                <div class="table-wrapper">
                    <table class="transaction-table">

                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Dicatat oleh</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="transaksiTable">
                            <tr>
                                <td colspan="6" class="table-empty">Memuat data...</td>
                            </tr>
                        </tbody>

                    </table>
                </div>

            </section>

            <!-- Form hapus (dipakai semua baris) -->
            <form id="formHapus"
                  action="../backend/transaksi/pemasukan_process.php"
                  method="POST" hidden>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hapusId">
            </form>

        </div>

    </main>
</div>

<script src="../assets/js/script.js"></script>
<script src="../assets/js/transaksi.js"></script>

</body>
</html>