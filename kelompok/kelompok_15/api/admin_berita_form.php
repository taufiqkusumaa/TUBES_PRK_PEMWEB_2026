<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR EDITOR BERITA - ORANG 5
require_editor_or_admin();

$id = max(0, (int) ($_GET['id'] ?? 0));
$article = null;
$error = '';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $article = $stmt->fetch();

    if (!$article || !can_manage_article($article)) {
        http_response_code(404);
        exit('Artikel tidak ditemukan atau tidak dapat diakses.');
    }
}

$categories = get_categories(false);
$faculties = get_faculties(false);
$newUploadedImage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
    $slugInput = trim(is_string($_POST['slug'] ?? null) ? $_POST['slug'] : '');
    $slug = substr(slugify($slugInput !== '' ? $slugInput : $title), 0, 280);
    $excerptText = trim(is_string($_POST['excerpt'] ?? null) ? $_POST['excerpt'] : '');
    $content = trim(is_string($_POST['content'] ?? null) ? $_POST['content'] : '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : 'draft';
    $caption = trim(is_string($_POST['image_caption'] ?? null) ? $_POST['image_caption'] : '');
    $featured = is_admin()
        ? (!empty($_POST['is_featured']) ? 1 : 0)
        : (int) ($article['is_featured'] ?? 0);
    $facultyId = is_admin()
        ? (((int) ($_POST['faculty_id'] ?? 0)) ?: null)
        : (int) (current_user()['faculty_id'] ?? 0);

    $categoryStmt = db()->prepare('SELECT id, is_active FROM categories WHERE id = ? LIMIT 1');
    $categoryStmt->execute([$categoryId]);
    $category = $categoryStmt->fetch();

    if ($title === '' || $content === '' || !$category) {
        $error = 'Judul, kategori, dan isi berita wajib diisi.';
    } elseif (strlen($title) > 255 || strlen($slug) > 280 || strlen($excerptText) > 500 || strlen($caption) > 255) {
        $error = 'Judul, slug, ringkasan, atau keterangan gambar melebihi batas panjang yang diperbolehkan.';
    } elseif (empty($category['is_active']) && (int) ($article['category_id'] ?? 0) !== $categoryId) {
        $error = 'Kategori yang dipilih sudah nonaktif. Pilih kategori aktif.';
    } elseif (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $error = 'Status artikel tidak valid.';
    } elseif (!is_admin() && !$facultyId) {
        $error = 'Akun editor belum memiliki penugasan fakultas.';
    } elseif (is_admin() && $facultyId !== null) {
        $facultyCheck = db()->prepare('SELECT id, is_active FROM faculties WHERE id = ? LIMIT 1');
        $facultyCheck->execute([$facultyId]);
        $facultyRecord = $facultyCheck->fetch();
        $existingFacultyId = (int) ($article['faculty_id'] ?? 0);
        if (!$facultyRecord || (empty($facultyRecord['is_active']) && $existingFacultyId !== $facultyId)) {
            $error = 'Fakultas yang dipilih tidak ditemukan atau sedang nonaktif.';
        }
    }

    if ($error === '') {
        $oldImage = $article['featured_image'] ?? null;
        try {
            $slugCheck = db()->prepare('SELECT id FROM articles WHERE slug = ? AND id <> ? LIMIT 1');
            $slugCheck->execute([$slug, $id]);
            if ($slugCheck->fetch()) {
                $suffix = '-' . bin2hex(random_bytes(3));
                $slug = substr($slug, 0, 280 - strlen($suffix)) . $suffix;
            }

            $image = image_upload('featured_image', $oldImage);
            if ($image !== $oldImage) {
                $newUploadedImage = $image;
            }

            if ($id > 0) {
                $sql = "UPDATE articles
                        SET faculty_id = ?, category_id = ?, title = ?, slug = ?, excerpt = ?,
                            content = ?, featured_image = ?, image_caption = ?, status = ?,
                            is_featured = ?,
                            published_at = CASE
                                WHEN ? = 'published' THEN COALESCE(published_at, NOW())
                                ELSE NULL
                            END
                        WHERE id = ?";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    $facultyId, $categoryId, $title, $slug, $excerptText, $content,
                    $image, $caption, $status, $featured, $status, $id,
                ]);
            } else {
                $sql = "INSERT INTO articles (
                            author_id, faculty_id, category_id, title, slug, excerpt,
                            content, featured_image, image_caption, status, is_featured, published_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                            CASE WHEN ? = 'published' THEN NOW() ELSE NULL END)";
                $stmt = db()->prepare($sql);
                $stmt->execute([
                    (int) current_user()['id'], $facultyId, $categoryId, $title, $slug,
                    $excerptText, $content, $image, $caption, $status, $featured, $status,
                ]);
            }

            if ($newUploadedImage !== null) {
                delete_uploaded_image($oldImage);
                $newUploadedImage = null;
            }

            flash('success', $id ? 'Artikel berhasil diperbarui.' : 'Artikel berhasil disimpan.');
            redirect('admin/berita.html');
        } catch (Throwable $ex) {
            if ($newUploadedImage !== null) {
                delete_uploaded_image($newUploadedImage);
                $newUploadedImage = null;
            }
            $error = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Terjadi kesalahan saat menyimpan artikel. Periksa data dan koneksi database.';
            error_log('Pena Kampus article save error: ' . $ex->getMessage());
        }
    }
}

