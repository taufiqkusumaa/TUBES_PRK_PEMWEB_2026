# BENGKEL-SERVIS — Sistem Manajemen Servis Kendaraan & Work Order

Repositori ini berisi kode sumber Tugas Besar Praktikum Pemrograman Web 2026 **Kelompok 13** (Program Studi Teknik Informatika, Universitas Lampung). Aplikasi **Bengkel-Servis** mendigitalisasi alur operasional bengkel mulai dari pengelolaan master kendaraan dan paket layanan, pemesanan antrean servis oleh pelanggan, pelacakan progres pengerjaan (*stepper tracking*), pengelolaan *work order* teknisi dan pencatatan suku cadang, hingga pencetakan nota pengambilan serta kuitansi pembayaran.

---

## 1. Spesifikasi Teknis & Arsitektur

- **Pola Arsitektur**: PHP Native Model-View-Controller (MVC)
- **Akses Basis Data**: MySQL dengan PHP Data Objects (PDO) *Prepared Statements*
- **Komunikasi Asinkron**: REST API (`public/api.php`) & JavaScript Fetch API (`async/await`)
- **Antarmuka Pengguna**: HTML5 Semantik, Bootstrap 5.3, Custom CSS (`@media print` untuk pencetakan dokumen)

---

## 2. Daftar Anggota Tim & Pembagian Modul (Kelompok 13)

| No | Nama Anggota | NPM | Branch Fitur | Tanggung Jawab Modul |
| :- | :--- | :--- | :--- | :--- |
| 1 | **Taufiq Kusumawibowo** | *(Isi NPM)* | `feat/kelompok-13-transaksi` | **Ketua Tim / Modul Pelanggan & Tracking**: Konfigurasi PDO, skema DB, router utama, pemesanan servis, unggah foto kendala, riwayat servis, dan *stepper tracking*. |
| 2 | **Fareluna Nazwa Hafiez** | *(Isi NPM)* | `feat/kelompok-13-auth-pelanggan` | **Modul Autentikasi & Session**: Login, registrasi, logout, *role-based access control* (Admin, Teknisi, Customer), profil, serta aktivasi akun teknisi. |
| 3 | **Raffi Ichwan Pramudya** | *(Isi NPM)* | `feat/kelompok-13-master-data` | **Modul Master Data**: CRUD kategori kendaraan (Roda 2/Roda 4, merk, tipe) serta CRUD paket layanan servis beserta estimasi tarif dan durasi. |
| 4 | **Triandika** | 2415061064 | `feat/kelompok-13-teknisi-work-order` | **Modul Teknisi & Work Order**: Daftar antrean kerja teknisi, pembaruan status pengerjaan unit, unggah dokumentasi komponen mesin, dan catatan suku cadang. |
| 5 | **Ezzra Rei Setiawan** | 2415061096 | `feat/kelompok-13-ui-dashboard` | **Modul UI/UX Global, Dashboard & Cetak Dokumen**: Layout global Bootstrap 5 (`header`, `sidebar`, `footer`), dashboard metrik operasional admin, serta fitur cetak Nota Pengambilan & Kuitansi Servis. |

---

## 3. Struktur Direktori

```text
kelompok/kelompok_13/
├── app/
│   ├── Config/
│   │   └── Database.php            # Konfigurasi koneksi PDO MySQL & helper response
│   ├── Controllers/                # Handler logika bisnis & endpoint JSON API
│   │   ├── KendaraanController.php
│   │   ├── LayananController.php
│   │   ├── MekanikController.php
│   │   ├── PelangganController.php
│   │   ├── SparepartController.php
│   │   └── TransaksiController.php
│   ├── Models/                     # Query SQL murni berbasis PDO Prepared Statements
│   │   ├── KendaraanModel.php
│   │   ├── LayananModel.php
│   │   ├── MekanikModel.php
│   │   ├── PelangganModel.php
│   │   ├── SparepartModel.php
│   │   └── TransaksiModel.php
│   └── Views/                      # Template antarmuka pengguna
│       ├── layouts/
│       │   ├── header.php
│       │   ├── sidebar.php
│       │   └── footer.php
│       ├── dashboard.php
│       ├── kendaraan.php
│       ├── layanan.php
│       ├── mekanik.php
│       ├── pelanggan.php
│       ├── sparepart.php
│       └── transaksi.php
├── database/
│   └── schema.sql                  # DDL tabel & seeder data awal
├── public/                         # Document root aplikasi
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/                     # Script Fetch API per modul
│   ├── uploads/                    # Penyimpanan foto kendala & dokumentasi komponen
│   ├── api.php                     # API Gateway (JSON)
│   └── index.php                   # Front Controller (Router halaman)
└── README.md