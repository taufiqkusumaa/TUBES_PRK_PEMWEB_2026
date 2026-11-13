<?php
include '../../config/koneksi.php';
include '../../views/header.php';

// Proteksi tambahan: pastikan hanya admin yang bisa mengakses halaman ini
$cek_role = strtolower(trim($_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '')));
if ($cek_role !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='../../index.php';</script>";
    exit;
}

// Filter berdasarkan Role
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';
$allowed_roles = ['admin', 'peternak', 'dokter'];
$where = "";

if (in_array($filter_role, $allowed_roles, true)) {
    $safe_role = mysqli_real_escape_string($koneksi, $filter_role);
    $where = "WHERE role = '$safe_role'";
}

$query_str = "SELECT * FROM users $where ORDER BY id_user DESC";
$query = mysqli_query($koneksi, $query_str);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>Data Pengguna (User)</h2>
    <a href="tambah.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Tambah User</a>
</div>

<!-- Filter Role -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">Filter Role:</label>
            </div>
            <div class="col-auto">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Role --</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="peternak" <?= $filter_role === 'peternak' ? 'selected' : ''; ?>>Peternak</option>
                    <option value="dokter" <?= $filter_role === 'dokter' ? 'selected' : ''; ?>>Dokter</option>
                </select>
            </div>
            <?php if ($filter_role !== ''): ?>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th class="text-center text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (mysqli_num_rows($query) > 0):
                        while ($row = mysqli_fetch_assoc($query)): 
                            $badge_role = 'bg-secondary';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($row['username']); ?></strong></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><span class="badge <?= $badge_role; ?>"><?= ucfirst(htmlspecialchars($row['role'])); ?></span></td>
                        <td><?= !empty($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '-'; ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="edit.php?id=<?= (int)$row['id_user']; ?>" class="btn btn-outline-success btn-sm" title="Edit">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                <a href="hapus.php?id=<?= (int)$row['id_user']; ?>" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data user. Klik tombol tambah di atas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>