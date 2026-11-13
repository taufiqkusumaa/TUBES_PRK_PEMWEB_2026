<?php
require_once '../config/database.php';
require_once 'auth_check.php';

$query = $pdo->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");
$kategoriList = $query->fetchAll();

$nama = $_SESSION['nama_lengkap'] ?? 'Mahasiswa';

function e($data) {
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Aspirasi - KabarSi FISIP</title>
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
        .back-link {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 700; color: #141414;
            background: #ffffff;
            border: 1px solid #141414;
            padding: 8px 16px;
            border-radius: 8px;
            margin-bottom: 28px;
        }
        .back-link:hover { border-color: #FF5A1F; color: #FF5A1F; }

        .page-header { margin-bottom: 32px; }
        .badge-pill {
            display: inline-flex; align-items: center; gap: 8px;
            color: #6B655B;
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .page-title { font-size: 34px; font-weight: 800; color: #141414; margin-bottom: 10px; letter-spacing: -0.02em; }
        .page-title span { color: #FF5A1F; }
        .page-desc { font-size: 15px; color: #57534e; max-width: 670px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 28px;
            align-items: start;
        }

        .left-col { display: flex; flex-direction: column; gap: 20px; }
        .banner-card {
            background: #141414;
            border-radius: 16px;
            padding: 28px;
            color: #ffffff;
        }
        .banner-icon {
            width: 48px; height: 48px;
            background: #FF5A1F;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 20px;
        }
        .banner-title { font-size: 20px; font-weight: 800; margin-bottom: 12px; }
        .banner-text { font-size: 14px; color: #d6d3d1; line-height: 1.6; }

        .tips-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #141414;
        }
        .tips-title { font-weight: 800; font-size: 16px; margin-bottom: 20px; color: #141414; }
        .tip-item { display: flex; gap: 12px; margin-bottom: 18px; }
        .tip-item:last-child { margin-bottom: 0; }
        .tip-icon {
            width: 36px; height: 36px; flex-shrink: 0;
            border-radius: 8px; background: #F6F2E9;
            display: flex; align-items: center; justify-content: center;
        }
        .tip-head { font-size: 14px; font-weight: 700; color: #141414; }
        .tip-sub { font-size: 12px; color: #6B655B; margin-top: 2px; }

        .privacy-card {
            background: #ffffff;
            border: 1px solid #141414;
            border-radius: 16px;
            padding: 20px;
            display: flex; gap: 12px;
        }
        .privacy-title { font-size: 14px; font-weight: 800; color: #141414; }
        .privacy-desc { font-size: 12px; color: #57534e; margin-top: 6px; line-height: 1.5; }

        .form-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #141414;
            overflow: hidden;
        }
        .form-header {
            padding: 24px 28px;
            border-bottom: 1px solid #e7e2d8;
            display: flex; align-items: center; gap: 12px;
        }
        .form-header-icon {
            width: 44px; height: 44px;
            border-radius: 12px; background: #F6F2E9;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .form-body { padding: 28px; }
        .form-group { margin-bottom: 24px; }
        .form-label {
            display: block; font-size: 14px; font-weight: 700;
            color: #141414; margin-bottom: 8px;
        }
        .req { color: #ef4444; }
        .form-control {
            width: 100%;
            border: 1px solid #141414;
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 14px;
            color: #141414;
            font-family: inherit;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #FF5A1F;
        }
        .form-hint { font-size: 12px; color: #6B655B; margin-top: 8px; }

        .anon-box {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px;
            background: #F6F2E9;
            border: 1px solid #141414;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 28px;
        }
        .anon-box:hover { border-color: #FF5A1F; }
        .anon-box input { width: 20px; height: 20px; margin-top: 2px; accent-color: #141414; }

        .btn-submit {
            width: 100%;
            background: #141414;
            color: #ffffff;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .btn-submit:hover {
            background: #292524;
            transform: translateY(-2px);
        }

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

        @media (max-width: 900px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
        }
        @media (max-width: 640px) {
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

        <a href="dashboard.php" class="back-link"><span>←</span> Kembali ke Dashboard</a>

        <div class="page-header">
            <div class="badge-pill">RUANG ASPIRASI MAHASISWA</div>
            <h2 class="page-title">Suaramu Berarti. <span>Sampaikan Sekarang!</span></h2>
            <p class="page-desc">Setiap pendapat memiliki arti. Sampaikan ide, kritik, maupun saran untuk menciptakan lingkungan FISIP yang lebih baik bersama KabarSi.</p>
        </div>

        <div class="form-grid">

            <div class="left-col">
                <div class="banner-card">
                    <div class="banner-icon">💬</div>
                    <h3 class="banner-title">Satu Suara, Banyak Perubahan</h3>
                    <p class="banner-text">Aspirasi yang kamu sampaikan menjadi bagian dari langkah untuk membangun lingkungan kampus yang lebih baik.</p>
                </div>

                <div class="tips-card">
                    <h3 class="tips-title">Tips Menyampaikan Aspirasi</h3>
                    <div class="tip-item">
                        <div class="tip-icon">📝</div>
                        <div>
                            <p class="tip-head">Gunakan judul yang jelas</p>
                            <p class="tip-sub">Gambarkan inti aspirasi secara singkat.</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">🎯</div>
                        <div>
                            <p class="tip-head">Sampaikan secara spesifik</p>
                            <p class="tip-sub">Jelaskan permasalahan dan harapanmu.</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">🤝</div>
                        <div>
                            <p class="tip-head">Gunakan bahasa yang sopan</p>
                            <p class="tip-sub">Sampaikan pendapat dengan baik dan santun.</p>
                        </div>
                    </div>
                </div>

                <div class="privacy-card">
                    <div style="font-size:20px;">🔒</div>
                    <div>
                        <h4 class="privacy-title">Privasi Terjaga</h4>
                        <p class="privacy-desc">Kamu dapat memilih opsi anonim. Identitas tetap tercatat di database untuk keperluan pengelolaan admin.</p>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-header">
                    <div class="form-header-icon">📋</div>
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#141414;">Formulir Aspirasi</h3>
                        <p style="font-size:12px; color:#6B655B;">Lengkapi informasi berikut dengan benar.</p>
                    </div>
                </div>

                <form action="aspirasi_simpan.php" method="POST" class="form-body">
                    <div class="form-group">
                        <label class="form-label">Judul Aspirasi <span class="req">*</span></label>
                        <input type="text" name="judul_aspirasi" maxlength="255" required placeholder="Contoh: Perbaikan Fasilitas Ruang Kelas" class="form-control">
                        <p class="form-hint">Buat judul yang singkat dan mudah dipahami.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kategori Aspirasi <span class="req">*</span></label>
                        <select name="id_kategori" required class="form-control">
                            <option value="">Pilih kategori aspirasi</option>
                            <?php foreach ($kategoriList as $kategori): ?>
                                <option value="<?= (int) $kategori['id_kategori']; ?>"><?= e($kategori['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Isi Aspirasi <span class="req">*</span></label>
                        <textarea name="isi_aspirasi" rows="7" required placeholder="Ceritakan aspirasi, kritik, saran, atau pendapatmu secara jelas dan terperinci..." class="form-control" style="resize:vertical;"></textarea>
                        <p class="form-hint">Jelaskan permasalahan dan solusi yang kamu harapkan.</p>
                    </div>

                    <label class="anon-box">
                        <input type="checkbox" name="is_anonim" value="1">
                        <div>
                            <p style="font-size:14px; font-weight:700; color:#141414;">Kirim sebagai Anonim</p>
                            <p style="font-size:12px; color:#57534e; margin-top:4px;">Namamu tidak ditampilkan pada tampilan publik. Identitas tetap tersimpan di database admin.</p>
                        </div>
                    </label>

                    <div style="border-top:1px solid #e7e2d8; padding-top:24px;">
                        <button type="submit" class="btn-submit">KIRIM ASPIRASI</button>
                        <p style="text-align:center; font-size:12px; color:#6B655B; margin-top:16px;">Terima kasih telah berkontribusi untuk lingkungan FISIP.</p>
                    </div>
                </form>
            </div>

        </div>

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