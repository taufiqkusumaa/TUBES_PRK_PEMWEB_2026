<?php

require __DIR__ . '/bootstrap.php';

$idGuru = (int)$_SESSION['guru']['id_guru'];
$action = $_GET['action'] ?? '';

const KOMPONEN_JENIS = ['Tugas', 'Kuis', 'UTS', 'UAS'];
const KOMPONEN_BOBOT_KOLOM = [
    'Tugas' => 'bobot_tugas',
    'Kuis'  => 'bobot_kuis',
    'UTS'   => 'bobot_uts',
    'UAS'   => 'bobot_uas'
];
const REKOMENDASI_KUADRAN = [
    'Rajin & nilai tinggi'         => 'Pertahankan konsistensi belajar. Siswa siap diberikan materi pengayaan dan dapat ditugaskan sebagai tutor sebaya.',
    'Jarang kumpul & nilai tinggi' => 'Kemampuan kognitif tinggi namun kedisiplinan rendah. Perlu peningkatan motivasi serta pemantauan pengumpulan tugas harian.',
    'Rajin & nilai rendah'         => 'Kedisiplinan belajar sangat baik namun hasil nilai belum optimal. Disarankan evaluasi cara belajar atau pendampingan remedial konsep dasar.',
    'Jarang kumpul & nilai rendah' => 'Perlu perhatian segera. Jadwalkan sesi pendampingan belajar khusus dan komunikasi bersama wali kelas/orang tua.'
];

function hitungPredikat(?float $nilaiAkhir): string {
    if ($nilaiAkhir === null) return '-';
    if ($nilaiAkhir >= 85) return 'A';
    if ($nilaiAkhir >= 75) return 'B';
    if ($nilaiAkhir >= 65) return 'C';
    if ($nilaiAkhir >= 55) return 'D';
    return 'E';
}

function hitungDistribusiPredikat(array $nilaiArr): array {
    $distribusi = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    foreach ($nilaiArr as $nilai) {
        if ($nilai === null) continue;
        $predikat = hitungPredikat((float)$nilai);
        if (isset($distribusi[$predikat])) $distribusi[$predikat]++;
    }
    return $distribusi;
}

function hitungDistribusiKuadran(array $rekapAll): array {
    $distribusi = [
        'Rajin & nilai tinggi'         => 0,
        'Jarang kumpul & nilai tinggi' => 0,
        'Rajin & nilai rendah'         => 0,
        'Jarang kumpul & nilai rendah' => 0
    ];
    foreach ($rekapAll as $r) {
        $k = $r['kategori_kuadran'] ?? '';
        if (isset($distribusi[$k])) $distribusi[$k]++;
    }
    return $distribusi;
}

