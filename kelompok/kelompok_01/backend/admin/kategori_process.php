<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/admin_kategori.php');
    exit;
}

$aksi = $_POST['aksi'] ?? '';

try {
    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $jenis = $_POST['jenis'] ?? '';

        if ($nama === '' || strlen($nama) > 60 || !in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
            throw new Exception('Nama atau jenis kategori tidak valid.');
        }

        if ($aksi === 'tambah') {
            $stmt = $pdo->prepare('CALL sp_kategori_tambah(?, ?)');
            $stmt->execute([$nama, $jenis]);
        } else {
            $id = (int) ($_POST['id_kategori'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID kategori tidak valid.');
            }

            $stmt = $pdo->prepare('CALL sp_kategori_ubah(?, ?, ?)');
            $stmt->execute([$id, $nama, $jenis]);
        }

        $stmt->closeCursor();
        $_SESSION['success'] = $aksi === 'tambah'
            ? 'Kategori berhasil ditambahkan.'
            : 'Kategori berhasil diperbarui.';

    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id_kategori'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID kategori tidak valid.');
        }

        $stmt = $pdo->prepare('CALL sp_kategori_hapus(?)');
        $stmt->execute([$id]);
        $stmt->closeCursor();

        $_SESSION['success'] = 'Kategori berhasil dihapus.';

    } else {
        throw new Exception('Aksi tidak dikenali.');
    }

} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal memproses kategori. Pastikan nama kategori tidak duplikat atau sedang digunakan transaksi.';
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../../pages/admin_kategori.php');
exit;