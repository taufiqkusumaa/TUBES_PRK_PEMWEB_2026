<?php
include '../../config/koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Hapus data pakan dari database
    $hapus = mysqli_query($koneksi, "DELETE FROM pakan WHERE id_pakan = '$id'");

    if ($hapus) {
        echo "<script>alert('Data pakan berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data pakan!'); window.location='index.php';</script>";
    }
} else {
    header("Location: index.php");
}
?>