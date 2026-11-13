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
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = strtolower(trim($_POST['role'] ?? ''));

if (
    $id <= 0 ||
    $name === '' ||
    $username === '' ||
    !in_array($role, ['admin', 'kasir'], true)
) {
    die("Data pengguna tidak valid!");
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Format email tidak valid!");
}

$getUser = $conn->prepare("SELECT id, photo FROM users WHERE id = ?");
$getUser->bind_param("i", $id);
$getUser->execute();

$userData = $getUser->get_result()->fetch_assoc();
$getUser->close();

if (!$userData) {
    die("Pengguna tidak ditemukan!");
}

$currentPhoto = $userData['photo'];

$check = $conn->prepare(
    "SELECT id FROM users WHERE username = ? AND id != ?"
);
$check->bind_param("si", $username, $id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $check->close();
    die("Username sudah digunakan oleh pengguna lain!");
}

$check->close();

if ($email !== '') {
    $check = $conn->prepare(
        "SELECT id FROM users WHERE email = ? AND id != ?"
    );
    $check->bind_param("si", $email, $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $check->close();
        die("Email sudah digunakan oleh pengguna lain!");
    }

    $check->close();
}

$newPhoto = $currentPhoto;
$uploadedPath = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        die("Upload foto gagal. Silakan coba lagi.");
    }

    if (!is_uploaded_file($_FILES['photo']['tmp_name'])) {
        die("File foto tidak valid!");
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        die("Ukuran foto maksimal 2 MB!");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['photo']['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mime])) {
        die("Format foto harus JPG, PNG, atau WebP!");
    }

    $uploadDirectory = __DIR__ . '/assets/img/';

    if (!is_dir($uploadDirectory)) {
        die("Folder penyimpanan foto tidak ditemukan!");
    }

    $newPhoto = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $uploadedPath = $uploadDirectory . $newPhoto;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadedPath)) {
        die("Gagal menyimpan foto profil.");
    }
}

$query = "UPDATE users
          SET name = ?, username = ?, email = ?, role = ?, photo = ?
          WHERE id = ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    if ($uploadedPath !== null && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    die("Gagal menyiapkan query: " . $conn->error);
}

$stmt->bind_param(
    "sssssi",
    $name,
    $username,
    $email,
    $role,
    $newPhoto,
    $id
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    if (
        $newPhoto !== $currentPhoto &&
        !empty($currentPhoto) &&
        !in_array($currentPhoto, ['admin-avatar.svg', 'avatar-default.svg', 'logo.svg'], true)
    ) {
        $oldPath = __DIR__ . '/assets/img/' . basename($currentPhoto);

        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    header("Location: users.php?status=edit_berhasil");
    exit;
}

$error = $stmt->error;

$stmt->close();
$conn->close();

if ($uploadedPath !== null && is_file($uploadedPath)) {
    unlink($uploadedPath);
}

die("Gagal memperbarui pengguna: " . $error);
