<?php
// Pengaturan fitur masuk dan daftar. Dibaca oleh includes/auth.php.
//
// Mau mengubah sesuatu? JANGAN edit file ini. Salin file config/auth.local.contoh.php
// menjadi config/auth.local.php lalu isi hanya bagian yang ingin diganti.
// File auth.local.php tidak ikut di-commit, jadi kunci rahasia (client secret) tetap aman.

return [
    // "Ingat akun saya": lama cookie berlaku (hari).
    'ingat_hari' => 30,

    // Lupa kata sandi: lama tautan reset berlaku (menit).
    'reset_menit' => 60,

    // Pembatas tebak kata sandi: maksimal gagal per akun + alamat IP dalam sekian menit.
    'gagal_maks'  => 5,
    'gagal_menit' => 15,

    // MODE DEMO (untuk presentasi di komputer sendiri; ubah ke false bila situs dipakai sungguhan).
    //  demo_tautan_reset: karena belum ada server email, tautan reset kata sandi ditampilkan di layar.
    //  demo_sosial: tombol Google/LinkedIn/Facebook memakai halaman SIMULASI (tidak terhubung ke penyedia
    //               aslinya) selama client_id dan client_secret di bawah belum diisi.
    'demo_tautan_reset' => true,
    'demo_sosial'       => true,

    // Alamat dasar situs untuk redirect OAuth, contoh 'http://localhost'. Kosong = otomatis.
    'base_publik' => '',

    // Login media sosial (OAuth 2.0). Isi client_id dan client_secret di auth.local.php agar tombolnya
    // memakai penyedia asli. Alamat redirect yang didaftarkan di konsol penyedia:
    //   <alamat situs>/auth/sosial_callback.php
    'sosial' => [
        'google' => [
            'nama' => 'Google', 'ikon' => 'bi-google', 'format' => 'oidc',
            'client_id' => '', 'client_secret' => '',
            'auth_url'     => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url'    => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope' => 'openid email profile',
            'extra' => ['prompt' => 'select_account'],
        ],
        'linkedin' => [
            'nama' => 'LinkedIn', 'ikon' => 'bi-linkedin', 'format' => 'oidc',
            'client_id' => '', 'client_secret' => '',
            'auth_url'     => 'https://www.linkedin.com/oauth/v2/authorization',
            'token_url'    => 'https://www.linkedin.com/oauth/v2/accessToken',
            'userinfo_url' => 'https://api.linkedin.com/v2/userinfo',
            'scope' => 'openid profile email',
            'extra' => [],
        ],
        'facebook' => [
            'nama' => 'Facebook', 'ikon' => 'bi-facebook', 'format' => 'facebook',
            'client_id' => '', 'client_secret' => '',
            'auth_url'     => 'https://www.facebook.com/v19.0/dialog/oauth',
            'token_url'    => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'userinfo_url' => 'https://graph.facebook.com/v19.0/me?fields=id,name,email',
            'scope' => 'email,public_profile',
            'extra' => [],
        ],
    ],
];
