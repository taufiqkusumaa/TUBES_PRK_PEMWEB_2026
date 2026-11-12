# Machinify

**Machinify** adalah aplikasi web untuk mengelola operasional bengkel: data kendaraan dan paket servis, pemesanan pelanggan, progres work order, pencatatan suku cadang, serta ringkasan dan dokumen transaksi.

Proyek ini dibuat sebagai Tugas Besar Praktikum Pemrograman Web 2026 oleh **Kelompok 13**, Program Studi Teknik Informatika, Universitas Lampung.

## Fitur

- Login, registrasi, profil, dan hak akses berdasarkan peran.
- Admin mengelola kategori kendaraan, kendaraan pelanggan, paket servis, pengguna, dan transaksi.
- Customer mendaftarkan kendaraan, membuat booking, dan memantau status servis.
- Teknisi melihat work order yang menjadi tugasnya, memperbarui progres, serta mencatat komponen dan suku cadang.
- Perhitungan total biaya dari tarif paket dan komponen yang dicatat.
- Unggah foto kendala dan dokumentasi komponen.
- Dashboard operasional serta fitur cetak dokumen transaksi.

## Teknologi

- PHP Native dengan pola MVC.
- MySQL melalui PDO dan prepared statements.
- HTML, CSS, Bootstrap 5.3, dan JavaScript Fetch API.
- Bootstrap dan ikon dimuat dari CDN; koneksi internet diperlukan agar seluruh aset eksternal tampil.

## Persyaratan

- PHP 8.1 atau lebih baru, dengan ekstensi `PDO`, `pdo_mysql`, `fileinfo`, dan `mbstring`.
- MySQL 8.0 atau lebih baru.
- Apache (misalnya melalui Laragon) atau server PHP bawaan.
- Browser modern.

## Menjalankan Secara Lokal

### 1. Siapkan proyek

Letakkan atau clone repositori di folder web server. Pada Laragon, contoh lokasinya:

```text
C:\laragon\www\TUBES_PRK_PEMWEB_2026\kelompok\kelompok_13
```

Jalankan layanan Apache dan MySQL melalui Laragon.

### 2. Buat database

Untuk instalasi baru, impor file [`database/schema.sql`](database/schema.sql) melalui phpMyAdmin atau MySQL CLI. File tersebut membuat database `bengkel_db`, tabel, dan data demo.

**Peringatan:** `schema.sql` menghapus tabel yang sudah ada sebelum membuat ulang tabel dan mengisi data awal. Gunakan hanya untuk database baru atau jika memang ingin mereset seluruh data. Jangan impor ulang ke database yang berisi data penting.

### 3. Periksa konfigurasi koneksi

Pengaturan koneksi berada di [`app/Config/Database.php`](app/Config/Database.php). Nilai bawaan untuk Laragon adalah:

| Pengaturan | Nilai bawaan |
| --- | --- |
| Host | `127.0.0.1` |
| Database | `bengkel_db` |
| Username | `root` |
| Password | kosong |

Sesuaikan nilai tersebut jika konfigurasi MySQL lokal berbeda.

### 4. Jalankan aplikasi

**Dengan Laragon/Apache:** atur document root virtual host ke folder `public`. Jika memakai URL berbasis subfolder dari instalasi Laragon, contoh URL-nya:

```text
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_13/public/
```

**Dengan server PHP bawaan:** jalankan perintah berikut dari folder `kelompok_13`:

```powershell
php -S 127.0.0.1:8000 -t public
```

Kemudian buka <http://127.0.0.1:8000/>.

Pastikan folder `public/uploads` dapat ditulisi oleh PHP. Batas unggahan di aplikasi adalah 5 MB untuk foto kendala dan 2 MB untuk foto komponen.

## Akun Demo

Semua akun demo berikut menggunakan kata sandi `password123`.

| Peran | Email |
| --- | --- |
| Admin | `admin@bengkel.com` |
| Teknisi | `agus.teknisi@bengkel.com` |
| Teknisi | `bambang.teknisi@bengkel.com` |
| Customer | `fareluna@customer.com` |
| Customer | `taufiq@customer.com` |

