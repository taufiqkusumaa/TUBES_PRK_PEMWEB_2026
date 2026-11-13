<section class="max-w-4xl space-y-5">
    <a href="penugasan-guru.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#eef0ed] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">&larr; Kembali ke daftar penugasan</a>
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e9] text-[#4e7a5d]"><?php attendara_ui_icon('users', 'h-5 w-5'); ?></span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Penugasan Guru' : 'Tambah Penugasan Guru' ?></h2>
            <p class="mt-1 text-sm text-slate-600">Pilih guru, mata pelajaran, dan kelas untuk penugasan ini.</p>
        </div>
    </div>
    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?php foreach ($errors as $error): ?><li>&bull; <?= h($error) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post" action="penugasan-guru.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">
        <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">

        <div>
            <label for="assignment-teacher" class="mb-1.5 block text-sm font-medium text-slate-700">Guru</label>
            <select id="assignment-teacher" name="guru_id" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                <option value="" <?= $formValues['guru_id'] === 0 ? 'selected' : '' ?> disabled>Pilih guru</option>
                <?php foreach ($guruList as $guru): ?><option value="<?= h((string) $guru['id']) ?>" <?= (int) $guru['id'] === $formValues['guru_id'] ? 'selected' : '' ?>><?= h((string) $guru['nama']) ?> &middot; <?= h((string) $guru['nomor_induk']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="assignment-subject" class="mb-1.5 block text-sm font-medium text-slate-700">Mata Pelajaran</label>
            <select id="assignment-subject" name="mata_pelajaran_id" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                <option value="" <?= $formValues['mata_pelajaran_id'] === 0 ? 'selected' : '' ?> disabled>Pilih mata pelajaran</option>
                <?php foreach ($mataPelajaranList as $mapel): ?><option value="<?= h((string) $mapel['id']) ?>" <?= (int) $mapel['id'] === $formValues['mata_pelajaran_id'] ? 'selected' : '' ?>><?= h((string) $mapel['nama']) ?> &middot; <?= h((string) $mapel['kode']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="assignment-class" class="mb-1.5 block text-sm font-medium text-slate-700">Kelas</label>
            <select id="assignment-class" name="kelas_id" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                <option value="" <?= $formValues['kelas_id'] === 0 ? 'selected' : '' ?> disabled>Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?><option value="<?= h((string) $kelas['id']) ?>" <?= (int) $kelas['id'] === $formValues['kelas_id'] ? 'selected' : '' ?>><?= h((string) $kelas['nama']) ?> &middot; <?= h((string) $kelas['semester_nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2"><?= $isEditing ? 'Simpan Perubahan' : 'Simpan Penugasan' ?></button>
            <a href="penugasan-guru.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batal</a>
        </div>
    </form>
</section>
