<?php
require __DIR__ . '/bootstrap.php';

$idGuru = $_SESSION['guru']['id_guru'];
$aksi = $_GET['aksi'] ?? '';
$jenisValid = ['Tugas', 'Kuis', 'UTS', 'UAS'];

function ambilPenugasan(PDO $pdo, $id) {
    $id = positiveId($id);
    $stmt = $pdo->prepare('SELECT * FROM PENUGASAN_MENGAJAR WHERE id_penugasan = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Penugasan tidak ditemukan.', 404);
    if (!isAdmin() && (int)$row['id_guru'] !== (int)$_SESSION['guru']['id_guru'] && !canAccessClass($pdo, (int)$row['id_kelas'])) {
        respond(null, 'Anda tidak memiliki akses ke penugasan ini.', 403);
    }
    return $row;
}

function ambilPenilaian(PDO $pdo, $id) {
    $id = positiveId($id);
    $stmt = $pdo->prepare('SELECT * FROM PENILAIAN WHERE id_penilaian = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) respond(null, 'Penilaian tidak ditemukan.', 404);
    $row['penugasan'] = ambilPenugasan($pdo, $row['id_penugasan']);
    return $row;
}

function validasiNilai($value) {
    $value = trim((string)$value);
    if ($value === '') return null;
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value) || (float)$value < 0 || (float)$value > 100) {
        respond(null, 'Nilai harus berupa angka antara 0 sampai 100.', 422);
    }
    return round((float)$value, 2);
}

