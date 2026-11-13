<?php
session_start();

require_once __DIR__ . '/includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tambah_user.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));

if (
    $name === '' ||
    $username === '' ||
    $password === '' ||
    !in_array($role, ['admin', 'kasir'], true)
) {
    die("Semua data wajib diisi dengan benar!");
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Format email tidak valid!");
}

$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $check->close();
    die("Username sudah digunakan! Silakan gunakan username lain.");
}

$check->close();

if ($email !== '') {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $check->close();
        die("Email sudah digunakan! Silakan gunakan email lain.");
    }

    $check->close();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$photoName = null;

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

    $photoName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    $uploadPath = $uploadDirectory . $photoName;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
        die("Gagal menyimpan foto profil.");
    }
}

$query = "INSERT INTO users (name, username, email, password, role, photo)
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if (!$stmt) {
    if ($photoName !== null) {
        $failedPath = __DIR__ . '/assets/img/' . $photoName;

        if (is_file($failedPath)) {
            unlink($failedPath);
        }
    }

    die("Gagal menyiapkan query: " . $conn->error);
}

$stmt->bind_param(
    "ssssss",
    $name,
    $username,
    $email,
    $hashed_password,
    $role,
    $photoName
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: users.php?status=berhasil");
    exit;
}

if ($photoName !== null) {
    $failedPath = __DIR__ . '/assets/img/' . $photoName;

    if (is_file($failedPath)) {
        unlink($failedPath);
    }
}

$error = $stmt->error;

$stmt->close();
$conn->close();

die("Gagal menambahkan pengguna: " . $error);