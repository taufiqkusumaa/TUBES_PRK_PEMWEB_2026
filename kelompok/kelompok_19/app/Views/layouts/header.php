<?php
// app/Views/layouts/header.php
// Kerangka global: head, bar mobile, dan content; sidebar desktop di layouts/sidebar.php.
$bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$tanggalPanjang = date('j') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
$halamanAktif = isset($title) ? $title : '';
$menu = [
    ['judul' => 'Manajemen Lahan', 'label' => 'Lahan', 'url' => '/lahan', 'ikon' => 'lahan'],
    ['judul' => 'Manajemen Siklus Tanam', 'label' => 'Siklus', 'url' => '/siklus', 'ikon' => 'siklus'],
    ['judul' => 'Manajemen Inventaris', 'label' => 'Inventaris', 'url' => '/inventaris', 'ikon' => 'inventaris'],
    ['judul' => 'Manajemen Pekerja', 'label' => 'Pekerja', 'url' => '/pekerja', 'ikon' => 'pekerja'],
    ['judul' => 'Manajemen Panen', 'label' => 'Panen', 'url' => '/panen', 'ikon' => 'panen'],
];
?>
<!DOCTYPE html>
<html lang="id" data-tema="terang">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#F6F8F2">
    <title><?= htmlspecialchars($halamanAktif !== '' ? $halamanAktif : 'Beranda') ?> · KebunKu</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232F5249'/%3E%3Cpath d='M9 23V9m0 8c6 0 9-3 9-8m-9 8c0 5 3 7 9 7' stroke='%23E3DE61' stroke-width='2.4' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
    <script>
        (function () {
            try {
                if (localStorage.getItem('kebunku-tema') === 'gelap') {
                    document.documentElement.setAttribute('data-tema', 'gelap');
                }
            } catch (error) {
                document.documentElement.setAttribute('data-tema', 'terang');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<a href="#konten" class="skip-link">Lompat ke konten utama</a>

<header class="mobile-bar">
    <a href="/" class="brand-mobile" aria-label="KebunKu, beranda">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V8"/><path d="M12 13c5 0 7-3 7-7-5 0-7 3-7 7Z"/><path d="M12 17c-5 0-7-3-7-7 5 0 7 3 7 7Z"/></svg>
        <span>KebunKu</span>
    </a>
    <button class="theme-toggle theme-toggle--mobile" type="button" data-tema-toggle aria-pressed="false" aria-label="Ganti ke mode gelap">
        <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5Z"/></svg>
    </button>
</header>

<?php require __DIR__ . "/sidebar.php"; ?>

<main id="konten" class="content" tabindex="-1">