function validasiPenugasan(PDO $pdo, int $idPenugasan, bool $wajibPengampu = false): array {
    $stmt = $pdo->prepare(
        'SELECT pm.*, k.nama_kelas, k.tingkat, k.id_wali_kelas,
                m.nama_mapel, m.kode_mapel,
                g.nama_guru, g.nip,
                p.tahun_ajaran, p.semester, p.status_aktif
         FROM PENUGASAN_MENGAJAR pm
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         WHERE pm.id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $penugasan = $stmt->fetch();
    if (!$penugasan) respond(null, 'Penugasan tidak ditemukan.', 404);

    $idGuruLogin = (int)$_SESSION['guru']['id_guru'];
    $adalahPengampu = isAdmin() || (int)$penugasan['id_guru'] === $idGuruLogin;
    $bolehAkses = $adalahPengampu || (int)$penugasan['id_wali_kelas'] === $idGuruLogin;

    if (!$bolehAkses) {
        respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
    }
    if ($wajibPengampu && !$adalahPengampu) {
        respond(null, 'Hanya guru pengampu atau admin yang dapat mengubah bobot dan menyimpan kalkulasi.', 403);
    }

    $penugasan['bisa_ubah'] = $adalahPengampu;
    return $penugasan;
}

function ambilDaftarPenugasanAkses(PDO $pdo, int $idGuru): array {
    $where  = '';
    $params = [];
    if (!isAdmin()) {
        $where  = 'WHERE pm.id_guru = ? OR k.id_wali_kelas = ?';
        $params = [$idGuru, $idGuru];
    }

    $stmt = $pdo->prepare(
        "SELECT pm.id_penugasan, pm.id_guru, pm.id_kelas, pm.id_mapel, pm.id_periode,
                g.nama_guru, g.nip, k.nama_kelas, k.tingkat, k.id_wali_kelas,
                m.nama_mapel, m.kode_mapel,
                p.tahun_ajaran, p.semester, p.status_aktif
         FROM PENUGASAN_MENGAJAR pm
         JOIN GURU g ON g.id_guru = pm.id_guru
         JOIN KELAS k ON k.id_kelas = pm.id_kelas
         JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
         JOIN PERIODE_PENILAIAN p ON p.id_periode = pm.id_periode
         $where
         ORDER BY p.status_aktif DESC, p.tahun_ajaran DESC,
                  FIELD(p.semester, 'Genap', 'Ganjil') ASC,
                  FIELD(k.tingkat, 'X', 'XI', 'XII', '10', '11', '12'),
                  LENGTH(k.nama_kelas) ASC, k.nama_kelas ASC, m.nama_mapel ASC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['bisa_ubah'] = isAdmin() || (int)$row['id_guru'] === $idGuru;
    }
    return $rows;
}

function ambilBobot(PDO $pdo, int $idPenugasan): array {
    $stmt = $pdo->prepare('SELECT * FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $bobot = $stmt->fetch();
    if (!$bobot) {
        return [
            'id_bobot'     => null,
            'id_penugasan' => $idPenugasan,
            'bobot_tugas'  => 25.0,
            'bobot_kuis'   => 25.0,
            'bobot_uts'    => 25.0,
            'bobot_uas'    => 25.0,
            'is_default'   => true
        ];
    }
    return [
        'id_bobot'     => (int)$bobot['id_bobot'],
        'id_penugasan' => (int)$bobot['id_penugasan'],
        'bobot_tugas'  => (float)$bobot['bobot_tugas'],
        'bobot_kuis'   => (float)$bobot['bobot_kuis'],
        'bobot_uts'    => (float)$bobot['bobot_uts'],
        'bobot_uas'    => (float)$bobot['bobot_uas'],
        'is_default'   => false
    ];
}

function ambilNilaiBulk(PDO $pdo, array $idPenilaianAll): array {
    $nilaiMap = [];
    if (!empty($idPenilaianAll)) {
        $marks = implode(',', array_fill(0, count($idPenilaianAll), '?'));
        $stmt = $pdo->prepare(
            "SELECT id_siswa, id_penilaian, waktu_pengumpulan, status_pengumpulan, nilai
             FROM NILAI_DAN_PENGUMPULAN
             WHERE id_penilaian IN ($marks)"
        );
        $stmt->execute($idPenilaianAll);
        foreach ($stmt->fetchAll() as $n) {
            $nilaiMap[$n['id_siswa']][$n['id_penilaian']] = $n;
        }
    }
    return $nilaiMap;
}

function hitungRataPerJenis(array $penilaianPerJenis, array $nilaiSiswa): array {
    $rataPerJenis = [];
    foreach ($penilaianPerJenis as $jenis => $ids) {
        $nilaiJenis = [];
        foreach ($ids as $idP) {
            if (isset($nilaiSiswa[$idP]) && $nilaiSiswa[$idP]['nilai'] !== null) {
                $nilaiJenis[] = (float)$nilaiSiswa[$idP]['nilai'];
            }
        }
        $rataPerJenis[$jenis] = !empty($nilaiJenis)
            ? round(array_sum($nilaiJenis) / count($nilaiJenis), 2)
            : null;
    }
    return $rataPerJenis;
}

/**
 * Nilai akhir dinormalisasi berdasarkan komponen yang sudah memiliki nilai,
 * sehingga komponen yang belum dibuat/dinilai tidak dihitung sebagai nilai 0.
 */
function hitungNilaiAkhir(array $rataPerJenis, array $bobot): ?float {
    $adaNilai     = false;
    $skorTerbobot = 0.0;
    $bobotTerisi  = 0.0;

    foreach (KOMPONEN_BOBOT_KOLOM as $jenis => $kolomBobot) {
        if ($rataPerJenis[$jenis] !== null) {
            $adaNilai = true;
            $w = (float)$bobot[$kolomBobot];
            $skorTerbobot += $rataPerJenis[$jenis] * $w;
            $bobotTerisi  += $w;
        }
    }

    if (!$adaNilai) return null;
    if ($bobotTerisi <= 0.0) return 0.0;

    return round($skorTerbobot / $bobotTerisi, 2);
}

/**
 * Sinkron dengan modul Monitoring (Dwi Septy) & Klasifikasi (Natasya):
 * hanya komponen Tugas yang menentukan indeks kedisiplinan.
 */
function statusTugasSiswa(?array $nilaiRow, ?string $tenggatWaktu, int $sekarang): string {
    $waktu    = $nilaiRow['waktu_pengumpulan'] ?? null;
    $statusDb = strtolower(trim((string)($nilaiRow['status_pengumpulan'] ?? '')));
    $adaWaktu = $waktu !== null && $waktu !== '';

    if (!$tenggatWaktu) {
        if ($adaWaktu || $statusDb === 'tepat waktu') return 'Tepat waktu';
        if ($statusDb === 'terlambat') return 'Terlambat';
        return 'Belum jatuh tempo';
    }

    if ($adaWaktu) {
        return strtotime($waktu) <= strtotime($tenggatWaktu) ? 'Tepat waktu' : 'Terlambat';
    }

    if ($statusDb === 'tepat waktu') return 'Tepat waktu';
    if ($statusDb === 'terlambat') return 'Terlambat';

    return $sekarang > strtotime($tenggatWaktu) ? 'Belum mengumpulkan' : 'Belum jatuh tempo';
}

function hitungIndeksKedisiplinan(array $daftarTugas, array $nilaiSiswa, int $sekarang): ?float {
    $skor   = 0;
    $jumlah = 0;

    foreach ($daftarTugas as $tugas) {
        $status = statusTugasSiswa($nilaiSiswa[$tugas['id_penilaian']] ?? null, $tugas['tenggat_waktu'], $sekarang);
        if ($status === 'Tepat waktu') {
            $skor += 100;
            $jumlah++;
        } elseif ($status === 'Terlambat') {
            $skor += 50;
            $jumlah++;
        } elseif ($status === 'Belum mengumpulkan') {
            $jumlah++;
        }
    }

    return $jumlah > 0 ? round($skor / $jumlah, 2) : null;
}

function tentukanKuadran(float $nilaiAkhir, float $indeksKedisiplinan): string {
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan >= 80) return 'Rajin & nilai tinggi';
    if ($nilaiAkhir >= 75 && $indeksKedisiplinan <  80) return 'Jarang kumpul & nilai tinggi';
    if ($nilaiAkhir <  75 && $indeksKedisiplinan >= 80) return 'Rajin & nilai rendah';
    return 'Jarang kumpul & nilai rendah';
}

function ambilRekomendasi(?string $kuadran): ?string {
    if ($kuadran === null) return null;
    return REKOMENDASI_KUADRAN[$kuadran] ?? 'Perlu evaluasi berkala.';
}

function hitungDataPenugasan(PDO $pdo, array $penugasan, array $bobot): array {
    $idPenugasan = (int)$penugasan['id_penugasan'];
    $sekarang    = strtotime($pdo->query('SELECT NOW()')->fetchColumn());

    $stmt = $pdo->prepare(
        'SELECT id_siswa, nisn, nama_siswa, jenis_kelamin
         FROM SISWA WHERE id_kelas = ? ORDER BY nama_siswa ASC'
    );
    $stmt->execute([$penugasan['id_kelas']]);
    $daftarSiswa = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT id_penilaian, jenis_komponen, tenggat_waktu
         FROM PENILAIAN WHERE id_penugasan = ?'
    );
    $stmt->execute([$idPenugasan]);
    $daftarPenilaian = $stmt->fetchAll();

    $penilaianPerJenis = ['Tugas' => [], 'Kuis' => [], 'UTS' => [], 'UAS' => []];
    $daftarTugas       = [];
    $idPenilaianAll    = [];

    foreach ($daftarPenilaian as $p) {
        $idP = (int)$p['id_penilaian'];
        $idPenilaianAll[] = $idP;
        if (isset($penilaianPerJenis[$p['jenis_komponen']])) {
            $penilaianPerJenis[$p['jenis_komponen']][] = $idP;
        }
        if (strcasecmp(trim((string)$p['jenis_komponen']), 'Tugas') === 0) {
            $daftarTugas[] = $p;
        }
    }

    $totalPenilaian = count($daftarPenilaian);
    $nilaiMap       = ambilNilaiBulk($pdo, $idPenilaianAll);

    $stmt = $pdo->prepare('SELECT * FROM REKAP_AKADEMIK_KUADRAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $rekapTersimpan = [];
    foreach ($stmt->fetchAll() as $r) {
        $rekapTersimpan[$r['id_siswa']] = $r;
    }

    $hasilSiswa       = [];
    $perluSinkronDb   = empty($rekapTersimpan) && !empty($daftarSiswa);
    $bobotTerpakai    = array_sum(array_map(
        fn($j) => !empty($penilaianPerJenis[$j]) ? (float)$bobot[KOMPONEN_BOBOT_KOLOM[$j]] : 0.0,
        KOMPONEN_JENIS
    ));

    foreach ($daftarSiswa as $siswa) {
        $idSiswa    = (int)$siswa['id_siswa'];
        $nilaiSiswa = $nilaiMap[$idSiswa] ?? [];

        $rataPerJenis = hitungRataPerJenis($penilaianPerJenis, $nilaiSiswa);

        // Hanya hitung record yang nilai-nya benar-benar terisi (bukan NULL dari modul Monitoring).
        $sudahDinilai = 0;
        foreach ($idPenilaianAll as $idP) {
            if (isset($nilaiSiswa[$idP]) && $nilaiSiswa[$idP]['nilai'] !== null) {
                $sudahDinilai++;
            }
        }

        $nilaiAkhir         = hitungNilaiAkhir($rataPerJenis, $bobot);
        $predikat           = hitungPredikat($nilaiAkhir);
        $indeksKedisiplinan = hitungIndeksKedisiplinan($daftarTugas, $nilaiSiswa, $sekarang);
        $statusKelengkapan  = ($totalPenilaian > 0 && $sudahDinilai >= $totalPenilaian)
            ? 'Lengkap'
            : 'Belum lengkap';

        if ($nilaiAkhir !== null && $indeksKedisiplinan !== null) {
            $kuadran     = tentukanKuadran($nilaiAkhir, $indeksKedisiplinan);
            $rekomendasi = ambilRekomendasi($kuadran);
        } else {
            $kuadran     = null;
            $rekomendasi = null;
        }

        $tersimpan = $rekapTersimpan[$idSiswa] ?? null;
        if (
            !$tersimpan ||
            (($tersimpan['nilai_akhir'] === null ? null : round((float)$tersimpan['nilai_akhir'], 2)) !== $nilaiAkhir) ||
            (($tersimpan['indeks_kedisiplinan'] === null ? null : round((float)$tersimpan['indeks_kedisiplinan'], 2)) !== $indeksKedisiplinan) ||
            ($tersimpan['status_kelengkapan_nilai'] ?? null) !== $statusKelengkapan ||
            ($tersimpan['kategori_kuadran'] ?? null) !== $kuadran
        ) {
            $perluSinkronDb = true;
        }

        $hasilSiswa[] = [
            'id_siswa'                 => $idSiswa,
            'nisn'                     => $siswa['nisn'],
            'nama_siswa'               => $siswa['nama_siswa'],
            'jenis_kelamin'            => $siswa['jenis_kelamin'],
            'rata_tugas'               => $rataPerJenis['Tugas'],
            'rata_kuis'                => $rataPerJenis['Kuis'],
            'rata_uts'                 => $rataPerJenis['UTS'],
            'rata_uas'                 => $rataPerJenis['UAS'],
            'nilai_akhir'              => $nilaiAkhir,
            'predikat'                 => $predikat,
            'indeks_kedisiplinan'      => $indeksKedisiplinan,
            'status_kelengkapan_nilai' => $statusKelengkapan,
            'jumlah_dinilai'           => $sudahDinilai,
            'jumlah_penilaian'         => $totalPenilaian,
            'kategori_kuadran'         => $kuadran,
            'rekomendasi_sistem'       => $rekomendasi
        ];
    }

    return [
        'siswa'               => $hasilSiswa,
        'jumlah_penilaian'    => $totalPenilaian,
        'bobot_komponen_ada'  => round($bobotTerpakai, 2),
        'sudah_tersimpan'     => !empty($rekapTersimpan),
        'perlu_hitung_ulang'  => $perluSinkronDb
    ];
}

function simpanRekapKeDatabase(PDO $pdo, int $idPenugasan, array $hasilSiswa): void {
    $idSiswaAktif = array_column($hasilSiswa, 'id_siswa');

    if (empty($idSiswaAktif)) {
        $pdo->prepare('DELETE FROM REKAP_AKADEMIK_KUADRAN WHERE id_penugasan = ?')->execute([$idPenugasan]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($idSiswaAktif), '?'));
    $del = $pdo->prepare(
        "DELETE FROM REKAP_AKADEMIK_KUADRAN
         WHERE id_penugasan = ? AND id_siswa NOT IN ($placeholders)"
    );
    $del->execute(array_merge([$idPenugasan], $idSiswaAktif));

    $upsert = $pdo->prepare(
        'INSERT INTO REKAP_AKADEMIK_KUADRAN
         (id_penugasan, id_siswa, nilai_akhir, indeks_kedisiplinan,
          status_kelengkapan_nilai, kategori_kuadran, rekomendasi_sistem)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            nilai_akhir = VALUES(nilai_akhir),
            indeks_kedisiplinan = VALUES(indeks_kedisiplinan),
            status_kelengkapan_nilai = VALUES(status_kelengkapan_nilai),
            kategori_kuadran = VALUES(kategori_kuadran),
            rekomendasi_sistem = VALUES(rekomendasi_sistem)'
    );

    foreach ($hasilSiswa as $row) {
        $upsert->execute([
            $idPenugasan,
            $row['id_siswa'],
            $row['nilai_akhir'],
            $row['indeks_kedisiplinan'],
            $row['status_kelengkapan_nilai'],
            $row['kategori_kuadran'],
            $row['rekomendasi_sistem']
        ]);
    }
}

function buatRingkasan(array $rows, bool $sudahTersimpan, bool $perluHitungUlang): array {
    $jumlahSiswa = count($rows);
    $nilaiValid  = [];
    $jumlahLengkap = 0;

    foreach ($rows as $r) {
        if ($r['nilai_akhir'] !== null) {
            $nilaiValid[] = (float)$r['nilai_akhir'];
        }
        if (($r['status_kelengkapan_nilai'] ?? '') === 'Lengkap') {
            $jumlahLengkap++;
        }
    }

    $jumlahDinilai = count($nilaiValid);

    return [
        'sudah_dihitung'      => $sudahTersimpan,
        'perlu_hitung_ulang'  => $perluHitungUlang,
        'ada_nilai'           => $jumlahDinilai > 0,
        'jumlah_siswa'        => $jumlahSiswa,
        'jumlah_dinilai'      => $jumlahDinilai,
        'jumlah_lengkap'      => $jumlahLengkap,
        'rata_rata_kelas'     => $jumlahDinilai > 0 ? round(array_sum($nilaiValid) / $jumlahDinilai, 2) : null,
        'nilai_tertinggi'     => $jumlahDinilai > 0 ? round(max($nilaiValid), 2) : null,
        'nilai_terendah'      => $jumlahDinilai > 0 ? round(min($nilaiValid), 2) : null,
        'persen_lengkap'      => $jumlahSiswa > 0 ? round(($jumlahLengkap / $jumlahSiswa) * 100, 1) : 0,
        'distribusi_predikat' => hitungDistribusiPredikat($nilaiValid),
        'distribusi_kuadran'  => hitungDistribusiKuadran($rows)
    ];
}

/* ==================== ROUTING ENDPOINT ==================== */

if ($action === 'konteks') {
    if ($method !== 'GET') respond(null, 'Metode tidak didukung.', 405);

    respond([
        'penugasan' => ambilDaftarPenugasanAkses($pdo, $idGuru),
        'pilihan'   => $_SESSION['konteks_penilaian'] ?? null
    ]);
}

if ($action === 'simpan_konteks') {
    if ($method !== 'POST') respond(null, 'Metode tidak didukung.', 405);

    $input = inputData();
    $pm    = validasiPenugasan($pdo, positiveId($input['id_penugasan'] ?? 0));

    $_SESSION['konteks_penilaian'] = [
        'id_kelas'     => (int)$pm['id_kelas'],
        'id_mapel'     => (int)$pm['id_mapel'],
        'id_periode'   => (int)$pm['id_periode'],
        'id_penugasan' => (int)$pm['id_penugasan']
    ];

    respond($_SESSION['konteks_penilaian'], 'Konteks laporan diperbarui.');
}

if ($action === 'penugasan') {
    if ($method !== 'GET') respond(null, 'Metode tidak didukung.', 405);
    respond(ambilDaftarPenugasanAkses($pdo, $idGuru));
}

$idPenugasan = positiveId($_GET['id_penugasan'] ?? 0);

if ($action === 'bobot' && $method === 'GET') {
    validasiPenugasan($pdo, $idPenugasan);
    respond(ambilBobot($pdo, $idPenugasan));
}

function parseBobotInput($val): float {
    if (is_bool($val) || $val === null || $val === '' || !is_numeric($val)) {
        respond(null, 'Semua bobot harus berupa angka.', 422);
    }
    $num = (float)$val;
    if (!is_finite($num) || $num < 0) {
        respond(null, 'Bobot tidak boleh bernilai negatif.', 422);
    }
    return round($num, 2);
}

if ($action === 'bobot' && in_array($method, ['POST', 'PUT'], true)) {
    $penugasan = validasiPenugasan($pdo, $idPenugasan, true);
    $input     = inputData();

    $bobotTugas = parseBobotInput($input['bobot_tugas'] ?? null);
    $bobotKuis  = parseBobotInput($input['bobot_kuis']  ?? null);
    $bobotUts   = parseBobotInput($input['bobot_uts']   ?? null);
    $bobotUas   = parseBobotInput($input['bobot_uas']   ?? null);

    $total = $bobotTugas + $bobotKuis + $bobotUts + $bobotUas;
    if (abs($total - 100) > 0.01) {
        respond(null, 'Total bobot harus tepat 100%. Saat ini: ' . round($total, 2) . '%', 422);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id_bobot FROM PENGATURAN_BOBOT WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE PENGATURAN_BOBOT
             SET bobot_tugas = ?, bobot_kuis = ?, bobot_uts = ?, bobot_uas = ?
             WHERE id_penugasan = ?'
        );
        $stmt->execute([$bobotTugas, $bobotKuis, $bobotUts, $bobotUas, $idPenugasan]);
        $idBobot = (int)$existing['id_bobot'];
        $statusCode = 200;
        $pesan = 'Bobot berhasil diperbarui dan rekap nilai disinkronkan.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO PENGATURAN_BOBOT
             (id_penugasan, bobot_tugas, bobot_kuis, bobot_uts, bobot_uas)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPenugasan, $bobotTugas, $bobotKuis, $bobotUts, $bobotUas]);
        $idBobot = (int)$pdo->lastInsertId();
        $statusCode = 201;
        $pesan = 'Bobot berhasil disimpan dan rekap nilai disinkronkan.';
    }

    // Otomatis sinkronkan REKAP_AKADEMIK_KUADRAN dengan bobot terbaru.
    $bobotBaru = [
        'bobot_tugas' => (float)$bobotTugas,
        'bobot_kuis'  => (float)$bobotKuis,
        'bobot_uts'   => (float)$bobotUts,
        'bobot_uas'   => (float)$bobotUas
    ];
    $hasil = hitungDataPenugasan($pdo, $penugasan, $bobotBaru);
    simpanRekapKeDatabase($pdo, $idPenugasan, $hasil['siswa']);

    $pdo->commit();
    respond(['id_bobot' => $idBobot], $pesan, $statusCode);
}

