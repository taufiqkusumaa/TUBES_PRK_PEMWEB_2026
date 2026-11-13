# RUMDIS (Forum Diskusi Online Bantuan Hukum)

RUMDIS adalah forum diskusi masalah hukum di Indonesia dengan pengalaman
**Guest First, Login Optional**. Pengunjung langsung mendapatkan identitas
`Pengguna-xxxx` yang disimpan di database dan dipertahankan melalui PHP Session.
Pengunjung dapat membuat akun kapan saja; postingan dan jawaban dari akun tamu
tetap dapat digunakan setelah registrasi.

## Fitur yang tersedia

- Beranda dengan feed, filter enam kategori hukum, dan pengurutan berdasarkan
  terbaru, terpopuler, atau jumlah jawaban.
- Membuat, mengedit, dan menghapus postingan milik sendiri.
- Detail postingan, jawaban via AJAX, serta edit dan hapus jawaban milik sendiri.
- Like/unlike postingan tanpa memuat ulang halaman.
- Pencarian AJAX berdasarkan isi postingan, username, dan kategori.
- Halaman profil, Jawaban Saya, login, registrasi, dan logout.
- Daftar diskusi trending pada sidebar dan mode gelap yang disimpan di browser.

## Teknologi

- PHP Native dan MySQL dengan PDO
- HTML5, CSS3 (Grid dan Flexbox)
- JavaScript Vanilla dengan Fetch API
- PHP Session

Proyek ini tidak menggunakan framework, Composer, atau npm.

## Keamanan

Implementasi saat ini menggunakan prepared statement PDO, escaping output melalui
fungsi `e()`, validasi frontend dan backend, CSRF token, pemeriksaan kepemilikan
data, `session_regenerate_id()` saat login/registrasi, serta
`password_hash()`/`password_verify()`.

## Instalasi lokal (XAMPP)

1. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Salin folder `kelompok_16` ke `C:\xampp\htdocs\`.
3. Buka `http://localhost/phpmyadmin`.
4. Import `database/ruang_hukum.sql`. Berkas ini membuat database
   `ruang_hukum` beserta tabel `users`, `categories`, `posts`, `replies`, dan
   `likes`.
5. Import `database/sheder_dumy.sql` jika ingin mengisi kategori, akun contoh,
   postingan, jawaban, dan like awal.
6. Sesuaikan `DB_USER` dan `DB_PASS` pada `config/database.php` dengan akun
   MySQL lokal yang digunakan.
7. Buka `http://localhost/kelompok_16/`.

Akun contoh dari data dummy menggunakan password `password123`. Username yang
tersedia antara lain `budi_santoso`, `siti_rahma`, `andi_pratama`,
`dewi_lestari`, `rizky_hukum`, dan `maya_putri`.

## Struktur folder

```
kelompok_16/
├── index.php, search.php, detail-post.php, edit-post.php
├── profile.php, my-answers.php, login.php, register.php, logout.php
├── config/      database.php, session.php
├── includes/    init.php, functions.php, header.php, footer.php,
│               post_card.php, reply_item.php
├── api/         like.php, reply.php, search.php
├── process/     create_post.php, update_post.php, delete_post.php,
│               delete_reply.php, login_process.php, register_process.php
├── assets/      css/style.css, js/app.js, images/logo.svg
└── database/    ruang_hukum.sql, sheder_dumy.sql
```

## Catatan

Isi diskusi bersifat informasi umum dan bukan nasihat hukum resmi.
