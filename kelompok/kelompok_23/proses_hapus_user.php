<?php
session_start();

require_once __DIR__ . '/includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: users.php?status=gagal_hapus");
    exit;
}

if ($id === (int) $_SESSION['user_id']) {
    header("Location: users.php?status=gagal_hapus");
    exit;
}

$getUser = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$getUser->bind_param("i", $id);
$getUser->execute();

$user = $getUser->get_result()->fetch_assoc();
$getUser->close();

if (!$user) {
    $conn->close();
    header("Location: users.php?status=gagal_hapus");
    exit;
}

$query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    $conn->close();
    die("Gagal menyiapkan query penghapusan.");
}

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $photo = $user['photo'];

    $stmt->close();
    $conn->close();

    if (
        !empty($photo) &&
        !in_array($photo, ['admin-avatar.svg', 'avatar-default.svg', 'logo.svg'], true)
    ) {
        $photoPath = __DIR__ . '/assets/img/' . basename($photo);

        if (is_file($photoPath)) {
            unlink($photoPath);
        }
    }

    header("Location: users.php?status=hapus_berhasil");
    exit;
}

$stmt->close();
$conn->close();

header("Location: users.php?status=gagal_hapus");
exit;
