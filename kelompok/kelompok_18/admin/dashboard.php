<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

// Jumlah guru
$statement = $pdo->query('SELECT COUNT(*) FROM guru');
$guruCount = (int) $statement->fetchColumn();

// Jumlah siswa
$statement = $pdo->query('SELECT COUNT(*) FROM siswa');
$siswaCount = (int) $statement->fetchColumn();

// Jumlah kelas
$statement = $pdo->query('SELECT COUNT(*) FROM kelas');
$kelasCount = (int) $statement->fetchColumn();

// Jumlah mata pelajaran
$statement = $pdo->query('SELECT COUNT(*) FROM mata_pelajaran');
$mapelCount = (int) $statement->fetchColumn();

// Jumlah penugasan guru
$statement = $pdo->query('SELECT COUNT(*) FROM penugasan_guru');
$penugasanCount = (int) $statement->fetchColumn();

// Semester aktif
$statement = $pdo->prepare('SELECT nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$semesterRow = $statement->fetch();
$semesterAktif = $semesterRow ? $semesterRow['nama'] : '-';
$ringkasNamaSemester = static function (string $nama): string {
    $nama = preg_replace('/^Tahun Ajaran\s+/u', '', $nama) ?? $nama;
    return preg_replace('/\s*[—–-]\s*/u', ' · ', $nama) ?? $nama;
};
if ($semesterAktif !== '-') {
    $semesterAktif = $ringkasNamaSemester((string) $semesterAktif);
}

// Kelas pada semester aktif dengan jumlah siswa pada semester kelas tersebut.
$statement = $pdo->prepare('
    SELECT k.nama AS nama_kelas,
           sm.nama AS nama_semester,
           COUNT(ak.siswa_id) AS jumlah_siswa
    FROM kelas AS k
    INNER JOIN semester AS sm ON sm.id = k.semester_id
    LEFT JOIN anggota_kelas AS ak ON ak.kelas_id = k.id AND ak.semester_id = k.semester_id
    WHERE k.is_active = TRUE AND sm.status = :status
    GROUP BY k.id, k.nama, sm.nama
    ORDER BY k.nama
');
$statement->execute(['status' => 'aktif']);
$kelasAktif = $statement->fetchAll();

// Jumlah siswa yang tercatat pada setiap semester, termasuk semester lampau.
$statement = $pdo->query('
    SELECT sm.id,
           sm.nama AS nama_semester,
           COUNT(DISTINCT ak.siswa_id) AS jumlah_siswa
    FROM semester AS sm
    LEFT JOIN anggota_kelas AS ak ON ak.semester_id = sm.id
    GROUP BY sm.id, sm.nama, sm.tanggal_mulai
    ORDER BY sm.tanggal_mulai
');
$siswaPerSemester = $statement->fetchAll();

$jumlahSiswaKelasAktif = 0;
$chartColors = ['#4e7a5d', '#c2785c', '#927a9e', '#c19a3d', '#628a74', '#b86e73'];
$chartStops = [];
foreach ($kelasAktif as $index => $kelas) {
    $jumlahSiswaKelasAktif += (int) ($kelas['jumlah_siswa'] ?? 0);
}
$sudutAkhir = 0;
if ($jumlahSiswaKelasAktif > 0) {
    foreach ($kelasAktif as $index => $kelas) {
        $jumlah = (int) ($kelas['jumlah_siswa'] ?? 0);
        $sudutBerikutnya = $index === array_key_last($kelasAktif)
            ? 100
            : $sudutAkhir + (($jumlah / $jumlahSiswaKelasAktif) * 100);
        if ($jumlah > 0) {
            $warna = $chartColors[$index % count($chartColors)];
            $chartStops[] = $warna . ' ' . number_format($sudutAkhir, 2, '.', '') . '% ' . number_format($sudutBerikutnya, 2, '.', '') . '%';
        }
        $sudutAkhir = $sudutBerikutnya;
    }
}

$jumlahCatatanSemester = array_sum(array_map(
    static fn (array $semester): int => (int) ($semester['jumlah_siswa'] ?? 0),
    $siswaPerSemester
));
$semesterChartStops = [];
$sudutAkhir = 0;
if ($jumlahCatatanSemester > 0) {
    foreach ($siswaPerSemester as $index => $semester) {
        $jumlah = (int) ($semester['jumlah_siswa'] ?? 0);
        $sudutBerikutnya = $index === array_key_last($siswaPerSemester)
            ? 100
            : $sudutAkhir + (($jumlah / $jumlahCatatanSemester) * 100);
        if ($jumlah > 0) {
            $warna = $chartColors[$index % count($chartColors)];
            $semesterChartStops[] = $warna . ' ' . number_format($sudutAkhir, 2, '.', '') . '% ' . number_format($sudutBerikutnya, 2, '.', '') . '%';
        }
        $sudutAkhir = $sudutBerikutnya;
    }
}

admin_page_start('Dashboard', 'dashboard.php');
?>
<section aria-label="Grafik ringkasan siswa" class="grid gap-6 xl:grid-cols-2">
    <article class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-[#303832]">Siswa per Kelas Aktif</h2>
            <p class="mt-1 text-sm text-slate-600">Proporsi siswa pada setiap kelas semester aktif.</p>
        </div>
        <?php if ($kelasAktif === []): ?>
            <p class="rounded-2xl border border-[#dfe6de] bg-white px-5 py-6 text-sm text-slate-500">Belum ada kelas pada semester aktif.</p>
        <?php else: ?>
            <figure class="flex flex-col items-center gap-6 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:flex-row sm:justify-center sm:p-6">
                <?php if ($jumlahSiswaKelasAktif > 0): ?>
                    <div role="img" aria-label="Diagram proporsi jumlah siswa untuk setiap kelas aktif" class="relative h-48 w-48 shrink-0 rounded-full" style="background: conic-gradient(<?= attendara_ui_escape(implode(', ', $chartStops)) ?>)">
                        <div class="absolute inset-8 flex flex-col items-center justify-center rounded-full bg-white text-center">
                            <span class="text-3xl font-semibold leading-none text-[#303832]"><?= attendara_ui_escape((string) $jumlahSiswaKelasAktif) ?></span>
                            <span class="mt-2 text-xs text-slate-500">Jumlah siswa</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div aria-hidden="true" class="relative h-48 w-48 shrink-0 rounded-full bg-[#e6ebe5]"><div class="absolute inset-8 flex items-center justify-center rounded-full bg-white text-center text-xs text-slate-500">Belum ada siswa</div></div>
                <?php endif; ?>
                <figcaption class="w-full max-w-sm">
                    <h3 class="mb-3 text-sm font-semibold text-[#303832]">Jumlah siswa per kelas</h3>
                    <ul class="space-y-2.5">
                        <?php foreach ($kelasAktif as $index => $row): ?>
                            <?php $jumlah = (int) ($row['jumlah_siswa'] ?? 0); $warna = $chartColors[$index % count($chartColors)]; ?>
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="flex min-w-0 items-center gap-2.5 text-slate-700">
                                    <span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-full" style="background-color: <?= attendara_ui_escape($warna) ?>"></span>
                                    <span class="truncate"><?= attendara_ui_escape((string) ($row['nama_kelas'] ?? '-')) ?></span>
                                </span>
                                <span class="shrink-0 font-medium text-slate-700"><?= attendara_ui_escape((string) $jumlah) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </figcaption>
            </figure>
        <?php endif; ?>
    </article>

    <article class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-[#303832]">Siswa per Semester</h2>
            <p class="mt-1 text-sm text-slate-600">Perbandingan jumlah siswa dari semester ke semester.</p>
        </div>
        <?php if ($siswaPerSemester === []): ?>
            <p class="rounded-2xl border border-[#dfe6de] bg-white px-5 py-6 text-sm text-slate-500">Belum ada data semester.</p>
        <?php else: ?>
            <figure class="flex flex-col items-center gap-6 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:flex-row sm:justify-center sm:p-6">
                <?php if ($jumlahCatatanSemester > 0): ?>
                    <div role="img" aria-label="Diagram proporsi jumlah siswa pada setiap semester" class="relative h-48 w-48 shrink-0 rounded-full" style="background: conic-gradient(<?= attendara_ui_escape(implode(', ', $semesterChartStops)) ?>)">
                        <div class="absolute inset-8 flex flex-col items-center justify-center rounded-full bg-white text-center">
                            <span class="text-3xl font-semibold leading-none text-[#303832]"><?= attendara_ui_escape((string) count($siswaPerSemester)) ?></span>
                            <span class="mt-2 text-xs text-slate-500">Semester</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div aria-hidden="true" class="relative h-48 w-48 shrink-0 rounded-full bg-[#e6ebe5]"><div class="absolute inset-8 flex items-center justify-center rounded-full bg-white text-center text-xs text-slate-500">Belum ada siswa</div></div>
                <?php endif; ?>
                <figcaption class="w-full max-w-sm">
                    <h3 class="mb-3 text-sm font-semibold text-[#303832]">Jumlah siswa per semester</h3>
                    <ul class="space-y-2.5">
                        <?php foreach ($siswaPerSemester as $index => $row): ?>
                            <?php $jumlah = (int) ($row['jumlah_siswa'] ?? 0); $warna = $chartColors[$index % count($chartColors)]; ?>
                            <li class="flex items-center justify-between gap-4 text-sm">
                                <span class="flex min-w-0 items-center gap-2.5 text-slate-700">
                                    <span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-full" style="background-color: <?= attendara_ui_escape($warna) ?>"></span>
                                    <span class="truncate"><?= attendara_ui_escape($ringkasNamaSemester((string) ($row['nama_semester'] ?? '-'))) ?></span>
                                </span>
                                <span class="shrink-0 font-medium text-slate-700"><?= attendara_ui_escape((string) $jumlah) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </figcaption>
            </figure>
        <?php endif; ?>
    </article>
</section>

<section aria-label="Ringkasan administrasi" class="grid grid-cols-3 gap-2 sm:gap-4">
    <?php
    attendara_ui_stat_card('Total Guru', (string) $guruCount, 'Guru terdaftar', 'users', true, false, true);
    attendara_ui_stat_card('Total Siswa', (string) $siswaCount, 'Siswa terdaftar', 'users', true, false, true);
    attendara_ui_stat_card('Total Kelas', (string) $kelasCount, 'Kelas terdaftar', 'school', true, false, true);
    attendara_ui_stat_card('Total Mata Pelajaran', (string) $mapelCount, 'Mata pelajaran terdaftar', 'book', true, false, true);
    attendara_ui_stat_card('Total Penugasan', (string) $penugasanCount, 'Penugasan guru terdaftar', 'users', true, false, true);
    attendara_ui_stat_card('Semester Aktif', $semesterAktif, 'Status data: aktif', 'calendar', true, true, true);
    ?>
</section>
<?php admin_page_end(); ?>
