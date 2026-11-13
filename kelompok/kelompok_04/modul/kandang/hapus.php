<?php
include '../../config/koneksi.php';

if (isset($_GET['id'])){
    $id = (int) $_GET['id'];
    $hapus = mysqli_query($koneksi, "DELETE FROM kandang WHERE id_kandang = '$id'");

    if($hapus){
        echo "<script>alert('Data kandang berhasil dihapus!'); window.location='index.php';</script>";
    }else{
        echo "<script>alert('Gagal Menghapus data!'); window.location='index.php';</script>";
    }
}else{
    header("Location: index.php");
}
?>