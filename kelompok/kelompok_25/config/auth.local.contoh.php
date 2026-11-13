<?php
// CONTOH. Salin file ini menjadi config/auth.local.php (hapus ".contoh" dari namanya), lalu isi.
// auth.local.php tidak ikut di-commit (lihat .gitignore), jadi aman untuk menyimpan kunci rahasia.
// Cukup tulis bagian yang ingin diganti; sisanya tetap memakai config/auth.php.

return [
    // Matikan mode demo kalau situs dipakai sungguhan:
    // 'demo_tautan_reset' => false,
    // 'demo_sosial'       => false,

    // Hidupkan login Google asli (buat "OAuth client ID" di console.cloud.google.com, jenis Web application,
    // lalu daftarkan alamat redirect: http://localhost/<folder proyek>/kelompok/kelompok_25/auth/sosial_callback.php):
    // 'sosial' => [
    //     'google' => ['client_id' => 'xxxx.apps.googleusercontent.com', 'client_secret' => 'xxxx'],
    // ],
];
