<?php
session_start();

require_once __DIR__ . '/includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int) $_GET['id'] <= 0) {
    header("Location: users.php");
    exit;
}

$id = (int) $_GET['id'];

$query = "SELECT id, name, username, email, role, photo
          FROM users
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    $conn->close();
    header("Location: users.php");
    exit;
}
?>