Akun tersebut hanya untuk demonstrasi lokal. Jangan gunakan kata sandi bawaan untuk penerapan publik.

Pendaftaran sebagai teknisi menghasilkan akun nonaktif sampai admin mengaktifkannya melalui menu **Manajemen Pengguna**. Customer dapat langsung masuk setelah registrasi.

## Alur Penggunaan Singkat

1. Admin menambahkan kategori kendaraan dan paket servis melalui menu **Master Kendaraan** dan **Paket Layanan Servis**.
2. Customer mendaftarkan kendaraan, memilih paket yang sesuai, lalu mengirim booking beserta keluhan.
3. Admin memeriksa transaksi dan menugaskan teknisi.
4. Teknisi memperbarui progres work order dan mencatat komponen saat servis sedang dikerjakan.
5. Customer memantau status melalui menu **Pemesanan & Tracking**. Admin dapat memeriksa transaksi dan mencetak dokumen yang tersedia.

Paket servis yang ditampilkan saat booking harus tersedia dan terhubung ke kategori kendaraan yang dipilih. Jika kategori baru belum memiliki paket servis, admin perlu menambahkannya terlebih dahulu.

## Cara Pakai Tiap Halaman

Menu yang tersedia menyesuaikan peran akun. Admin mengelola data dan transaksi, customer membuat booking serta memantau servisnya, sedangkan teknisi menangani work order dan mencatat komponen.

### Login dan Registrasi

- Buka aplikasi dan masuk menggunakan email serta kata sandi. Pengguna yang belum memiliki akun dapat memilih tautan **Daftar akun servis**.
- Saat registrasi, isi nama, email, nomor telepon (opsional), kata sandi, dan pilih tipe akun **Customer** atau **Teknisi**.
- Akun teknisi baru perlu diaktifkan oleh admin sebelum dapat menggunakan fitur operasional. Customer dapat langsung masuk setelah registrasi.
- Gunakan tombol mode terang/gelap di halaman autentikasi untuk mengganti tema.

### Dashboard & Cetak (Admin)

- Dashboard menampilkan jumlah antrean, unit yang sedang dikerjakan, servis yang siap diambil atau selesai, dan total nilai transaksi.
- Tabel work order dapat disaring berdasarkan status dan dicari menggunakan nomor order, nomor polisi, atau nama pemilik.
- Gunakan tombol **Muat Ulang** untuk mengambil data terbaru atau **Order Servis Baru** untuk membuka halaman pemesanan.
- Pada baris transaksi, buka pratinjau dokumen yang tersedia lalu pilih **Cetak Dokumen (A4)** untuk mencetaknya.

### Pemesanan & Tracking (Customer dan Admin)

- Customer memilih **Booking Servis Baru**, lalu memilih kendaraan dan paket layanan yang sesuai. Jika belum memiliki kendaraan, daftarkan kendaraan terlebih dahulu dari tautan pada form booking.
- Isi keluhan kendaraan dan unggah foto kendala bila diperlukan, kemudian kirim booking.
- Tabel menampilkan booking dan statusnya. Gunakan filter status atau kolom pencarian untuk menemukan order, lalu pilih **Tracking & Status** untuk melihat perkembangan dan rincian biaya.
- Admin menggunakan halaman ini untuk memeriksa booking, menetapkan teknisi, memperbarui status pengerjaan, menambahkan catatan pemeriksaan, atau menghapus order yang perlu dibatalkan.

### Master Kendaraan (Admin)

- Buat kategori kendaraan dengan mengisi jenis roda, merk, dan tipe. Kategori ini digunakan untuk menghubungkan kendaraan dan paket layanan.
- Tambahkan kendaraan dengan memilih pemilik dan kategori, lalu isi nomor polisi serta model. Tahun dan warna dapat diisi bila tersedia.
- Daftar kategori dan kendaraan menyediakan aksi untuk mengubah atau menghapus data. Periksa relasi yang digunakan transaksi sebelum menghapus data.

### Paket Layanan Servis (Admin)

- Tambahkan paket dengan memilih kategori kendaraan, mengisi nama paket, tarif dasar, dan estimasi durasi dalam menit.
- Gunakan daftar paket untuk memeriksa, mengubah, atau menghapus layanan. Paket yang tersedia akan menjadi pilihan saat customer membuat booking.

