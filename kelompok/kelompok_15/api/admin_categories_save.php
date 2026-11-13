<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR SIMPAN KATEGORI - ORANG 2
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();
$id = max(0, (int) ($_POST['id'] ?? 0));
$name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
$slugInput = trim(is_string($_POST['slug'] ?? null) ? $_POST['slug'] : '');
$slug = substr(slugify($slugInput !== '' ? $slugInput : $name), 0, 120);
$description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');

if ($name === '' || strlen($name) > 100 || strlen($description) > 255) {
    flash('error', 'Nama kategori wajib diisi dan panjang data harus sesuai batas.');
    redirect('admin/kategori.html');
}

if ($id > 0) {
    $exists = db()->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        flash('error', 'Kategori tidak ditemukan.');
        redirect('admin/kategori.html');
    }
}

$check = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1');
$check->execute([$slug, $id]);
if ($check->fetch()) {
    flash('error', 'Slug kategori sudah digunakan. Gunakan slug yang berbeda.');
    redirect($id > 0 ? 'admin/kategori.html?edit=' . $id : 'admin/kategori.html');
}

try {
    if ($id > 0) {
        db()->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?')
            ->execute([$name, $slug, $description, $id]);
        $message = 'Kategori berhasil diperbarui.';
    } else {
        db()->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)')
            ->execute([$name, $slug, $description]);
        $message = 'Kategori berhasil ditambahkan.';
    }
    flash('success', $message);
} catch (PDOException $ex) {
    error_log('Pena Kampus category save error: ' . $ex->getMessage());
    flash('error', 'Kategori tidak dapat disimpan. Periksa kembali data yang dimasukkan.');
}

redirect('admin/kategori.html');
