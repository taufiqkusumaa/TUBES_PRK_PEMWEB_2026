<section class="max-w-4xl space-y-5">
    <a href="semester.php" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#e8f0e9] hover:text-[#303832] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">← Kembali ke daftar semester</a>

    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e9] text-[#4e7a5d]">
            <?php attendara_ui_icon('calendar', 'h-5 w-5'); ?>
        </span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Semester' : 'Tambah Semester' ?></h2>
            <p class="mt-1 text-sm text-slate-600">Isi nama, kode, dan rentang tanggal semester.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="semester.php" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <?php
        admin_ui_input('Kode', 'kode', $formValues['kode'], 'text', 'Contoh: 2026-GANJIL');
        admin_ui_input('Nama', 'nama', $formValues['nama'], 'text', 'Contoh: 2026/2027 Ganjil');
        admin_ui_input('Tanggal mulai', 'tanggal_mulai', $formValues['tanggal_mulai'], 'date');
        admin_ui_input('Tanggal selesai', 'tanggal_selesai', $formValues['tanggal_selesai'], 'date');
        ?>

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Semester' ?>
            </button>
            <a href="semester.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200 focus-visible:ring-offset-2">Batal</a>
        </div>
    </form>
</section>
