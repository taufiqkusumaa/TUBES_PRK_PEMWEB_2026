<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

// Hanya kepala keluarga yang boleh menambahkan anggota
if (!isset($_SESSION['user']) ||
    ($_SESSION['user']['role'] ?? '') !== 'kepala_keluarga') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/anggota_keluarga.php');
    exit;
}

$idKeluarga = $_SESSION['user']['id_keluarga'] ?? null;

$namaLengkap = trim($_POST['nama_lengkap'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

function kembaliDenganError($pesan)
{
    $_SESSION['error'] = $pesan;
    header('Location: ../../pages/anggota_keluarga.php');
    exit;
}

if (!$idKeluarga) {
    kembaliDenganError('Data keluarga tidak ditemukan.');
}

if (
    $namaLengkap === '' ||
    $email === '' ||
    $password === '' ||
    $konfirmasiPassword === ''
) {
    kembaliDenganError('Semua kolom wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    kembaliDenganError('Format email tidak valid.');
}

if (strlen($password) < 8) {
    kembaliDenganError('Password minimal 8 karakter.');
}

if ($password !== $konfirmasiPassword) {
    kembaliDenganError('Konfirmasi password tidak cocok.');
}

try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // ID keluarga diambil dari session kepala keluarga
    $stmt = $pdo->prepare('CALL sp_tambah_anggota(?, ?, ?, ?)');

    $stmt->execute([
        $idKeluarga,
        $namaLengkap,
        $email,
        $passwordHash
    ]);

    $stmt->closeCursor();

    $_SESSION['success'] = 'Anggota keluarga berhasil ditambahkan.';
    header('Location: ../../pages/anggota_keluarga.php');
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        kembaliDenganError('Email sudah digunakan oleh akun lain.');
    }

    kembaliDenganError('Gagal menambahkan anggota keluarga.');
}