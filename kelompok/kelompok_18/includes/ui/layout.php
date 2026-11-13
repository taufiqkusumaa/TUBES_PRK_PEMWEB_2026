<?php

declare(strict_types=1);

require_once __DIR__ . '/components.php';

/** Open the shared responsive application shell. */
function attendara_ui_page_start(string $title, array $navigation, string $activeHref = '', bool $compactHeader = false, bool $adminShell = false, ?array $headerAction = null, ?string $headerDescription = null, ?string $headerIcon = null, bool $guruShell = false): void
{
    $sidebarShell = $adminShell || $guruShell;
    $navigationId = $adminShell ? 'admin-navigation' : ($guruShell ? 'guru-navigation' : 'main-navigation');
    $menuToggleId = $adminShell ? 'admin-menu-toggle' : 'guru-menu-toggle';
    $menuCloseId = $adminShell ? 'admin-menu-close' : 'guru-menu-close';
    $menuBackdropId = $adminShell ? 'admin-nav-backdrop' : 'guru-nav-backdrop';
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= attendara_ui_escape($title) ?> | ATTENDARA</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <?php if ($adminShell || $guruShell): ?><style>
            .admin-shell table thead th,
            .guru-shell table thead th { text-align: center; }
            .admin-shell table th + th,
            .admin-shell table td + td,
            .guru-shell table th + th,
            .guru-shell table td + td { border-left: 1px solid #e6ebe5; }
        </style><?php endif; ?>
    </head>
    <body class="min-h-screen <?= $adminShell ? 'bg-[#f6f7f4] admin-shell' : ($guruShell ? 'bg-[#f6f7f4] guru-shell' : 'bg-[#f6f8f5]') ?> text-slate-900 antialiased">
        <div class="min-h-screen <?= $sidebarShell ? 'lg:pl-64' : 'md:flex' ?>">
            <?php if ($sidebarShell): ?><div id="<?= $menuBackdropId ?>" class="fixed inset-0 z-40 hidden bg-slate-950/30 lg:hidden" data-sidebar-close></div><?php endif; ?>
            <aside id="<?= $navigationId ?>" class="<?= $sidebarShell ? 'fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-[#41684f] bg-[#4e7a5d] transition-transform duration-200 lg:translate-x-0' : 'border-b border-[#dfe7dd] bg-[#edf2eb] md:min-h-screen md:w-64 md:shrink-0 md:border-b-0 md:border-r' ?>">
                <div class="flex items-center justify-between gap-3 px-5 py-4 md:px-6 md:py-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?= $sidebarShell ? 'bg-white/15' : 'bg-[#1e5638]' ?> text-white">
                            <?php attendara_ui_icon('school', 'h-5 w-5'); ?>
                        </span>
                        <div>
                            <a href="#" class="text-base font-bold tracking-tight <?= $sidebarShell ? 'text-white' : 'text-[#173e2b]' ?>">ATTENDARA</a>
                            <p class="mt-0.5 text-xs <?= $sidebarShell ? 'text-white/75' : 'text-slate-500' ?>"><?= $guruShell ? 'Ruang Guru' : 'Administrasi sekolah' ?></p>
                        </div>
                    </div>
                    <?php if ($sidebarShell): ?>
                        <button id="<?= $menuCloseId ?>" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white/85 hover:bg-white/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white lg:hidden" aria-label="Tutup menu navigasi">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                        </button>
                    <?php endif; ?>
                </div>
                <nav aria-label="Navigasi utama" class="<?= $sidebarShell ? 'flex-1 space-y-1.5 overflow-y-auto px-3 pb-4' : 'flex gap-1.5 overflow-x-auto px-3 pb-3 md:block md:space-y-1.5 md:px-3' ?>">
                    <?php foreach ($navigation as $item):
                        $label = (string) ($item['label'] ?? '');
                        $href = (string) ($item['href'] ?? '#');
                        $icon = (string) ($item['icon'] ?? 'file');
                        $isLogout = ($item['type'] ?? '') === 'logout';
                        $isActive = $activeHref !== '' && $href === $activeHref;
                        if ($sidebarShell && $isLogout) {
                            continue;
                        }
                        $classes = $isActive
                            ? ($sidebarShell
                                ? 'border-l-[3px] border-[#f2f6f2] bg-[#e8f0e9] pl-[9px] text-[#31583c] shadow-sm [&>span]:text-[#31583c]'
                                : 'border-l-[3px] border-[#bfd5c1] bg-[#1e5638] pl-[9px] text-white shadow-sm [&>span]:text-white')
                            : ($isLogout
                                ? 'border-l-[3px] border-transparent bg-red-50 text-red-700 hover:bg-red-100'
                                : ($sidebarShell
                                    ? 'border-l-[3px] border-transparent text-white/90 hover:bg-white/15 hover:text-white'
                                    : 'border-l-[3px] border-transparent text-[#526257] hover:bg-white/70 hover:text-[#173e2b]'));
                    ?>
                        <?php if ($isLogout): ?>
                            <form method="post" action="<?= attendara_ui_escape($href) ?>" class="shrink-0 <?= $sidebarShell ? 'w-full' : 'md:w-full' ?>">
                                <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
                                <button type="submit" class="flex min-h-10 w-full items-center gap-3 rounded-lg border-l-[3px] px-3 py-2 text-left text-sm font-semibold transition-colors <?= $classes ?>">
                                    <span><?php attendara_ui_icon($icon, 'h-[18px] w-[18px]'); ?></span>
                                    <span><?= attendara_ui_escape($label) ?></span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= attendara_ui_escape($href) ?>"<?= $isActive ? ' aria-current="page"' : '' ?> class="flex min-h-10 shrink-0 items-center gap-3 rounded-lg border-l-[3px] px-3 py-2 text-sm font-medium transition-colors <?= $sidebarShell ? 'w-full' : 'md:w-full' ?> <?= $classes ?>">
                                <span class="<?= $sidebarShell ? 'text-white/75' : 'text-[#718276]' ?>"><?php attendara_ui_icon($icon, 'h-[18px] w-[18px]'); ?></span>
                                <span><?= attendara_ui_escape($label) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <?php if ($sidebarShell): ?>
                    <?php foreach ($navigation as $item): ?>
                        <?php if (($item['type'] ?? '') === 'logout'): ?>
                            <div class="border-t border-white/20 px-3 py-3">
                                <form method="post" action="<?= attendara_ui_escape((string) ($item['href'] ?? '#')) ?>">
                                    <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
                                    <button type="submit" class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-white/90 transition-colors hover:bg-white/15 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                        <span class="text-white/75"><?php attendara_ui_icon((string) ($item['icon'] ?? 'logout'), 'h-[18px] w-[18px]'); ?></span>
                                        <span><?= attendara_ui_escape((string) ($item['label'] ?? 'Logout')) ?></span>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>
            <div class="min-w-0 <?= $sidebarShell ? 'min-h-screen' : 'flex-1' ?>">
                <header class="<?= $sidebarShell ? 'sticky top-0 z-30 border-[#dfe6de]' : 'border-[#e2e9e2]' ?> border-b bg-white px-5 py-4 sm:px-6 lg:px-8">
                    <div class="<?= $sidebarShell ? 'flex items-center justify-between gap-3 sm:gap-4' : 'flex items-center justify-between gap-4' ?>">
                        <div class="<?= $sidebarShell ? 'flex min-w-0 items-center gap-2.5 sm:gap-3' : 'flex min-w-0 items-center gap-3' ?>">
                            <?php if ($sidebarShell): ?>
                                    <button id="<?= $menuToggleId ?>" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#dfe6de] bg-white text-[#64666a] hover:bg-[#eaf1ea] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2 lg:hidden" aria-controls="<?= $navigationId ?>" aria-expanded="false" aria-label="Buka menu navigasi">
                                    <svg data-menu-open aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                                    <svg data-menu-close aria-hidden="true" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                                </button>
                            <?php endif; ?>
                            <div class="<?= $sidebarShell && $headerIcon !== null ? 'flex min-w-0 items-center gap-2.5' : '' ?>">
                                <?php if ($sidebarShell && $headerIcon !== null): ?>
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e8f0e9] text-[#4e7a5d]"><?php attendara_ui_icon($headerIcon, 'h-[18px] w-[18px]'); ?></span>
                                <?php endif; ?>
                                <div class="<?= $sidebarShell && $headerIcon !== null ? 'min-w-0' : '' ?>">
                                    <?php if (!$compactHeader): ?><p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#64806a]">Ruang kerja</p><?php endif; ?>
                                    <h1 class="<?= $compactHeader ? '' : 'mt-1 ' ?><?= $sidebarShell ? 'text-lg leading-tight sm:text-2xl sm:leading-normal' : 'text-xl sm:text-2xl' ?> font-semibold tracking-tight <?= $sidebarShell ? 'text-[#303832]' : 'text-[#20392a]' ?>"><?= attendara_ui_escape($title) ?></h1>
                                    <?php if ($adminShell && $headerDescription !== null && $headerDescription !== ''): ?>
                                        <p class="mt-1 max-w-2xl break-words text-[11px] leading-4 text-[#64666a] sm:text-sm sm:leading-5"><?= attendara_ui_escape($headerDescription) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($adminShell && $headerAction !== null && isset($headerAction['label'], $headerAction['href'])): ?>
                            <div class="shrink-0">
                                <span class="sm:hidden"><?php attendara_ui_button('Tambah', (string) $headerAction['href'], 'admin-primary'); ?></span>
                                <span class="hidden sm:inline"><?php attendara_ui_button((string) $headerAction['label'], (string) $headerAction['href'], 'admin-primary'); ?></span>
                            </div>
                        <?php elseif (!$compactHeader): ?>
                            <div class="hidden items-center gap-2 rounded-full bg-[#f1f5ef] px-3 py-1.5 text-xs font-medium text-[#526257] sm:flex">
                                <span class="h-2 w-2 rounded-full bg-[#6d9273]"></span>
                                Area aplikasi
                            </div>
                        <?php endif; ?>
                    </div>
                </header>
                <main class="space-y-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <?php
}

/** Close the shared application shell. */
function attendara_ui_page_end(bool $adminShell = false, bool $guruShell = false): void
{
    $sidebarShell = $adminShell || $guruShell;
    $navigationId = $adminShell ? 'admin-navigation' : 'guru-navigation';
    $menuToggleId = $adminShell ? 'admin-menu-toggle' : 'guru-menu-toggle';
    $menuCloseId = $adminShell ? 'admin-menu-close' : 'guru-menu-close';
    $menuBackdropId = $adminShell ? 'admin-nav-backdrop' : 'guru-nav-backdrop';
    ?>
                </main>
            </div>
        </div>
        <?php if ($sidebarShell): ?>
            <script>
            (() => {
                const menu = document.getElementById('<?= $navigationId ?>');
                const toggle = document.getElementById('<?= $menuToggleId ?>');
                const closeButton = document.getElementById('<?= $menuCloseId ?>');
                const backdrop = document.getElementById('<?= $menuBackdropId ?>');
                const closeMenu = () => {
                    menu.classList.add('-translate-x-full');
                    menu.classList.remove('translate-x-0');
                    backdrop.classList.add('hidden');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.setAttribute('aria-label', 'Buka menu navigasi');
                    toggle.querySelector('[data-menu-open]').classList.remove('hidden');
                    toggle.querySelector('[data-menu-close]').classList.add('hidden');
                };
                const openMenu = () => {
                    menu.classList.remove('-translate-x-full');
                    menu.classList.add('translate-x-0');
                    backdrop.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.setAttribute('aria-label', 'Tutup menu navigasi');
                    toggle.querySelector('[data-menu-open]').classList.add('hidden');
                    toggle.querySelector('[data-menu-close]').classList.remove('hidden');
                };
                if (!menu || !toggle || !closeButton || !backdrop) return;
                toggle.addEventListener('click', () => toggle.getAttribute('aria-expanded') === 'true' ? closeMenu() : openMenu());
                closeButton.addEventListener('click', closeMenu);
                backdrop.addEventListener('click', closeMenu);
                menu.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
                document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
                window.addEventListener('resize', () => { if (window.innerWidth >= 1024) closeMenu(); });
            })();
            </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
