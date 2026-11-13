<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$rawSessionId = $_GET['session_id'] ?? '';
$rawAssignmentId = $_GET['assignment_id'] ?? '';
if ((!is_string($rawSessionId) && !is_int($rawSessionId)) || (!is_string($rawAssignmentId) && !is_int($rawAssignmentId))) {
    http_response_code(422);
    exit('Konteks penugasan tidak valid.');
}
$sessionId = $rawSessionId === '' ? null : filter_var($rawSessionId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$assignmentId = $rawAssignmentId === '' ? null : filter_var($rawAssignmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (($rawSessionId !== '' && $sessionId === false) || ($rawAssignmentId !== '' && $assignmentId === false)) {
    http_response_code(422);
    exit('Konteks penugasan tidak valid.');
}

try {
    $profile = guruProfile();
    $activeSemester = guruActiveSemester();
    $classes = $profile !== null && $activeSemester !== null
        ? guruActiveAssignments((int) $profile['id'], (int) $activeSemester['id'])
        : [];
    $session = $sessionId === null ? null : guruOwnedSession((int) $sessionId);
    if ($sessionId !== null && ($session === null || (int) $session['semester_id'] !== (int) ($activeSemester['id'] ?? 0))) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
    if ($session !== null) {
        if ($assignmentId !== null && $assignmentId !== (int) $session['assignment_id']) {
            http_response_code(422);
            exit('Penugasan tidak sesuai dengan sesi.');
        }
        $assignmentId = (int) $session['assignment_id'];
    } elseif ($assignmentId === null && count($classes) === 1) {
        $assignmentId = (int) $classes[0]['assignment_id'];
    }

    $selectedClass = null;
    foreach ($classes as $classOption) {
        if ($assignmentId !== null && (int) $classOption['assignment_id'] === $assignmentId) {
            $selectedClass = $classOption;
            break;
        }
    }
    if ($assignmentId !== null && $selectedClass === null) {
        http_response_code(404);
        exit('Penugasan tidak ditemukan.');
    }
    $groups = $selectedClass === null
        ? []
        : guruGroupsForAssignment((int) $profile['id'], (int) $selectedClass['assignment_id']);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Kelompok Sesi', 'kelas.php');
?>
<?php if ($session !== null): ?>
<?php guru_session_header($session, 'groups'); ?>
<?php endif; ?>
<section class="flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-lg font-semibold text-[#303832]">Kelompok Pembelajaran</h2><p class="mt-1 text-sm text-slate-600">Kelompok dikelola per penugasan mata pelajaran pada semester aktif.</p></div>
    <?php if ($selectedClass !== null): ?><a href="kelompok-baru.php?assignment_id=<?= (int) $selectedClass['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f]">Tambah Kelompok</a><?php endif; ?>
</section>
<?php if ($session === null && count($classes) > 1): ?>
    <form method="get" action="kelompok.php" class="max-w-lg rounded-2xl border border-[#dfe6de] bg-white p-4">
        <label for="group-class" class="mb-1.5 block text-sm font-medium text-slate-700">Pilih Penugasan</label>
        <div class="flex flex-wrap gap-2"><select id="group-class" name="assignment_id" required class="min-h-10 min-w-56 flex-1 rounded-lg border border-[#dfe6de] bg-white px-3 text-sm"><option value="">Pilih penugasan</option><?php foreach ($classes as $classOption): ?><option value="<?= (int) $classOption['assignment_id'] ?>"<?= $assignmentId === (int) $classOption['assignment_id'] ? ' selected' : '' ?>><?= attendara_ui_escape($classOption['name'] . ' · ' . $classOption['subject_name'] . ' · ' . $classOption['semester']) ?></option><?php endforeach; ?></select><button type="submit" class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white">Tampilkan</button></div>
    </form>
<?php endif; ?>
<?php if ($selectedClass === null): ?>
    <p class="rounded-2xl border border-[#dfe6de] bg-white px-5 py-8 text-center text-sm text-slate-500"><?= $classes === [] ? 'Belum ada penugasan pada semester aktif.' : 'Pilih penugasan untuk melihat kelompok.' ?></p>
<?php else: ?>
    <p class="text-sm text-slate-500"><?= attendara_ui_escape($selectedClass['name'] . ' · ' . $selectedClass['subject_name'] . ' · ' . $selectedClass['semester']) ?></p>
    <section class="space-y-3">
        <?php foreach ($groups as $group): ?>
            <article class="rounded-2xl border border-[#dfe6de] bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-[#303832]"><?= attendara_ui_escape($group['name']) ?></h3><p class="mt-1 text-sm text-slate-500"><?= attendara_ui_escape($selectedClass['name'] . ' · ' . $selectedClass['subject_name']) ?> · <?= (int) $group['member_count'] ?> anggota</p></div><a href="kelompok-edit.php?id=<?= (int) $group['id'] ?>" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] px-3 text-sm font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Edit Kelompok</a></div>
            </article>
        <?php endforeach; ?>
        <?php if ($groups === []): ?><p class="rounded-2xl border border-[#dfe6de] bg-white px-5 py-8 text-center text-sm text-slate-500">Belum ada kelompok untuk penugasan ini.</p><?php endif; ?>
    </section>
<?php endif; ?>
<?php if ($session !== null): ?>
    <a href="sesi-aktif.php?id=<?= (int) $session['id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Kembali ke Sesi</a>
<?php elseif ($selectedClass !== null): ?>
    <a href="kelas-detail.php?assignment_id=<?= (int) $selectedClass['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Kembali ke Kelas</a>
<?php endif; ?>
<?php guru_page_end(); ?>
