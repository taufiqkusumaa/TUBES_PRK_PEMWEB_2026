
<?php

// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['id_user'])) {

    header("Location: ../auth/login.php");
    exit();

}

// Memeriksa hak akses pengguna
if ($_SESSION['role'] !== 'mahasiswa') {

    http_response_code(403);
    exit("Akses ditolak! Halaman ini hanya untuk mahasiswa.");

}

?>
