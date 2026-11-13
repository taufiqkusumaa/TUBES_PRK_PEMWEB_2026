<?php
// Sidebar navigasi admin: panel ikon mengambang (gaya rail). Dipanggil otomatis oleh admin_header.php.
// Link memakai admin_url(), jadi tetap benar dari subfolder seperti admin/berita/.
// Halaman yang sedang dibuka ditandai otomatis dan diberi aria-current="page".

$sbSkrip = $_SERVER['SCRIPT_NAME'] ?? '';
if (str_contains($sbSkrip, '/admin/berita/')) {
    $sbAktif = 'berita';
} elseif (str_contains($sbSkrip, '/admin/aspirasi/aspirasi.php')) {
    $sbAktif = 'aspirasi';
} else {
    $sbAktif = 'dashboard';
}

$sbMenu = [
    'dashboard' => ['Dashboard', '/admin/index.php', 'dashboard'],
    'berita'    => ['Berita', '/admin/berita/index.php', 'berita'],
    'aspirasi'  => ['Aspirasi', '/admin/aspirasi/aspirasi.php', 'aspirasi'],
];
$sbKelasIkon = 'flex h-11 w-11 items-center justify-center rounded-full transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2';
?>
<div id="admin-sidebar-overlay" class="fixed inset-0 z-30 hidden bg-sage-900/40 md:hidden" aria-hidden="true"></div>

<aside id="admin-sidebar" aria-label="Navigasi admin"
       class="kaca fixed inset-y-3 left-3 z-40 flex w-16 -translate-x-[120%] flex-col items-center rounded-[2rem] py-5 transition-transform duration-200 motion-reduce:transition-none md:sticky md:top-5 md:inset-auto md:z-auto md:h-[calc(100vh-2.5rem)] md:shrink-0 md:translate-x-0 md:self-start">
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-clay-600 text-xs font-bold tracking-tight text-white" aria-hidden="true">KS</span>
    <span class="sr-only">KabarSih Admin Panel</span>

    <button type="button" id="admin-sidebar-tutup"
            class="mt-2 hidden rounded-full p-1.5 text-sage-700 hover:bg-sage-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600 max-md:block"
            aria-label="Tutup menu navigasi">
        <?= ikon('tutup', 'h-5 w-5') ?>
    </button>

    <nav class="mt-8 flex flex-1 flex-col items-center gap-3" aria-label="Menu utama">
        <?php foreach ($sbMenu as $kunci => [$label, $path, $namaIkon]): ?>
            <?php $aktif = ($sbAktif === $kunci); ?>
            <a href="<?= e(admin_url($path)) ?>" title="<?= e($label) ?>"
               class="<?= $sbKelasIkon ?> <?= $aktif ? 'bg-sage-900 text-white shadow-md' : 'text-sage-700 hover:bg-sage-100 hover:text-sage-900' ?>"
               <?= $aktif ? 'aria-current="page"' : '' ?>>
                <?= ikon($namaIkon, 'h-5 w-5') ?>
                <span class="sr-only"><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a href="<?= e(admin_url('/auth/logout.php')) ?>" title="Logout"
       class="<?= $sbKelasIkon ?> text-sage-700 hover:bg-sage-100 hover:text-sage-900">
        <?= ikon('logout', 'h-5 w-5') ?>
        <span class="sr-only">Logout</span>
    </a>
</aside>

<script>
// Buka dan tutup panel navigasi di layar kecil. Tutup juga lewat overlay dan tombol Esc.
(function () {
    var panel = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-sidebar-overlay');
    var tombolBuka = document.getElementById('admin-sidebar-buka');
    var tombolTutup = document.getElementById('admin-sidebar-tutup');
    if (!panel || !tombolBuka) return;

    function atur(terbuka) {
        panel.classList.toggle('translate-x-0', terbuka);
        panel.classList.toggle('-translate-x-[120%]', !terbuka);
        overlay.classList.toggle('hidden', !terbuka);
        tombolBuka.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
    }

    tombolBuka.addEventListener('click', function () { atur(true); });
    if (tombolTutup) tombolTutup.addEventListener('click', function () { atur(false); });
    overlay.addEventListener('click', function () { atur(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') atur(false); });
})();
</script>