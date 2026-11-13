<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$adminPageTitle = 'Kategori';
$adminActive = 'categories';

$rows = $pdo->query(
    'SELECT c.id, c.name, c.description FROM categories c ORDER BY c.name'
)->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-page-heading">
    <div>
        <span class="eyebrow eyebrow-blue">KATALOG INFORMASI LABORATORIUM</span>
        <h1>Kategori Peralatan</h1>
        <p>Kelola pengelompokan kategori untuk datasheet peralatan laboratorium.</p>
    </div>
    <a class="btn btn-primary add-equipment-button" href="<?= url('admin/category-form.php') ?>">
        <span class="button-plus">+</span> Tambah Kategori
    </a>
</div>

<section class="admin-panel category-list-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Kategori</h2>
            <p><?= count($rows) ?> kategori terdaftar.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table category-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="table-empty">Belum ada kategori.</td></tr>
            <?php else: foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= e($row['name']) ?></strong></td>
                    <td><?= e($row['description'] ?: '-') ?></td>
                    <td class="action-cell">
                        <a class="icon-action edit" title="Edit kategori" aria-label="Edit kategori" href="<?= url('admin/category-form.php?id=' . (int) $row['id']) ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16.5-.8 4.3 4.3-.8L19.8 7.7a2.1 2.1 0 0 0-3-3L4 16.5Z"/><path d="m14.9 6.6 3 3"/></svg>
                        </a>
                        <form method="post" action="<?= url('admin/category-delete.php') ?>" data-category-delete>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-action delete" type="submit" title="Hapus kategori" aria-label="Hapus kategori">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m3 0-.8 13H6.8L6 7m3.5 3v6m5-6v6"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>