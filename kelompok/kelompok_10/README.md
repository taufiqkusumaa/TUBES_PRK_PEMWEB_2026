# SisaSense &bull; Kelompok 10

**Sistem Manajemen Persediaan, Kasir POS Modern, dan Analisis Performa Penjualan UMKM**  
*Tugas Besar Praktikum Pemrograman Web 2026 &bull; Kelompok 10*

---

## 1. Identitas Proyek & Anggota Tim

SisaSense adalah aplikasi web *enterprise-grade* berbasis **PHP Native 8.2** dan **MySQL** yang dirancang untuk mendigitalkan operasional UMKM retail dan pangan nusantara. Sistem mengintegrasikan pencatatan kasir POS real-time, alokasi stok berprinsip **FEFO (*First Expired, First Out*)**, sistem pendukung keputusan restok barang (*ADS & ROP*), serta laporan laba-rugi kotor otomatis.

### Anggota Pengembang (Kelompok 10):
| No | Nama Mahasiswa | NIM / NPM | Peran & Kontribusi |
|:---:|---|:---:|---|
| 1 | **Fadhlan** *(Lead Developer)* | *2317051...* | Arsitektur Sistem, Integrasi Database & Backend |
| 2 | **Anggota Kelompok 10** | *2317051...* | Frontend UI/UX NutrAmla, Kasir POS & Modul Transaksi |
| 3 | **Anggota Kelompok 10** | *2317051...* | Modul Analitik Keuangan, Laporan & Testing |

---

## 2. Tiga Peran Pengguna (Multi-Role System)

Sistem menerapkan pembatasan hak akses berbasis peran (*Role-Based Access Control / RBAC*) dengan session yang aman:

| Peran | Hak Akses Utama | Halaman Utama |
|---|---|---|
| **Administrator** | Akses Penuh: Manajemen produk & master kategori, input stok batch masuk, opname/koreksi barang rusak, analisis laporan keuangan, rekomendasi restok otomatis (ROP), dan simulasi diskon harga. | `dashboard.php`, `products/`, `inventory/`, `reports/`, `sales/` |
| **Kasir (Cashier)** | Akses Operasional Toko: Kasir POS interaktif, pencarian cepat produk & SKU, kalkulasi uang tunai/kembalian, modal multi-metode pembayaran, riwayat transaksi, dan cetak struk nota belanja. | `dashboard.php`, `sales/pos.php`, `sales/history.php`, `inventory/index.php` |
| **Pembeli (Customer)** | Portal Pelanggan: Registrasi akun mandiri (`register.php`), eksplorasi etalase toko bertema NutrAmla, keranjang belanja interaktif, dan riwayat pesanan pribadi. | `dashboard.php`, `products/index.php`, `sales/my_orders.php` |

### Akun Demo Pengujian (Tinggal Pakai):
Seluruh akun demo telah di-seed dengan kata sandi default **`123`**:

| Peran | Username | Email | Password | URL Akses |
|---|---|---|:---:|---|
| **Administrator** | `admin` | `admin@sisasense.id` | **`123`** | `login.php` &rarr; `dashboard.php` |
| **Kasir** | `kasir` | `kasir@sisasense.id` | **`123`** | `login.php` &rarr; `sales/pos.php` |
| **Pembeli** | `pembeli` | `pembeli@sisasense.id` | **`123`** | `login.php` &rarr; `products/index.php` |

> *Catatan: Pengunjung baru juga dapat mendaftar langsung sebagai pembeli melalui menu **Daftar Akun Baru** pada `register.php`.*

---

## 3. Fitur Unggulan Sistem

### A. Desain UI/UX E-Commerce Modern (Terinspirasi NutrAmla & Ecavo)
1. **Top Navbar Header 3-Zona (Ala NutrAmla `nutramla.com`):**
   - Menghilangkan sidebar samping yang kaku; menggantinya dengan canvas navigasi horizontal penuh (*full-width canvas*).
   - **Kiri:** Tautan navigasi aktif sesuai role (`Beranda`, `Katalog`, `Kasir POS`, `Laporan`, dll).
   - **Tengah:** Framed Badge Logo elegan `UMKM Nusantara & SisaSense`.
   - **Kanan:** Pencarian instan katalog, avatar pengguna dengan dropdown profil, serta badge tas keranjang hijau limau (*lime green*) dinamis.
