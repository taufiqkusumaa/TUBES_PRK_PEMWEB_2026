<section class="max-w-4xl space-y-5">
    <a href="guru.php" class="inline-flex min-h-9 items-center rounded-lg border border-[#dfe6de] bg-white px-3 py-1.5 text-sm font-medium text-[#5b645b] hover:bg-[#eef0ed] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">&larr; Kembali ke daftar guru</a>

    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e9] text-[#4e7a5d]">
            <?php attendara_ui_icon('users', 'h-5 w-5'); ?>
        </span>
        <div>
            <h2 class="text-lg font-semibold text-[#303832]"><?= $isEditing ? 'Edit Guru' : 'Tambah Guru' ?></h2>
            <p class="mt-1 text-sm text-slate-600">Lengkapi profil dan akun login guru.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <ul class="space-y-1 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <?php foreach ($errors as $error): ?>
                <li>&bull; <?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="guru.php" class="grid gap-5 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-7">
        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) $formValues['id']) ?>">

        <div>
            <label for="nomor_induk" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor induk</label>
            <input id="nomor_induk" name="nomor_induk" type="text" required maxlength="50" value="<?= h($formValues['nomor_induk']) ?>" placeholder="Contoh: 1987001" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="nama" class="mb-1.5 block text-sm font-medium text-slate-700">Nama</label>
            <input id="nama" name="nama" type="text" required maxlength="150" value="<?= h($formValues['nama']) ?>" placeholder="Contoh: Kadek Dwi" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="email" name="email" type="email" maxlength="254" value="<?= h($formValues['email']) ?>" placeholder="Contoh: guru@sekolah.sch.id" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="no_telepon" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor telepon <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="no_telepon" name="no_telepon" type="tel" maxlength="30" value="<?= h($formValues['no_telepon']) ?>" placeholder="Contoh: 081234567890" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="100" pattern="[A-Za-z0-9._-]{3,100}" value="<?= h($formValues['username']) ?>" placeholder="Contoh: kadek.dwi" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password<?= $isEditing ? ' baru (opsional)' : '' ?></label>
            <input id="password" name="password" type="password" <?= $isEditing ? '' : 'required' ?> minlength="8" autocomplete="new-password" placeholder="<?= $isEditing ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' ?>" class="min-h-10 w-full rounded-lg border border-[#dfe6de] bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#679173] focus:outline-none focus:ring-2 focus:ring-[#eaf1ea]">
            <?php if ($isEditing): ?><p class="mt-1.5 text-xs text-slate-500">Kosongkan jika tidak ingin mengganti password.</p><?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                <?= $isEditing ? 'Simpan Perubahan' : 'Simpan Guru' ?>
            </button>
            <a href="guru.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200 focus-visible:ring-offset-2">Batal</a>
        </div>
    </form>
</section>
