<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "petani") {
    http_response_code(403);
    exit("Akses ditolak! Halaman ini khusus petani.");
}

?>