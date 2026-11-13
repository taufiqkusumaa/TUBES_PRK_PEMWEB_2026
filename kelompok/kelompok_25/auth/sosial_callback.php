<?php
// Tujuan balik dari Google/LinkedIn/Facebook setelah pengguna memberi izin.
require_once __DIR__ . '/../config/app.php';
if (is_logged_in()) { redirect(dashboard_path(current_user()['role'])); }

$oauth = $_SESSION['oauth'] ?? null;
unset($_SESSION['oauth']);                      // state hanya berlaku sekali
$state = (string) ($_GET['state'] ?? '');
if (!is_array($oauth) || $state === '' || !hash_equals((string) $oauth['state'], $state) || time() - (int) $oauth['waktu'] > 600) {
    flash('danger', 'Permintaan masuk tidak valid atau kedaluwarsa. Silakan coba lagi.');
    redirect('auth/login.php');
}
$kunci = (string) $oauth['provider'];
$daftar = auth_sosial_daftar();
if (!isset($daftar[$kunci]) || $daftar[$kunci]['mode'] !== 'real') {
    flash('danger', 'Penyedia login tidak dikenal.');
    redirect('auth/login.php');
}
if (isset($_GET['error']) || empty($_GET['code'])) {
    flash('warning', 'Masuk dengan ' . $daftar[$kunci]['nama'] . ' dibatalkan.');
    redirect('auth/login.php');
}
$profil = auth_sosial_ambil_profil($kunci, (string) $_GET['code']);
if (!$profil) {
    flash('danger', 'Tidak bisa mengambil data dari ' . $daftar[$kunci]['nama'] . '. Periksa koneksi internet lalu coba lagi.');
    redirect('auth/login.php');
}
auth_sosial_proses($kunci, $profil);
