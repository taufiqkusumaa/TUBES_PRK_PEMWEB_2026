<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$adminPageTitle = 'Dashboard';
$adminActive = 'dashboard';

$totalEquipment = (int) $pdo->query('SELECT COUNT(*) FROM equipment')->fetchColumn();
$totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$withBrand = (int) $pdo->query("SELECT COUNT(*) FROM equipment WHERE brand IS NOT NULL AND brand <> ''")->fetchColumn();

$recent = $pdo->query(
    'SELECT e.id, e.equipment_code, e.equipment_name, e.laboratory_name, c.name AS category_name
     FROM equipment e
     INNER JOIN categories c ON c.id = e.category_id
     ORDER BY e.updated_at DESC
     LIMIT 6'
)->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-dashboard-hero">
    <div class="dashboard-hero-copy">
        <span class="eyebrow eyebrow-blue">LABCAT ADMIN PANEL</span>
        <h1>Kelola Katalog Alat Laboratorium</h1>
        <p>Tambahkan informasi alat, perbarui spesifikasi, unggah galeri foto, dan pastikan datasheet laboratorium Fakultas MIPA selalu rapi serta mudah diakses.</p>
        <div class="dashboard-hero-actions">
            <a class="btn btn-primary add-equipment-button" href="<?= url('admin/equipment-form.php') ?>">
                <span class="button-plus">+</span> Tambah Alat
            </a>
            <a class="btn btn-outline" href="<?= url('admin/equipment.php') ?>">Kelola Data Alat</a>
        </div>
    </div>
    <div class="dashboard-hero-card">
        <div class="dashboard-hero-brand">
            <img class="brand-icon brand-icon-navy" src="<?= url('assets/images/flask-mark-navy.svg') ?>" alt="">
            <div>
                <strong>LABCAT</strong>
                <span>Laboratory Catalog System</span>
            </div>
        </div>
        <ul class="dashboard-hero-points">
            <li>Informasi alat lebih terstruktur</li>
            <li>Galeri foto untuk setiap alat</li>
            <li>Pencarian dan kategori mudah dikelola</li>
        </ul>
    </div>
</div>

<div class="dashboard-stats-grid">
    <article class="dashboard-stat-card">
        <div class="stat-icon-box blue"><svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3M8 15h8"/></svg></div>
        <div>
            <small>Total Data Alat</small>
            <strong><?= number_format($totalEquipment) ?></strong>
            <span>Data datasheet terdaftar</span>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon-box purple"><svg viewBox="0 0 24 24"><path d="M20 13 13 20 3 10V4h6l11 9Z"/><circle cx="7.5" cy="7.5" r="1"/></svg></div>
        <div>
            <small>Kategori Alat</small>
            <strong><?= number_format($totalCategories) ?></strong>
            <span>Kategori tersedia</span>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon-box green"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m5 17 5-4 3 2 3-3 3 3"/></svg></div>
        <div>
            <small>Data Bermerek</small>
            <strong><?= number_format($withBrand) ?></strong>
            <span>Memiliki merek tercatat</span>
        </div>
    </article>
</div>

<section class="admin-panel dashboard-table-panel">
    <div class="panel-heading">
        <div>
            <h2>Data Alat Terakhir Diperbarui</h2>
            <p>Daftar datasheet yang baru ditambahkan atau diperbarui.</p>
        </div>
        <a class="text-link" href="<?= url('admin/equipment.php') ?>">Lihat Semua →</a>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Alat</th>
                    <th>Kategori</th>
                    <th>Laboratorium</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="5" class="table-empty">Belum ada data alat. Silakan tambahkan datasheet pertama.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $row): ?>
                        <tr>
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
