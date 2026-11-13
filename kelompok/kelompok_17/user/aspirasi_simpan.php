<?php

require_once '../config/database.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: aspirasi_tambah.php");
    exit();
}

$judul = trim($_POST['judul_aspirasi'] ?? '');
$isi = trim($_POST['isi_aspirasi'] ?? '');
$id_kategori = filter_var(
    $_POST['id_kategori'] ?? '',
    FILTER_VALIDATE_INT
);

$is_anonim = isset($_POST['is_anonim']) ? 1 : 0;

$id_user = $_SESSION['id_user'];

if ($judul === '' || $isi === '' || !$id_kategori || $id_kategori <= 0) {
    echo "<script>
        alert('Semua data aspirasi wajib diisi!');
        window.location.href = 'aspirasi_tambah.php';
    </script>";
    exit();
}

$stmtKategori = $pdo->prepare("
    SELECT id_kategori
    FROM kategori
    WHERE id_kategori = :id_kategori
");

$stmtKategori->execute([
    ':id_kategori' => $id_kategori
]);

$kategori = $stmtKategori->fetch();

if (!$kategori) {
    echo "<script>
        alert('Kategori tidak ditemukan!');
        window.location.href = 'aspirasi_tambah.php';
    </script>";
    exit();
}

try {

    $sql = "
        INSERT INTO aspirasi (
            id_user,
            id_kategori,
            judul_aspirasi,
            isi_aspirasi,
            is_anonim
        )
        VALUES (
            :id_user,
            :id_kategori,
            :judul,
            :isi,
            :is_anonim
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id_user' => $id_user,
        ':id_kategori' => $id_kategori,
        ':judul' => $judul,
        ':isi' => $isi,
        ':is_anonim' => $is_anonim
    ]);

    header("Location: dashboard.php?pesan=berhasil");
    exit();

} catch (PDOException $e) {

    error_log($e->getMessage());

    echo "Terjadi kesalahan saat menyimpan aspirasi.
          Silakan coba kembali.";

}

?>