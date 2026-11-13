<?php
// api/akun.php: CRUD akun guru, khusus admin. Dipakai di bagian admin halaman "Profil saya".
// GET            -> daftar akun guru (tanpa password)
// POST           -> tambah akun   (JSON: nama_guru, nip, email, peran, password)
// PUT    ?id=ID  -> ubah akun    (password opsional; kosong = tidak diganti)
// DELETE ?id=ID  -> hapus akun   (ditolak bila masih dipakai data lain)
require __DIR__ . '/bootstrap.php';
if (!isAdmin()) respond(null, 'Hanya admin yang boleh mengelola akun guru.', 403);

const PERAN_AKUN = ['guru', 'admin'];
$idSaya = (int)$_SESSION['guru']['id_guru'];

// Validasi isian akun; mengembalikan [nama, nip, email, peran, password].
function akunFields(array $input, bool $passwordWajib): array {
    $nama = trim((string)($input['nama_guru'] ?? ''));
    $nip = trim((string)($input['nip'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $peran = trim((string)($input['peran'] ?? ''));
    $password = (string)($input['password'] ?? '');
    if ($nama === '' || preg_match_all('/./us', $nama) > 100) {
        respond(null, 'Nama wajib diisi (maksimal 100 karakter).', 422);
    }
    if ($nip === '' || strlen($nip) > 50) respond(null, 'NIP wajib diisi (maksimal 50 karakter).', 422);
    if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(null, 'Email tidak valid.', 422);
    }
    if (!in_array($peran, PERAN_AKUN, true)) respond(null, 'Peran harus guru atau admin.', 422);
    if ($password === '' && $passwordWajib) respond(null, 'Password wajib diisi untuk akun baru.', 422);
    if ($password !== '' && (strlen($password) < 8 || strlen($password) > 72)) {
        respond(null, 'Password harus 8–72 karakter.', 422);
    }
    return [$nama, $nip, $email, $peran, $password];
}

if ($method === 'GET') {
    $rows = $pdo->query('SELECT g.id_guru, g.nip, g.nama_guru, g.email, g.peran,
            (SELECT COUNT(*) FROM KELAS k WHERE k.id_wali_kelas = g.id_guru)
          + (SELECT COUNT(*) FROM PENUGASAN_MENGAJAR p WHERE p.id_guru = g.id_guru)
          + (SELECT COUNT(*) FROM CATATAN_BIMBINGAN c WHERE c.id_guru = g.id_guru) AS terkait
        FROM GURU g ORDER BY g.nama_guru')->fetchAll();
    foreach ($rows as &$row) {
        $row['terkait'] = (int)$row['terkait'];
        $row['saya'] = (int)$row['id_guru'] === $idSaya;
    }
    unset($row);
    respond($rows);
}

if ($method === 'POST') {
    [$nama, $nip, $email, $peran, $password] = akunFields(inputData(), true);
    try {
        $stmt = $pdo->prepare('INSERT INTO GURU (nip, nama_guru, email, password, peran) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nip, $nama, $email, password_hash($password, PASSWORD_DEFAULT), $peran]);
    } catch (PDOException $error) {
        if ($error->getCode() == '23000') respond(null, 'NIP atau email sudah dipakai akun lain.', 409);
        throw $error;
    }
    respond(['id_guru' => (int)$pdo->lastInsertId()], 'Akun guru berhasil ditambahkan.', 201);
}

if (!in_array($method, ['PUT', 'DELETE'], true)) respond(null, 'Metode tidak didukung.', 405);

$id = positiveId($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id_guru, peran FROM GURU WHERE id_guru = ?');
$stmt->execute([$id]);
$akun = $stmt->fetch();
if (!$akun) respond(null, 'Akun guru tidak ditemukan.', 404);

if ($method === 'DELETE') {
    if ($id === $idSaya) respond(null, 'Akun yang sedang Anda pakai tidak dapat dihapus.', 422);
    try {
        $stmt = $pdo->prepare('DELETE FROM GURU WHERE id_guru = ?');
        $stmt->execute([$id]);
    } catch (PDOException $error) {
        if ($error->getCode() == '23000') {
            respond(null, 'Akun masih dipakai sebagai wali kelas, penugasan mengajar, atau catatan bimbingan, sehingga tidak dapat dihapus.', 409);
        }
        throw $error;
    }
    respond(null, 'Akun guru berhasil dihapus.');
}

// PUT
[$nama, $nip, $email, $peran, $password] = akunFields(inputData(), false);
if ($id === $idSaya && $peran !== $akun['peran']) {
    respond(null, 'Peran akun Anda sendiri tidak dapat diubah.', 422);
}
try {
    if ($password !== '') {
        $stmt = $pdo->prepare('UPDATE GURU SET nip = ?, nama_guru = ?, email = ?, peran = ?, password = ? WHERE id_guru = ?');
        $stmt->execute([$nip, $nama, $email, $peran, password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE GURU SET nip = ?, nama_guru = ?, email = ?, peran = ? WHERE id_guru = ?');
        $stmt->execute([$nip, $nama, $email, $peran, $id]);
    }
} catch (PDOException $error) {
    if ($error->getCode() == '23000') respond(null, 'NIP atau email sudah dipakai akun lain.', 409);
    throw $error;
}
if ($id === $idSaya) $_SESSION['guru']['nama_guru'] = $nama;
respond(['id_guru' => $id], 'Akun guru berhasil diperbarui.');
