<?php
require_once '../config/database.php';
require_once 'auth_check.php';

$id_user = $_SESSION['id_user'];
$nama = $_SESSION['nama_lengkap'];

$queryKategori = $pdo->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");
$kategoriList = $queryKategori->fetchAll();

$id_kategori = filter_input(INPUT_GET, 'kategori', FILTER_VALIDATE_INT);

$sql = "
    SELECT
        aspirasi.id_aspirasi,
        aspirasi.judul_aspirasi,
        aspirasi.isi_aspirasi,
        aspirasi.tanggal_kirim,
        aspirasi.status,
        aspirasi.is_anonim,
        kategori.nama_kategori
    FROM aspirasi
    INNER JOIN kategori ON aspirasi.id_kategori = kategori.id_kategori
    WHERE aspirasi.id_user = :id_user
";

$params = [':id_user' => $id_user];

if ($id_kategori !== false && $id_kategori !== null && $id_kategori > 0) {
    $sql .= " AND aspirasi.id_kategori = :id_kategori";
    $params[':id_kategori'] = $id_kategori;
}

$sql .= " ORDER BY aspirasi.tanggal_kirim DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$aspirasiList = $stmt->fetchAll();

$queryJumlah = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'menunggu')  AS pending,
        SUM(status = 'ditolak')   AS ditolak,
        SUM(status = 'publikasi') AS publikasi
    FROM aspirasi
    WHERE id_user = :id_user
");
$queryJumlah->execute([':id_user' => $id_user]);
$jumlah = $queryJumlah->fetch();

