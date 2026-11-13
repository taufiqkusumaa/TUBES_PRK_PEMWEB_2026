<?php
require_once '../includes/header.php';
require_once '../config/koneksi.php';

// hanya admin yang bisa mengakses halaman ini
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$hari_ini = date('Y-m-d');
$tanggal_filter = (isset($_GET['tanggal']) && $_GET['tanggal'] !== '') ? $_GET['tanggal'] : $hari_ini;
$dokter_filter = isset($_GET['dokter_id']) ? (int) $_GET['dokter_id'] : 0;

// Daftar dokter yang memiliki antrean pada tanggal terpilih (untuk dropdown)
$stmtDokter = $pdo->prepare(
    "SELECT d.id, d.nama, d.spesialis, COUNT(*) AS jumlah
     FROM antrian a
     JOIN dokter d ON d.id = a.dokter_id
     WHERE a.tanggal = :tanggal
     GROUP BY d.id, d.nama, d.spesialis
     ORDER BY d.nama ASC"
);
$stmtDokter->execute(['tanggal' => $tanggal_filter]);
$dokterBerantrian = $stmtDokter->fetchAll(PDO::FETCH_ASSOC);

// Tentukan dokter yang sedang dipilih
$dokterTerpilih = null;
foreach ($dokterBerantrian as $dk) {
    if ((int) $dk['id'] === $dokter_filter) {
        $dokterTerpilih = $dk;
        break;
    }
}
if ($dokterTerpilih === null && !empty($dokterBerantrian)) {
    $dokterTerpilih = $dokterBerantrian[0];
}

// Ambil antrean hanya untuk dokter terpilih
$antrian_list = [];
$sedangDipanggil = null;

if ($dokterTerpilih !== null) {
    $stmt = $pdo->prepare(
        "SELECT a.id, a.nomor AS nomor_antrian, a.status, u.nama AS nama_pasien
         FROM antrian a
         JOIN users u ON a.user_id = u.id
         WHERE a.dokter_id = :dokter AND a.tanggal = :tanggal
         ORDER BY a.nomor ASC"
    );
    $stmt->execute([
        'dokter'  => $dokterTerpilih['id'],
        'tanggal' => $tanggal_filter,
    ]);
    $antrian_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cari antrean yang sedang berjalan (status dipanggil)
    foreach ($antrian_list as $r) {
        if ($r['status'] === 'dipanggil') {
            $sedangDipanggil = $r;
            break;
        }
    }
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2 small">
        <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-underline fw-medium" style="color: #0f766e;">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Daftar Antrian</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-primary mb-2">Daftar Antrian Pasien</h2>
        <p class="text-muted small mb-0">Pilih dokter pada dropdown, lalu panggil atau selesaikan antrean pasien.</p>
    </div>
    <span class="badge px-3 py-2 rounded-pill small fw-semibold" style="background-color: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4;">
        <?= ($tanggal_filter === $hari_ini) ? 'Hari Ini: ' : 'Tanggal: ' ?><?= date('d M Y', strtotime($tanggal_filter)); ?>
    </span>
</div>

<!-- Menampilkan Pesan Sukses / Error dari Backend -->
<?php if (isset($_SESSION['flash_pesan'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_tipe']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= htmlspecialchars($_SESSION['flash_pesan']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
    // Hapus pesan setelah ditampilkan
    unset($_SESSION['flash_tipe'], $_SESSION['flash_pesan']);
    ?>
<?php endif; ?>

<?php if (empty($dokterBerantrian)): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 text-center text-muted">
            <?= ($tanggal_filter === $hari_ini) ? 'Belum ada pasien yang mengambil antrian hari ini.' : 'Tidak ada riwayat antrian pada tanggal tersebut.' ?>
        </div>
    </div>
<?php else: ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Dropdown Button Nama Dokter -->
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle px-3" type="button" id="dropdownDokter" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($dokterTerpilih['nama']) ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownDokter" style="max-height: 320px; overflow-y: auto;">
                            <?php foreach ($dokterBerantrian as $dk): ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center gap-3 <?= ((int) $dk['id'] === (int) $dokterTerpilih['id']) ? 'active' : '' ?>"
                                       href="?tanggal=<?= urlencode($tanggal_filter) ?>&dokter_id=<?= (int) $dk['id'] ?>">
                                        <span><?= htmlspecialchars($dk['nama']) ?></span>
                                        <span class="badge <?= ((int) $dk['id'] === (int) $dokterTerpilih['id']) ? 'bg-light text-primary' : 'bg-secondary' ?>"><?= (int) $dk['jumlah'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <span class="text-muted small"><?= (int) $dokterTerpilih['jumlah'] ?> antrean &middot; <?= htmlspecialchars($dokterTerpilih['spesialis']) ?></span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <input type="text" id="cari-admin" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Cari nama pasien...">
                    <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
                        <input type="hidden" name="dokter_id" value="<?= (int) $dokterTerpilih['id'] ?>">
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" onchange="this.form.submit()" class="form-control form-control-sm" style="width: 150px; cursor: pointer;" title="Pilih tanggal riwayat antrian">
                        <?php if ($tanggal_filter !== $hari_ini): ?>
                            <a href="antrian_list.php?dokter_id=<?= (int) $dokterTerpilih['id'] ?>" class="btn btn-outline-secondary btn-sm text-nowrap">Hari Ini</a>
                        <?php endif; ?>
                    </form>
                    <select id="filter-status-admin" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">Semua Status</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="dipanggil">Dipanggil</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <!-- Peringatan bila ada pasien yang sedang berjalan -->
            <?php if ($sedangDipanggil !== null): ?>
                <div class="alert alert-warning py-2 mb-3" role="alert">
                    Sedang melayani <strong>#<?= htmlspecialchars($sedangDipanggil['nomor_antrian']) ?> - <?= htmlspecialchars($sedangDipanggil['nama_pasien']) ?></strong>.
                    Selesaikan dulu sebelum memanggil pasien berikutnya.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3" width="15%">No. Antrian</th>
                            <th scope="col" class="py-3" width="45%">Nama Pasien</th>
                            <th scope="col" class="py-3" width="15%">Status</th>
                            <th scope="col" class="py-3 text-center" width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($antrian_list) > 0): ?>
                            <?php foreach ($antrian_list as $row): ?>
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 36px; height: 36px; font-size: 0.9rem; background-color: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4;">
                                            <?= htmlspecialchars($row['nomor_antrian']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pasien']) ?></td>

                                    <td>
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small">Menunggu</span>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">Dipanggil</span>
                                        <?php elseif ($row['status'] === 'selesai'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">Selesai</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ($row['status'] === 'menunggu'): ?>
                                            <?php if ($sedangDipanggil !== null): ?>
                                                <!-- Dikunci: masih ada pasien yang sedang dipanggil -->
                                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" disabled
                                                        title="Selesaikan antrean #<?= htmlspecialchars($sedangDipanggil['nomor_antrian']) ?> terlebih dahulu">
                                                    Panggil
                                                </button>
                                            <?php else: ?>
                                                <form action="antrian_panggil.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm px-3">Panggil</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php elseif ($row['status'] === 'dipanggil'): ?>
                                            <form action="antrian_selesai.php" method="POST" class="d-inline">
                                                <input type="hidden" name="antrian_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>">
                                                <button type="submit" class="btn btn-outline-success btn-sm px-3">Selesai</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3 py-1">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Tidak ada antrean untuk dokter ini pada tanggal tersebut.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="admin.js"></script>
<?php
require_once '../includes/footer.php';
?>
