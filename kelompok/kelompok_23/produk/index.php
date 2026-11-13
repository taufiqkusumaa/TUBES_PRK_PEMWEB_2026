<?php
session_start();

require_once __DIR__ . '/../includes/role_guard.php';
requireRole(['Admin', 'Kasir']);

require_once __DIR__ . '/../config/database.php';

$keyword = trim($_GET['cari'] ?? '');

$sql = "
    SELECT products.*, categories.name AS nama_kategori
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    WHERE products.name LIKE ?
    ORDER BY products.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Terjadi kesalahan database: " . $conn->error);
}

$searchKeyword = "%{$keyword}%";
$stmt->bind_param("s", $searchKeyword);
$stmt->execute();

$query = $stmt->get_result();

$pageTitle = 'Data Produk';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helper.php';
?>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="app-layout d-flex flex-grow-1">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Produk</h3>
                <p class="text-muted mb-0">
                    Kelola dan pantau seluruh produk FEBMart.
                </p>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>

            <?php
            $statusMessages = [
                'berhasil' => 'Produk baru berhasil ditambahkan!',
                'edit_berhasil' => 'Produk berhasil diperbarui!',
                'hapus_berhasil' => 'Produk berhasil dihapus!'
            ];

            $status = $_GET['status'];
            ?>

            <?php if (isset($statusMessages[$status])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($statusMessages[$status]); ?>

                    <button type="button" class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                    <div>
                        <h5 class="fw-bold mb-1">Daftar Produk</h5>
                        <p class="text-muted small mb-0">
                            Informasi seluruh produk FEBMart.
                        </p>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2">

                        <span class="badge bg-light text-dark border p-2">
                            <i class="bi bi-box-seam me-1"></i>
                            Total: <?= $query->num_rows; ?> Produk
                        </span>

                        <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
                            <a href="tambah.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>
                                Tambah Produk
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                <form method="GET" class="mb-4">

                    <div class="input-group" style="max-width: 420px;">

                        <input
                            type="text"
                            name="cari"
                            class="form-control"
                            placeholder="Cari nama produk..."
                            value="<?= htmlspecialchars($keyword); ?>"
                        >

                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-search me-1"></i>
                            Cari
                        </button>

                        <a href="index.php" class="btn btn-outline-secondary">
                            Reset
                        </a>

                    </div>

                </form>

                <div class="table-responsive">

                    <table class="table table-hover table-bordered align-middle">

                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Gambar</th>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($query->num_rows > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($data = $query->fetch_assoc()): ?>

                                    <tr>

                                        <td class="text-center">
                                            <?= $no++; ?>
                                        </td>

                                        <td class="text-center">

                                            <?php if (!empty($data['image'])): ?>

                                                <img
                                                    src="../assets/img/<?= htmlspecialchars($data['image']); ?>"
                                                    alt="Gambar produk"
                                                    width="50"
                                                    height="50"
                                                    style="object-fit: cover; border-radius: 8px;"
                                                >

                                            <?php else: ?>

                                                <span class="text-muted small">
                                                    <i class="bi bi-image"></i>
                                                    No Image
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= htmlspecialchars($data['sku']); ?>
                                        </td>

                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($data['name']); ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-light text-primary border">
                                                <?= htmlspecialchars($data['nama_kategori'] ?? '-'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= formatRupiah($data['price']); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($data['stock']); ?>
                                        </td>

                                        <td class="text-center">

                                            <?php if (strtolower($_SESSION['role']) === 'admin'): ?>

                                                <div class="d-flex justify-content-center gap-1 flex-wrap">

                                                    <a
                                                        href="edit.php?id=<?= (int) $data['id']; ?>"
                                                        class="btn btn-warning btn-sm"
                                                        title="Edit produk"
                                                    >
                                                        <i class="bi bi-pencil-square"></i>
                                                        Edit
                                                    </a>

                                                    <a
                                                        href="hapus.php?id=<?= (int) $data['id']; ?>"
                                                        class="btn btn-danger btn-sm"
                                                        data-confirm="Yakin ingin menghapus produk ini?"
                                                        title="Hapus produk"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                        Hapus
                                                    </a>

                                                </div>

                                            <?php else: ?>

                                                <span class="text-muted small">
                                                    Tidak ada aksi
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>

                                        <span class="text-muted">
                                            <?= $keyword !== ''
                                                ? 'Produk yang kamu cari tidak ditemukan.'
                                                : 'Belum ada data produk.'; ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="text-muted small mt-3">
                    Menampilkan <?= $query->num_rows; ?> data produk.
                </div>

            </div>
        </div>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
$stmt->close();
$conn->close();
?>