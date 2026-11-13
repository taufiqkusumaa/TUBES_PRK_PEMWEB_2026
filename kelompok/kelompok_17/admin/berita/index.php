<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

// Pesan singkat setelah tambah, edit, atau hapus. Hanya kode yang diizinkan yang ditampilkan.
$pesanTeks = [
    'tambah' => 'Berita berhasil ditambahkan.',
    'edit'   => 'Berita berhasil diperbarui.',
    'hapus'  => 'Berita berhasil dihapus.',
];
$kode = $_GET['pesan'] ?? '';
$pesan = $pesanTeks[$kode] ?? '';

// Ambil daftar berita beserta nama kategori dan penulisnya.
$stmt = $pdo->prepare(
    'SELECT b.id_berita, b.judul_berita, b.gambar_url, b.tanggal_publikasi,
            k.nama_kategori, u.nama_lengkap
       FROM berita b
       JOIN kategori k ON k.id_kategori = b.id_kategori
       JOIN users u ON u.id_user = b.id_user
      ORDER BY b.tanggal_publikasi DESC'
);
$stmt->execute();
$daftarBerita = $stmt->fetchAll();
?>
<?php
$judulTab   = 'Berita';
$breadcrumb = [['Dashboard', '/admin/index.php'], ['Berita', null]];
include __DIR__ . '/../../includes/admin_header.php';
?>

<section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold text-sage-900">Kelola Berita</h1>
            <span class="rounded-full bg-sage-100 px-2.5 py-0.5 text-xs font-medium text-sage-800"><?= count($daftarBerita) ?> berita</span>
        </div>
        <p class="mt-1 text-sm text-sage-700">Tambah, ubah, atau hapus artikel berita.</p>
    </div>
    <a href="tambah.php"
       class="inline-flex items-center gap-2 self-start rounded-lg bg-clay-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-clay-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2 sm:self-auto">
        <?= ikon('tambah', 'h-4 w-4') ?> Tambah Berita
    </a>
</section>

<?php if ($pesan !== ''): ?>
    <div class="kaca mb-5 flex items-center gap-3 rounded-2xl p-4 text-sm font-medium text-emerald-800" role="status">
        <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
        <?= e($pesan) ?>
    </div>
<?php endif; ?>

<section class="kaca overflow-hidden rounded-3xl" aria-label="Daftar berita">
    <?php if (!$daftarBerita): ?>
        <div class="flex flex-col items-center px-6 py-16 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-sage-100 text-sage-600"><?= ikon('berita', 'h-6 w-6') ?></span>
            <p class="mt-4 text-sm font-medium text-sage-900">Belum ada berita</p>
            <p class="mt-1 text-sm text-sage-700">Klik "Tambah Berita" untuk membuat artikel pertama.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="bg-white/40 text-xs uppercase tracking-wide text-sage-700">
                    <tr>
                        <th scope="col" class="w-28 px-5 py-3 font-medium">Gambar</th>
                        <th scope="col" class="w-2/5 px-4 py-3 font-medium">Judul</th>
                        <th scope="col" class="w-1/6 px-4 py-3 font-medium">Kategori</th>
                        <th scope="col" class="w-1/6 px-4 py-3 font-medium">Penulis</th>
                        <th scope="col" class="w-1/6 px-4 py-3 font-medium">Tanggal</th>
                        <th scope="col" class="w-24 px-4 py-3 font-medium"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sage-100">
                <?php foreach ($daftarBerita as $b): ?>
                    <?php
                    $namaFile   = basename((string) $b['gambar_url']);
                    $adaGambar  = $namaFile !== '' && is_file(__DIR__ . '/../../uploads/' . $namaFile);
                    $inisialPenulis = mb_strtoupper(mb_substr((string) $b['nama_lengkap'], 0, 1));
                    ?>
                    <tr class="hover:bg-white/60">
                        <td class="px-5 py-3">
                            <?php if ($adaGambar): ?>
                                <img src="../../uploads/<?= e($namaFile) ?>" alt="" class="h-14 w-20 rounded-xl object-cover ring-1 ring-sage-200">
                            <?php else: ?>
                                <span class="flex h-14 w-20 items-center justify-center rounded-xl bg-sage-100 text-sage-400 ring-1 ring-sage-200" aria-hidden="true"><?= ikon('berita', 'h-6 w-6') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="truncate px-4 py-3 font-medium text-sage-900" title="<?= e($b['judul_berita']) ?>"><?= e($b['judul_berita']) ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex max-w-full truncate rounded-full bg-sage-100 px-2.5 py-0.5 text-xs font-medium text-sage-800"><?= e($b['nama_kategori']) ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 text-sage-800">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-clay-100 text-xs font-semibold text-clay-700" aria-hidden="true"><?= e($inisialPenulis) ?></span>
                                <span class="truncate"><?= e($b['nama_lengkap']) ?></span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sage-700"><?= e(date('d M Y', strtotime($b['tanggal_publikasi']))) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="edit.php?id=<?= (int) $b['id_berita'] ?>" title="Edit berita"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-sage-700 hover:bg-sage-100 hover:text-sage-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600">
                                    <?= ikon('edit', 'h-4 w-4') ?><span class="sr-only">Edit</span>
                                </a>
                                <form method="POST" action="hapus.php" class="inline"
                                      onsubmit="return confirm('Hapus berita ini? Komentar di berita ini juga akan ikut terhapus.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_berita" value="<?= (int) $b['id_berita'] ?>">
                                    <button type="submit" title="Hapus berita"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                                        <?= ikon('hapus', 'h-4 w-4') ?><span class="sr-only">Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
</main></div>
</body>
</html>