2. **Koleksi Etalase & Card Produk Interaktif:**
   - Showcase *"Shop Collections"* 4 kolom responsif (Camilan Snack, Minuman Herbal, Sembako Pokok, Oleh-Oleh Khas Daerah).
   - Card produk dilengkapi rating bintang `★★★★★`, stepper jumlah kuantitas `[-] 1 [+]`, tombol cepat `Add to Cart`, dan tombol hijau `Buy Now`.
3. **Bebas Gangguan (*Clean UI*):**
   - Menghapus tulisan asing/India ("सिसासेंस", dll) dan meniadakan nomor kontak/WhatsApp yang mengganggu ruang kerja kasir.

### B. Kasir POS & Modal Pembayaran Multi-Metode (`sales/pos.php`)
1. **Pencarian & Filter Cepat:** Temukan produk secara instan berdasarkan nama atau SKU, dengan pill filter kategori.
2. **Modal Checkout Pop-Up 4 Metode (Terinspirasi Ecavo):**
   - 💵 **Tunai (Cash):** Kalkulasi kembalian otomatis real-time & quick cash buttons (`Uang Pas`, `+10rb`, `+20rb`, `+50rb`, `+100rb`).
   - 📱 **QRIS (Standar Bank Indonesia):** QR code dinamis terintegrasi e-wallet & mobile banking.
   - 🏦 **Transfer Bank:** Nomor rekening resmi BCA, Mandiri, dan BRI dengan tombol salin instan (*copy-to-clipboard*).
   - 💳 **Kartu Debit / EDC:** Pilihan mesin EDC bank dan pencatatan nomor approval kartu.
3. **Struk Nota Kasir Siap Cetak (`sales/receipt.php`):**
   - Desain nota printer termal 58mm/80mm maupun kertas A4 dengan dukungan `@media print`.

### C. Alokasi Stok Cerdas FEFO & Transaksi Atomik (`sales/checkout.php`)
1. **FEFO (First Expired, First Out):** Algoritma checkout otomatis memprioritaskan pengurangan stok dari batch barang yang memiliki tanggal kedaluwarsa paling dekat.
2. **Integritas Database Atomik:** Menggunakan `BEGIN TRANSACTION` dengan *row-level locking* (`FOR UPDATE`) pada MySQL InnoDB. Mengembalikan (*rollback*) secara utuh jika stok fisik tidak mencukupi.
3. **Audit Trail Mutasi Stok (`inventory/movements.php`):** Setiap transaksi penjualan, stok masuk, atau koreksi opname otomatis tercatat ke tabel audit log persediaan.

### D. Analitik Keuangan & Sistem Pendukung Keputusan (SPK)
1. **Executive Dashboard (`reports/index.php`):**
   - Ringkasan Omzet Bersih, Total HPP Terjual, Laba Kotor, dan Persentase Margin Keuntungan.
   - Grafik tren penjualan 7 hari terakhir berbasis **Native Pure SVG Micro-Chart** tanpa ketergantungan library JavaScript pihak ketiga.
2. **SPK Rekomendasi Restok (`reports/restock_recommendations.php`):**
   - Perhitungan *Average Daily Sales* (ADS), *Safety Stock*, *Lead Time*, dan *Reorder Point* (ROP) untuk mencegah kehabisan stok (*stockout*).
3. **Simulator Diskon & Proteksi Margin (`reports/discount_simulator.php`):**
   - Simulasi potongan harga yang membunyikan alarm peringatan jika harga diskon berada di bawah HPP modal UMKM.
4. **Ekspor Laporan CSV Excel-Ready (`reports/export_sales_csv.php`):**
   - Laporan transaksi penjualan dan persediaan berformat CSV standar RFC 4180 lengkap dengan UTF-8 BOM untuk Microsoft Excel.

---

## 4. Struktur Direktori Proyek

