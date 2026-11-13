<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$groupId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($groupId) || $groupId < 1) {
    http_response_code(404);
    exit('Kelompok tidak ditemukan.');
}

$pageError = '';
$selectedStudentIds = [];
try {
    $group = guruOwnedActiveGroup($groupId);
    if ($group === null) {
        http_response_code(404);
        exit('Kelompok tidak ditemukan.');
    }
    $students = guruGroupStudents((int) $group['assignment_id'], $groupId);
    $groupName = $group['name'];
    foreach ($students as $student) {
        if ((int) $student['is_member'] === 1) {
            $selectedStudentIds[] = (int) $student['id'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Permintaan tidak valid.');
        }
        $postedGroupId = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
        $postedName = $_POST['group_name'] ?? null;
        $postedMembers = $_POST['member_ids'] ?? [];
        if ($postedMembers === null) {
            $postedMembers = [];
        }
        if (!is_array($postedMembers) || $postedGroupId !== $groupId || !is_string($postedName)) {
            http_response_code(422);
            $pageError = 'Data kelompok tidak valid.';
        } else {
            $selectedStudentIds = [];
            foreach ($postedMembers as $memberId) {
                if (!is_string($memberId) || filter_var($memberId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    $pageError = 'Daftar anggota tidak valid.';
                    break;
                }
                $selectedStudentIds[] = (int) $memberId;
            }
            $groupName = $postedName;
            if ($pageError === '') {
                guruUpdateGroup($groupId, $groupName, $selectedStudentIds);
                header('Location: kelompok.php?assignment_id=' . (int) $group['assignment_id']);
                exit;
            }
        }
    }
} catch (DomainException $exception) {
    $pageError = $exception->getMessage();
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}
$selectedLookup = array_fill_keys($selectedStudentIds, true);

guru_page_start('Edit Kelompok', 'kelas.php');
?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php endif; ?>
<section class="max-w-3xl space-y-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:p-6">
    <div><h2 class="text-lg font-semibold text-[#303832]"><?= attendara_ui_escape($groupName) ?></h2><p class="mt-1 text-sm text-slate-600"><?= attendara_ui_escape($group['class_name'] . ' · ' . $group['semester']) ?></p></div>
    <form method="post" action="kelompok-edit.php?id=<?= (int) $groupId ?>" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
        <input type="hidden" name="group_id" value="<?= (int) $groupId ?>">
        <div><label for="group-name-edit" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Kelompok</label><input id="group-name-edit" name="group_name" maxlength="100" required value="<?= attendara_ui_escape($groupName) ?>" class="min-h-10 w-full rounded-lg border border-[#dfe6de] px-3 text-sm"></div>
        <fieldset><legend class="mb-2 text-sm font-medium text-slate-700">Anggota</legend><div class="divide-y divide-[#e6ebe5] rounded-xl border border-[#dfe6de]">
            <?php foreach ($students as $student): ?>
                <label class="flex items-center gap-3 px-3 py-2.5"><input type="checkbox" name="member_ids[]" value="<?= (int) $student['id'] ?>"<?= isset($selectedLookup[(int) $student['id']]) ? ' checked' : '' ?> class="h-4 w-4 rounded border-[#bac9ba] text-[#4e7a5d] focus:ring-[#679173]"><span class="text-sm text-slate-700"><?= attendara_ui_escape($student['name'] . ' · ' . $student['number']) ?></span></label>
            <?php endforeach; ?>
        </div></fieldset>
        <div class="flex flex-wrap gap-2"><button id="save-group-edit" type="submit" class="min-h-10 rounded-lg bg-[#4e7a5d] px-4 text-sm font-semibold text-white">Simpan Perubahan</button><a href="kelompok.php?assignment_id=<?= (int) $group['assignment_id'] ?>" class="inline-flex min-h-10 items-center rounded-lg border border-[#dfe6de] px-4 text-sm font-semibold text-[#41684f]">Batal</a></div>
    </form>
</section>
<?php guru_page_end(); ?>
