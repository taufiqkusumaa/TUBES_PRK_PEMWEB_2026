
<?php
// ==========================================
// 1. KONFIGURASI ERROR
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ==========================================
// 2. MEMULAI SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 3. MENGHAPUS SELURUH DATA SESSION
// ==========================================
$_SESSION = [];

// ==========================================
// 4. MENGHAPUS COOKIE SESSION
// ==========================================
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// ==========================================
// 5. MENGHANCURKAN SESSION
// ==========================================
session_destroy();

// ==========================================
// 6. MENGARAHKAN KE HALAMAN LOGIN
// ==========================================
header("Location: login.php?logout=success");
exit;
