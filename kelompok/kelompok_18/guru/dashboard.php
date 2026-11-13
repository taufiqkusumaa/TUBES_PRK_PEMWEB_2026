<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $schedules = $profile !== null && $activeSemester !== null
        ? guruActiveSchedules((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $classCounts = [];
    foreach ($classes as $class) {
        $classCounts[(int) $class['id']] = (int) $class['student_count'];
    }
    $studentCount = array_sum($classCounts);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Dashboard Guru', 'dashboard.php');
?>
<section aria-label="Ringkasan Guru" class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
    <div class="col-span-2 sm:col-span-1">
        <?php attendara_ui_stat_card('Profil Guru', $profile['nomor_induk'] ?? 'Belum terhubung', $profile['nama'] ?? 'Profil Guru belum tersedia', 'users', true, false, true); ?>
    </div>
    <div class="col-span-1">
        <?php attendara_ui_stat_card('Kelas ditugaskan', (string) count($classCounts), $studentCount . ' siswa pada kelas aktif', 'school', true, false, true); ?>
    </div>
    <div class="col-span-1">
        <?php attendara_ui_stat_card('Jadwal mengajar', (string) count($schedules), $activeSemester['nama'] ?? 'Tidak ada semester aktif', 'calendar', true, false, true); ?>
    </div>
</section>

<section class="space-y-4">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#303832]">Hari Mengajar</h2>
            <p class="mt-1 text-sm text-slate-600">Hari, kelas, dan waktu mengajar.</p>
        </div>
        <span class="text-sm font-medium text-slate-500"><?= count($schedules) ?> jadwal</span>
    </div>
    <?php if ($schedules === []): ?>
        <div class="rounded-2xl border border-dashed border-[#dfe6de] bg-white px-5 py-10 text-center">
            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e8f0e9] text-[#3f6548]"><?php attendara_ui_icon('calendar', 'h-6 w-6'); ?></span>
            <h3 class="mt-4 text-sm font-semibold text-[#303832]">Belum ada jadwal mengajar</h3>
            <p class="mt-1 text-sm text-slate-500">Jadwal untuk semester aktif akan tampil di sini.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-3 lg:grid-cols-2">
            <?php foreach ($schedules as $schedule): ?>
                <article class="flex min-w-0 items-center gap-4 rounded-2xl border border-[#dfe6de] bg-white p-4 shadow-[0_2px_8px_rgba(30,55,38,0.04)] sm:p-5">
                    <span class="inline-flex min-h-12 min-w-20 shrink-0 items-center justify-center rounded-xl bg-[#e8f0e9] px-3 py-2 text-center text-sm font-semibold text-[#3f6548]"><?= attendara_ui_escape($schedule['day_label']) ?></span>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-[#303832]"><?= attendara_ui_escape($schedule['class_name']) ?></h3>
                        <p class="mt-1 truncate text-sm text-slate-500"><?= attendara_ui_escape($schedule['subject_name'] . ' · ' . $schedule['semester']) ?></p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-[#f6f8f5] px-3 py-2 text-sm font-semibold tabular-nums text-slate-700"><?= attendara_ui_escape($schedule['start'] . '–' . $schedule['end']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php guru_page_end(); ?>
