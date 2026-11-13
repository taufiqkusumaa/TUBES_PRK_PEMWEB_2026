<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$rawClassId = $_GET['class_id'] ?? '';
$rawSemesterId = $_GET['semester_id'] ?? '';
if ((!is_string($rawClassId) && !is_int($rawClassId)) || (!is_string($rawSemesterId) && !is_int($rawSemesterId))) {
    http_response_code(422);
    exit('Filter arsip tidak valid.');
}
$classId = $rawClassId === '' ? null : filter_var($rawClassId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$semesterId = $rawSemesterId === '' ? null : filter_var($rawSemesterId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (($rawClassId !== '' && $classId === false) || ($rawSemesterId !== '' && $semesterId === false)) {
    http_response_code(422);
    exit('Filter arsip tidak valid.');
}

try {
    $filters = guruArchiveFilters();
    $sessions = guruArchiveSessions($classId === false ? null : $classId, $semesterId === false ? null : $semesterId);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Arsip Pembelajaran', 'arsip-pembelajaran.php');
?>
<section class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#303832]">Riwayat Sesi</h2><p class="mt-1 text-sm text-slate-600">Sesi pembelajaran selesai dan dibatalkan.</p></div></div>
    <form method="get" action="arsip-pembelajaran.php" class="grid gap-3 rounded-2xl border border-[#dfe6de] bg-white p-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
        <div><label for="archive-class" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label><select id="archive-class" name="class_id" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm"><option value="">Semua kelas</option><?php foreach ($filters['classes'] as $class): ?><option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>><?= attendara_ui_escape($class['name']) ?></option><?php endforeach; ?></select></div>
        <div><label for="archive-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label><select id="archive-semester" name="semester_id" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm"><option value="">Semua semester</option><?php foreach ($filters['semesters'] as $semester): ?><option value="<?= (int) $semester['id'] ?>"<?= $semesterId === (int) $semester['id'] ? ' selected' : '' ?>><?= attendara_ui_escape($semester['name']) ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white">Terapkan Filter</button>
        <a href="arsip-pembelajaran.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Reset</a>
    </form>
    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white shadow-[0_2px_8px_rgba(48,56,50,0.05)]">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Waktu</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jumlah Siswa</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th><th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold sm:px-5">Aksi</th></tr></thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($sessions === []): ?><tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada sesi pembelajaran.</td></tr>
                <?php else: foreach ($sessions as $session):
                    if ($session['waktu_mulai'] !== null && $session['waktu_selesai'] !== null) {
                        $time = date('H:i', strtotime($session['waktu_mulai'])) . '–' . date('H:i', strtotime($session['waktu_selesai']));
                    } elseif ($session['scheduled_start'] !== null && $session['scheduled_end'] !== null) {
                        $time = substr((string) $session['scheduled_start'], 0, 5) . '–' . substr((string) $session['scheduled_end'], 0, 5);
                    } else { $time = '—'; }
                ?>
                    <tr class="transition-colors hover:bg-[#fafbf9]"><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape(date('d M Y', strtotime($session['tanggal']))) ?></td><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape($session['class_name']) ?></td><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape($session['semester']) ?></td><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape($time) ?></td><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= (int) $session['student_count'] ?> siswa</td><td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= attendara_ui_escape(ucfirst((string) $session['status'])) ?></td><td class="whitespace-nowrap px-4 py-3.5 text-right sm:px-5"><a href="arsip-detail.php?id=<?= (int) $session['id'] ?>" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-3 text-xs font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Lihat Detail</a></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php guru_page_end(); ?>
