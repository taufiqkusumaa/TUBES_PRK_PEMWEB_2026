<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$name = '';
$description = '';
$error = '';

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $category = $stmt->fetch();
    if (!$category) {
        set_flash('danger', 'Kategori tidak ditemukan.');
        redirect('admin/categories.php');
    }
    $name = $category['name'];
    $description = $category['description'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name AND id <> :id');
        $check->execute(['name' => $name, 'id' => $id]);

        if ((int) $check->fetchColumn() > 0) {
            $error = 'Nama kategori sudah tersedia.';
        } else {
            if ($isEdit) {
                $save = $pdo->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
                $save->execute(['name' => $name, 'description' => $description, 'id' => $id]);
                set_flash('success', 'Kategori berhasil diperbarui.');
            } else {
                $save = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
                $save->execute(['name' => $name, 'description' => $description]);
                set_flash('success', 'Kategori berhasil ditambahkan.');
            }
            redirect('admin/categories.php');
        }
    }
}

$adminPageTitle = $isEdit ? 'Edit Kategori' : 'Tambah Kategori';
$adminActive = 'categories';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-page-heading">
    <div>
        <span class="eyebrow eyebrow-blue">MANAJEMEN KATEGORI</span>
        <h1><?= $isEdit ? 'Edit Kategori' : 'Tambah Kategori' ?></h1>
        <p>Gunakan nama kategori yang ringkas dan mudah dipahami pengguna katalog.</p>
    </div>
    <a class="btn btn-outline" href="<?= url('admin/categories.php') ?>">← Kembali</a>
</div>

<section class="admin-panel category-form-panel">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <label>Nama Kategori <b>*</b>
            <input type="text" name="name" required maxlength="100" placeholder="Contoh: Alat Optik" value="<?= e($name) ?>">
        </label>
        <label>Deskripsi Kategori
            <textarea name="description" rows="5" placeholder="Keterangan singkat kategori..."><?= e($description) ?></textarea>
        </label>
        <div class="form-actions">
            <a class="btn btn-outline" href="<?= url('admin/categories.php') ?>">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan Kategori</button>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