function e($data) {
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - KabarSi FISIP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F6F2E9;
            color: #141414;
            min-height: 100vh;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
        }
        a { text-decoration: none; color: inherit; }

        .navbar {
            background: #141414;
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-logo {
            background: #FF5A1F;
            padding: 6px 10px;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: #ffffff; font-weight: 800; font-size: 14px;
        }
        .brand-title { font-size: 18px; font-weight: 800; color: #ffffff; }
        .brand-title span { color: #FF5A1F; }

        .nav-actions { display: flex; align-items: center; gap: 16px; }
        .nav-links { display: flex; align-items: center; gap: 20px; font-size: 12px; font-weight: 700; letter-spacing: 0.05em; }
        .nav-links a:hover { color: #FF5A1F; }
        .btn-nav-dash {
            background: #FF5A1F;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }
        .btn-nav-logout {
            background: #EF4444;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }
        .btn-nav-logout:hover { background: #dc2626; }

        .main-container {
            max-width: 1152px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 20px;
            flex: 1;
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 36px;
        }
        .badge-pill {
            display: inline-flex; align-items: center; gap: 8px;
            color: #6B655B;
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .welcome-title { font-size: 34px; font-weight: 800; color: #141414; letter-spacing: -0.02em; }
        .welcome-title span { color: #FF5A1F; }
        .welcome-desc { font-size: 14px; color: #57534e; margin-top: 6px; }

        .btn-black {
            background: #141414;
            color: #ffffff;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s ease;
            border: none; cursor: pointer;
        }
        .btn-black:hover {
            background: #292524;
            transform: translateY(-2px);
        }

        .alert-success {
            margin-bottom: 28px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .alert-icon {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #d1fae5; color: #059669;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
        }
        .alert-title { font-size: 14px; font-weight: 700; color: #065f46; }
        .alert-sub { font-size: 12px; color: #059669; margin-top: 2px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 36px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #141414;
            transition: all 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #FF5A1F;
        }
        .stat-top { display: flex; justify-content: space-between; align-items: center; }
        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .stat-tag {
            font-size: 11px; font-weight: 700;
            padding: 4px 12px; border-radius: 9999px;
            text-transform: uppercase;
        }
        .stat-label { font-size: 14px; font-weight: 600; color: #6B655B; margin-top: 18px; }
        .stat-num { font-size: 32px; font-weight: 800; margin-top: 4px; color: #141414; }
        .stat-note { font-size: 12px; color: #8a857b; margin-top: 6px; }

        .bg-or-soft { background: #ffedd5; } .text-or { color: #FF5A1F; }
        .bg-am-soft { background: #fef3c7; } .text-am { color: #d97706; }
        .bg-rd-soft { background: #fee2e2; } .text-rd { color: #dc2626; }
        .bg-em-soft { background: #d1fae5; } .text-em { color: #059669; }

        .filter-box {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #141414;
            padding: 24px;
            margin-bottom: 36px;
        }
        .filter-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .filter-form { display: flex; gap: 12px; flex-wrap: wrap; }
        .filter-select {
            flex: 1;
            min-width: 200px;
            border: 1px solid #141414;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            color: #141414;
            background: #ffffff;
            font-family: inherit;
        }
        .filter-select:focus {
            outline: none;
            border-color: #FF5A1F;
        }
        .btn-filter {
            background: #141414; color: #ffffff;
            padding: 12px 26px; border-radius: 8px;
            font-size: 13px; font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none; cursor: pointer; font-family: inherit;
            transition: all 0.2s ease;
        }
        .btn-filter:hover { background: #292524; }
        .btn-reset {
            background: #ffffff; color: #141414;
            border: 1px solid #141414;
            padding: 12px 24px; border-radius: 8px;
            font-size: 13px; font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: center;
        }
        .btn-reset:hover { background: #F6F2E9; }

        .history-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px;
        }
        .history-title { font-size: 28px; font-weight: 800; color: #141414; letter-spacing: -0.02em; }
        .history-title span { color: #78716c; font-weight: 600; }
        .history-sub { font-size: 14px; color: #6B655B; margin-top: 4px; }
        .count-badge {
            font-size: 12px; font-weight: 700;
            color: #141414; background: #ffffff;
            padding: 8px 14px; border-radius: 8px;
            border: 1px solid #141414;
        }

        .history-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .asp-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #141414;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .asp-card:hover {
            border-color: #FF5A1F;
        }
        .asp-card:hover .asp-title {
            color: #FF5A1F;
        }
        .asp-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid #e7e2d8;
            margin-bottom: 18px;
        }
        .asp-meta-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .cat-badge {
            background: #ffffff;
            color: #141414;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #141414;
        }
        .anon-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #57534e;
            font-weight: 600;
        }
        .asp-date { font-size: 12px; color: #78716c; font-weight: 500; }
        .asp-title {
            font-size: 20px;
            font-weight: 800;
            color: #141414;
            margin-bottom: 8px;
            transition: color 0.2s ease;
        }
        .asp-body {
            font-size: 14px;
            color: #57534e;
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-line;
        }
        .asp-bottom {
            border-top: 1px solid #e7e2d8;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid;
        }
        .st-menunggu { background: #fef3c7; color: #b45309; border-color: #f59e0b; }
        .st-ditinjau { background: #ffedd5; color: #c2410c; border-color: #f97316; }
        .st-ditolak  { background: #fee2e2; color: #b91c1c; border-color: #ef4444; }
        .st-selesai  { background: #d1fae5; color: #047857; border-color: #10b981; }

        .empty-state {
            background: #ffffff;
            border: 1px solid #141414;
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
        }
        .empty-icon {
            width: 72px; height: 72px;
            background: #F6F2E9;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
        }
        .empty-title { font-size: 18px; font-weight: 800; color: #141414; margin-bottom: 8px; }
        .empty-desc { font-size: 14px; color: #6B655B; max-width: 420px; margin: 0 auto 28px; }

        .footer {
            background: #141414;
            color: #ffffff;
            margin-top: 64px;
            padding: 36px 24px;
        }
        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .footer-left { display: flex; flex-direction: column; gap: 8px; }
        .footer-brand-row { display: flex; align-items: center; gap: 10px; }
        .footer-logo {
            background: #FF5A1F;
            padding: 4px 8px;
            border-radius: 5px;
            color: #ffffff;
            font-weight: 800;
            font-size: 12px;
        }
        .footer-brand { font-size: 16px; font-weight: 800; color: #ffffff; }
        .footer-sub { font-size: 12px; color: #a8a29e; }
        .footer-right { font-size: 12px; font-weight: 700; color: #ffffff; }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
        }
        @media (max-width: 640px) {
            .stats-grid, .history-grid { grid-template-columns: 1fr; }
            .footer-container { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">
                <div class="brand-logo">KS</div>
                <div>
                    <h1 class="brand-title">KabarSi <span>FISIP</span></h1>
                </div>
            </a>
            <div class="nav-actions">
                <div class="nav-links">
                    <a href="../index.php">BERANDA</a>
                    <a href="../index.php#berita">BERITA</a>
                    <a href="../index.php#aspirasi">ASPIRASI</a>
                </div>
                <a href="dashboard.php" class="btn-nav-dash">DASHBOARD SAYA</a>
                <a href="../auth/logout.php" class="btn-nav-logout">LOGOUT</a>
            </div>
        </div>
    </nav>

    <main class="main-container">

        <div class="welcome-header">
            <div>
                <div class="badge-pill">PORTAL RESMI FISIP UNILA</div>
                <h2 class="welcome-title">Halo, <span><?= e($nama); ?>!</span></h2>
                <p class="welcome-desc">Selamat datang kembali di KabarSi FISIP. Yuk, pantau perkembangan aspirasi kamu!</p>
            </div>
            <a href="aspirasi_tambah.php" class="btn-black">
                <span>+</span> KIRIM ASPIRASI
            </a>
        </div>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil'): ?>
            <div class="alert-success">
                <div class="alert-icon">✓</div>
                <div>
                    <p class="alert-title">Aspirasi berhasil dikirim!</p>
                    <p class="alert-sub">Terima kasih telah menyampaikan aspirasi melalui KabarSi FISIP.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon bg-or-soft">📋</div>
                    <span class="stat-tag bg-or-soft text-or">Keseluruhan</span>
                </div>
                <p class="stat-label">Total Aspirasi</p>
                <h3 class="stat-num"><?= (int) ($jumlah['total'] ?? 0); ?></h3>
                <p class="stat-note">Aspirasi yang telah dikirim</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon bg-am-soft">⏳</div>
                    <span class="stat-tag bg-am-soft text-am">Pending</span>
                </div>
                <p class="stat-label">Pending</p>
                <h3 class="stat-num text-am"><?= (int) ($jumlah['pending'] ?? 0); ?></h3>
                <p class="stat-note">Belum mendapatkan tinjauan</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon bg-rd-soft">❌</div>
                    <span class="stat-tag bg-rd-soft text-rd">Ditolak</span>
                </div>
                <p class="stat-label">Ditolak</p>
                <h3 class="stat-num text-rd"><?= (int) ($jumlah['ditolak'] ?? 0); ?></h3>
                <p class="stat-note">Aspirasi tidak disetujui</p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon bg-em-soft">📢</div>
                    <span class="stat-tag bg-em-soft text-em">Publikasi</span>
                </div>
                <p class="stat-label">Publikasi</p>
                <h3 class="stat-num text-em"><?= (int) ($jumlah['publikasi'] ?? 0); ?></h3>
                <p class="stat-note">Aspirasi dipublikasikan</p>
            </div>
        </div>

        <div class="filter-box">
            <div class="filter-header">
                <div class="stat-icon bg-or-soft" style="width:40px; height:40px; font-size:18px;">🔎</div>
                <div>
                    <h3 style="font-weight:800; font-size:16px; color:#141414;">Filter Aspirasi</h3>
                    <p style="font-size:12px; color:#6B655B;">Temukan aspirasi berdasarkan kategori.</p>
                </div>
            </div>

            <form method="GET" action="dashboard.php" class="filter-form">
                <select name="kategori" class="filter-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $kategori): ?>
                        <option value="<?= (int) $kategori['id_kategori']; ?>" <?= ($id_kategori == $kategori['id_kategori']) ? 'selected' : ''; ?>>
                            <?= e($kategori['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter">Terapkan Filter</button>
                <a href="dashboard.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="history-header">
            <div>
                <h3 class="history-title">Riwayat Aspirasi <span>Saya</span></h3>
                <p class="history-sub">Pantau perkembangan aspirasi yang telah kamu kirim.</p>
            </div>
            <span class="count-badge"><?= count($aspirasiList); ?> Aspirasi</span>
        </div>

        <?php if (count($aspirasiList) > 0): ?>
            <div class="history-grid">
                <?php foreach ($aspirasiList as $aspirasi): ?>
                    <?php
                        $status = strtolower($aspirasi['status']);
                        $stClass = 'st-' . $status;
                    ?>
                    <div class="asp-card">
                        <div>
                            <div class="asp-top">
                                <div class="asp-meta-left">
                                    <span class="cat-badge"><?= e($aspirasi['nama_kategori']); ?></span>
                                    <div class="anon-info">
                                        <?php if ($aspirasi['is_anonim']): ?>
                                            <span>🔒</span> <span>Anonim</span>
                                        <?php else: ?>
                                            <span>🎓</span> <span><?= e($nama); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="asp-date"><?= e(date('d/m/Y', strtotime($aspirasi['tanggal_kirim']))); ?></span>
                            </div>
                            <h4 class="asp-title"><?= e($aspirasi['judul_aspirasi']); ?></h4>
                            <p class="asp-body"><?= e($aspirasi['isi_aspirasi']); ?></p>
                        </div>
                        <div class="asp-bottom">
                            <span style="font-size:12px; font-weight:700; color:#6B655B;">Status Tinjauan</span>
                            <span class="status-badge <?= e($stClass); ?>"><?= e(ucfirst($status)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h4 class="empty-title">Belum Ada Aspirasi</h4>
                <p class="empty-desc">Kamu belum memiliki riwayat aspirasi pada kategori ini. Yuk, mulai sampaikan ide, kritik, atau saranmu melalui KabarSi FISIP!</p>
                <a href="aspirasi_tambah.php" class="btn-black"><span>+</span> KIRIM ASPIRASI PERTAMA</a>
            </div>
        <?php endif; ?>

    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-brand-row">
                    <span class="footer-logo">KS</span>
                    <span class="footer-brand">KabarSi FISIP</span>
                </div>
                <p class="footer-sub">Portal Berita Resmi & Suara Aspirasi Mahasiswa FISIP Universitas Lampung</p>
            </div>
            <div class="footer-right">
                © <?= date('Y'); ?> KabarSi FISIP Universitas Lampung
            </div>
        </div>
    </footer>

</body>
</html>