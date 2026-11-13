<?php
require_once '../config/database.php';
require_once '../includes/functions.php';


wajibAdmin();

$q_total = $pdo->query("SELECT COUNT(*) FROM laporan");
$total_laporan = $q_total->fetchColumn();

$q_menunggu = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'menunggu'");
$total_menunggu = $q_menunggu->fetchColumn();

$q_diproses = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diproses'");
$total_diproses = $q_diproses->fetchColumn();

$q_selesai = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'selesai'");
$total_selesai = $q_selesai->fetchColumn();

$q_pengaduan = $pdo->query("SELECT COUNT(*) FROM laporan WHERE jenis = 'pengaduan'");
$total_pengaduan = $q_pengaduan->fetchColumn();

$q_aspirasi = $pdo->query("SELECT COUNT(*) FROM laporan WHERE jenis = 'aspirasi'");
$total_aspirasi = $q_aspirasi->fetchColumn();

$persen_pengaduan = 0;
$persen_aspirasi = 0;

if ($total_laporan > 0) {
    $persen_pengaduan = round(($total_pengaduan / $total_laporan) * 100);
    $persen_aspirasi = round(($total_aspirasi / $total_laporan) * 100);
}

$q_kategori = $pdo->query("SELECT kategori.nama_kategori, COUNT(laporan.id) AS total 
                           FROM kategori 
                           LEFT JOIN laporan ON kategori.id = laporan.kategori_id 
                           GROUP BY kategori.id");
$data_kategori = $q_kategori->fetchAll(PDO::FETCH_ASSOC);

$q_terbaru = $pdo->query("SELECT laporan.*, users.nama AS nama_pelapor 
                          FROM laporan 
                          JOIN users ON laporan.user_id = users.id 
                          ORDER BY laporan.created_at DESC LIMIT 5");
$list_terbaru = $q_terbaru->fetchAll(PDO::FETCH_ASSOC);

$title = "Dashboard Admin - Aspiraku";
include '../includes/header.php';
?>

<main class="container" style="padding: 30px 20px;">
    
    <div style="margin-bottom: 25px;">
        <h1 style="color: #1E3B2F; font-size: 26px; margin-bottom: 6px;">Dashboard Admin</h1>
        <p style="color: #5F6B4E; font-size: 15px;">
            Selamat datang, <strong><?= sanitize($_SESSION['nama'] ?? 'Admin') ?></strong>! Berikut adalah ringkasan pengaduan dan aspirasi kampus saat ini.
        </p>
    </div>

    <!-- Tombol menu admin -->
    <div style="margin-bottom: 25px;">
        <a href="kelola.php" class="btn" style="background: #1E3B2F; color: #F5F0E6; padding: 10px 18px; border-radius: 6px; text-decoration: none; margin-right: 8px;">
            Kelola Semua Laporan &rarr;
        </a>
        <a href="kategori.php" class="btn" style="background: transparent; color: #1E3B2F; border: 1px solid #7F8C6A; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
            Kelola Kategori
        </a>
    </div>

    <!-- Kotak Angka Statistik -->
    <h3 style="color: #1E3B2F; margin-bottom: 15px; font-size: 18px;">Statistik Laporan</h3>
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
        
        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px; text-align: center;">
            <div style="font-size: 32px; font-weight: bold; color: #1E3B2F; margin-bottom: 4px;">
                <?= $total_laporan ?>
            </div>
            <div style="font-size: 12px; font-weight: bold; color: #5F6B4E;">TOTAL LAPORAN</div>
        </div>

        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px; text-align: center;">
            <div style="font-size: 32px; font-weight: bold; color: #6B5420; margin-bottom: 4px;">
                <?= $total_menunggu ?>
            </div>
            <div style="font-size: 12px; font-weight: bold; color: #5F6B4E;">MENUNGGU</div>
        </div>

        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px; text-align: center;">
            <div style="font-size: 32px; font-weight: bold; color: #34402A; margin-bottom: 4px;">
                <?= $total_diproses ?>
            </div>
            <div style="font-size: 12px; font-weight: bold; color: #5F6B4E;">DIPROSES</div>
        </div>

        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px; text-align: center;">
            <div style="font-size: 32px; font-weight: bold; color: #1E3B2F; margin-bottom: 4px;">
                <?= $total_selesai ?>
            </div>
            <div style="font-size: 12px; font-weight: bold; color: #5F6B4E;">SELESAI</div>
        </div>

    </div>

    <!-- Grafik Batang -->
    <h3 style="color: #1E3B2F; margin-bottom: 15px; font-size: 18px;">Grafik Laporan</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Jenis Laporan -->
        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px;">
            <h4 style="color: #1E3B2F; margin-bottom: 15px; font-size: 15px;">Perbandingan Jenis Laporan</h4>
            
            <!-- Pengaduan -->
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                    <span>Pengaduan (<?= $total_pengaduan ?>)</span>
                    <strong><?= $persen_pengaduan ?>%</strong>
                </div>
                <div style="background: #EEE5D0; border-radius: 5px; height: 14px;">
                    <div style="background: #8B2E2E; height: 14px; width: <?= $persen_pengaduan ?>%; border-radius: 5px;"></div>
                </div>
            </div>

            <!-- Aspirasi -->
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                    <span>Aspirasi (<?= $total_aspirasi ?>)</span>
                    <strong><?= $persen_aspirasi ?>%</strong>
                </div>
                <div style="background: #EEE5D0; border-radius: 5px; height: 14px;">
                    <div style="background: #7F8C6A; height: 14px; width: <?= $persen_aspirasi ?>%; border-radius: 5px;"></div>
                </div>
            </div>
        </div>

        <!-- Kategori -->
        <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 20px;">
            <h4 style="color: #1E3B2F; margin-bottom: 15px; font-size: 15px;">Laporan Berdasarkan Kategori</h4>
            
            <?php if (empty($data_kategori)): ?>
                <p style="color: #5F6B4E; font-size: 13px;">Belum ada data kategori.</p>
            <?php else: ?>
                <?php foreach ($data_kategori as $kat): ?>
                    <?php 
                        $persen_kat = 0;
                        if ($total_laporan > 0) {
                            $persen_kat = round(($kat['total'] / $total_laporan) * 100);
                        }
                    ?>
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span><?= sanitize($kat['nama_kategori']) ?> (<?= $kat['total'] ?>)</span>
                            <strong><?= $persen_kat ?>%</strong>
                        </div>
                        <div style="background: #EEE5D0; border-radius: 5px; height: 10px;">
                            <div style="background: #1E3B2F; height: 10px; width: <?= $persen_kat ?>%; border-radius: 5px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <h3 style="color: #1E3B2F; margin-bottom: 15px; font-size: 18px;">5 Laporan Terbaru</h3>
    <div class="table-responsive" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 15px; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 2px solid #DDD5C0; color: #1E3B2F;">
                    <th style="padding: 10px 8px;">No</th>
                    <th style="padding: 10px 8px;">Pelapor</th>
                    <th style="padding: 10px 8px;">Judul Laporan</th>
                    <th style="padding: 10px 8px;">Jenis</th>
                    <th style="padding: 10px 8px;">Status</th>
                    <th style="padding: 10px 8px;">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_terbaru)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #5F6B4E;">Belum ada laporan yang masuk.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list_terbaru as $i => $row): ?>
                        <tr style="border-bottom: 1px solid #EEE5D0;">
                            <td style="padding: 10px 8px;"><?= $i + 1 ?></td>
                            <td style="padding: 10px 8px; font-weight: 500; color: #1E3B2F;">
                                <?= sanitize($row['nama_pelapor']) ?>
                            </td>
                            <td style="padding: 10px 8px;">
                                <?= sanitize($row['judul']) ?>
                            </td>
                            <td style="padding: 10px 8px;">
                                <?= ucfirst(sanitize($row['jenis'])) ?>
                            </td>
                            <td style="padding: 10px 8px;">
                                <?php if (function_exists('badgeStatus')): ?>
                                    <?= badgeStatus($row['status']) ?>
                                <?php else: ?>
                                    <span class="badge badge-<?= sanitize($row['status']) ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #DCE2D0; color: #1E3B2F;">
                                        <?= ucfirst(sanitize($row['status'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 8px; color: #5F6B4E; white-space: nowrap;">
                                <?= sanitize($row['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<?php include '../includes/footer.php'; ?>