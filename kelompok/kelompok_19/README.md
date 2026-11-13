# KebunKu

Sistem Informasi Manajemen Agrikultur berbasis web untuk memonitor dan mengelola operasional pertanian secara terpadu, mencakup pemetaan lahan, pemantauan siklus tanam, pengelolaan inventaris logistik, pendataan tenaga kerja, serta pencatatan hasil panen.

Aplikasi dibangun menggunakan PHP Native dengan pola MVC ringan, koneksi database PDO, dan interaksi antarmuka berbasis AJAX (Fetch API) tanpa framework CSS eksternal.

---

## Fitur

- **Dashboard** — Ringkasan metrik utama operasional kebun dan integrasi widget prakiraan cuaca.
- **Manajemen Lahan** — Pendataan lokasi, luas area, dan klasifikasi jenis tanah.
- **Siklus Tanam** — Monitoring fase penanaman per petak lahan dari tanam, perawatan, hingga estimasi panen.
- **Inventaris** — Pengendalian stok sarana produksi (bibit, pupuk, pestisida, peralatan) beserta biaya satuan.
- **Tenaga Kerja** — Pengelolaan data pekerja, jabatan fungsional, dan skema kompensasi.
- **Hasil Panen** — Pencatatan log panen per siklus, volume produksi (kg), dan grading kualitas hasil.

---

## Struktur Direktori

```
kebunku/
├── app/
│   ├── Config/
│   │   └── Database.php          # Konfigurasi koneksi PDO & helper response JSON
│   ├── Controllers/              # Handler request & logika bisnis API
│   ├── Models/                   # Layer abstraksi data & query SQL (PDO)
│   └── Views/                    # Template antarmuka
│       └── layouts/              # Komponen bersama (header, sidebar, footer)
├── database/
│   └── schema.sql                # DDL tabel & data inisialisasi awal
└── public/                       # Web root publik
    ├── index.php                 # Front controller (page router)
    ├── api.php                   # Gateway endpoint REST API
    └── assets/
        ├── css/                  # Stylesheet kustom & design tokens
        └── js/                   # Skrip controller AJAX (Fetch API)
```

---

## Alur Kerja Sistem

1. **Routing Halaman**: Semua permintaan antarmuka diproses melalui `public/index.php` berdasarkan path URL (`/`, `/lahan`, `/siklus`, `/inventaris`, `/pekerja`, `/panen`) yang memuat view terkait beserta layout dasarnya.
2. **Komunikasi Data**: Setiap halaman memuat script JavaScript masing-masing di direktori `public/assets/js/` yang berkomunikasi ke `public/api.php` secara asinkron (AJAX Fetch).
3. **API Gateway**: `public/api.php` menerima permintaan REST dengan pola `/api/<modul>` dan `/api/<modul>/<id>` untuk metode HTTP (`GET`, `POST`, `PUT`, `DELETE`), kemudian mendelegasikannya ke controller yang sesuai. Sub-route khusus: `/api/inventaris/transaksi` dan `/api/pekerja/presensi`.
4. **Data Access**: Controller memanggil model terkait untuk mengeksekusi prepared statement PDO, kemudian mengembalikan respons terstandarisasi dalam format JSON.

---

## Kebutuhan Sistem

- PHP >= 8.0 (ekstensi `pdo_mysql` aktif)
- MySQL / MariaDB >= 10.3
- Web Server Apache / Nginx, atau PHP Built-in Server

---

## Instalasi & Menjalankan

1. **Clone / Pasang Repositori**  
   Tempatkan folder proyek ke dalam direktori server web (misalnya `laragon/www/` atau `xampp/htdocs/`).

2. **Setup Database**  
   Buat database baru di MySQL dan impor skema awal:
   ```bash
   mysql -u root -p -e "CREATE DATABASE kebunku;"
   mysql -u root -p kebunku < database/schema.sql
   ```
   Di Windows PowerShell, operator `<` tidak dikenali. Gunakan salah satu alternatif berikut:
   ```powershell
   Get-Content database/schema.sql | mysql -u root -p kebunku
   ```
   ```cmd
   mysql -u root -p kebunku < database/schema.sql
   ```

   > `database/schema.sql` sudah memuat `CREATE DATABASE IF NOT EXISTS kebunku;` dan `USE kebunku;`, serta data contoh. Menjalankan ulang file ini akan gagal pada `CREATE TABLE` karena tabel sudah ada. Hapus dulu database `kebunku` bila ingin impor dari awal.