if ($action === 'rekap' && $method === 'GET') {
    $penugasan = validasiPenugasan($pdo, $idPenugasan);
    $bobot     = ambilBobot($pdo, $idPenugasan);
    $hasil     = hitungDataPenugasan($pdo, $penugasan, $bobot);
    $ringkasan = buatRingkasan($hasil['siswa'], $hasil['sudah_tersimpan'], $hasil['perlu_hitung_ulang']);

    respond([
        'penugasan' => [
            'id_penugasan' => $idPenugasan,
            'id_kelas'     => (int)$penugasan['id_kelas'],
            'id_mapel'     => (int)$penugasan['id_mapel'],
            'id_periode'   => (int)$penugasan['id_periode'],
            'nama_guru'    => $penugasan['nama_guru'],
            'nip'          => $penugasan['nip'],
            'nama_kelas'   => $penugasan['nama_kelas'],
            'tingkat'      => $penugasan['tingkat'],
            'nama_mapel'   => $penugasan['nama_mapel'],
            'kode_mapel'   => $penugasan['kode_mapel'],
            'tahun_ajaran' => $penugasan['tahun_ajaran'],
            'semester'     => $penugasan['semester'],
            'bisa_ubah'    => $penugasan['bisa_ubah']
        ],
        'bobot' => [
            'bobot_tugas' => $bobot['bobot_tugas'],
            'bobot_kuis'  => $bobot['bobot_kuis'],
            'bobot_uts'   => $bobot['bobot_uts'],
            'bobot_uas'   => $bobot['bobot_uas'],
            'is_default'  => $bobot['is_default']
        ],
        'jumlah_penilaian'   => $hasil['jumlah_penilaian'],
        'bobot_komponen_ada' => $hasil['bobot_komponen_ada'],
        'jumlah_siswa'       => count($hasil['siswa']),
        'ringkasan'          => $ringkasan,
        'siswa'              => $hasil['siswa']
    ]);
}

