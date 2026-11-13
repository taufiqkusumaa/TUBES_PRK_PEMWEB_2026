<?php
include '../../config/koneksi.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Ambil data foto untuk dihapus dari folder assets/images/
    $get_foto = mysqli_query($koneksi, "SELECT foto FROM ternak WHERE id_ternak = '$id'");
    $data = mysqli_fetch_assoc($get_foto);

    if (!empty($data['foto']) && file_exists('../../assets/images/' . $data['foto'])) {
        unlink('../../assets/images/' . $data['foto']);
    }

    // Hapus data ternak dari database
    $hapus = mysqli_query($koneksi, "DELETE FROM ternak WHERE id_ternak = '$id'");

    if ($hapus) {
        echo "<script>alert('Data ternak berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data ternak!'); window.location='index.php';</script>";
    }
} else {
    header("Location: index.php");
}
?>