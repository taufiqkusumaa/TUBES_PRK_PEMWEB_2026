<section class="max-w-4xl space-y-5">
    <a href="siswa.php" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#e8f0e9] hover:text-[#303832] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">← Kembali ke daftar siswa</a>
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e9] text-[#4e7a5d]">
            <?php attendara_ui_icon('users', 'h-5 w-5'); ?>
        </span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Siswa' : 'Tambah Siswa' ?></h2>
            <p class="mt-1 text-sm text-slate-600">Lengkapi identitas siswa sesuai data sekolah.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>• <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="siswa.php" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <div>
            <label for="nomor_induk" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor induk</label>
            <input id="nomor_induk" name="nomor_induk" type="text" required maxlength="50" value="<?= h($formValues['nomor_induk']) ?>" placeholder="Contoh: 2026001" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="nama" class="mb-1.5 block text-sm font-medium text-slate-700">Nama</label>
            <input id="nama" name="nama" type="text" required maxlength="150" value="<?= h($formValues['nama']) ?>" placeholder="Contoh: Kadek Dwi" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="jenis_kelamin" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
                <option value="" disabled <?= $formValues['jenis_kelamin'] === '' ? 'selected' : '' ?>>Pilih jenis kelamin</option>
                <option value="L" <?= $formValues['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="P" <?= $formValues['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
        </div>
        <div>
            <label for="tanggal_lahir" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal lahir <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="<?= h($formValues['tanggal_lahir']) ?>" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
            <p class="mt-1.5 text-xs text-slate-500">Pilih tanggal lahir siswa jika tersedia.</p>
        </div>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Siswa' ?>
            </button>
            <a href="siswa.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200 focus-visible:ring-offset-2">Batal</a>
        </div>
    </form>
</section>
