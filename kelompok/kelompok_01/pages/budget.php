<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Silakan login terlebih dahulu.';
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Anggaran';

$idKeluarga = $_SESSION['user']['id_keluarga'];
$bulanSekarang = (int) date('n');
$tahunSekarang = (int) date('Y');

$stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE jenis = 'pengeluaran' ORDER BY nama_kategori ASC");
$stmt->execute();
$kategori = $stmt->fetchAll();

$pesanSukses = $_SESSION['success'] ?? '';
$pesanError = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaveSmart - Anggaran</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/budget.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>

<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="budget-content">

            <section class="welcome-section">
                <div>
                    <h1>Anggaran keluarga</h1>
                    <p>Atur batas pengeluaran keluarga agar keuangan tetap terkontrol.</p>
                </div>

                <div class="date-label">
                    <?= $namaBulan[$bulanSekarang] . ' ' . $tahunSekarang ?>
                </div>
            </section>

            <?php if ($pesanSukses): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($pesanSukses) ?>
                </div>
            <?php endif; ?>

            <?php if ($pesanError): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($pesanError) ?>
                </div>
            <?php endif; ?>

            <section class="budget-filter">
                <div>
                    <label for="filterBulan">Bulan</label>
                    <select id="filterBulan">
                        <?php foreach ($namaBulan as $nomor => $nama): ?>
                            <option value="<?= $nomor ?>" <?= $nomor === $bulanSekarang ? 'selected' : '' ?>>
                                <?= $nama ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filterTahun">Tahun</label>
                    <select id="filterTahun">
                        <?php for ($tahun = $tahunSekarang - 2; $tahun <= $tahunSekarang + 2; $tahun++): ?>
                            <option value="<?= $tahun ?>" <?= $tahun === $tahunSekarang ? 'selected' : '' ?>>
                                <?= $tahun ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </section>

            <section class="summary-grid">

                <div class="summary-card">
                    <div class="summary-icon balance">▤</div>
                    <p>Total Anggaran</p>
                    <h3 id="totalAnggaran">Rp 0</h3>
                    <small>Batas pengeluaran bulan ini</small>
                </div>

                <div class="summary-card">
                    <div class="summary-icon expense">↗</div>
                    <p>Total Terpakai</p>
                    <h3 id="totalTerpakai">Rp 0</h3>
                    <small>Total pengeluaran aktual</small>
                </div>

                <div class="summary-card">
                    <div class="summary-icon saving">◎</div>
                    <p>Sisa Anggaran</p>
                    <h3 id="sisaAnggaran">Rp 0</h3>
                    <small>Anggaran yang masih tersedia</small>
                </div>

                <div class="summary-card">
                    <div class="summary-icon income">%</div>
                    <p>Penggunaan</p>
                    <h3 id="persenPenggunaan">0%</h3>
                    <small>Persentase anggaran terpakai</small>
                </div>

            </section>

            <section class="budget-layout">

                <div class="budget-panel form-panel">

                    <div class="panel-heading">
                        <h3>Tambah Anggaran</h3>
                        <p>Tentukan batas pengeluaran berdasarkan kategori.</p>
                    </div>

                    <form action="../backend/anggaran/anggaran_process.php" method="POST" id="formAnggaran">

                        <input type="hidden" name="aksi" value="simpan">

                        <div class="form-group">
                            <label for="kategori">Kategori Pengeluaran</label>
                            <select name="id_kategori" id="kategori" required>
                                <option value="">Pilih kategori</option>
                                <?php foreach ($kategori as $item): ?>
                                    <option value="<?= $item['id_kategori'] ?>">
                                        <?= htmlspecialchars($item['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jumlah">Jumlah Anggaran</label>
                            <input type="number" name="jumlah" id="jumlah" min="1" placeholder="Contoh: 500000" required>
                        </div>

                        <input type="hidden" name="bulan" id="inputBulan" value="<?= $bulanSekarang ?>">
                        <input type="hidden" name="tahun" id="inputTahun" value="<?= $tahunSekarang ?>">

                        <button type="submit" class="btn-budget">
                            + Simpan Anggaran
                        </button>

                    </form>

                </div>

                <div class="budget-panel progress-panel">

                    <div class="panel-heading">
                        <h3>Penggunaan Anggaran</h3>
                        <p>Pantau penggunaan anggaran keluarga.</p>
                    </div>

                    <div id="progressContainer">
                        <div class="empty-state">
                            <div class="empty-icon">▤</div>
                            <h4>Belum Ada Anggaran</h4>
                            <p>Tambahkan anggaran untuk melihat progres penggunaannya.</p>
                        </div>
                    </div>

                </div>

            </section>

            <section class="budget-panel budget-list-panel">

                <div class="panel-heading">
                    <h3>Daftar Anggaran</h3>
                    <p>Rincian anggaran berdasarkan kategori pengeluaran.</p>
                </div>

                <div class="table-responsive">
                    <table class="budget-table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Anggaran</th>
                                <th>Terpakai</th>
                                <th>Sisa</th>
                                <th>Progres</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="daftarAnggaran">
                            <tr>
                                <td colspan="6" class="table-empty">Memuat data anggaran...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </section>

        </div>

    </main>

</div>

<script>
    window.budgetConfig = {
        endpoint: '../backend/anggaran/anggaran_process.php',
        bulan: <?= $bulanSekarang ?>,
        tahun: <?= $tahunSekarang ?>
    };
</script>

<script src="../assets/js/budget.js"></script>
<script src="../assets/js/script.js"></script>

</body>
</html>