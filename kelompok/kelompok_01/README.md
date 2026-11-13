# SaveSmart

SaveSmart adalah aplikasi web untuk mencatat dan memantau keuangan keluarga. Pemasukan, pengeluaran, anggaran bulanan, dan target tabungan semuanya dikelola di satu tempat, lalu diringkas lewat dashboard dan laporan. Aplikasi ini dibuat untuk Tugas Besar Pemrograman Web 2026 (Kelompok 01).

Repository: https://github.com/HafizhAbdoelGhofarr/tubes_prk_pemweb_2026

## Fitur

**Pengguna (kepala keluarga)**
- Registrasi dan login. Saat mendaftar, sistem otomatis membuat satu data keluarga untuk akun tersebut.
- Dashboard berisi ringkasan pemasukan, pengeluaran, saldo, dan tabungan, plus grafik bulanan dan transaksi terbaru.
- Catat, ubah, dan hapus pemasukan serta pengeluaran.
- Atur anggaran bulanan per kategori, lalu lihat realisasinya.
- Buat target tabungan, catat setoran, dan pantau progresnya. Total terkumpul dihitung otomatis lewat trigger di database.
- Laporan keuangan berdasarkan rentang tanggal.

**Admin**
- Dashboard admin.
- Kelola pengguna (aktifkan / nonaktifkan akun).
- Kelola kategori transaksi.

## Teknologi

- PHP Native (PDO)
- MySQL / MariaDB (dengan stored procedure, view, dan trigger)
- HTML, CSS, JavaScript
- Chart.js untuk grafik (dimuat lewat CDN, jadi butuh koneksi internet)
- Laragon (Apache) untuk server lokal

## Cara Instalasi

Panduan ini pakai Laragon di Windows. Kalau pakai XAMPP, langkahnya sama saja, tinggal sesuaikan foldernya.

1. **Clone atau ekstrak proyek** ke folder web server.
   - Laragon: `C:\laragon\www\`
   - XAMPP: `C:\xampp\htdocs\`

   ```bash
   git clone https://github.com/HafizhAbdoelGhofarr/tubes_prk_pemweb_2026.git
   ```

2. **Nyalakan Apache dan MySQL** dari Laragon (klik *Start All*).

3. **Import database.** Buka phpMyAdmin (atau HeidiSQL di Laragon), lalu import file `database/database.sql`. File ini sudah membuat database `savesmart` sendiri, jadi tidak perlu bikin database kosong dulu.

   Lewat terminal juga bisa:

   ```bash
   mysql -u root < database/database.sql
   ```

4. **Cek konfigurasi koneksi** di `config/database.php`. Nilai bawaannya:

   ```php
   $host = 'localhost';
   $dbname = 'savesmart';
   $username = 'root';
   $password = '';
   ```

   Ubah kalau MySQL-mu pakai user atau password yang berbeda.

5. **Buka di browser:**

   ```
   http://localhost/tubes_prk_pemweb_2026/kelompok/kelompok_01/index.php
   ```

   Sesuaikan nama folder dengan hasil clone-mu.

## Akun Default

Database sudah berisi satu akun admin:

| Role  | Email                | Password            |
|-------|----------------------|---------------------|
| Admin | admin@savesmart.com  | `admin123` |

Akun pengguna biasa dibuat sendiri lewat halaman **Daftar** (`register.php`). Password minimal 8 karakter.

## Cara Penggunaan

1. Daftar akun baru lewat halaman registrasi, isi nama keluarga, nama lengkap, email, dan password. Setelah itu login.
2. Di **Pemasukan** dan **Pengeluaran**, tambahkan transaksi dengan memilih kategori, jumlah, tanggal, dan keterangan.
3. Di **Anggaran**, tentukan batas pengeluaran per kategori untuk bulan tertentu. Realisasinya akan muncul dibandingkan dengan transaksi yang sudah dicatat.
4. Di **Tabungan**, buat target (nama, nominal, tanggal target), lalu catat setoran setiap kali menabung. Progresnya naik otomatis.
5. Di **Laporan**, pilih rentang tanggal untuk melihat total dan rincian pemasukan serta pengeluaran.
6. Login sebagai admin kalau mau mengelola pengguna dan kategori. Admin akan langsung diarahkan ke dashboard admin.

## Struktur Folder

```
├── assets/          CSS, JavaScript, dan gambar
├── backend/         Proses form (auth, transaksi, anggaran, tabungan, laporan, admin)
├── components/      Header, navbar, dan sidebar
├── config/          Koneksi database dan session
├── database/        database.sql (tabel, view, trigger, stored procedure, data awal)
├── pages/           Halaman aplikasi setelah login
├── index.php
├── login.php
├── register.php
└── logout.php
```

## Database

Terdapat 8 tabel: `keluarga`, `pengguna`, `kategori`, `pemasukan`, `pengeluaran`, `anggaran`, `target_tabungan`, dan `setoran_tabungan`. Sebagian besar query dijalankan lewat stored procedure (diawali `sp_`), dan ada beberapa view (`v_transaksi`, `v_realisasi_anggaran`, `v_progres_tabungan`, `v_ringkasan_bulanan`) untuk kebutuhan dashboard dan laporan.


## Anggota Kelompok

| Nama | NPM |
|------|-----|
| Adelia Aura Nabilla | 2415061035 |
| Hafizh Abdoel Ghofar | 2415061076 |
| AKHMAD FAISHAL KHARISMA | 2415061054 |
| Nabila Saida Zara | 2415061110 |
| Anugrah Aziz Firdaus | 2415061115 |