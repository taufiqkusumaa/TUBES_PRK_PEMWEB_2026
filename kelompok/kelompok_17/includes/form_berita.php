<?php
// Komponen form berita bersama untuk tambah.php dan edit.php.
// Variabel yang diharapkan sebelum include:
//   $action        : tujuan form (misal "tambah.php" atau "edit.php?id=3")
//   $nilai         : ['judul' => ..., 'kategori' => ..., 'isi' => ..., 'gambar_url' => ...]
//   $kategori      : daftar kategori dari tabel kategori
//   $errors        : daftar pesan error (boleh kosong)
//   $judulHalaman  : judul di atas form
//   csrf_field() harus sudah tersedia (dari includes/csrf.php)

$judulHalaman = $judulHalaman ?? 'Form Berita';
$fieldKelas   = 'w-full rounded-xl border border-sage-200 bg-white px-4 py-2.5 text-sm text-sage-900 shadow-sm placeholder:text-sage-400 focus:border-clay-500 focus:outline-none focus:ring-4 focus:ring-clay-500/15';
?>
<section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-sage-900"><?= e($judulHalaman) ?></h1>
        <p class="mt-1 text-sm text-sage-700">Isi judul, kategori, dan isi berita. Gambar boleh dikosongkan.</p>
    </div>
    <a href="index.php" class="inline-flex items-center gap-2 rounded-lg border border-sage-200 bg-white/70 px-4 py-2 text-sm font-medium text-sage-800 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600">
        <?= ikon('panah', 'h-4 w-4 rotate-180') ?> Kembali ke daftar
    </a>
</section>

<?php if (!empty($errors)): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50/80 p-4 text-sm text-red-800" role="alert">
        <p class="font-medium">Periksa kembali isian berikut:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <?php foreach ($errors as $pesanError): ?>
                <li><?= e($pesanError) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?= e($action) ?>" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
    <?= csrf_field() ?>

    <div class="kaca space-y-6 rounded-3xl p-6 md:p-8 lg:col-span-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-sage-900" for="judul">Judul Berita</label>
            <input type="text" id="judul" name="judul" maxlength="255" required
                   value="<?= e($nilai['judul']) ?>" placeholder="Tulis judul yang jelas dan singkat"
                   class="<?= $fieldKelas ?>">
            <p class="mt-1.5 text-xs text-sage-700/80">Maksimal 255 karakter.</p>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-sage-900" for="kategori">Kategori</label>
            <select id="kategori" name="kategori" required class="<?= $fieldKelas ?>">
                <option value="">Pilih kategori</option>
                <?php foreach ($kategori as $k): ?>
                    <option value="<?= (int) $k['id_kategori'] ?>"
                        <?= ((string) $nilai['kategori'] === (string) $k['id_kategori']) ? 'selected' : '' ?>>
                        <?= e($k['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-sage-900" for="isi">Isi Berita</label>
            <textarea id="isi" name="isi" rows="14" required placeholder="Tulis isi berita di sini"
                      class="<?= $fieldKelas ?> resize-y leading-relaxed"><?= e($nilai['isi']) ?></textarea>
        </div>
    </div>

    <aside class="kaca flex flex-col gap-6 rounded-3xl p-6 lg:self-start">
        <div>
            <p class="mb-1.5 text-sm font-medium text-sage-900">Gambar Berita</p>

            <?php if (!empty($nilai['gambar_url'])): ?>
                <img src="../../uploads/<?= e($nilai['gambar_url']) ?>" alt="Gambar berita saat ini"
                     class="mb-3 aspect-video w-full rounded-2xl object-cover">
                <p class="mb-3 text-xs text-sage-700/80">Mengunggah gambar baru akan menggantikan gambar ini.</p>
            <?php endif; ?>

            <img id="pratinjau" alt="Pratinjau gambar yang dipilih"
                 class="mb-3 hidden aspect-video w-full rounded-2xl object-cover">

            <label for="gambar"
                   class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-sage-300 bg-white/60 p-6 text-center transition hover:border-clay-500 hover:bg-white focus-within:ring-4 focus-within:ring-clay-500/20">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-sage-100 text-sage-700" aria-hidden="true">
                    <?= ikon('tambah', 'h-5 w-5') ?>
                </span>
                <span class="text-sm font-medium text-sage-900">Klik untuk pilih gambar</span>
                <span class="text-xs text-sage-700/80">JPG, PNG, atau WEBP, maks. 2 MB</span>
                <span id="nama-gambar" class="mt-1 text-xs text-clay-700">Belum ada file dipilih</span>
                <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
        </div>

        <div class="flex flex-col gap-3">
            <button type="submit"
                    class="w-full rounded-xl bg-clay-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-clay-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2">
                Simpan Berita
            </button>
            <a href="index.php"
               class="w-full rounded-xl border border-sage-200 bg-white/70 px-4 py-2.5 text-center text-sm font-medium text-sage-800 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600">
                Batal
            </a>
        </div>
    </aside>
</form>

<script>
// Pratinjau gambar yang dipilih, dan nama filenya, langsung di browser. Tidak dikirim ke server.
(function () {
    var input = document.getElementById('gambar');
    var pratinjau = document.getElementById('pratinjau');
    var nama = document.getElementById('nama-gambar');
    if (!input || !pratinjau || !nama) return;

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) {
            pratinjau.classList.add('hidden');
            pratinjau.removeAttribute('src');
            nama.textContent = 'Belum ada file dipilih';
            return;
        }
        nama.textContent = file.name;
        pratinjau.src = URL.createObjectURL(file);
        pratinjau.classList.remove('hidden');
    });
})();
</script>