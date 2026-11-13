<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo

// Daftar status yang diizinkan, sama dengan nilai ENUM di kabarsi.sql.
$daftarStatus = ['menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'publikasi' => 'Publikasi'];

// Pesan singkat setelah status diubah.
$pesan = isset($_GET['pesan']) && $_GET['pesan'] === 'status' ? 'Status aspirasi berhasil diubah.' : '';

// Ambil semua aspirasi. Pengirim yang anonim tidak ditampilkan namanya.
$daftarAspirasi = $pdo->query(
    'SELECT a.id_aspirasi, a.judul_aspirasi, a.status, a.tanggal_kirim, a.is_anonim,
            u.nama_lengkap, k.nama_kategori
       FROM aspirasi a
       JOIN users u ON u.id_user = a.id_user
       JOIN kategori k ON k.id_kategori = a.id_kategori
      ORDER BY a.tanggal_kirim DESC'
)->fetchAll();
?>
<?php
$judulTab   = 'Aspirasi';
$breadcrumb = [['Dashboard', '/admin/index.php'], ['Aspirasi', null]];
include __DIR__ . '/../../includes/admin_header.php';
?>

<section class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Kelola Aspirasi</h1>
    <p class="mt-1 text-sm text-slate-500">Ubah status aspirasi yang masuk dari mahasiswa.</p>
</section>

<?php if ($pesan !== ''): ?>
    <div class="kaca mb-4 rounded-2xl p-4 text-sm font-medium text-emerald-800"><?= e($pesan) ?></div>
<?php endif; ?>

<section class="kaca overflow-hidden rounded-2xl" aria-label="Daftar aspirasi">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-white/40 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="px-5 py-3 font-medium">Judul</th>
                    <th scope="col" class="px-5 py-3 font-medium">Kategori</th>
                    <th scope="col" class="px-5 py-3 font-medium">Pengirim</th>
                    <th scope="col" class="px-5 py-3 font-medium">Tanggal</th>
                    <th scope="col" class="px-5 py-3 font-medium">Status</th>
                    <th scope="col" class="px-5 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
            <?php if (!$daftarAspirasi): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada aspirasi.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarAspirasi as $a): ?>
                <tr class="align-top hover:bg-white/50">
                    <td class="px-5 py-3 font-medium text-slate-900"><?= e($a['judul_aspirasi']) ?></td>
                    <td class="px-5 py-3 text-slate-700"><?= e($a['nama_kategori']) ?></td>
                    <td class="px-5 py-3 text-slate-700"><?= $a['is_anonim'] ? '<em>Anonim</em>' : e($a['nama_lengkap']) ?></td>
                    <td class="whitespace-nowrap px-5 py-3 text-slate-500"><?= e(date('d M Y H:i', strtotime($a['tanggal_kirim']))) ?></td>
                    <td class="px-5 py-3">
                        <form method="POST" action="ubah_status.php" class="flex flex-wrap items-center gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_aspirasi" value="<?= (int) $a['id_aspirasi'] ?>">
                            <select name="status" class="rounded-lg border border-slate-300 bg-white/80 px-2 py-1.5 text-sm focus:border-clay-600 focus:outline-none focus:ring-2 focus:ring-clay-600/30">
                                <?php foreach ($daftarStatus as $nilaiStatus => $labelStatus): ?>
                                    <option value="<?= e($nilaiStatus) ?>" <?= $a['status'] === $nilaiStatus ? 'selected' : '' ?>>
                                        <?= e($labelStatus) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="rounded-lg bg-clay-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-clay-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2">Simpan</button>
                        </form>
                    </td>
                    <td class="px-5 py-3">
                        <a href="<?= e(admin_url('/aspirasi_detail.php?id=' . (int) $a['id_aspirasi'])) ?>"
                           class="inline-flex rounded-lg border border-clay-600 px-3 py-1.5 text-sm font-medium text-clay-700 hover:bg-clay-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2">
                            Preview
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
</main></div>
</body>
</html>
