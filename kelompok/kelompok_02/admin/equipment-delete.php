<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/equipment.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, image FROM equipment WHERE id = :id');
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if ($item) {
    $imageStmt = $pdo->prepare('SELECT image_path FROM equipment_images WHERE equipment_id = :id');
    $imageStmt->execute(['id' => $id]);
    $imagePaths = array_column($imageStmt->fetchAll(), 'image_path');

    if (!empty($item['image']) && !in_array($item['image'], $imagePaths, true)) {
        $imagePaths[] = $item['image'];
    }

    $delete = $pdo->prepare('DELETE FROM equipment WHERE id = :id');
    $delete->execute(['id' => $id]);

    foreach ($imagePaths as $path) {
        remove_equipment_image($path);
    }

    set_flash('success', 'Data alat beserta seluruh foto berhasil dihapus.');
} else {
    set_flash('warning', 'Data alat tidak ditemukan.');
}

redirect('admin/equipment.php');
