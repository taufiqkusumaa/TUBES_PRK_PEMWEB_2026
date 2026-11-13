<?php
session_start();

require_once __DIR__ . '/../includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?status=hapus_berhasil");
} else {
    header("Location: index.php?status=gagal_hapus");
}

$stmt->close();
exit;