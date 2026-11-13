<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$item = [
    'equipment_code' => '',
    'equipment_name' => '',
    'category_id' => '',
    'laboratory_name' => '',
    'brand' => '',
    'model' => '',
    'description' => '',
    'specifications' => '',
    'image' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM equipment WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        set_flash('danger', 'Data alat tidak ditemukan.');
        redirect('admin/equipment.php');
    }
    $item = $existing;
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$imageQuery = $pdo->prepare(
    'SELECT id, image_path, sort_order
     FROM equipment_images
     WHERE equipment_id = :equipment_id
     ORDER BY sort_order ASC, id ASC'
);
$existingImages = [];
if ($isEdit) {
    $imageQuery->execute(['equipment_id' => $id]);
    $existingImages = $imageQuery->fetchAll();
}

$error = '';
$selectedDeleteIds = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $item['equipment_code'] = strtoupper(trim((string) ($_POST['equipment_code'] ?? '')));
    $item['equipment_name'] = trim((string) ($_POST['equipment_name'] ?? ''));
    $item['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $item['laboratory_name'] = trim((string) ($_POST['laboratory_name'] ?? ''));
    $item['brand'] = trim((string) ($_POST['brand'] ?? ''));
    $item['model'] = trim((string) ($_POST['model'] ?? ''));
    $item['description'] = trim((string) ($_POST['description'] ?? ''));
    $item['specifications'] = trim((string) ($_POST['specifications'] ?? ''));

    $categoryCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
    $categoryCheck->execute(['id' => $item['category_id']]);

    if ($item['equipment_code'] === '' || $item['equipment_name'] === '' || $item['laboratory_name'] === '') {
        $error = 'Kode alat, nama alat, dan nama laboratorium wajib diisi.';
    } elseif ((int) $categoryCheck->fetchColumn() === 0) {
        $error = 'Silakan pilih kategori yang valid.';
    } else {
        $duplicate = $pdo->prepare('SELECT COUNT(*) FROM equipment WHERE equipment_code = :code AND id <> :id');
        $duplicate->execute(['code' => $item['equipment_code'], 'id' => $id]);
        if ((int) $duplicate->fetchColumn() > 0) {
            $error = 'Kode inventaris sudah digunakan. Gunakan kode lain.';
        }
    }

    $requestedDeletes = $_POST['delete_image_ids'] ?? [];
    if (is_array($requestedDeletes)) {
        $selectedDeleteIds = array_values(array_unique(array_filter(
            array_map('intval', $requestedDeletes),
            static fn ($value) => $value > 0
        )));
    }

    $existingIds = array_map(static fn ($image) => (int) $image['id'], $existingImages);
    $validDeleteIds = array_values(array_intersect($selectedDeleteIds, $existingIds));
    $remainingImages = array_values(array_filter(
        $existingImages,
        static fn ($image) => !in_array((int) $image['id'], $validDeleteIds, true)
    ));

    $incomingNames = $_FILES['images']['name'] ?? [];
    $incomingCount = 0;
    if (is_array($incomingNames)) {
        foreach ($incomingNames as $name) {
            if ($name !== '') {
                $incomingCount++;
            }
        }
    }

    if ($error === '' && count($remainingImages) + $incomingCount > MAX_EQUIPMENT_IMAGES) {
        $error = 'Setiap alat dapat memiliki maksimal ' . MAX_EQUIPMENT_IMAGES . ' foto.';
    }

    if ($error === '') {
        $newImagePaths = [];
        $deletedImagePaths = [];
        try {
            $newImagePaths = upload_equipment_images($_FILES['images'] ?? []);
            $pdo->beginTransaction();

            $data = [
                'equipment_code' => $item['equipment_code'],
                'equipment_name' => $item['equipment_name'],
                'category_id' => $item['category_id'],
                'laboratory_name' => $item['laboratory_name'],
                'brand' => $item['brand'],
                'model' => $item['model'],
                'description' => $item['description'],
                'specifications' => $item['specifications'],
            ];

            if ($isEdit) {
                $data['id'] = $id;
                $save = $pdo->prepare(
                    'UPDATE equipment SET
                        equipment_code = :equipment_code,
                        equipment_name = :equipment_name,
                        category_id = :category_id,
                        laboratory_name = :laboratory_name,
                        brand = :brand,
                        model = :model,
                        description = :description,
                        specifications = :specifications,
                        updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $save->execute($data);
                $equipmentId = $id;
            } else {
                $save = $pdo->prepare(
                    'INSERT INTO equipment
                        (equipment_code, equipment_name, category_id, laboratory_name, brand, model, description, specifications)
                     VALUES
                        (:equipment_code, :equipment_name, :category_id, :laboratory_name, :brand, :model, :description, :specifications)'
                );
                $save->execute($data);
                $equipmentId = (int) $pdo->lastInsertId();
            }

            if ($validDeleteIds) {
                $deleteImage = $pdo->prepare('DELETE FROM equipment_images WHERE id = :image_id AND equipment_id = :equipment_id');
                $findDeleted = $pdo->prepare('SELECT image_path FROM equipment_images WHERE id = :image_id AND equipment_id = :equipment_id');

                foreach ($validDeleteIds as $imageId) {
                    $findDeleted->execute(['image_id' => $imageId, 'equipment_id' => $equipmentId]);
                    $deleted = $findDeleted->fetch();
                    if ($deleted) {
                        $deletedImagePaths[] = $deleted['image_path'];
                    }
                    $deleteImage->execute(['image_id' => $imageId, 'equipment_id' => $equipmentId]);
                }
            }

            $nextSort = 0;
            if ($remainingImages) {
                $nextSort = max(array_map(static fn ($image) => (int) $image['sort_order'], $remainingImages)) + 1;
            }

            $insertImage = $pdo->prepare('INSERT INTO equipment_images (equipment_id, image_path, sort_order) VALUES (:equipment_id, :image_path, :sort_order)');
            foreach ($newImagePaths as $path) {
                $insertImage->execute([
                    'equipment_id' => $equipmentId,
                    'image_path' => $path,
                    'sort_order' => $nextSort++,
                ]);
            }

            $coverChoice = (string) ($_POST['cover_choice'] ?? '');
            $coverPath = null;

            if (str_starts_with($coverChoice, 'existing:')) {
                $coverId = (int) substr($coverChoice, 9);
                foreach ($remainingImages as $image) {
                    if ((int) $image['id'] === $coverId) {
                        $coverPath = $image['image_path'];
                        break;
                    }
                }
            } elseif (str_starts_with($coverChoice, 'new:')) {
                $newIndex = (int) substr($coverChoice, 4);
                if (isset($newImagePaths[$newIndex])) {
                    $coverPath = $newImagePaths[$newIndex];
                }
            }

            if ($coverPath === null && !empty($item['image'])) {
                foreach ($remainingImages as $image) {
                    if ($image['image_path'] === $item['image']) {
                        $coverPath = $item['image'];
                        break;
                    }
                }
            }
            if ($coverPath === null && $remainingImages) {
                $coverPath = $remainingImages[0]['image_path'];
            }
            if ($coverPath === null && $newImagePaths) {
                $coverPath = reset($newImagePaths);
            }

            $updateCover = $pdo->prepare('UPDATE equipment SET image = :image WHERE id = :id');
            $updateCover->execute(['image' => $coverPath, 'id' => $equipmentId]);

            $pdo->commit();

            foreach ($deletedImagePaths as $path) {
                remove_equipment_image($path);
            }

            set_flash('success', $isEdit ? 'Datasheet alat dan galeri foto berhasil diperbarui.' : 'Datasheet alat dan galeri foto berhasil ditambahkan.');
            redirect('admin/equipment.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($newImagePaths as $path) {
                remove_equipment_image($path);
            }
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Data gagal disimpan. Periksa koneksi database dan tabel galeri foto.';
        }
    }
}

$adminPageTitle = $isEdit ? 'Edit Datasheet Alat' : 'Tambah Datasheet Alat';
$adminActive = 'equipment';

$defaultCoverChoice = '';
foreach ($existingImages as $image) {
    if ($image['image_path'] === ($item['image'] ?? null)) {
        $defaultCoverChoice = 'existing:' . (int) $image['id'];
        break;
    }
}
if ($defaultCoverChoice === '' && $existingImages) {
    $defaultCoverChoice = 'existing:' . (int) $existingImages[0]['id'];
}

require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-page-heading">
    <div>
        <span class="eyebrow eyebrow-blue">MANAJEMEN KATALOG</span>
        <h1><?= $isEdit ? 'Edit Informasi Alat' : 'Tambah Informasi Alat' ?></h1>
        <p>Kelola identitas alat, foto galeri, deskripsi, dan spesifikasi teknis.</p>
    </div>
    <a class="btn btn-outline" href="<?= url('admin/equipment.php') ?>">← Kembali</a>
</div>

<section class="admin-panel form-panel equipment-form-panel">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form equipment-admin-form">
        <?= csrf_field() ?>

        <div class="form-section-title">
            <span>01</span>
            <div><h2>Identitas dan Foto Alat</h2><p>Satu alat dapat memiliki hingga <?= MAX_EQUIPMENT_IMAGES ?> foto.</p></div>
        </div>

        <div class="equipment-form-layout">
            <aside class="equipment-photo-column">
                <label class="upload-dropzone" for="equipment-images">
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M24 31V8m0 0-8 8m8-8 8 8M8 29v10a3 3 0 0 0 3 3h26a3 3 0 0 0 3-3V29" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <strong>Unggah Foto Alat</strong>
                    <span>JPG, PNG, atau WEBP</span>
                    <small>Maksimal 4 MB per foto</small>
                    <span class="upload-select-label">Pilih Beberapa Foto</span>
                    <input id="equipment-images" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                </label>

                <div class="upload-limit-note">
                    <strong><?= count($existingImages) ?> / <?= MAX_EQUIPMENT_IMAGES ?> foto tersimpan</strong>
                    <span>Pilih foto utama untuk ditampilkan pada kartu katalog.</span>
                </div>

                <?php if ($existingImages): ?>
                    <div class="existing-gallery">
                        <h3>Foto yang Tersimpan</h3>
                        <div class="existing-gallery-grid">
                            <?php foreach ($existingImages as $index => $image): ?>
                                <?php
                                    $imageId = (int) $image['id'];
                                    $isCover = $image['image_path'] === ($item['image'] ?? null);
                                    if (!$isCover && $defaultCoverChoice === 'existing:' . $imageId) {
                                        $isCover = true;
                                    }
                                ?>
                                <article class="existing-image-card" data-existing-image-card>
                                    <img src="<?= e(image_url($image['image_path'])) ?>" alt="Foto <?= e($item['equipment_name']) ?> <?= $index + 1 ?>">
                                    <label class="cover-choice">
                                        <input type="radio" name="cover_choice" value="existing:<?= $imageId ?>" <?= $isCover ? 'checked' : '' ?>>
                                        <span>Foto utama</span>
                                    </label>
                                    <label class="delete-choice">
                                        <input type="checkbox" name="delete_image_ids[]" value="<?= $imageId ?>" data-delete-existing-image <?= in_array($imageId, $selectedDeleteIds, true) ? 'checked' : '' ?>>
                                        <span>Hapus foto</span>
                                    </label>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="new-gallery-preview" id="new-gallery-preview" hidden>
                    <h3>Foto Baru yang Dipilih</h3>
                    <div class="new-gallery-grid" id="new-gallery-grid"></div>
                </div>
            </aside>

            <div class="equipment-data-column">
                <div class="form-grid">
                    <label>Kode Inventaris <b>*</b><input type="text" name="equipment_code" required maxlength="50" placeholder="Contoh: ALT-001" value="<?= e($item['equipment_code']) ?>"></label>
                    <label>Nama Alat <b>*</b><input type="text" name="equipment_name" required maxlength="150" placeholder="Contoh: Mikroskop" value="<?= e($item['equipment_name']) ?>"></label>
                    <label>Kategori <b>*</b>
                        <select name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (int) $item['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Laboratorium <b>*</b><input type="text" name="laboratory_name" required maxlength="150" placeholder="Contoh: Laboratorium Biologi" value="<?= e($item['laboratory_name']) ?>"></label>
                    <label>Merek<input type="text" name="brand" maxlength="100" placeholder="Contoh: Olympus" value="<?= e($item['brand']) ?>"></label>
                    <label>Tipe / Model<input type="text" name="model" maxlength="100" placeholder="Contoh: CX23" value="<?= e($item['model']) ?>"></label>
                </div>

                <div class="form-section-title form-section-subtitle">
                    <span>02</span>
                    <div><h2>Deskripsi dan Spesifikasi</h2><p>Informasi teknis yang dibaca pengunjung katalog.</p></div>
                </div>
                <div class="form-grid form-grid-two">
                    <label>Deskripsi / Fungsi Alat<textarea name="description" rows="6" placeholder="Jelaskan fungsi, prinsip kerja, dan kegunaan alat..."><?= e($item['description']) ?></textarea></label>
                    <label>Spesifikasi Teknis<textarea name="specifications" rows="6" placeholder="Contoh:&#10;Perbesaran: 40x–1000x&#10;Sumber cahaya: LED&#10;Resolusi: ..."><?= e($item['specifications']) ?></textarea></label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-outline" href="<?= url('admin/equipment.php') ?>">Batal</a>
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Datasheet' ?></button>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
