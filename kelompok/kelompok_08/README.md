# BuyPanen

BuyPanen adalah aplikasi web katalog hasil panen. Petani bisa memasang produknya sendiri, sedangkan pembeli bisa melihat katalog dan menghubungi petani lewat WhatsApp. Ada juga halaman edukasi yang berisi artikel seputar pertanian.

Dibuat untuk Tugas Besar Praktikum Pemrograman Web 2026 oleh Kelompok 08.

Tema dan judul sistem: Fakultas Pertanian - BuyPanen

## Anggota

| Nama | Bagian yang dikerjakan |
|------|------------------------|
| Hartanto | Project lead, dashboard petani, CRUD produk |
| Elan | Database, login dan register, profil petani |
| Pandu | Landing page dan katalog |
| Zahra Dini Amarta | Pencarian, filter, detail produk |
| M. Adhiyatma | Edukasi, dokumentasi, laporan |

## Fitur

Untuk pengunjung:
- melihat katalog hasil panen
- mencari dan memfilter produk
- melihat detail produk dan menghubungi petani lewat WhatsApp
- membaca artikel edukasi

Untuk petani (harus login):
- register dan login
- mengubah profil
- melihat dashboard
- menambah, mengubah, dan menghapus produk
- menulis artikel edukasi sendiri (tambah, edit, hapus, dan atur status draft atau publish)

## Teknologi

PHP (MySQLi), MySQL, Bootstrap 5, dijalankan dengan Laragon atau XAMPP.

## Instalasi

Kebutuhan: Laragon atau XAMPP (PHP 8 ke atas, MySQL), Git, dan browser.

1. Nyalakan Apache dan MySQL di Laragon atau XAMPP.
2. Clone repository ke folder `www` (Laragon) atau `htdocs` (XAMPP).
   ```bash
   git clone https://github.com/Tantocpp/TUBES_PRK_PEMWEB_2026.git
   cd TUBES_PRK_PEMWEB_2026
   git checkout staging
   ```
3. Buka phpMyAdmin (`http://localhost/phpmyadmin`), lalu impor dua file SQL ini sesuai urutan:
   1. `kelompok/kelompok_08/database/buypanen.sql`
   2. `kelompok/kelompok_08/database/artikel.sql`

   `buypanen.sql` harus lebih dulu karena membuat database `buypanen` dan tabel `users`, yang dibutuhkan tabel artikel.
4. Cek `kelompok/kelompok_08/config/database.php`. Isi bawaannya host `localhost`, user `root`, password kosong, dan database `buypanen`. Ubah kalau komputermu berbeda.
5. Buka aplikasi di browser:
   `http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_08/`

Kalau muncul error tabel tidak ditemukan, berarti salah satu file SQL belum terimpor. Hapus database `buypanen`, buat lagi dengan nama yang sama, lalu impor ulang kedua file sesuai urutan di atas.

## Cara penggunaan

### Sebagai pengunjung

1. Buka halaman utama. Daftar produk tampil di bagian katalog.
2. Ketik nama produk atau nama petani di kolom pencarian, atau pilih kategori (Sayur, Buah, Pangan) untuk menyaring.
3. Klik Lihat Detail untuk melihat informasi lengkap produk.
4. Klik Hubungi Petani untuk membuka WhatsApp petani dengan pesan yang sudah terisi.
5. Buka menu Edukasi (`/edukasi/`) untuk membaca artikel. Kategori artikel bisa dipilih lewat tombol di atas daftar.

### Sebagai petani

1. Buka `auth/register.php` lalu daftarkan akun dengan nama, email, dan password.
2. Login di `auth/login.php`. Setelah berhasil, kamu masuk ke halaman profil.
3. Lengkapi profil (nomor WhatsApp dan lokasi kebun) di menu profil.
4. Buka dashboard petani untuk melihat ringkasan dan katalog.
5. Kelola produk lewat menu Produk: tambah produk baru, ubah, atau hapus produk milikmu.
6. Kelola artikel edukasi lewat `edukasi/kelola/`:
   - klik Tambah Artikel, isi judul, kategori, ringkasan, dan isi, lalu pilih status
   - artikel berstatus draft hanya terlihat olehmu, artikel publish tampil di halaman edukasi
   - gunakan tombol Edit atau Hapus untuk mengubah artikel milikmu
7. Klik Logout kalau sudah selesai.

Catatan: artikel contoh di `artikel.sql` otomatis ditulis oleh akun pertama di tabel `users`. Supaya muncul di daftar kelola artikel akunmu, jalankan query ini di phpMyAdmin dengan `id` akunmu:

```sql
UPDATE artikel SET penulis_id = 2;
```

Hak akses edukasi: semua orang bisa membaca artikel yang berstatus publish, halaman `edukasi/kelola/` hanya bisa dibuka petani yang sudah login, dan petani hanya bisa mengubah atau menghapus artikelnya sendiri.

## Struktur folder

```
kelompok_08/
├── index.php          halaman utama dan katalog
├── assets/            gambar dan css
├── auth/              login, register, logout
├── config/            koneksi database
├── middleware/        pengecekan login (auth.php)
├── petani/            dashboard dan profil petani
├── produk/            daftar, cari, filter, detail, tambah, edit, hapus produk
├── edukasi/           halaman edukasi
│   ├── index.php      daftar artikel
│   ├── detail.php     isi artikel
│   ├── header.php
│   ├── footer.php
│   └── kelola/        tambah, edit, hapus artikel (khusus petani)
├── database/          file SQL
└── Readme.md/         untuk dokumentasi dari hasil kerja projek

## Database

File skema ada di folder `database/`:

- `buypanen.sql` untuk tabel utama
- `artikel.sql` untuk tabel artikel edukasi

| Tabel | Isi |
|-------|-----|
| users | akun (nama, email, password, role) |
| petani | nomor WhatsApp dan lokasi kebun, terhubung ke users |
| kategori | kategori produk: Sayur, Buah, Pangan |
| produk | nama, harga, satuan, stok, deskripsi, foto, status tayang |
| kategori_artikel | kategori artikel edukasi |
| artikel | artikel edukasi, terhubung ke users (penulis) dan kategori_artikel |


## Alur kerja Git

Semua kerja dilakukan di dalam folder `kelompok/kelompok_08/` dan lewat branch fitur, tidak langsung di `main` atau `staging`.

1. `git checkout staging` lalu `git pull origin staging`
2. `git checkout -b feat/kelompok-08-nama-fitur`
3. Kerjakan fiturnya, lalu `git add kelompok/kelompok_08/`
4. `git commit -m "feat(kelompok_08): deskripsi singkat"` dan `git push origin feat/kelompok-08-nama-fitur`
5. Buat pull request ke branch `staging` di repo fork. Hartanto yang mereview dan merge.
