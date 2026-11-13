<section class="max-w-4xl space-y-5">
    <a href="kelas.php" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#64666a] hover:bg-[#eff3ee] hover:text-[#303832] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">← Kembali ke daftar kelas</a>

    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eaf1ea] text-[#4e7a5d]">
            <?php attendara_ui_icon('school', 'h-5 w-5'); ?>
        </span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Kelas' : 'Tambah Kelas' ?></h2>
            <p class="mt-1 text-sm text-[#64666a]">Kelas digunakan untuk penempatan siswa dan penugasan guru per semester.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="kelas.php" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <div class="sm:col-span-2">
            <label for="semester_id" class="mb-1.5 block text-sm font-medium text-[#414942]">Semester</label>
            <select id="semester_id" name="semester_id" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 text-sm text-[#303832] focus:border-[#4e7a5d] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
                <option value="0">Pilih semester</option>
                <?php foreach ($semesterList as $semester): ?>
                    <option value="<?= h((string) $semester['id']) ?>" <?= (int) $semester['id'] === $formValues['semester_id'] ? 'selected' : '' ?>>
                        <?= h((string) $semester['nama']) ?><?= $semester['status'] === 'aktif' ? ' (aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php
        admin_ui_input('Kode', 'kode', $formValues['kode'], 'text', 'Contoh: X-A');
        admin_ui_input('Nama', 'nama', $formValues['nama'], 'text', 'Contoh: Kelas X-A');
        admin_ui_input('Tingkat (opsional)', 'tingkat', $formValues['tingkat'], 'number', 'Contoh: 10');
        ?>

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Kelas' ?>
            </button>
            <a href="kelas.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200 focus-visible:ring-offset-2">Batal</a>
        </div>
    </form>
</section>