if ($method === 'GET') {
    if ($aksi === 'konteks') {
        $sql = 'SELECT pm.id_penugasan, pm.id_kelas, pm.id_mapel, pm.id_periode,
                    k.nama_kelas, m.nama_mapel, pr.tahun_ajaran, pr.semester, pr.status_aktif
                FROM PENUGASAN_MENGAJAR pm
                JOIN KELAS k ON k.id_kelas = pm.id_kelas
                JOIN MATA_PELAJARAN m ON m.id_mapel = pm.id_mapel
                JOIN PERIODE_PENILAIAN pr ON pr.id_periode = pm.id_periode';
        $params = [];
        if (!isAdmin()) {
            $sql .= ' WHERE pm.id_guru = ? OR k.id_wali_kelas = ?';
            $params = [$idGuru, $idGuru];
        }
        $sql .= ' ORDER BY pr.status_aktif DESC, k.nama_kelas, m.nama_mapel';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $penugasan = $stmt->fetchAll();

        $orderKelas = "ORDER BY FIELD(tingkat, 'X', 'XI', 'XII', '10', '11', '12'), LENGTH(nama_kelas) ASC, nama_kelas ASC";
        if (isAdmin()) {
            $kelas = $pdo->query("SELECT id_kelas, nama_kelas, tingkat FROM KELAS $orderKelas")->fetchAll();
        } else {
            $stK = $pdo->prepare("SELECT id_kelas, nama_kelas, tingkat FROM KELAS
                WHERE id_wali_kelas = ? OR EXISTS (
                    SELECT 1 FROM PENUGASAN_MENGAJAR p WHERE p.id_kelas = KELAS.id_kelas AND p.id_guru = ?
                ) $orderKelas");
            $stK->execute([$idGuru, $idGuru]);
            $kelas = $stK->fetchAll();
        }
        $mapel = $pdo->query('SELECT id_mapel, kode_mapel, nama_mapel FROM MATA_PELAJARAN ORDER BY nama_mapel ASC, kode_mapel ASC')->fetchAll();
        $periode = $pdo->query("SELECT id_periode, tahun_ajaran, semester, status_aktif FROM PERIODE_PENILAIAN ORDER BY tahun_ajaran ASC, FIELD(semester, 'Ganjil', 'Genap') ASC")->fetchAll();
        respond([
            'penugasan' => $penugasan,
            'kelas' => $kelas,
            'mapel' => $mapel,
            'periode' => $periode,
            'pilihan' => $_SESSION['konteks_penilaian'] ?? null
        ]);
    }

    if ($aksi === 'daftar') {
        $pm = ambilPenugasan($pdo, $_GET['id_penugasan'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM SISWA WHERE id_kelas = ?');
        $stmt->execute([$pm['id_kelas']]);
        $jumlahSiswa = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT p.id_penilaian, p.judul_penilaian, p.jenis_komponen,
                p.tenggat_waktu, p.tanggal_dibuat, COUNT(n.nilai) AS terisi, AVG(n.nilai) AS rata_rata
            FROM PENILAIAN p
            LEFT JOIN NILAI_DAN_PENGUMPULAN n ON n.id_penilaian = p.id_penilaian
            WHERE p.id_penugasan = ?
            GROUP BY p.id_penilaian
            ORDER BY p.id_penilaian DESC');
        $stmt->execute([$pm['id_penugasan']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['jumlah_siswa'] = $jumlahSiswa;
            $r['rata_rata'] = $r['rata_rata'] === null ? null : round((float)$r['rata_rata'], 2);
        }
        respond($rows);
    }

    if ($aksi === 'nilai') {
        $p = ambilPenilaian($pdo, $_GET['id_penilaian'] ?? 0);
        $stmt = $pdo->prepare('SELECT s.id_siswa, s.nisn, s.nama_siswa, n.nilai
            FROM SISWA s
            LEFT JOIN NILAI_DAN_PENGUMPULAN n
                ON n.id_siswa = s.id_siswa AND n.id_penilaian = ?
            WHERE s.id_kelas = ?
            ORDER BY s.nama_siswa');
        $stmt->execute([$p['id_penilaian'], $p['penugasan']['id_kelas']]);
        respond(['penilaian' => [
            'id_penilaian' => $p['id_penilaian'],
            'judul_penilaian' => $p['judul_penilaian'],
            'jenis_komponen' => $p['jenis_komponen'],
            'tenggat_waktu' => $p['tenggat_waktu']
        ], 'siswa' => $stmt->fetchAll()]);
    }

    if ($aksi === 'rekap') {
        $pm = ambilPenugasan($pdo, $_GET['id_penugasan'] ?? 0);
        $stmt = $pdo->prepare('SELECT id_siswa, nisn, nama_siswa FROM SISWA
            WHERE id_kelas = ? ORDER BY nama_siswa');
        $stmt->execute([$pm['id_kelas']]);
        $siswa = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT n.id_siswa, p.jenis_komponen, AVG(n.nilai) AS rata
            FROM NILAI_DAN_PENGUMPULAN n
            JOIN PENILAIAN p ON p.id_penilaian = n.id_penilaian
            WHERE p.id_penugasan = ? AND n.nilai IS NOT NULL
            GROUP BY n.id_siswa, p.jenis_komponen');
        $stmt->execute([$pm['id_penugasan']]);
        $peta = [];
        foreach ($stmt->fetchAll() as $r) {
            $peta[$r['id_siswa']][$r['jenis_komponen']] = round((float)$r['rata'], 2);
        }
        foreach ($siswa as &$s) {
            foreach ($jenisValid as $j) $s[$j] = $peta[$s['id_siswa']][$j] ?? null;
        }
        respond($siswa);
    }

    respond(null, 'Aksi tidak dikenal.', 404);
}

if ($method === 'POST' && $aksi === 'siapkan_konteks') {
    $input = inputData();
    $idKelas = positiveId($input['id_kelas'] ?? 0);
    $idMapel = positiveId($input['id_mapel'] ?? 0);
    $idPeriode = positiveId($input['id_periode'] ?? 0);
    requireClass($pdo, $idKelas);

    $sqlCari = 'SELECT id_penugasan FROM PENUGASAN_MENGAJAR WHERE id_kelas = ? AND id_mapel = ? AND id_periode = ?';
    $paramsCari = [$idKelas, $idMapel, $idPeriode];
    if (!isAdmin()) {
        $sqlCari .= ' AND id_guru = ?';
        $paramsCari[] = $idGuru;
    }
    $sqlCari .= ' LIMIT 1';
    $stmt = $pdo->prepare($sqlCari);
    $stmt->execute($paramsCari);
    $idPenugasan = $stmt->fetchColumn();

    if (!$idPenugasan) {
        $ins = $pdo->prepare('INSERT INTO PENUGASAN_MENGAJAR (id_guru, id_kelas, id_mapel, id_periode) VALUES (?, ?, ?, ?)');
        $ins->execute([$idGuru, $idKelas, $idMapel, $idPeriode]);
        $idPenugasan = (int)$pdo->lastInsertId();
    }

    $_SESSION['konteks_penilaian'] = [
        'id_kelas' => (int)$idKelas,
        'id_mapel' => (int)$idMapel,
        'id_periode' => (int)$idPeriode,
        'id_penugasan' => (int)$idPenugasan
    ];

    respond(['id_penugasan' => (int)$idPenugasan], 'Kelas dan mata pelajaran siap dinilai.');
}

if ($method === 'POST' && $aksi === 'simpan_nilai') {
    $p = ambilPenilaian($pdo, $_GET['id_penilaian'] ?? 0);
    $input = inputData();

    $stmt = $pdo->prepare('SELECT id_siswa FROM SISWA WHERE id_kelas = ?');
    $stmt->execute([$p['penugasan']['id_kelas']]);
    $siswaKelas = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $data = [];
    foreach ($input as $key => $value) {
        if (!preg_match('/^s_(\d+)$/', (string)$key, $m)) continue;
        $idSiswa = (int)$m[1];
        if (!in_array($idSiswa, $siswaKelas, true)) {
            respond(null, 'Siswa tidak terdaftar pada kelas ini.', 422);
        }
        $data[$idSiswa] = validasiNilai($value);
    }

    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT id_nilai_pengumpulan FROM NILAI_DAN_PENGUMPULAN
        WHERE id_siswa = ? AND id_penilaian = ?');
    $upd = $pdo->prepare('UPDATE NILAI_DAN_PENGUMPULAN SET nilai = ? WHERE id_nilai_pengumpulan = ?');
    $ins = $pdo->prepare('INSERT INTO NILAI_DAN_PENGUMPULAN (id_siswa, id_penilaian, nilai) VALUES (?, ?, ?)');
    $tersimpan = 0;
    foreach ($data as $idSiswa => $nilai) {
        $find->execute([$idSiswa, $p['id_penilaian']]);
        $id = $find->fetchColumn();
        if ($id) { $upd->execute([$nilai, $id]); $tersimpan++; }
        elseif ($nilai !== null) { $ins->execute([$idSiswa, $p['id_penilaian'], $nilai]); $tersimpan++; }
    }
    $pdo->commit();
    respond(['tersimpan' => $tersimpan], 'Nilai berhasil disimpan.');
}

if (!in_array($method, ['POST', 'PUT', 'DELETE'])) respond(null, 'Metode tidak didukung.', 405);

if ($method === 'DELETE') {
    $p = ambilPenilaian($pdo, $_GET['id'] ?? 0);
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM NILAI_DAN_PENGUMPULAN WHERE id_penilaian = ?')->execute([$p['id_penilaian']]);
    $pdo->prepare('DELETE FROM PENILAIAN WHERE id_penilaian = ?')->execute([$p['id_penilaian']]);
    $pdo->commit();
    respond(null, 'Penilaian berhasil dihapus.');
}

$input = inputData();
$judul = trim((string)($input['judul_penilaian'] ?? ''));
$jenis = trim((string)($input['jenis_komponen'] ?? ''));
$tenggatInput = trim((string)($input['tenggat_waktu'] ?? ''));

if ($judul === '' || preg_match_all('/./us', $judul) > 100) {
    respond(null, 'Judul penilaian wajib diisi, maksimal 100 karakter.', 422);
}
if (!in_array($jenis, $jenisValid, true)) {
    respond(null, 'Jenis komponen harus Tugas, Kuis, UTS, atau UAS.', 422);
}

$tenggatFormatted = null;
if ($jenis === 'Tugas') {
    if ($tenggatInput === '') {
        respond(null, 'Tenggat waktu wajib diisi untuk komponen Tugas.', 422);
    }
    $dt = date_create($tenggatInput);
    if (!$dt) {
        respond(null, 'Format tenggat waktu tidak valid.', 422);
    }
    $tenggatFormatted = $dt->format('Y-m-d H:i:s');
}

if ($method === 'PUT') {
    $p = ambilPenilaian($pdo, $_GET['id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE PENILAIAN SET judul_penilaian = ?, jenis_komponen = ?, tenggat_waktu = ? WHERE id_penilaian = ?');
    $stmt->execute([$judul, $jenis, $tenggatFormatted, $p['id_penilaian']]);
    respond(['id_penilaian' => (int)$p['id_penilaian']], 'Penilaian berhasil diperbarui.');
}

$pm = ambilPenugasan($pdo, $input['id_penugasan'] ?? 0);
$stmt = $pdo->prepare('INSERT INTO PENILAIAN (id_penugasan, judul_penilaian, jenis_komponen, tenggat_waktu, tanggal_dibuat)
    VALUES (?, ?, ?, ?, NOW())');
$stmt->execute([$pm['id_penugasan'], $judul, $jenis, $tenggatFormatted]);
respond(['id_penilaian' => (int)$pdo->lastInsertId()], 'Penilaian berhasil dibuat.', 201);