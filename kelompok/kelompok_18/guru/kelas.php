<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Kelas', 'kelas.php');
?>
<section class="space-y-4">
    <div class="flex items-end justify-between gap-3">
        <div><h2 class="text-lg font-semibold text-[#303832]">Kelas yang Ditugaskan</h2><p class="mt-1 text-sm text-slate-600">Daftar kelas dan mata pelajaran pada semester aktif.</p></div>
        <span class="shrink-0 text-sm font-medium text-slate-500"><?= count($classes) ?> kelas</span>
    </div>
    <?php if ($classes === []): ?>
        <div class="rounded-2xl border border-dashed border-[#dfe6de] bg-white px-5 py-10 text-center">
            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e8f0e9] text-[#4e7a5d]"><?php attendara_ui_icon('school', 'h-6 w-6'); ?></span>
            <h3 class="mt-4 text-sm font-semibold text-[#303832]">Belum ada kelas aktif</h3>
            <p class="mt-1 text-sm text-slate-500">Kelas yang ditugaskan pada semester aktif akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-4 xl:grid-cols-2">
            <?php foreach ($classes as $class): ?>
                <article class="rounded-2xl border border-[#dfe6de] bg-white p-5 shadow-[0_2px_8px_rgba(48,56,50,0.05)] sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-[#64806a]">Kode <?= attendara_ui_escape($class['code']) ?></p><h3 class="mt-1 text-lg font-semibold text-[#303832]"><?= attendara_ui_escape($class['name']) ?></h3><p class="mt-1 text-sm text-slate-500">Tingkat <?= attendara_ui_escape((string) $class['level']) ?> · <?= attendara_ui_escape($class['semester']) ?></p></div>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e8f0e9] text-[#4e7a5d]"><?php attendara_ui_icon('school', 'h-5 w-5'); ?></span>
                    </div>
                    <p class="mt-4 border-t border-[#e6ebe5] pt-4 text-sm font-medium text-[#526257]"><?= attendara_ui_escape($class['subject_name']) ?></p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="kelas-detail.php?assignment_id=<?= (int) $class['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">Lihat Kelas</a>
                        <a href="pengaturan-kelas.php?assignment_id=<?= (int) $class['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f] transition-colors hover:bg-[#eff3ee]">Pengaturan Kelas</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php guru_page_end(); ?>
