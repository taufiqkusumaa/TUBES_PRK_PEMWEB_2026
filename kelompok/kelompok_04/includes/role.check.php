
<?php
// ==========================================
// 1. MEMERIKSA STATUS LOGIN
// ==========================================
require_once __DIR__ . '/auth.check.php';

// ==========================================
// 2. MENGAMBIL ROLE PENGGUNA
// ==========================================
$role_sekarang = strtolower(
    trim($_SESSION['user']['role'] ?? '')
);

// ==========================================
// 3. MEMERIKSA HAK AKSES
// ==========================================
$allowed_roles = $allowed_roles ?? [];

if (!in_array($role_sekarang, $allowed_roles, true)) {

    http_response_code(403);

    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Akses Ditolak</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'
              rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container mt-5 text-center'>
            <h1 class='text-danger'>403 - Akses Ditolak</h1>
            <p>Maaf, Anda tidak memiliki izin untuk membuka halaman ini.</p>
            <a href='/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_04/index.php'
               class='btn btn-success'>
                Kembali ke Dashboard
            </a>
        </div>
    </body>
    </html>
    ";

    exit;
}
