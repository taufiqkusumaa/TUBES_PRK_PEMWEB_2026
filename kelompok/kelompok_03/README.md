# Sistem Antrian Klinik - Kelompok 03

Aplikasi web sistem antrian klinik berbasis PHP native. Pasien dapat melihat jadwal dokter, mengambil nomor antrean secara online, dan memantau status giliran secara real-time. Admin dapat mengelola data dokter dan memproses antrean pasien.

## 1. Teknologi

- PHP Native (8.0+)

- PDO (prepared statement)

- MySQL 8.0.16+ / MariaDB 10.3+

- InnoDB

- HTML5 + CSS3 native

- JavaScript Vanilla

- UTF-8 (utf8mb4)

## 2. Fitur

- Autentikasi dan registrasi akun (role admin dan pasien)

- Halaman jadwal dokter dengan filter spesialis/poli

- Pengambilan nomor antrean online

- Pemantauan status antrean real-time (sedang dilayani, sisa antrean di depan)

- Pembatalan antrean oleh pasien

- Dashboard admin: kelola dokter dan kelola antrean (panggil/selesai)

## 3. Struktur Direktori

```

kelompok_03/

|-- admin/ # Halaman & proses admin (CRUD dokter, kelola antrean)

|-- assets/ # style.css (CSS utama aplikasi)

|-- auth/ # Login, register, logout

|-- config/ # koneksi.php (koneksi database)

|-- database/ # klinik.sql (dump struktur + data)

|-- includes/ # header, footer, init, cek_login

|-- pasien/ # Portal pasien, ambil/batal antrean

|-- index.php # Halaman depan (jadwal dokter)

`-- README.md

```

## 4. Struktur Database

Database yang digunakan adalah `klinik_antrian`.

Database memiliki tiga tabel utama:

| Tabel | Fungsi |

| ------- | ---------------------------------------------- |

| users | Menyimpan akun admin dan pasien |

| dokter | Menyimpan informasi dokter dan jadwal praktik |

| antrian | Menyimpan pendaftaran dan nomor antrean pasien |

## 5. Struktur Relasi

- Satu pengguna dapat memiliki banyak data antrean.

- Satu dokter dapat memiliki banyak data antrean.

- Setiap antrean terhubung dengan satu pengguna.

- Setiap antrean terhubung dengan satu dokter.

Relasi menggunakan foreign key dengan InnoDB.

## 6. Instalasi

### 6.1 Kebutuhan Sistem

- Web server (Apache/Nginx) dengan PHP 8.0+

- Ekstensi PHP: `pdo_mysql`, `mbstring`

- MySQL 8.0.16+ / MariaDB 10.3+

- Contoh paket siap pakai: XAMPP, MAMP, atau Laragon

### 6.2 Langkah Instalasi

Unduh/clone repositori ini.

Letakkan folder `kelompok_03` di dalam document root web server:

- XAMPP: `C:\xampp\htdocs\`

- MAMP: `/Applications/MAMP/htdocs/`

- Laragon: `C:\laragon\www\`

Jalankan Apache dan MySQL melalui control panel (XAMPP/MAMP/Laragon).

Buat database dan import data:

- Buka phpMyAdmin di `http://localhost/phpmyadmin\`.

- Pilih menu **Import**.

- Pilih file `database/klinik.sql`.

- Klik **Import**.

- Pastikan database `klinik_antrian` berhasil dibuat beserta tabel `users`, `dokter`, dan `antrian`.

Sesuaikan konfigurasi koneksi bila perlu (lihat bagian 7).

Buka aplikasi di browser: `http://localhost/kelompok_03/index.php\`.

### 6.3 Instalasi Database via Terminal (opsional)

Jika menggunakan MySQL client di terminal:

```bash

mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS klinik_antrian CHARACTER SET utf8mb4;"

mysql -u root -p klinik_antrian < database/klinik.sql

```

## 7. Konfigurasi Koneksi

File koneksi berada di `config/koneksi.php`. Nilai default:

| Parameter | Nilai |

| --------- | -------------- |

| Host | 127.0.0.1 |

| Port | 3306 |

| Database | klinik_antrian |

| Username | root |

| Password | Kosong |

Koneksi database menggunakan PDO dan mendukung konfigurasi lewat variabel lingkungan:

| Env | Default |

| ---------- | -------------- |

| `DB_HOST` | 127.0.0.1 |

| `DB_PORT` | 3306 |

| `DB_NAME` | klinik_antrian |

| `DB_USER` | root |

| `DB_PASS` | (kosong) |

Terdapat fallback otomatis untuk lingkungan MAMP (port `8889`, user/password `root`).

File PHP yang membutuhkan koneksi dapat memanggil:

```php

require_once DIR . '/../config/koneksi.php';

```

Objek koneksi database tersedia melalui variabel `$pdo`.

## 8. Penggunaan

### 8.1 Alur Pasien

Buka `index.php` untuk melihat jadwal dokter hari ini beserta sisa kuota.

Klik **Daftar Akun** untuk membuat akun baru, lalu login lewat halaman **Masuk**.

Setelah login, buka menu **Antrean Saya** untuk masuk ke portal pasien.

Pilih dokter yang praktik hari ini, lalu klik **Ambil Nomor**.

Pantau status antrean (sedang dilayani dan sisa antrean di depan). Gunakan tombol **Segarkan Status** untuk memperbarui.

Antrean yang masih berstatus *menunggu* dapat dibatalkan lewat tombol **Batalkan Antrean**.

### 8.2 Alur Admin

Login menggunakan akun admin (lihat bagian 9).

Pada **Dashboard Admin**, pilih **Kelola Dokter** atau **Buka Antrian**.

**Kelola Dokter**: tambah, edit, atau hapus data dokter (nama, spesialis, kuota per hari, hari praktik).

**Daftar Antrian**: pantau antrean hari ini, gunakan tombol **Panggil** untuk memanggil pasien dan **Selesai** untuk menutup antrean.

Untuk keluar, klik **Keluar** pada navigasi.

## 9. Akun Pengujian

| Role | Email | Password |

| ------ | ------------------------- | ---------------- |

| Admin | admin@klinik.local | AdminKlinik#2026 |

| Pasien | andi.pasien@klinik.local | Pasien01#2026 |

| Pasien | bunga.pasien@klinik.local | Pasien02#2026 |

| Pasien | citra.pasien@klinik.local | Pasien03#2026 |

Password disimpan dalam bentuk hash menggunakan PHP `password_hash()` dengan algoritma PASSWORD_DEFAULT.

Gunakan `password_verify()` untuk proses verifikasi password.

Akun tersebut hanya digunakan untuk pengujian praktikum.