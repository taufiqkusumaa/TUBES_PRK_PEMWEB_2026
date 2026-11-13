<?php
include '../../config/koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Hapus hanya rekam medisnya; status ternak saat ini tidak diubah
    $stmt = mysqli_prepare($koneksi, "DELETE FROM log_kesehatan WHERE id_kesehatan = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $hapus = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($hapus) {
        echo "<script>window.location='index.php?status=hapus';</script>";
    } else {
        echo "<script>window.location='index.php?status=gagal';</script>";
    }
} else {
    header("Location: index.php");
}
?>