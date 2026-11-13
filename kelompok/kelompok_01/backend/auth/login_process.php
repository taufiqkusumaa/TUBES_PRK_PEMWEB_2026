
<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email dan password wajib diisi.';
    header('Location: ../../login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Format email tidak valid.';
    header('Location: ../../login.php');
    exit;
}

try {
    $stmt = $pdo->prepare('CALL sp_login(?)');
    $stmt->execute([$email]);

    $user = $stmt->fetch();
    $stmt->closeCursor();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Email atau password salah.';
        header('Location: ../../login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id_pengguna' => $user['id_pengguna'],
        'id_keluarga' => $user['id_keluarga'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role' => $user['role']
    ];

    if ($user['role'] === 'admin') {
        header('Location: ../../pages/admin_dashboard.php');
    } else {
        header('Location: ../../pages/dashboard.php');
    }

    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Terjadi kesalahan saat proses login.';
    header('Location: ../../login.php');
    exit;
}