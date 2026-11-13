<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('admin');

require_once __DIR__ . '/../includes/ui/layout.php';

$adminUser = currentUser();
$adminNavigation = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['label' => 'Kelas', 'href' => 'kelas.php', 'icon' => 'school'],
    ['label' => 'Siswa', 'href' => 'siswa.php', 'icon' => 'users'],
    ['label' => 'Guru', 'href' => 'guru.php', 'icon' => 'users'],
    ['label' => 'Mata Pelajaran', 'href' => 'mata-pelajaran.php', 'icon' => 'book'],
    ['label' => 'Semester', 'href' => 'semester.php', 'icon' => 'calendar'],
    ['label' => 'Penempatan Kelas', 'href' => 'penempatan-kelas.php', 'icon' => 'school'],
    ['label' => 'Penugasan Guru', 'href' => 'penugasan-guru.php', 'icon' => 'users'],
    ['label' => 'Jadwal Mengajar', 'href' => 'jadwal-mengajar.php', 'icon' => 'calendar'],
    ['label' => 'Logout', 'href' => '../logout.php', 'icon' => 'logout', 'type' => 'logout'],
];

/** Open the shared Admin shell. */
function admin_page_start(string $title, string $activeHref, ?array $headerAction = null, ?string $headerDescription = null): void
{
    global $adminNavigation;
    $headerIcon = null;
    foreach ($adminNavigation as $item) {
        if (($item['href'] ?? '') === $activeHref) {
            $headerIcon = (string) ($item['icon'] ?? 'file');
            break;
        }
    }
    attendara_ui_page_start($title, $adminNavigation, $activeHref, true, true, $headerAction, $headerDescription, $headerIcon);
}

function admin_page_end(): void
{
    attendara_ui_page_end(true);
}

/** Render an Admin input with the Admin accent colors. */
function admin_ui_input(string $label, string $name, string $value = '', string $type = 'text', string $placeholder = ''): void
{
    attendara_ui_input($label, $name, $value, $type, $placeholder, true);
}

/** UI-only button; it does not submit data or perform CRUD. */
function admin_ui_button(string $label, string $variant = 'primary'): void
{
    $classes = $variant === 'secondary'
        ? 'border border-[#dfe6de] bg-white text-[#41684f] hover:bg-[#eff3ee]'
        : 'bg-[#4e7a5d] text-white hover:bg-[#41684f]';
    ?>
    <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2 <?= $classes ?>">
        <?= attendara_ui_escape($label) ?>
    </button>
    <?php
}

/** Render text rows; action controls are visual only. */
function admin_render_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.', array $actions = []): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr>
                <?php foreach ($headers as $header): ?><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th><?php endforeach; ?>
                <?php if ($actions !== []): ?><th scope="col" class="px-4 py-3.5 font-semibold sm:px-5">Aksi</th><?php endif; ?>
            </tr></thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers) + ($actions !== [] ? 1 : 0), 1) ?>" class="px-4 py-9 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr class="hover:bg-[#fafbf9]">
                        <?php foreach ($row as $cell): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) $cell) ?></td><?php endforeach; ?>
                        <?php if ($actions !== []): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><div class="flex gap-2"><?php foreach ($actions as $action): ?><button type="button" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]"><?= attendara_ui_escape($action) ?></button><?php endforeach; ?></div></td><?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/** Neutral display for literal semester status values. */
function admin_status_badge(string $status): void
{
    ?>
    <span class="inline-flex rounded-full bg-[#f1f3f0] px-2.5 py-1 text-xs font-medium text-[#5d685f]"><?= attendara_ui_escape($status) ?></span>
    <?php
}