```text
kelompok_10/
├── analytics/                   # [MODUL ANALITIK]
│   ├── functions.php            # Formula agregasi data & kalkulasi margin
│   └── index.php                # Dashboard analitik performa toko
├── assets/
│   └── css/
│       └── style.css            # Desain sistem NutrAmla, Ecavo, POS, & cetak nota
├── config/
│   ├── auth.php                 # Autentikasi RBAC, session cookie unik, CSRF, URL resolver
│   ├── database.php             # Koneksi PDO Singleton (mendukung local & InfinityFree)
│   └── database.local.example.php # Template konfigurasi koneksi hosting InfinityFree
├── database/
│   ├── sisa_sense.sql           # DDL 8 tabel + seeder awal lokal (Laragon/XAMPP)
│   └── infinityfree_seeder.sql  # SQL Seeder khusus InfinityFree (Bebas error 1044)
├── includes/
│   ├── header.php               # Top Navbar 3-Zona ala NutrAmla (responsif)
│   └── footer.php               # Penutup layout bersama
├── inventory/                   # [MODUL PERSEDIAAN & BATCH]
│   ├── index.php                # Monitoring stok fisik real-time
│   ├── incoming.php             # Form catat stok masuk (Batch baru)
│   ├── batches.php              # Pelacak batch & hitung mundur kedaluwarsa (FEFO)
│   ├── adjustment.php           # Form penyesuaian barang rusak / selisih opname
│   ├── movements.php            # Riwayat mutasi persediaan (audit log lengkap)
│   ├── stock_in.php             # Input penerimaan stok
│   └── stock_out.php            # Input pengeluaran non-penjualan
├── products/                    # [MODUL PRODUK & KATEGORI]
│   ├── index.php                # Katalog etalase produk dengan filter kategori
│   ├── create.php               # Form penambahan produk baru + auto-SKU
│   ├── edit.php                 # Form edit data produk & status aktif
│   ├── form.php                 # Komponen form bersama produk
│   └── categories.php           # Manajemen kategori barang
├── recommendations/             # [MODUL SPK & REKOMENDASI]
│   ├── restock.php              # Logika kalkulasi Reorder Point (ROP) & ADS
│   └── discount.php             # Logika evaluasi batas diskon aman vs HPP
├── reports/                     # [MODUL LAPORAN & KEUANGAN]
│   ├── index.php                # Executive Dashboard Keuangan & Tren SVG
│   ├── sales_report.php         # Laporan penjualan detail dengan filter periode
│   ├── inventory_report.php     # Laporan valuasi aset persediaan (HPP)
│   ├── export_sales_csv.php     # Unduh CSV transaksi penjualan (Excel-ready)
│   ├── export_inventory_csv.php # Unduh CSV ringkasan stok persediaan
│   ├── restock_recommendations.php # Halaman rekomendasi restok barang
│   └── discount_simulator.php   # Simulator diskon harga & alarm defisit modal
├── sales/                       # [MODUL KASIR POS & TRANSAKSI]
│   ├── pos.php                  # Antarmuka kasir belanja interaktif (Card & Modal)
│   ├── checkout.php             # Pemroses transaksi atomik database (FEFO)
│   ├── history.php              # Riwayat struk invoice transaksi toko
│   ├── receipt.php              # Tampilan nota kasir siap cetak termal/A4
│   ├── my_orders.php            # Riwayat belanja khusus akun pembeli
│   └── void.php                 # Pembatalan transaksi kasir (dengan otorisasi)
├── skills/                      # [PANDUAN DESAIN SISTEM]
│   ├── artisan-pos-design/SKILL.md
│   ├── ecavo-modern-agro/SKILL.md
│   └── nutramla-shopify-design/SKILL.md
├── tests/
│   ├── run.php                  # Test suite otomatis logika bisnis & keamanan (6 suite)
│   └── test_nutramla_render.php # Uji rendering tampilan antarmuka NutrAmla
├── dashboard.php                # Beranda utama (beradaptasi otomatis sesuai peran)
├── index.php                    # Gerbang landing page & katalog etalase utama
├── login.php                    # Form masuk akun (dilengkapi cheat sheet demo)
├── logout.php                   # Penghancur sesi & pembersihan cookie aman
├── register.php                 # Form registrasi mandiri akun pembeli
├── seeder.php                   # Web Seeder interaktif 1-klik (InfinityFree & Lokal)
├── setup.php                    # Inisialisasi awal admin toko
├── readme.md                    # Dokumentasi lengkap proyek
└── .gitignore
```

---

## 5. Dokumentasi Instalasi Sistem

