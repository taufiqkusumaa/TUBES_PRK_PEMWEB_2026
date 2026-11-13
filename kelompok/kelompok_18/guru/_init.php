<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
startAppSession();
requireRole('guru');
require_once __DIR__ . '/../includes/ui/layout.php';
require_once __DIR__ . '/_helpers.php';

$guruNavigation = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['label' => 'Kelas', 'href' => 'kelas.php', 'icon' => 'school'],
    ['label' => 'Arsip Pembelajaran', 'href' => 'arsip-pembelajaran.php', 'icon' => 'file'],
    ['label' => 'Logout', 'href' => '../logout.php', 'icon' => 'logout', 'type' => 'logout'],
];

/** Open the compact shared shell for Guru pages. */
function guru_page_start(string $title, string $activeHref): void
{
    global $guruNavigation;
    $headerIcon = 'file';
    foreach ($guruNavigation as $item) {
        if (($item['href'] ?? '') === $activeHref) {
            $headerIcon = (string) ($item['icon'] ?? 'file');
            break;
        }
    }
    attendara_ui_page_start($title, $guruNavigation, $activeHref, true, false, null, null, $headerIcon, true);
}

function guru_page_end(): void
{
    attendara_ui_page_end(false, true);
}

/** Keep the session identity, controls, and section navigation visible on every session page. */
function guru_session_header(array $session, string $activeMenu = '', bool $showNavigation = true): void
{
    $sessionId = (int) $session['id'];
    $status = (string) ($session['status'] ?? '');
    $isOngoing = $status === 'berlangsung';
    $statusLabels = ['berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
    $statusLabel = $statusLabels[$status] ?? $status;
    $scheduleText = ($session['schedule_id'] ?? null) === null
        ? 'Tanpa jadwal'
        : (($session['day_label'] ?? '') . ' · ' . substr((string) ($session['scheduled_start'] ?? ''), 0, 5)
            . '–' . substr((string) ($session['scheduled_end'] ?? ''), 0, 5));
    $menuItems = [
        'tap' => ['Tap Kehadiran', 'tap-sesi.php?session_id=' . $sessionId],
        'attendance' => ['Data Siswa & Poin', 'absensi.php?session_id=' . $sessionId],
        'groups' => ['Kelompok', 'kelompok.php?session_id=' . $sessionId],
    ];
    ?>
    <section class="flex flex-col gap-4 rounded-2xl border border-[#dfe7dd] bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="inline-flex rounded-full bg-[#e8f1e7] px-3 py-1 text-xs font-semibold text-[#31583c]"><?= attendara_ui_escape($statusLabel) ?></span><span class="text-sm text-slate-500"><?= attendara_ui_escape($session['subject_name'] ?? '') ?></span></div><h2 class="mt-2 text-lg font-semibold text-[#303832]"><?= attendara_ui_escape($session['class_name'] ?? '') ?></h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($scheduleText . ' · ' . ($session['semester'] ?? '') . ' · ' . date('d M Y', strtotime((string) $session['tanggal']))) ?></p></div>
        <?php if ($isOngoing): ?>
            <div class="flex shrink-0 flex-wrap gap-2"><button id="open-finish" type="button" class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white hover:bg-[#41684f]">Selesaikan Sesi</button><button id="open-cancel" type="button" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Sesi</button></div>
        <?php elseif ($status === 'selesai'): ?>
            <form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>" onsubmit="return window.confirm('Tandai sesi ini sebagai dibatalkan?')"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="cancel_session"><button type="submit" class="min-h-10 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700 hover:bg-red-50">Tandai Sesi Dibatalkan</button></form>
        <?php endif; ?>
    </section>
    <?php if ($showNavigation): ?>
        <nav aria-label="Menu sesi" class="flex flex-wrap gap-2">
            <?php foreach ($menuItems as $key => [$label, $href]): ?><a href="<?= attendara_ui_escape($href) ?>"<?= $activeMenu === $key ? ' aria-current="page"' : '' ?> class="rounded-lg px-4 py-2 text-sm font-semibold <?= $activeMenu === $key ? 'bg-[#4e7a5d] text-white' : 'border border-[#dfe6de] bg-white text-[#526257] hover:bg-[#f2f6f2]' ?>"><?= attendara_ui_escape($label) ?></a><?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <?php if ($isOngoing): ?>
        <dialog id="finish-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#303832]">Selesaikan sesi?</h2><p class="mt-2 text-sm text-slate-600">Pastikan status kehadiran dan poin sudah diperiksa.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="finish-dialog" class="min-h-10 rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold">Kembali</button><form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="finish_session"><button type="submit" class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white">Selesaikan</button></form></div></div></dialog>
        <dialog id="cancel-dialog" class="w-[min(28rem,calc(100%-2rem))] rounded-2xl border border-[#dfe7dd] p-0 shadow-xl backdrop:bg-slate-900/30"><div class="p-6"><h2 class="text-lg font-semibold text-[#303832]">Batalkan sesi?</h2><p class="mt-2 text-sm text-slate-600">Sesi ini akan ditandai batal.</p><div class="mt-6 flex justify-end gap-2"><button type="button" data-close="cancel-dialog" class="min-h-10 rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold">Kembali</button><form method="post" action="sesi-aktif.php?id=<?= $sessionId ?>"><input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="cancel_session"><button type="submit" class="min-h-10 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white">Batalkan Sesi</button></form></div></div></dialog>
        <script>
        const finishDialog = document.getElementById('finish-dialog');
        const cancelDialog = document.getElementById('cancel-dialog');
        document.getElementById('open-finish').addEventListener('click', () => finishDialog.showModal());
        document.getElementById('open-cancel').addEventListener('click', () => cancelDialog.showModal());
        document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.close).close()));
        </script>
    <?php endif; ?>
    <?php
}

/** Render a visual-only action button. */
function guru_ui_button(string $label, string $variant = 'primary'): void
{
    $classes = $variant === 'secondary'
        ? 'border border-[#dfe6de] bg-white text-[#41684f] hover:bg-[#f2f6f2]'
        : 'bg-[#4e7a5d] text-white hover:bg-[#41684f]';
    ?>
    <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2 <?= $classes ?>">
        <?= attendara_ui_escape($label) ?>
    </button>
    <?php
}

/** Render a responsive table from escaped text values. */
function guru_render_table(array $headers, array $rows, string $emptyMessage = 'Belum ada data.'): void
{
    ?>
    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white shadow-[0_2px_8px_rgba(48,56,50,0.05)]">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr>
                <?php foreach ($headers as $header): ?><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5"><?= attendara_ui_escape((string) $header) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= max(count($headers), 1) ?>" class="px-4 py-8 text-center text-slate-500"><?= attendara_ui_escape($emptyMessage) ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr class="transition-colors hover:bg-[#fafbf9]"><?php foreach ($row as $cell): ?><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape((string) $cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
