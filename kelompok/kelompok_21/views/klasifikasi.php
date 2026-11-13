<?php
if (!isset($pdo) || !$pdo) {
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        require_once __DIR__ . '/../includes/db.php';
    } elseif (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
    } elseif (file_exists(__DIR__ . '/../api/db.php')) {
        require_once __DIR__ . '/../api/db.php';
    }
}

$daftarKelas = [];
$daftarMapel = [];
$daftarPeriode = [];
try {
    if (isset($pdo) && $pdo) {
        $stmtKls = $pdo->query("SELECT id_kelas, nama_kelas FROM KELAS ORDER BY id_kelas ASC");
        $daftarKelas = $stmtKls->fetchAll(PDO::FETCH_ASSOC);

        $stmtMapel = $pdo->query("SELECT id_mapel, nama_mapel FROM MATA_PELAJARAN ORDER BY nama_mapel ASC");
        $daftarMapel = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        $stmtPeriode = $pdo->query("SELECT id_periode, tahun_ajaran, semester, status_aktif FROM PERIODE_PENILAIAN ORDER BY status_aktif DESC, tahun_ajaran DESC, semester ASC");
        $daftarPeriode = $stmtPeriode->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
}
?>

<div class="arunika-wrapper">
    <div class="page-header">
        <div>
            <h2>Klasifikasi Belajar Siswa</h2>
            <p>Pengelompokan capaian nilai ujian akhir dan indeks kedisiplinan pengumpulan tugas.</p>
        </div>
        <div class="total-badge" id="badge-total-siswa">Total: 0 Siswa</div>
    </div>

    <div class="filter-box">
        <div class="filter-item">
            <label for="filter-kelas">Kelas:</label>
            <select id="filter-kelas">
                <option value="0">Semua Kelas</option>
                <?php foreach ($daftarKelas as $kls): ?>
                    <option value="<?= htmlspecialchars($kls['id_kelas']) ?>">
                        <?= htmlspecialchars($kls['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter-periode">Periode:</label>
            <select id="filter-periode">
                <option value="0">Semua Periode</option>
                <?php foreach ($daftarPeriode as $prd): ?>
                    <option value="<?= htmlspecialchars($prd['id_periode']) ?>" <?= $prd['status_aktif'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prd['tahun_ajaran'] . ' - ' . $prd['semester']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter-mapel">Mata Pelajaran:</label>
            <select id="filter-mapel">
                <option value="0">Semua Mata Pelajaran</option>
                <?php foreach ($daftarMapel as $mpl): ?>
                    <option value="<?= htmlspecialchars($mpl['id_mapel']) ?>">
                        <?= htmlspecialchars($mpl['nama_mapel']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="input-kkm">KKM Nilai:</label>
            <input type="number" id="input-kkm" value="75" min="0" max="100">
        </div>
        <button type="button" id="btn-terapkan-filter" class="btn-green">Terapkan Filter</button>
    </div>

    <div class="quadrant-layout">
        <div class="quadrant-card q-bakat">
            <div class="q-card-header">
                <h3>Jarang kumpul & nilai tinggi</h3>
                <span class="q-badge" id="count-bakat">0</span>
            </div>
            <p class="q-note">Jarang kumpul tugas, namun nilai ujian tinggi.</p>
            <div class="student-container" id="list-bakat"></div>
        </div>

        <div class="quadrant-card q-bintang">
            <div class="q-card-header">
                <h3>Rajin & nilai tinggi</h3>
                <span class="q-badge" id="count-bintang">0</span>
            </div>
            <p class="q-note">Rajin kerjakan tugas dan nilai ujian sangat baik.</p>
            <div class="student-container" id="list-bintang"></div>
        </div>

        <div class="quadrant-card q-bahaya">
            <div class="q-card-header">
                <h3>Jarang kumpul & nilai rendah</h3>
                <span class="q-badge" id="count-bahaya">0</span>
            </div>
            <p class="q-note">Jarang kerjakan tugas dan nilai ujian rendah.</p>
            <div class="student-container" id="list-bahaya"></div>
        </div>

        <div class="quadrant-card q-butuh">
            <div class="q-card-header">
                <h3>Rajin & nilai rendah</h3>
                <span class="q-badge" id="count-butuh">0</span>
            </div>
            <p class="q-note">Sangat rajin ngumpul tugas, tapi nilai ujian rendah.</p>
            <div class="student-container" id="list-butuh"></div>
        </div>
    </div>
</div>

<dialog id="studentModal" class="student-modal" style="border:none; border-radius:16px; padding:24px; max-width:440px; width:90%; box-shadow:0 12px 32px rgba(0,0,0,0.18);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
        <h3 id="modalNamaSiswa" style="margin:0; font-size:1.25rem; font-weight:700; color:#1a1a1a;">Nama Siswa</h3>
        <button id="btnCloseModal" type="button" style="background:none; border:none; font-size:1.4rem; line-height:1; cursor:pointer; color:#777; padding:0 4px;">&times;</button>
    </div>

    <div style="margin-bottom:16px;">
        <span id="modalBadgeKuadran" class="badge" style="background:#1e5631; color:#fff; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; display:inline-block;">-</span>
    </div>

    <div style="background:#f9f9f9; border-radius:10px; padding:12px 14px; margin-bottom:14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="color:#555; font-size:13px;">Kedisiplinan Tugas:</span>
            <strong id="modalNilaiTugas" style="font-size:14px; color:#111;">0%</strong>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="color:#555; font-size:13px;">Nilai Ujian Akhir:</span>
            <strong id="modalNilaiUjian" style="font-size:14px; color:#111;">0</strong>
        </div>
    </div>

    <div style="background:#f4fbf7; border-left:4px solid #1e5631; border-radius:8px; padding:12px 14px; margin-bottom:20px;">
        <span style="font-size:12px; font-weight:700; color:#1e5631; display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Rekomendasi Sistem</span>
        <p id="modalRekomendasi" style="font-size:13px; color:#2d3748; margin:0; line-height:1.5;">-</p>
    </div>

    <div style="display:flex; justify-content:flex-end;">
        <button id="btnTutupModalFooter" type="button" style="background:#e5e7eb; border:none; padding:8px 18px; border-radius:8px; font-weight:600; font-size:13px; color:#374151; cursor:pointer;">Tutup</button>
    </div>
</dialog>