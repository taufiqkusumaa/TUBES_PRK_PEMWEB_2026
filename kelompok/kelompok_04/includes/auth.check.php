
<?php
// ==========================================
// 1. MEMULAI SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 2. CEK STATUS LOGIN
// ==========================================
if (!isset($_SESSION['user'])) {
    header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_04/login.php");
    exit;
}

// ==========================================
// 3. CEK DATA ROLE
// ==========================================
if (!isset($_SESSION['user']['role'])) {
    session_unset();
    session_destroy();

    header("Location: /TUBES_PRK_PEMWEB_2026/kelompok/kelompok_04/login.php");
    exit;
}