3. **Konfigurasi Lingkungan**  
   Sesuaikan parameter koneksi database pada `app/Config/Database.php` bila diperlukan:
   ```php
   private $host = "localhost";
   private $db_name = "kebunku";
   private $username = "root";
   private $password = "";
   ```

4. **Menjalankan Aplikasi**  
   Buka terminal pada root proyek:
   ```bash
   cd public
   php -S localhost:8000
   ```
   Lalu buka `http://localhost:8000` pada browser.

---

## Penggunaan

Aplikasi tidak memakai halaman login. Semua menu langsung dapat diakses dari sidebar kiri: **Lahan**, **Siklus**, **Inventaris**, **Pekerja**, dan **Panen**. Tombol **Mode gelap** di bagian bawah sidebar mengganti tema terang/gelap.

### Urutan pengisian data

Isi data mengikuti relasi antar tabel agar pilihan pada form berikutnya tersedia:

1. **Lahan** — buat petak lahan lebih dulu.
2. **Siklus** — pilih lahan yang sudah dibuat, isi tanaman dan tanggal tanam.
3. **Panen** — pilih siklus yang sudah dibuat, isi tanggal, berat, dan kualitas.

Untuk modul lain:

- **Inventaris** — buat item barang, lalu catat transaksi masuk/keluar per siklus.
- **Pekerja** — daftarkan pekerja, lalu catat presensi beserta upah harian per siklus.

### Cara pakai tiap halaman

- **Dashboard** — menampilkan ringkasan stok menipis, siklus terdekat, upah tercatat, dan cuaca. Tautan aksi cepat membuka halaman modul terkait. Widget cuaca mengambil data dari `api.open-meteo.com`, jadi butuh koneksi internet; tanpa internet widget menampilkan "Data cuaca tidak tersedia."
- **Lahan** — tombol **Tambah petak** membuka dialog form. Isi nama, lokasi, luas, dan jenis tanah, lalu **Simpan petak**. Tombol **Edit**/**Hapus** ada di kolom aksi setiap baris tabel.
- **Siklus** — tombol **Tambah siklus** membuka form siklus. Gunakan panel **Filter siklus** lalu **Terapkan** untuk menyaring data; **Reset** mengembalikan tampilan penuh.
- **Inventaris** — **Tambah item** untuk data barang. Gunakan **Catat transaksi** pada baris item untuk mencatat barang masuk/keluar; stok item ikut menyesuaikan.
- **Pekerja** — **Tambah pekerja** untuk data pekerja, **Catat presensi** untuk kehadiran dan upah harian. Panel **Filter presensi** menyaring riwayat presensi.
- **Panen** — **Catat panen** membuka form panen (siklus, tanggal, berat kg, kualitas A/B/C/Reject, catatan). Isi rentang **Filter tanggal** lalu **Terapkan** untuk menghitung total pada periode tersebut; **Reset** menampilkan semua catatan. **Cetak PDF** membuka dialog cetak browser (pilih "Save as PDF"); elemen tombol, filter, dialog, dan sidebar otomatis disembunyikan saat dicetak.

### Catatan

- Semua tanggal memakai format `YYYY-MM-DD` pada input dan ditampilkan dalam format Indonesia.
- Berat panen minimal 0,01 kg. Kualitas hanya boleh `A`, `B`, `C`, atau `Reject`.
- Data contoh dari `schema.sql` bisa diubah atau dihapus langsung dari antarmuka; tidak ada data yang dilindungi.
- Pesan berhasil/gagal tampil sebagai banner di atas halaman. Pesan berhasil hilang otomatis setelah ±4,5 detik; pesan gagal tetap tampil sampai ditutup. Kesalahan validasi form juga tampil di dalam dialog.