if ($action === 'ringkasan' && $method === 'GET') {
    validasiPenugasan($pdo, $idPenugasan);

    $stmt = $pdo->prepare('SELECT * FROM REKAP_AKADEMIK_KUADRAN WHERE id_penugasan = ?');
    $stmt->execute([$idPenugasan]);
    $rekapAll = $stmt->fetchAll();

    if (empty($rekapAll)) {
        respond([
            'sudah_dihitung'      => false,
            'ada_nilai'           => false,
            'jumlah_siswa'        => 0,
            'jumlah_dinilai'      => 0,
            'jumlah_lengkap'      => 0,
            'rata_rata_kelas'     => null,
            'nilai_tertinggi'     => null,
            'nilai_terendah'      => null,
            'persen_lengkap'      => 0,
            'distribusi_predikat' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
            'distribusi_kuadran'  => [
                'Rajin & nilai tinggi'         => 0,
                'Jarang kumpul & nilai tinggi' => 0,
                'Rajin & nilai rendah'         => 0,
                'Jarang kumpul & nilai rendah' => 0
            ]
        ]);
    }

    respond(buatRingkasan($rekapAll, true, false));
}

if ($action === 'hitung' && $method === 'POST') {
    $penugasan = validasiPenugasan($pdo, $idPenugasan, true);
    $bobot     = ambilBobot($pdo, $idPenugasan);
    $hasil     = hitungDataPenugasan($pdo, $penugasan, $bobot);

    if (empty($hasil['siswa'])) {
        respond(null, 'Tidak ada siswa di kelas ini.', 422);
    }

    $pdo->beginTransaction();
    simpanRekapKeDatabase($pdo, $idPenugasan, $hasil['siswa']);
    $pdo->commit();

    respond($hasil['siswa'], 'Rekap berhasil dihitung dan disimpan untuk ' . count($hasil['siswa']) . ' siswa.');
}

respond(null, 'Aksi tidak dikenali. Gunakan: konteks, simpan_konteks, penugasan, bobot, rekap, ringkasan, atau hitung.', 400);