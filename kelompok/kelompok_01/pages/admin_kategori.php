<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Manajemen Kategori';
$kategori = [];

try {
    $stmt = $pdo->prepare('CALL sp_kategori_daftar()');
    $stmt->execute();
    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Gagal mengambil data kategori.';
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - SaveSmart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

<div class="app-container">

    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../components/navbar.php'; ?>

        <div class="dashboard-content admin-content">

            <section class="admin-welcome">
                <div>
                    <span class="admin-eyebrow">ADMINISTRATOR</span>
                    <h1>Manajemen Kategori</h1>
                    <p>Kelola kategori pemasukan dan pengeluaran SaveSmart.</p>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <p class="admin-alert success-alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <p class="admin-alert error-alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </p>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <section class="admin-form-card">
                <h3>Tambah Kategori</h3>
                <form action="../backend/admin/kategori_process.php" method="POST" class="admin-category-form">
                    <input type="hidden" name="aksi" value="tambah">

                    <div class="admin-form-group">
                        <label for="nama_kategori">Nama Kategori</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" placeholder="Contoh: Gaji, Makanan" maxlength="60" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="jenis">Jenis Kategori</label>
                        <select id="jenis" name="jenis" required>
                            <option value="">Pilih jenis</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>

                    <button type="submit" class="admin-submit-button">+ Tambah Kategori</button>
                </form>
            </section>

            <section class="admin-table-card">
                <div class="admin-table-heading">
                    <div>
                        <h3>Daftar Kategori</h3>
                        <p>Total <?= count($kategori) ?> kategori tersedia.</p>
                    </div>
                </div>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Kategori</th>
                                <th>Jenis</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kategori as $index => $item): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                                    <td>
                                        <span class="admin-status <?= $item['jenis'] === 'pemasukan' ? 'status-active' : 'status-inactive' ?>">
                                            <?= htmlspecialchars(ucfirst($item['jenis'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="admin-edit-button"
                                            onclick="editKategori(<?= (int) $item['id_kategori'] ?>, <?= htmlspecialchars(json_encode($item['nama_kategori']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($item['jenis']), ENT_QUOTES, 'UTF-8') ?>)">
                                            Edit
                                        </button>

                                        <form action="../backend/admin/kategori_process.php" method="POST" class="admin-delete-form" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id_kategori" value="<?= (int) $item['id_kategori'] ?>">
                                            <button type="submit" class="admin-delete-button">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($kategori)): ?>
                                <tr>
                                    <td colspan="4" class="admin-empty">Belum ada kategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-form-card" id="editKategoriForm" style="display: none;">
                <h3>Edit Kategori</h3>
                <form action="../backend/admin/kategori_process.php" method="POST" class="admin-category-form">
                    <input type="hidden" name="aksi" value="ubah">
                    <input type="hidden" name="id_kategori" id="edit_id">

                    <div class="admin-form-group">
                        <label for="edit_nama">Nama Kategori</label>
                        <input type="text" id="edit_nama" name="nama_kategori" maxlength="60" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="edit_jenis">Jenis Kategori</label>
                        <select id="edit_jenis" name="jenis" required>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>

                    <button type="submit" class="admin-submit-button">Simpan Perubahan</button>
                    <button type="button" class="admin-cancel-button" onclick="document.getElementById('editKategoriForm').style.display='none'">Batal</button>
                </form>
            </section>

        </div>
    </main>
</div>

<script>
function editKategori(id, nama, jenis) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_jenis').value = jenis;
    document.getElementById('editKategoriForm').style.display = 'block';
    document.getElementById('editKategoriForm').scrollIntoView({ behavior: 'smooth' });
}
</script>

<script src="../assets/js/script.js"></script>

</body>
</html>