### 5.1 Persyaratan Lingkungan (System Requirements)
- **PHP:** Versi 8.2 atau lebih baru.
- **Ekstensi PHP Wajib:** `pdo_mysql`, `mbstring`, `session`.
- **Database Server:** MySQL 5.7+ atau MariaDB 10.3+.
- **Web Server:** Apache (Laragon, XAMPP, atau PHP Built-in Server).
- **Peramban Web:** Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari modern.

---

### 5.2 Langkah Instalasi Lokal (Laragon / XAMPP)
1. **Jalankan Apache & MySQL:** Buka Laragon atau XAMPP, pastikan modul **Apache** dan **MySQL** berstatus *Running*.
2. **Impor Basis Data:**
   - Buka browser dan akses phpMyAdmin di `http://localhost/phpmyadmin`.
   - Pilih tab **Import**.
   - Klik **Choose File**, pilih berkas: `kelompok/kelompok_10/database/sisa_sense.sql`.
   - Klik **Go / Kirim**. Database `sisasense_db` beserta 8 tabel, 5 kategori, 24 produk foto real, dan 3 akun demo akan dibuat secara otomatis.
3. **Buka Aplikasi di Browser:**
   Akses melalui browser di alamat:
   ```text
   http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_10/
   ```
4. **Konfigurasi Database Bawaan (`config/database.php`):**
   - Host: `127.0.0.1` | Port: `3306`
   - Database: `sisasense_db`
   - User: `root` | Password: *(sesuai pengaturan MySQL lokal Anda)*

---

### 5.3 Langkah Deployment ke Hosting Publik (InfinityFree)

InfinityFree memiliki aturan keamanan cPanel yang ketat: nama basis data wajib diawali dengan prefix akun (misal: `if0_38123456_...`) dan **dilarang** menggunakan perintah `CREATE DATABASE` atau `USE ...` di phpMyAdmin.

SisaSense menyediakan dua opsi instalasi di InfinityFree:

#### Opsi A: Menggunakan Web Seeder Interaktif (`seeder.php`) — *Direkomendasikan (1-Klik)*
1. Unggah seluruh file folder `kelompok_10/` ke direktori `htdocs/` di InfinityFree (menggunakan File Manager cPanel atau FileZilla FTP).
2. Di vPanel InfinityFree, buka menu **MySQL Databases** dan buat database baru (misal: `if0_38123456_sisasense`).
3. Buat file `config/database.local.php` (bisa mencontoh template `config/database.local.example.php`) berisi:
   ```php
   <?php
   return [
       'host'     => 'sql300.infinityfree.com', // Lihat di MySQL Databases vPanel
       'database' => 'if0_38123456_sisasense',  // Nama Database lengkap dari vPanel
       'username' => 'if0_38123456',            // Username Akun vPanel
       'password' => 'PASSWORD_VPANEL_ANDA',    // Password Akun vPanel Anda
       'port'     => '3306',
   ];
   ```
4. Buka URL seeder di browser Anda:
   ```text
   http://<subdomain-anda>.infinityfreeapp.com/seeder.php
   ```
5. Halaman web seeder akan memeriksa koneksi, membuat seluruh tabel secara otomatis, dan menyuntikkan data akun `admin`, `kasir`, serta `pembeli` dengan password `123`.

#### Opsi B: Impor Manual File SQL via phpMyAdmin InfinityFree
1. Buka vPanel InfinityFree &gt; **MySQL Databases** &gt; klik tombol **phpMyAdmin** pada database yang telah dibuat.
2. Masuk ke tab **Import**.
3. Pilih file `kelompok/kelompok_10/database/infinityfree_seeder.sql`. File ini **bebas** dari klausul `CREATE DATABASE` maupun `USE`, serta telah dilengkapi `SET FOREIGN_KEY_CHECKS = 0;` sehingga terjamin **100% bebas dari error #1044 Access Denied**.
4. Klik tombol **Go / Kirim** di kanan bawah.

---

## 6. Dokumentasi Penggunaan Sistem (User Guide)

Panduan operasional alur kerja sistem untuk masing-masing hak akses pengguna:

### 6.1 Alur Penggunaan Sebagai Pembeli (Customer Flow)
1. **Masuk Akun / Pendaftaran:**
   - Akses halaman `login.php` dan gunakan kredensial demo `pembeli` / `123`.
   - Atau klik **Daftar Akun Pembeli** pada halaman `register.php` untuk membuat akun pribadi baru.
