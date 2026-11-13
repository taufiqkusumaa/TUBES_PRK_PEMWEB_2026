<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/database.php'; // harus menyediakan $pdo (PDO)

$status = ['menunggu' => 0, 'ditolak' => 0, 'publikasi' => 0];
$totalBerita = (int) $pdo->query('SELECT COUNT(*) FROM berita')->fetchColumn();
$totalUser   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();

$status = ['menunggu' => 0, 'ditolak' => 0, 'publikasi' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM aspirasi GROUP BY status') as $r) {
    $status[$r['status']] = (int) $r['n'];
}
$totalAspirasi   = array_sum($status);
$persenPublikasi = $totalAspirasi > 0 ? (int) round($status['publikasi'] / $totalAspirasi * 100) : 0;

// Lima aspirasi terbaru.
$terbaru = $pdo->query(
    'SELECT a.id_aspirasi, a.judul_aspirasi, a.status, a.tanggal_kirim, a.is_anonim,
            u.nama_lengkap, k.nama_kategori
       FROM aspirasi a
       JOIN users u ON u.id_user = a.id_user
       LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
      ORDER BY a.tanggal_kirim DESC
      LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$beritaTerbaru = $pdo->query(
    'SELECT b.id_berita, b.judul_berita, b.tanggal_publikasi, k.nama_kategori
       FROM berita b
       LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
      ORDER BY b.tanggal_publikasi DESC
      LIMIT 3'
)->fetchAll(PDO::FETCH_ASSOC);

// Jumlah aspirasi masuk per hari, 14 hari terakhir. Hari tanpa aspirasi dihitung 0.
$seri = [];
for ($i = 13; $i >= 0; $i--) {
    $seri[date('Y-m-d', strtotime("-$i day"))] = 0;
}
foreach ($pdo->query('SELECT DATE(tanggal_kirim) AS hari, COUNT(*) AS n FROM aspirasi
                       WHERE tanggal_kirim >= CURDATE() - INTERVAL 13 DAY GROUP BY hari') as $r) {
    if (isset($seri[$r['hari']])) {
        $seri[$r['hari']] = (int) $r['n'];
    }
}

// Grafik garis (SVG) dari daftar angka. Tanpa library, cukup path SVG.
function grafik_garis(array $nilai, int $lebar = 400, int $tinggi = 120): string
{
    $jumlah = count($nilai);
    $maks   = max(1, max($nilai));
    $titik  = [];
    $i = 0;
    foreach ($nilai as $v) {
        $x = $jumlah > 1 ? $i * $lebar / ($jumlah - 1) : $lebar / 2;
        $y = $tinggi - ($v / $maks) * ($tinggi - 12) - 6;
        $titik[] = round($x, 1) . ',' . round($y, 1);
        $i++;
    }
    $garis = 'M' . implode(' L', $titik);
    $area  = $garis . " L{$lebar},{$tinggi} L0,{$tinggi} Z";
    return '<svg viewBox="0 0 ' . $lebar . ' ' . $tinggi . '" preserveAspectRatio="none" class="h-full w-full" aria-hidden="true">'
        . '<path d="' . $area . '" fill="currentColor" fill-opacity="0.12"/>'
        . '<path d="' . $garis . '" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>'
        . '</svg>';
}

// Cincin persentase (SVG) untuk nilai 0 sampai 100.
function cincin_persen(int $persen): string
{
    $r        = 45;
    $keliling = 2 * M_PI * $r;
    $isi      = $keliling * max(0, min(100, $persen)) / 100;
    return '<svg viewBox="0 0 120 120" class="h-full w-full" aria-hidden="true">'
        . '<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="#dfe8e1" stroke-width="12"/>'
        . '<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round" '
        . 'stroke-dasharray="' . round($isi, 2) . ' ' . round($keliling, 2) . '" transform="rotate(-90 60 60)"/>'
        . '</svg>';
}

$judulTab   = 'Dashboard';
$breadcrumb = [['Dashboard', null]];
include __DIR__ . '/../includes/admin_header.php';

$namaDepan = explode(' ', trim($_SESSION['nama_lengkap'] ?? 'Admin'))[0];
$totalSeri = array_sum($seri);
?>

<section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-sage-900">Halo, <?= e($namaDepan) ?>!</h1>
        <p class="mt-1 text-sm text-sage-700">Ringkasan berita dan aspirasi KabarSih.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(admin_url('/admin/berita/tambah.php')) ?>"
           class="inline-flex items-center gap-2 rounded-lg bg-clay-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-clay-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600 focus-visible:ring-offset-2">
            <?= ikon('tambah', 'h-4 w-4') ?> Tambah Berita
        </a>
        <a href="<?= e(admin_url('/admin/aspirasi/aspirasi.php')) ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-sage-200 bg-white/70 px-4 py-2 text-sm font-medium text-sage-800 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-600 focus-visible:ring-offset-2">
            <?= ikon('aspirasi', 'h-4 w-4') ?> Kelola Aspirasi
        </a>
    </div>
</section>

<!-- Empat kartu statistik -->
<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Statistik">
    <div class="kaca rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-sage-700">Total Berita</p>
            <span class="rounded-xl bg-sage-100 p-2 text-sage-700"><?= ikon('berita', 'h-5 w-5') ?></span>
        </div>
        <p class="mt-3 text-3xl font-semibold text-sage-900"><?= $totalBerita ?></p>

    </div>

    <div class="kaca rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-sage-700">Total Aspirasi</p>
            <span class="rounded-xl bg-sage-100 p-2 text-sage-700"><?= ikon('aspirasi', 'h-5 w-5') ?></span>
        </div>
        <p class="mt-3 text-3xl font-semibold text-sage-900"><?= $totalAspirasi ?></p>
        <div class="mt-2 h-10 text-sage-500"><?= grafik_garis(array_values($seri)) ?></div>
    </div>

    <div class="kaca rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-sage-700">Aspirasi Menunggu</p>
            <span class="rounded-xl bg-amber-100 p-2 text-amber-700"><?= ikon('jam', 'h-5 w-5') ?></span>
        </div>
        <p class="mt-3 text-3xl font-semibold text-sage-900"><?= $status['menunggu'] ?></p>

    </div>

    <div class="kaca rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-sage-700">Total Mahasiswa</p>
            <span class="rounded-xl bg-sage-100 p-2 text-sage-700"><?= ikon('pengguna', 'h-5 w-5') ?></span>
        </div>
        <p class="mt-3 text-3xl font-semibold text-sage-900"><?= $totalUser ?></p>

    </div>
</section>

<!-- Grafik aspirasi masuk dan profil -->
<section class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="kaca rounded-2xl p-5 lg:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-sage-900">Aspirasi Masuk</h2>
                <p class="text-sm text-sage-700/80">14 hari terakhir</p>
            </div>
            <p class="text-right">
                <span class="block text-2xl font-semibold text-sage-900"><?= $totalSeri ?></span>
                <span class="text-xs text-sage-700/80">total aspirasi</span>
            </p>
        </div>
        <div class="mt-5 h-48 text-sage-600"><?= grafik_garis(array_values($seri), 600, 200) ?></div>
    </div>

    <div class="kaca rounded-2xl p-5">
            <h2 class="text-base font-semibold text-sage-900">Status Aspirasi</h2>
            <div class="mt-4 flex items-center gap-4">
                <div class="h-24 w-24 shrink-0 text-clay-600"><?= cincin_persen($persenPublikasi) ?></div>
                <div>
                    <p class="text-2xl font-semibold text-sage-900"><?= $persenPublikasi ?>%</p>
                    <p class="text-xs text-sage-700/80">aspirasi sudah dipublikasikan</p>
                </div>
            </div>
            <ul class="mt-5 space-y-3 text-sm">
                <?php foreach (['menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'publikasi' => 'Publikasi'] as $kunci => $label): ?>
                    <?php
                    $persen = $totalAspirasi > 0 ? round($status[$kunci] / $totalAspirasi * 100) : 0;
                    $warnaBar = ['menunggu' => 'bg-clay-600', 'ditolak' => 'bg-clay-800', 'publikasi' => 'bg-clay-400'][$kunci];
                    ?>
                    <li>
                        <div class="mb-1 flex justify-between text-sage-800">
                            <span><?= e($label) ?></span>
                            <span class="font-medium"><?= $status[$kunci] ?></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-sage-100" role="presentation">
                            <div class="h-2 rounded-full <?= $warnaBar ?>" style="width: <?= (int) $persen ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

</section>

<!-- Aspirasi terbaru dan status -->
<section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="kaca overflow-hidden rounded-2xl lg:col-span-2" aria-labelledby="judul-terbaru">
        <div class="flex items-center justify-between px-5 py-4">
            <h2 id="judul-terbaru" class="text-base font-semibold text-sage-900">Aspirasi Terbaru</h2>
            <a href="<?= e(admin_url('/admin/aspirasi/aspirasi.php')) ?>" class="rounded text-sm font-medium text-clay-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600">Lihat semua</a>
        </div>

        <?php if (!$terbaru): ?>
            <div class="flex flex-col items-center px-6 py-12 text-center">
                <span class="rounded-full bg-sage-100 p-3 text-sage-600"><?= ikon('aspirasi', 'h-6 w-6') ?></span>
                <p class="mt-3 text-sm font-medium text-sage-900">Belum ada aspirasi</p>
                <p class="mt-1 text-sm text-sage-700/80">Aspirasi yang dikirim mahasiswa akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full table-fixed text-left text-sm">
                    <thead class="bg-white/40 text-xs uppercase tracking-wide text-sage-700">
                        <tr>
                            <th scope="col" class="w-1/3 px-4 py-3 font-medium">Judul</th>
                            <th scope="col" class="w-1/6 px-4 py-3 font-medium">Pengirim</th>
                            <th scope="col" class="w-1/6 px-4 py-3 font-medium">Kategori</th>
                            <th scope="col" class="w-1/6 px-4 py-3 font-medium">Status</th>
                            <th scope="col" class="w-1/6 px-4 py-3 font-medium">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sage-100">
                        <?php foreach ($terbaru as $a): ?>
                            <tr class="hover:bg-white/50">
                                <td class="truncate px-4 py-3 font-medium text-sage-900"><?= e($a['judul_aspirasi']) ?></td>
                                <td class="truncate px-4 py-3 text-sage-800"><?= $a['is_anonim'] ? '<em>Anonim</em>' : e($a['nama_lengkap']) ?></td>
                                <td class="truncate px-4 py-3 text-sage-800"><?= e($a['nama_kategori'] ?? '-') ?></td>
                                <td class="px-4 py-3"><?= badge_status((string) $a['status']) ?></td>
                                <td class="truncate px-4 py-3 text-sage-700"><?= e(date('d M Y', strtotime($a['tanggal_kirim']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-col gap-4">
        <div class="kaca rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-sage-900">Berita Terbaru</h2>
                <a href="<?= e(admin_url('/admin/berita/index.php')) ?>" class="text-sm font-medium text-clay-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-clay-600">Kelola</a>
            </div>
            <?php if (!$beritaTerbaru): ?>
                <p class="mt-4 text-sm text-sage-700/80">Belum ada berita.</p>
            <?php else: ?>
                <ul class="mt-4 divide-y divide-sage-100">
                    <?php foreach ($beritaTerbaru as $b): ?>
                        <li class="py-3">
                            <p class="truncate text-sm font-medium text-sage-900"><?= e($b['judul_berita']) ?></p>
                            <p class="mt-0.5 text-xs text-sage-700/80">
                                <?= e($b['nama_kategori'] ?? '-') ?> · <?= e(date('d M Y', strtotime($b['tanggal_publikasi']))) ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
</main></div>
</body>
</html>