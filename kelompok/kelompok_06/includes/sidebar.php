<?php
// Menentukan menu aktif. Variabel $page berasal dari index.php
$active_page = $active_page ?? ($page ?? '');

// Daftar menu: [page, ikon lucide, label, hanya admin?]
$menu_items = [
    ['dashboard', 'layout-grid',  'Dashboard',       true],
    ['kasir',     'shopping-bag', 'Terminal Kasir',  false],
    ['produk',    'package',      'Data Produk',     true],
    ['laporan',   'file-text',    'Laporan & Struk', false],
];

$inisial = strtoupper(substr($nama_user, 0, 1));
?>

<!-- SIDEBAR KIRI: ramping, hanya ikon (disembunyikan saat mode print) -->
<aside class="w-[76px] bg-white border-r border-slate-200/70 flex flex-col items-center justify-between py-5 no-print shrink-0">

    <div class="flex flex-col items-center gap-8">
        <!-- Logo -->
        <a href="index.php" title="FlashPOS"
           class="w-11 h-11 rounded-2xl bg-brand-500 text-white flex items-center justify-center shadow-lg shadow-brand-500/30">
            <i data-lucide="zap" class="w-5 h-5"></i>
        </a>

        <!-- Navigasi -->
        <nav class="flex flex-col items-center gap-2">
            <?php foreach ($menu_items as [$slug, $ikon, $label, $khusus_admin]): ?>
                <?php if ($khusus_admin && ($_SESSION['role'] ?? '') !== 'admin') continue; ?>
                <?php $aktif = ($active_page === $slug); ?>

                <a href="index.php?page=<?= $slug ?>"
                   title="<?= htmlspecialchars($label) ?>"
                   class="group relative w-11 h-11 rounded-xl flex items-center justify-center transition
                          <?= $aktif
                                ? 'bg-brand-50 text-brand-500'
                                : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700' ?>">
                    <i data-lucide="<?= $ikon ?>" class="w-5 h-5"></i>

                    <?php if ($aktif): ?>
                        <span class="absolute -left-[18px] top-2 bottom-2 w-1 rounded-r-full bg-brand-500"></span>
                    <?php endif; ?>

                    <!-- Tooltip -->
                    <span class="pointer-events-none absolute left-14 z-50 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 group-hover:opacity-100 transition">
                        <?= htmlspecialchars($label) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Logout -->
    <a href="logout.php" title="Keluar Sesi"
       class="w-11 h-11 rounded-xl flex items-center justify-center text-red-400 hover:bg-red-50 hover:text-red-600 transition">
        <i data-lucide="log-out" class="w-5 h-5"></i>
    </a>
</aside>

<!-- AREA KONTEN KANAN -->
<div class="flex-1 flex flex-col overflow-hidden min-w-0">

    <!-- TOPBAR ATAS -->
    <header class="h-[72px] bg-white/80 backdrop-blur border-b border-slate-200/70 flex items-center justify-between gap-4 px-6 no-print shrink-0">

        <div class="min-w-0">
            <h1 class="text-[17px] font-bold text-slate-900 truncate"><?= htmlspecialchars($page_heading ?? 'FlashPOS') ?></h1>
            <p class="text-xs text-slate-400"><?= date('d M Y') ?></p>
        </div>

        <div class="flex items-center gap-4">
            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
            </span>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm">
                    <?= htmlspecialchars($inisial) ?>
                </div>
                <div class="hidden sm:block leading-tight">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($nama_user) ?></p>
                    <p class="text-[11px] text-slate-400 capitalize"><?= htmlspecialchars($role_user) ?></p>
                </div>
            </div>
        </div>
    </header>

    <!-- KONTEN DINAMIS DIMULAI DI SINI -->
    <main class="<?= $main_class ?? 'flex-1 overflow-y-auto p-8' ?>">