2. **Menjelajahi Etalase & Katalog Produk (`products/index.php`):**
   - Menikmati antarmuka bertema **NutrAmla (`nutramla.com`)** dengan koleksi visual *"Shop Collections"* (Camilan Snack, Minuman Segar, Sembako Pokok, dan Oleh-Oleh Khas Daerah).
   - Gunakan fitur pencarian instan nama produk atau klik pill filter kategori untuk menyaring produk.
3. **Menambahkan Produk ke Keranjang:**
   - Pada kartu produk, atur kuantitas pembelian menggunakan tombol stepper `[-] 1 [+]`.
   - Klik tombol **Add** untuk memasukkan barang ke keranjang belanja, atau klik tombol hijau **Buy Now** untuk langsung menuju proses pembelian.
4. **Melihat Riwayat Belanja (`sales/my_orders.php`):**
   - Klik ikon profil pada navbar atas &gt; pilih **Pesanan Saya**.
   - Pembeli dapat melihat seluruh histori transaksi pembelian, tanggal belanja, total biaya, dan melihat struk digital.

---

### 6.2 Alur Penggunaan Sebagai Kasir (Cashier / POS Flow)
1. **Masuk Akun Kasir:**
   - Masuk melalui `login.php` menggunakan username `kasir` dan password `123`.
2. **Membuka Antarmuka Kasir POS (`sales/pos.php`):**
   - Kasir disuguhkan workspace kasir yang bersih tanpa sidebar samping dan tanpa gangguan nomor kontak yang tidak relevan.
   - Pada panel kiri, klik kartu produk atau gunakan kolom cari nama/SKU untuk memasukkan item ke keranjang belanja.
   - Pada panel kanan keranjang kasir, kasir dapat menyesuaikan kuantitas `[+]` / `[-]` atau menghapus item.
3. **Memproses Checkout Kasir (Modal Ecavo):**
   - Klik tombol hijau **Checkout / Bayar** untuk membuka modal pop-up pembayaran interaktif:
     - **Metode Tunai (Cash):** Masukkan nominal uang yang diterima pembeli atau klik tombol cepat uang pas (`Uang Pas`, `+10.000`, `+20.000`, `+50.000`, `+100.000`). Sistem menghitung uang kembalian secara otomatis.
     - **Metode QRIS:** Tampilkan kode QR standar Bank Indonesia untuk dipindai e-wallet pembeli.
     - **Metode Transfer Bank:** Tampilkan nomor rekening resmi BCA, Mandiri, atau BRI dengan tombol satu-klik *Salin Nomor Rekening*.
     - **Metode Kartu Debit / EDC:** Pilih mesin EDC dan input nomor referensi approval.
4. **Menyelesaikan Transaksi & Cetak Struk (`sales/receipt.php`):**
   - Klik tombol **Konfirmasi & Cetak Struk**.
   - Sistem melakukan pengurangan stok atomik dengan alokasi **FEFO** dan langsung mengarahkan ke tampilan nota kasir siap cetak termal (58mm/80mm) atau PDF/A4.
5. **Riwayat Invoice Toko (`sales/history.php`):**
   - Kasir dapat melihat seluruh riwayat nota transaksi toko yang telah selesai serta mencetak ulang nota jika diperlukan.

---

### 6.3 Alur Penggunaan Sebagai Administrator (Admin Flow)
1. **Masuk Akun Administrator:**
   - Masuk melalui `login.php` menggunakan username `admin` dan password `123`.
2. **Memantau Executive Dashboard (`dashboard.php` & `reports/index.php`):**
   - Melihat metrik kartu performa: Omzet Bersih, HPP Terjual, Laba Kotor Usaha, dan Persentase Margin Keuntungan.
   - Mengamati tren omzet 7 hari terakhir lewat grafik Native Pure SVG Micro-Chart.
   - Memantau peringatan stok kritis dan barang yang mendekati batas kedaluwarsa.
3. **Manajemen Master Data Produk & Kategori (`products/`):**
   - Menambah produk baru dengan SKU otomatis (`products/create.php`).
   - Mengubah informasi harga jual, batas stok minimum, foto URL, dan status aktif produk (`products/edit.php`).
   - Menambah dan mengelola kategori produk (`products/categories.php`).