$value = static function (string $key, string $default = '') use ($article): string {
    $posted = $_POST[$key] ?? ($article[$key] ?? $default);
    return e(is_string($posted) ? $posted : $default);
};
$selectedCategoryId = (int) ($_POST['category_id'] ?? ($article['category_id'] ?? 0));
$adminTitle = $id ? 'Edit Berita' : 'Tulis Berita';
$activeAdmin = 'admin/berita.html';


$view = [];
$view['error'] = [];
if ($error) {
    $item1 = [];
    $item1['error2'] = e($error);
    $view['error'][] = $item1;
}
$view['id_Sunting_Artikel_Tulis_Berita_Baru'] = $id ? 'Sunting Artikel' : 'Tulis Berita Baru';
$view['csrf_token'] = csrf_token();
$view['value_title'] = $value('title');
$view['value_slug'] = $value('slug');
$view['value_excerpt'] = $value('excerpt');
$view['value_content'] = $value('content');
$view['categories'] = [];
foreach ($categories as $categoryOption) {
    $item1 = [];
    $isSelected = $selectedCategoryId === (int) $categoryOption['id'];
    $item1['categoryOption_is_active_isSelected'] = [];
    if ($categoryOption['is_active'] || $isSelected) {
        $item2 = [];
        $item2['int_categoryOption_id'] = (int) $categoryOption['id'];
        $item2['isSelected_selected'] = $isSelected ? 'selected' : '';
        $item2['categoryLabel'] = e(
            $categoryOption['name'] . (empty($categoryOption['is_active']) ? ' (Nonaktif)' : '')
        );
        $item1['categoryOption_is_active_isSelected'][] = $item2;
    }
    $view['categories'][] = $item1;
}
$view['is_admin'] = [];
if (is_admin()) {
    $item1 = [];
    $item1['faculties'] = [];
    foreach ($faculties as $facultyOption) {
        $item2 = [];
        $isSelectedFaculty = (int) ($_POST['faculty_id'] ?? ($article['faculty_id'] ?? 0))
            === (int) $facultyOption['id'];
        $item2['availableFaculty'] = [];
        if (!empty($facultyOption['is_active']) || $isSelectedFaculty) {
            $item3 = [];
            $item3['int_facultyOption_id'] = (int) $facultyOption['id'];
            $item3['isSelectedFaculty_selected'] = $isSelectedFaculty ? 'selected' : '';
            $item3['facultyLabel'] = e(
                $facultyOption['code'] . ' · ' . $facultyOption['name']
                    . (empty($facultyOption['is_active']) ? ' (Nonaktif — pilihan artikel saat ini)' : '')
            );
            $item2['availableFaculty'][] = $item3;
        }
        $item1['faculties'][] = $item2;
    }
    $view['is_admin'][] = $item1;
}
$view['otherwise_is_admin'] = [];
if (!$view['is_admin']) {
    $item1 = [];
    $item1['editorFaculty'] = e(current_user()['faculty_name'] ?? 'Belum ditentukan');
    $view['otherwise_is_admin'][] = $item1;
}
$selectedStatus = $_POST['status'] ?? ($article['status'] ?? 'draft');
$view['selectedStatus_draft_selected'] = $selectedStatus === 'draft' ? 'selected' : '';
$view['selectedStatus_published_selected'] = $selectedStatus === 'published' ? 'selected' : '';
$view['selectedStatus_archived_selected'] = $selectedStatus === 'archived' ? 'selected' : '';
$view['is_admin2'] = [];
if (is_admin()) {
    $item1 = [];
    $item1['featuredChecked'] = !empty($_POST['is_featured'] ?? ($article['is_featured'] ?? 0)) ? 'checked' : '';
    $view['is_admin2'][] = $item1;
}
$currentImage = $article['featured_image'] ?? null;
$view['currentImage_hidden'] = $currentImage ? '' : 'hidden';
$view['article_image_currentImage'] = e(article_image($currentImage));
$view['value_image_caption'] = $value('image_caption');

json_response([
    'content' => $view,
    'title' => $adminTitle . ' | Pena Kampus CMS',
    'layout' => admin_layout($adminTitle, $activeAdmin),
]);
