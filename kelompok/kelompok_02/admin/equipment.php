<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$adminPageTitle = 'Data Alat';
$adminActive = 'equipment';

$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = max(0, (int) ($_GET['category'] ?? 0));
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT e.*, c.name AS category_name
        FROM equipment e
        INNER JOIN categories c ON c.id = e.category_id
        WHERE 1=1';
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $sql .= ' AND (
        e.equipment_name LIKE :q_name
        OR e.equipment_code LIKE :q_code
        OR e.laboratory_name LIKE :q_lab
        OR e.brand LIKE :q_brand
        OR e.model LIKE :q_model
    )';
    $params['q_name'] = $term;
    $params['q_code'] = $term;
    $params['q_lab'] = $term;
    $params['q_brand'] = $term;
    $params['q_model'] = $term;
}

if ($categoryId > 0) {
    $sql .= ' AND e.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$sql .= ' ORDER BY e.updated_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-page-heading">
    <div>
        <span class="eyebrow eyebrow-blue">KATALOG INFORMASI LABORATORIUM</span>
        <h1>Data Alat Laboratorium</h1>
        <p>Kelola identitas, foto galeri, deskripsi, dan spesifikasi teknis peralatan.</p>
    </div>
    <a class="btn btn-primary add-equipment-button" href="<?= url('admin/equipment-form.php') ?>">
        <span class="button-plus">+</span> Tambah Alat
    </a>
</div>

<section class="admin-panel equipment-list-panel">
    <form class="admin-toolbar" method="get">
        <label class="search-box">
            <span>⌕</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau kode alat...">
        </label>
        <label class="filter-box">
            <select name="category">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <span>⌄</span>
        </label>
        <button class="btn btn-primary" type="submit">Cari</button>
        <?php if ($search !== '' || $categoryId > 0): ?>
            <a class="btn btn-outline" href="<?= url('admin/equipment.php') ?>">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-caption">
        <strong>Daftar datasheet peralatan</strong>
        <span><?= count($rows) ?> data alat</span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>No.</th><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th>Laboratorium</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="table-empty">Data alat tidak ditemukan.</td></tr>
            <?php else: foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= e($row['equipment_code']) ?></strong></td>
                    <td class="equipment-name-cell"><?= e($row['equipment_name']) ?></td>
                    <td><?= e($row['category_name']) ?></td>
                    <td><?= e($row['laboratory_name']) ?></td>
                    <td class="action-cell">
                        <a class="icon-action view" title="Lihat datasheet" aria-label="Lihat datasheet" href="<?= url('detail.php?id=' . (int) $row['id']) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.8"/></svg>
                        </a>
                        <a class="icon-action edit" title="Edit alat" aria-label="Edit alat" href="<?= url('admin/equipment-form.php?id=' . (int) $row['id']) ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16.5-.8 4.3 4.3-.8L19.8 7.7a2.1 2.1 0 0 0-3-3L4 16.5Z"/><path d="m14.9 6.6 3 3"/></svg>
                        </a>
                        <form method="post" action="<?= url('admin/equipment-delete.php') ?>" onsubmit="return confirm('Yakin ingin menghapus data alat beserta seluruh fotonya?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button class="icon-action delete" title="Hapus alat" aria-label="Hapus alat" type="submit">
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
