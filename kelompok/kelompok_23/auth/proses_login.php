<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: login.php?error=kosong");
    exit;
}

$query = "SELECT id, name, username, password, role, is_active, photo
          FROM users
          WHERE username = ?
          LIMIT 1";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Error database: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result === false) {
    die("Error database: " . $stmt->error);
}

$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login.php?error=gagal");
    exit;
}

if (!(int) $user['is_active']) {
    header("Location: login.php?error=nonaktif");
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['photo'] = $user['photo'] ?? null;

header("Location: ../dashboard.php");
exit;
