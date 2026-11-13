<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$assignmentId = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!is_int($assignmentId) || $assignmentId < 1) {
    http_response_code(404);
    exit('Kelas tidak ditemukan.');
}

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $class = $profile !== null && $activeSemester !== null
        ? guruOwnedActiveAssignment((int) $profile['id'], (int) $activeSemester['id'], $assignmentId)
        : null;
    if ($class === null) {
        http_response_code(404);
        exit('Kelas tidak ditemukan.');
    }
    $schedules = guruClassSchedules((int) $class['assignment_id']);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Pengaturan Kelas', 'kelas.php');
?>
<section class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-2xl border border-[#dfe6de] bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold text-[#303832]">Informasi Kelas</h2>
        <dl class="mt-4 divide-y divide-[#e6ebe5] text-sm">
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Nama</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['name']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Kode</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['code']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Tingkat</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape((string) $class['level']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Semester</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['semester']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Mata pelajaran</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($class['subject_name']) ?></dd></div>
        </dl>
    </article>
    <article class="rounded-2xl border border-[#dfe6de] bg-white p-5 sm:p-6">
        <h2 class="text-base font-semibold text-[#303832]">Jadwal Penugasan</h2>
        <?php if ($schedules === []): ?>
            <p class="mt-4 text-sm text-slate-500">Belum ada jadwal aktif untuk kelas ini.</p>
        <?php else: ?>
        <?php foreach ($schedules as $schedule): ?>
        <dl class="mt-4 divide-y divide-[#e6ebe5] text-sm">
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Hari</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($schedule['day_label']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Waktu</dt><dd class="font-medium text-slate-700"><?= attendara_ui_escape($schedule['start'] . '–' . $schedule['end']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Masa berlaku</dt><dd class="text-right font-medium text-slate-700"><?= attendara_ui_escape($schedule['valid_from'] . ' – ' . $schedule['valid_until']) ?></dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Penugasan</dt><dd class="font-medium text-slate-700">#<?= attendara_ui_escape((string) $class['assignment_id']) ?></dd></div>
        </dl>
        <?php endforeach; ?>
        <?php endif; ?>
    </article>
</section>
<p class="rounded-xl border border-[#dfe7dd] bg-[#eff3ee] px-4 py-3 text-sm text-[#526257]">Data kelas, penugasan, dan jadwal dikelola melalui administrasi sekolah.</p>
<a href="kelas-detail.php?assignment_id=<?= (int) $class['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Kembali ke Detail Kelas</a>
<?php guru_page_end(); ?>
