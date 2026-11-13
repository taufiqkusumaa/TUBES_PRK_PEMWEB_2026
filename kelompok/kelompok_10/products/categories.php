<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $errorMessage = 'Nama kategori wajib diisi dan maksimal 100 karakter.';
        } else {
            try {
                $stmt = db()->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
                $stmt->execute(['name' => $name]);
                if ($stmt->fetch()) {
                    $errorMessage = "Kategori '{$name}' sudah ada.";
                } else {
                    $stmt = db()->prepare('INSERT INTO categories (name, description, is_active) VALUES (:name, :desc, 1)');
                    $stmt->execute(['name' => $name, 'desc' => $description !== '' ? $description : null]);
                    flash('success', "Kategori '{$name}' berhasil ditambahkan.");
                    redirect('products/categories.php');
                }
            } catch (PDOException $e) {
                error_log('Error creating category: ' . $e->getMessage());
                $errorMessage = 'Gagal menyimpan kategori baru.';
            }
        }
    } elseif ($action === 'toggle') {
        $catId = (int) ($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            db()->prepare('UPDATE categories SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id')->execute(['id' => $catId]);
            flash('success', 'Status kategori berhasil diperbarui.');
            redirect('products/categories.php');
        }
    }
}

$categories = [];
try {
    $categories = db()->query('
        SELECT c.*, COUNT(p.id) AS product_count 
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ')->fetchAll();
} catch (PDOException $e) {
    error_log('Error loading categories: ' . $e->getMessage());
}

$pageTitle = 'Kelola Kategori Produk';
$activePage = 'categories';
require __DIR__ . '/../includes/header.php';
?>

<div class="grid-2" style="align-items: start;">
    <!-- Form Tambah Kategori -->
    <div class="panel">
        <div class="panel-header">
            <h3>+ Tambah Kategori Baru</h3>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label for="name">Nama Kategori *</label>
                <input id="name" name="name" placeholder="Misal: Minuman Segar, Snack, Sembako" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" placeholder="Catatan singkat peruntukan kategori..."></textarea>
            </div>

            <button type="submit" class="button button-primary">Simpan Kategori</button>
        </form>
    </div>

    <!-- Tabel Daftar Kategori -->
    <div class="panel">
        <div class="panel-header">
            <h3>Daftar Kategori Produk</h3>
            <span class="muted" style="font-size: 12px;"><?= count($categories) ?> kategori terdaftar</span>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="col-num">Jumlah Produk</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 24px; color: var(--ink-muted);">
                                Belum ada kategori produk.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?= e($cat['name']) ?></strong>
                                    <?php if (!empty($cat['description'])): ?>
                                        <small style="display: block; color: var(--ink-subtle);"><?= e($cat['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="col-num"><?= (int) $cat['product_count'] ?> item</td>
                                <td>
                                    <?php if ((int) $cat['is_active'] === 1): ?>
                                        <span class="badge badge-success"><span class="badge-dot"></span>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral"><span class="badge-dot"></span>Non-aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Ubah status aktif kategori ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
                                        <button type="submit" class="button button-secondary button-sm">
                                            <?= (int) $cat['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
