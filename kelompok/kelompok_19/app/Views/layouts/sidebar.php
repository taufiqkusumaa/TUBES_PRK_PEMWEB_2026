<?php
// app/Views/layouts/sidebar.php
// Navigasi samping desktop: brand, menu 5 modul, tema, dan identitas user.
// Memakai variabel $menu dan $halamanAktif yang disiapkan layouts/header.php.
?>
<nav id="nav-utama" class="sidebar" aria-label="Navigasi utama">
    <a href="/" class="brand"<?= $halamanAktif === 'Dashboard' ? ' aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V8"/><path d="M12 13c5 0 7-3 7-7-5 0-7 3-7 7Z"/><path d="M12 17c-5 0-7-3-7-7 5 0 7 3 7 7Z"/></svg>
        <span class="brand-text">KebunKu<small>Catatan kebun</small></span>
    </a>

    <ul class="navigation">
        <?php foreach ($menu as $item): ?>
            <li>
                <a href="<?= $item['url'] ?>"<?= $halamanAktif === $item['judul'] ? ' aria-current="page"' : '' ?>>
                    <?php if ($item['ikon'] === 'lahan'): ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20h18"/><path d="M5 20V9l7-5 7 5v11"/><path d="M9 20v-6h6v6"/></svg>
                    <?php elseif ($item['ikon'] === 'siklus'): ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v5h-5"/><path d="M12 8v4l3 2"/></svg>
                    <?php elseif ($item['ikon'] === 'inventaris'): ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5z"/><path d="m4 8.5 8 4.5 8-4.5"/><path d="M12 13v7"/></svg>
                    <?php elseif ($item['ikon'] === 'pekerja'): ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 5.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5c2.4.6 4 2.6 4 5.5"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h16l-1.5 10h-13z"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/><path d="M9 14v2M15 14v2"/></svg>
                    <?php endif; ?>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="nav-footer">
        <button class="theme-toggle" type="button" data-tema-toggle aria-pressed="false" aria-label="Ganti ke mode gelap">
            <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5Z"/></svg>
            <span class="theme-label">Mode gelap</span>
        </button>
        <p class="user">
            <span class="initials" aria-hidden="true">TK</span>
            <span>Tim Kebun<small>Mandor</small></span>
        </p>
    </div>
</nav>