### Work Order Teknisi

- Daftar work order menampilkan antrean dan progres servis. Pilih filter status atau cari berdasarkan kode order, nomor polisi, atau nama.
- Buka detail work order untuk melihat informasi kendaraan dan keluhan. Perbarui status ke tahap berikutnya melalui form status, lalu simpan.
- Dari detail order, pilih **Catat Suku Cadang** untuk membuka pencatatan komponen pada order tersebut.

### Dokumentasi & Suku Cadang

- Pilih work order yang berstatus **Dikerjakan** untuk melihat tarif paket, daftar komponen, dan total biaya servis.
- Tambahkan atau ubah nama suku cadang, jumlah, harga satuan, catatan teknis, dan foto komponen bila diperlukan. Format foto yang diterima JPG, PNG, atau WebP dengan ukuran maksimal 2 MB.
- Hapus komponen yang tidak diperlukan; subtotal dan total biaya servis akan dihitung ulang. Pencatatan komponen hanya dapat diubah saat work order sedang dikerjakan.

### Profil dan Manajemen Pengguna

- Customer dan teknisi membuka menu profil untuk mengubah nama, nomor telepon, atau kata sandi. Email akun bersifat tetap; biarkan kolom kata sandi kosong jika tidak ingin menggantinya.
- Admin membuka **Manajemen Pengguna** untuk melihat akun terdaftar, mencari berdasarkan nama/email, menyaring berdasarkan peran, dan mengelola status akun teknisi.

## Database dan Migrasi

- [`database/schema.sql`](database/schema.sql) adalah skema beserta data awal untuk instalasi baru. Data Yamaha Matic dan paket-paketnya sudah termasuk.
- [`database/migrate_yamaha_matic.sql`](database/migrate_yamaha_matic.sql) hanya diperlukan untuk database lama yang belum memiliki kategori Yamaha Matic beserta paket servisnya. Jalankan setelah membuat cadangan database. Tidak perlu menjalankannya setelah impor `schema.sql` saat ini.

## Struktur Proyek

```text
kelompok_13/
├── app/
│   ├── Config/       # Konfigurasi database
│   ├── Controllers/  # Logika halaman dan endpoint
│   ├── Models/       # Query database
│   └── Views/       # Tampilan aplikasi
├── database/         # Skema dan file migrasi SQL
├── public/
│   ├── assets/       # CSS dan JavaScript
│   ├── uploads/      # Foto yang diunggah
│   ├── api.php       # Gerbang API
│   └── index.php     # Front controller dan router
└── README.md
```

## Pemeriksaan Manual

- Login dengan akun demo sesuai peran dan pastikan menu yang tampil sesuai.
- Admin menambah kategori kendaraan, paket servis, dan data kendaraan.
- Customer mendaftarkan kendaraan, membuat booking, dan melihat tracking.
- Admin menugaskan teknisi; teknisi memperbarui progres dan mencatat komponen.
- Periksa total biaya dan coba fitur cetak dari halaman yang menyediakannya.
- Uji unggah foto dengan format JPG, PNG, atau WebP dan ukuran yang diizinkan.

## Pembagian Tugas Kelompok

| Anggota | Branch fitur | Tanggung jawab |
| --- | --- | --- |
| Taufiq Kusumawibowo | `feat/kelompok-13-transaksi` | Ketua tim, konfigurasi dan skema database, pemesanan servis, unggah foto kendala, riwayat, dan tracking. |
| Fareluna Nazwa Hafeiz | `feat/kelompok-13-auth-pelanggan` | Autentikasi, sesi, hak akses, profil, dan manajemen akun. |
| Raffi Ichwan Pramudya | `feat/kelompok-13-master-data` | CRUD kategori kendaraan dan paket layanan servis. |
| Triandika | `feat/kelompok-13-teknisi-wo` | Work order teknisi, progres pengerjaan, dokumentasi komponen, dan suku cadang. |
| Ezzra Rei Setiawan (`2415061096`) | `feat/kelompok-13-ui-dashboard` | UI/UX global, dashboard, dan dokumen cetak. |

