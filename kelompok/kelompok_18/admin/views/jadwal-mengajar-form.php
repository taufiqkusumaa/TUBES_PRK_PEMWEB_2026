<section class="max-w-4xl space-y-5">
    <a href="jadwal-mengajar.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#eef0ed] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">&larr; Kembali ke daftar jadwal</a>
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e9] text-[#4e7a5d]"><?php attendara_ui_icon('calendar', 'h-5 w-5'); ?></span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h2>
            <p class="mt-1 text-sm text-slate-600">Atur penugasan, hari, jam, dan masa berlaku jadwal.</p>
        </div>
    </div>
    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?php foreach ($errors as $error): ?><li>&bull; <?= h($error) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <?php if ($assignmentOptions === []): ?>
        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">Belum ada penugasan guru. Buat penugasan terlebih dahulu sebelum menambah jadwal.</p>
    <?php else: ?>
        <form method="post" action="jadwal-mengajar.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
            <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">
            <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">

            <div class="sm:col-span-2">
                <label for="schedule-assignment" class="mb-1.5 block text-sm font-medium text-slate-700">Penugasan Guru</label>
                <select id="schedule-assignment" name="penugasan_guru_id" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                    <option value="" <?= $formValues['penugasan_guru_id'] === 0 ? 'selected' : '' ?> disabled>Pilih penugasan</option>
                    <?php foreach ($assignmentOptions as $option): ?><option value="<?= h((string) $option['id']) ?>" <?= (int) $option['id'] === $formValues['penugasan_guru_id'] ? 'selected' : '' ?>>#<?= h((string) $option['id']) ?> &middot; <?= h((string) $option['guru_nama']) ?> &middot; <?= h((string) $option['mapel_nama']) ?> &middot; <?= h((string) $option['kelas_nama']) ?> &middot; <?= h((string) $option['semester_nama']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="schedule-day" class="mb-1.5 block text-sm font-medium text-slate-700">Hari</label>
                <select id="schedule-day" name="hari" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                    <option value="" <?= $formValues['hari'] === 0 ? 'selected' : '' ?> disabled>Pilih hari</option>
                    <?php foreach (JADWAL_DAYS as $number => $dayName): ?><option value="<?= h((string) $number) ?>" <?= $number === $formValues['hari'] ? 'selected' : '' ?>><?= h((string) $number) ?> &middot; <?= h($dayName) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php
            admin_ui_input('Jam mulai', 'jam_mulai', $formValues['jam_mulai'], 'text', 'Contoh: 07:00');
            admin_ui_input('Jam selesai', 'jam_selesai', $formValues['jam_selesai'], 'text', 'Contoh: 08:30');
            admin_ui_input('Berlaku mulai (opsional)', 'berlaku_mulai', $formValues['berlaku_mulai'], 'date');
            admin_ui_input('Berlaku sampai (opsional)', 'berlaku_sampai', $formValues['berlaku_sampai'], 'date');
            ?>
            <p class="text-xs leading-5 text-slate-500 sm:col-span-2">Hari mengikuti angka aplikasi (1 = Senin sampai 7 = Minggu). Jam menggunakan format HH:MM.</p>
            <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2"><?= $isEditing ? 'Simpan Perubahan' : 'Simpan Jadwal' ?></button>
                <a href="jadwal-mengajar.php<?= $filterSemesterId > 0 ? '?semester_id=' . h((string) $filterSemesterId) : '' ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batal</a>
            </div>
        </form>
    <?php endif; ?>
</section>
