<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
    $students = guruSessionAttendance($sessionId);
    $canEdit = $session['status'] === 'berlangsung';
} catch (DomainException $exception) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Data Siswa & Poin', 'kelas.php');
?>
<?php guru_session_header($session, 'attendance'); ?>
<section class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-semibold text-[#303832]">Kehadiran Siswa</h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($session['class_name'] . ' · ' . $session['semester']) ?>. Status awal siswa adalah Hadir.</p></div><div class="w-full sm:max-w-xs"><label for="student-filter" class="mb-1.5 block text-sm font-medium text-slate-700">Cari siswa</label><input id="student-filter" type="search" placeholder="Nama atau nomor induk" class="min-h-10 w-full rounded-lg border border-[#dfe6de] px-3 text-sm focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]"></div></div>
    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3.5 font-semibold">Nomor Induk</th><th class="px-4 py-3.5 font-semibold">Nama Siswa</th><th class="px-4 py-3.5 font-semibold">Status Kehadiran</th><th class="px-4 py-3.5 font-semibold">Poin</th><th class="px-4 py-3.5 font-semibold">Aksi</th></tr></thead>
            <tbody id="student-attendance-list" class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php foreach ($students as $student): ?>
                    <?php $attendanceLabel = ucfirst((string) $student['attendance_status']); ?>
                    <tr data-student-row>
                        <td class="whitespace-nowrap px-4 py-3.5"><?= attendara_ui_escape($student['number']) ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><?= attendara_ui_escape($student['name']) ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><span class="rounded-full bg-[#e8f1e7] px-2.5 py-1 text-xs font-medium text-[#31583c]"><?= attendara_ui_escape($attendanceLabel) ?></span></td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-slate-500"><?= number_format((float) $student['points'], 2, '.', ',') ?></td>
                        <td class="whitespace-nowrap px-4 py-3.5"><div class="flex gap-2"><?php if ($canEdit): ?><a href="absensi-edit.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $student['id'] ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#3f6548] hover:bg-[#edf4ec]">Ubah Status</a><a href="poin-tambah.php?session_id=<?= (int) $sessionId ?>&amp;id=<?= (int) $student['id'] ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#3f6548] hover:bg-[#edf4ec]">Tambah Poin</a><?php else: ?>—<?php endif; ?></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="no-students-found" class="hidden rounded-xl bg-white px-4 py-6 text-center text-sm text-slate-500">Tidak ada siswa yang cocok.</p>
</section>
<div class="flex flex-wrap gap-2"><a href="tap-sesi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Kembali ke Tap</a><a href="kelompok.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Lanjut ke Kelompok</a></div>
<script>
const attendanceRows = [...document.querySelectorAll('[data-student-row]')];
document.getElementById('student-filter').addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase('id');
    let shown = 0;
    attendanceRows.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('id').includes(query); if (!row.hidden) shown++; });
    document.getElementById('no-students-found').classList.toggle('hidden', shown > 0);
});
</script>
<?php guru_page_end(); ?>
