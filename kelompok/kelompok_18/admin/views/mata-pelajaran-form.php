<section class="max-w-4xl space-y-5">
    <a href="mata-pelajaran.php" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#e8f0e9] hover:text-[#303832] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">← Kembali ke daftar mata pelajaran</a>

    <div>
        <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?></h2>
        <p class="mt-1 text-sm text-slate-600">
            <?= $isEditing ? 'Perbarui data mata pelajaran.' : 'Tambahkan mata pelajaran yang akan digunakan untuk penugasan guru.' ?>
        </p>
    </div>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="mata-pelajaran.php" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <div>
            <label for="kode" class="mb-1.5 block text-sm font-medium text-slate-700">Kode</label>
            <input id="kode" name="kode" type="text" required maxlength="30" value="<?= h($formValues['kode']) ?>" placeholder="Contoh: MAT" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="nama" class="mb-1.5 block text-sm font-medium text-slate-700">Nama</label>
            <input id="nama" name="nama" type="text" required maxlength="100" value="<?= h($formValues['nama']) ?>" placeholder="Contoh: Matematika" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Mata Pelajaran' ?>
            </button>
            <a href="mata-pelajaran.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200 focus-visible:ring-offset-2">Batal</a>
        </div>
    </form>
</section>
