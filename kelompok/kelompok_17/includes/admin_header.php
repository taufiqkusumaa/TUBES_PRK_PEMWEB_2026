<?php
// Layout atas halaman admin. Dipakai semua halaman admin.
// Pemakaian di halaman admin:
//   require_once __DIR__ . '/../includes/auth_admin.php';   (baris pertama, seperti biasa)
//   $judulTab   = 'Dashboard';
//   $breadcrumb = [['Dashboard', null]];   // tiap item: [label, path atau null]
//   include __DIR__ . '/../includes/admin_header.php';
//   ... isi halaman ...

require_once __DIR__ . '/auth_admin.php'; // memastikan e() dan admin_url() tersedia

$judulTab   = $judulTab ?? 'Admin';
$breadcrumb = $breadcrumb ?? [];

// Ikon SVG inline bergaya outline. Pemakaian: echo ikon('berita', 'h-5 w-5');
if (!function_exists('ikon')) {
    function ikon(string $nama, string $kelas = 'h-5 w-5'): string
    {
        static $path = [
            'dashboard' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
            'berita'    => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z',
            'aspirasi'  => 'M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
            'logout'    => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
            'menu'      => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
            'tutup'     => 'M6 18 18 6M6 6l12 12',
            'tambah'    => 'M12 4.5v15m7.5-7.5h-15',
            'edit'      => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
            'hapus'     => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
            'pengguna'  => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
            'jam'       => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'panah'     => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        ];
        if (!isset($path[$nama])) {
            return '';
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="'
            . htmlspecialchars($kelas, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="'
            . $path[$nama] . '" /></svg>';
    }
}

// Badge status aspirasi: menunggu = amber, ditolak = merah, publikasi = emerald.
if (!function_exists('badge_status')) {
    function badge_status(string $status): string
    {
        $peta = [
            'menunggu' => ['Menunggu', 'bg-amber-100 text-amber-800'],
            'ditolak'   => ['Ditolak', 'bg-red-100 text-red-800'],
            'publikasi' => ['Publikasi', 'bg-emerald-100 text-emerald-800'],
        ];
        [$label, $kelas] = $peta[$status] ?? [$status, 'bg-slate-100 text-slate-700'];
        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $kelas . '">'
            . e($label) . '</span>';
    }
}

$namaAdmin = $_SESSION['nama_lengkap'] ?? 'Admin';
$inisial   = mb_strtoupper(mb_substr($namaAdmin, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($judulTab) ?> - KabarSih Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Inter sebagai font utama, dengan fallback ke font sistem jika koneksi internet tidak tersedia.
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            sage: { 50:'#f3f7f4', 100:'#e3ebe5', 200:'#c4d5ca', 300:'#9fb9a8', 400:'#77977f', 500:'#5a7a64', 600:'#476352', 700:'#3a5043', 800:'#2e4036', 900:'#1f2d25' },
                            clay: { 50:'#fdf4ef', 100:'#fae5d8', 200:'#f5c6ab', 300:'#eea07a', 400:'#e7875a', 500:'#e07a4f', 600:'#d0643a', 700:'#b3502c', 800:'#8f3f23' }
                        },
                        fontFamily: {
                            sans: ['Inter', 'ui-sans-serif', 'system-ui', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif']
                        }
                    }
                }
            };
        }
    </script>
    <style>
        html { background: radial-gradient(circle at 0% 0%, rgba(224,122,79,0.18) 0, transparent 36%), radial-gradient(circle at 100% 100%, rgba(90,122,100,0.24) 0, transparent 42%), #e3ebe5; min-height: 100%; }
        .kaca { background: rgba(255,255,255,0.58); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 10px 30px rgba(31,45,37,0.07); }
    </style>
</head>
<body class="flex gap-4 rounded-[2rem] bg-white/40 p-3 font-sans text-slate-900 antialiased sm:p-4 md:gap-6 md:p-5" style="width:94%;margin:1.5rem auto;min-height:calc(100vh - 3rem);">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-clay-700 focus:shadow">Lewati ke konten utama</a>

<?php include __DIR__ . '/admin_sidebar.php'; ?>

<div class="min-w-0 flex-1">
    <button type="button" id="admin-sidebar-buka"
            class="mb-3 inline-flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-medium text-sage-800 shadow-sm hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600 md:hidden"
            aria-controls="admin-sidebar" aria-expanded="false">
        <?= ikon('menu', 'h-5 w-5') ?> Menu
    </button>

    <main id="konten" class="w-full">