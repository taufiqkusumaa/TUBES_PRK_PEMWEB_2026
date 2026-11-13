<?php
// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Mengambil data pengguna dari session login
$nama_user = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role_user = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'FlashPOS — Sistem Kasir & Split Bill' ?></title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:  '#eef4ff',
                            100: '#dce8ff',
                            500: '#3b6ef5',
                            600: '#2f5ee0',
                            700: '#2449b8'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- CSS Khusus Cetak Bukti Transaksi -->
    <link rel="stylesheet" href="assets/css/print.css">

    <style>
        /* Scrollbar tipis supaya terasa seperti aplikasi, bukan halaman web */
        .scroll-slim::-webkit-scrollbar { width: 6px; height: 6px; }
        .scroll-slim::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .scroll-slim::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body class="bg-[#eef0f4] text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

<!-- Wrapper Utama -->
<div class="flex h-screen overflow-hidden">