4. **Pencatatan Stok Masuk & Manajemen Batch FEFO (`inventory/`):**
   - Menginput barang masuk dari supplier dengan mencatat tanggal kedaluwarsa dan nomor batch baru (`inventory/incoming.php`).
   - Memantau masa simpan seluruh lot/batch dan hitung mundur kedaluwarsa (`inventory/batches.php`).
   - Melakukan penyesuaian stok opname untuk barang rusak, hilang, atau kadaluwarsa (`inventory/adjustment.php`).
   - Menginspeksi audit log seluruh mutasi keluar-masuk barang (`inventory/movements.php`).
5. **Sistem Pendukung Keputusan (SPK) Rekomendasi Restok (`reports/restock_recommendations.php`):**
   - Sistem secara otomatis menghitung *Average Daily Sales* (ADS), *Safety Stock*, *Lead Time*, dan *Reorder Point* (ROP).
   - Admin mendapatkan rekomendasi jumlah unit yang harus segera dipesan ulang ke supplier sebelum stok habis.
6. **Simulator Diskon Harga (`reports/discount_simulator.php`):**
   - Admin dapat menguji simulasi promo diskon (persen atau nominal).
   - Fitur keamanan cerdas akan membunyikan alarm peringatan merah jika harga diskon jatuh di bawah HPP modal UMKM, melindungi toko dari potensi kerugian.
7. **Ekspor Laporan CSV Excel-Ready (`reports/export_sales_csv.php`):**
   - Mengunduh rekapitulasi data penjualan dan data stok persediaan berstandar RFC 4180 lengkap dengan UTF-8 BOM untuk dibuka langsung di Microsoft Excel.

---

## 7. Menjalankan Pengujian Otomatis (Testing)

Proyek ini dilengkapi test runner mandiri untuk memastikan reliabilitas logika bisnis dan keamanan sebelum dipresentasikan:

### 1. Test Suite Logika Bisnis & Keamanan:
```bash
php kelompok/kelompok_10/tests/run.php
```
*Menguji 6 aspek kritis:*
- `[1/6]` Autentikasi multi-role (Admin, Kasir, Pembeli) dan verifikasi password bcrypt `123`.
- `[2/6]` Sanitasi XSS (Cross-Site Scripting) dan validasi token CSRF form POST.
- `[3/6]` Dynamic URL Resolver (`app_url`) lintas subdirektori.
- `[4/6]` Algoritma alokasi batch FEFO (*First Expired, First Out*).
- `[5/6]` Akurasi formula Laba Kotor, HPP Snapshot, dan Margin Omzet.
- `[6/6]` Formula Reorder Point (ROP), Safety Stock, dan deteksi defisit diskon harga.

### 2. Test Rendering Tampilan UI NutrAmla:
```bash
php kelompok/kelompok_10/tests/test_nutramla_render.php
```

### 3. Pemeriksaan Sintaks PHP (Linter Seluruh File):
```powershell
Get-ChildItem kelompok\kelompok_10 -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

---

## 8. Standar Keamanan & Kepatuhan Praktikum

- **Anti-XSS (Cross-Site Scripting):** Setiap variabel dinamis yang ditampilkan ke browser dilewatkan melalui fungsi `e()` (`htmlspecialchars` dengan flag `ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5`).
- **Anti-SQL Injection:** 100% kueri basis data menggunakan PDO Prepared Statements dengan binding parameter (`:param` atau `?`).
- **Proteksi CSRF (Cross-Site Request Forgery):** Seluruh form pengiriman data POST diverifikasi dengan `csrf_token()` dan fungsi `require_valid_csrf()`.
- **Integritas Transaksi Atomik:** Transaksi checkout kasir dilindungi oleh mekanisme `beginTransaction()`, *pessimistic row locking* `FOR UPDATE`, `commit()`, dan `rollBack()`.
- **Pure Native & Tanpa Framework Luar:** Murni menggunakan HTML5 semantik, CSS3 modern, JavaScript Vanilla, PHP Native 8.2, dan MySQL tanpa ketergantungan framework berat, menjamin kecepatan rendering maksimal.
- **Isolasi Monorepo Terjaga:** 100% file kode dan aset berada di dalam direktori `kelompok/kelompok_10/` tanpa mengubah struktur root repository praktikum.
