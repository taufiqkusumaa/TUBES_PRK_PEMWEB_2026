<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../inventory/helpers.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    inventory_redirect('categories.php');
}
require_valid_csrf();

$pdo = db();
$action = (string) ($_POST['action'] ?? 'save');
$editId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$back = 'categories.php' . ($action === 'save' && $editId ? '?edit=' . $editId : '');

try {
    if ($action === 'toggle') {
        if (!$editId) {
            throw new InvalidArgumentException('Kategori tidak valid.');
        }
        $stmt = $pdo->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$editId]);
        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Kategori tidak ditemukan.');
        }
        flash('success', 'Status kategori diperbarui.');
        inventory_redirect('categories.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '') {
        throw new InvalidArgumentException('Nama kategori wajib diisi.');
    }
    if (mb_strlen($name) > 100) {
        throw new InvalidArgumentException('Nama kategori maksimal 100 karakter.');
    }
    if (mb_strlen($description) > 255) {
        throw new InvalidArgumentException('Deskripsi maksimal 255 karakter.');
    }
    $descriptionValue = $description === '' ? null : $description;

    $dup = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND id <> ?');
    $dup->execute([$name, $editId ?: 0]);
    if ($dup->fetch()) {
        throw new InvalidArgumentException('Nama kategori sudah dipakai.');
    }

    if ($editId) {
        $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $descriptionValue, $editId]);
        flash('success', 'Kategori berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $descriptionValue]);
        flash('success', 'Kategori berhasil ditambahkan.');
    }
    inventory_redirect('categories.php');
} catch (InvalidArgumentException $ex) {
    flash('error', $ex->getMessage());
    inventory_redirect($back);
} catch (PDOException $ex) {
    error_log('category_save: ' . $ex->getMessage());
    $duplicate = ($ex->errorInfo[1] ?? 0) === 1062;
    flash('error', $duplicate ? 'Nama kategori sudah dipakai.' : 'Gagal menyimpan kategori. Coba lagi.');
    inventory_redirect($back);
}
