-- =============================================================================
-- TUGAS BESAR PRAKTIKUM PEMROGRAMAN WEB 2026 - KELOMPOK 13
-- SISTEM MANAJEMEN SERVIS BENGKEL & WORK ORDER (MACHINIFY)
-- File: kelompok/kelompok_13/database/schema.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS bengkel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bengkel_db;

-- Nonaktifkan pengecekan foreign key sementara agar DROP TABLE berjalan lancar
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS work_order_komponen;
DROP TABLE IF EXISTS transaksi_servis;
DROP TABLE IF EXISTS kendaraan;
DROP TABLE IF EXISTS layanan_servis;
DROP TABLE IF EXISTS kategori_kendaraan;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. TABEL PENGGUNA & AUTENTIKASI (PIC: Fareluna Nazwa Hafeiz)
-- =============================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_telp VARCHAR(20) DEFAULT NULL,
    role ENUM('admin', 'teknisi', 'customer') NOT NULL DEFAULT 'customer',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif, 0 = Nonaktif/Ditangguhkan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- 2. TABEL MASTER KATEGORI & LAYANAN SERVIS (PIC: Raffi Ichwan Pramudya)
-- =============================================================================
CREATE TABLE kategori_kendaraan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis ENUM('Roda 2', 'Roda 4') NOT NULL,
    merk VARCHAR(50) NOT NULL COMMENT 'Honda, Yamaha, Toyota, Suzuki, dll',
    tipe VARCHAR(50) NOT NULL COMMENT 'Matic, Manual, Sport, Sedan, SUV, MPV',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE layanan_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    nama_paket VARCHAR(100) NOT NULL COMMENT 'Tune Up Injeksi, Ganti Oli Mesin, Turun Mesin / Overhaul, Skir Klep, Kalibrasi Throttle Body',
    deskripsi TEXT DEFAULT NULL,
    tarif_dasar DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estimasi_durasi_menit INT NOT NULL DEFAULT 60 COMMENT 'Estimasi waktu pengerjaan dalam menit',
    status_layanan ENUM('Tersedia', 'Tidak Tersedia') NOT NULL DEFAULT 'Tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_layanan_kategori FOREIGN KEY (kategori_id) REFERENCES kategori_kendaraan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- 3. TABEL KENDARAAN & PEMESANAN SERVIS (PIC: Taufiq Kusumawibowo)
-- =============================================================================
CREATE TABLE kendaraan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Pemilik kendaraan (Customer)',
    kategori_id INT NOT NULL,
    no_polisi VARCHAR(15) NOT NULL UNIQUE COMMENT 'Nomor Pelat Kendaraan (misal: BE 1234 ABC)',
    nama_model VARCHAR(100) NOT NULL COMMENT 'Vario 160, NMAX, Avanza 1.5, Innova, dll',
    tahun_pembuatan YEAR DEFAULT NULL,
    warna VARCHAR(30) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kendaraan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_kendaraan_kategori FOREIGN KEY (kategori_id) REFERENCES kategori_kendaraan(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transaksi_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) NOT NULL UNIQUE COMMENT 'Format: SRV-YYYYMMDD-XXXX',
    user_id INT NOT NULL COMMENT 'Customer pemesan',
    kendaraan_id INT NOT NULL,
    layanan_id INT NOT NULL,
    teknisi_id INT DEFAULT NULL COMMENT 'Mekanik yang ditugaskan menangani',
    keluhan_mesin TEXT NOT NULL,
    foto_kendala VARCHAR(255) DEFAULT NULL COMMENT 'Path file upload di public/uploads/',
    status_servis ENUM('Pending', 'Antre', 'Dikerjakan', 'Siap Diambil', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Pending',
    total_biaya DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    catatan_admin TEXT DEFAULT NULL,
    tanggal_pesan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_selesai DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_kendaraan FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_layanan FOREIGN KEY (layanan_id) REFERENCES layanan_servis(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_teknisi FOREIGN KEY (teknisi_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- 4. TABEL WORK ORDER & SUKU CADANG TEKNISI (PIC: Triandika)
-- =============================================================================
CREATE TABLE work_order_komponen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    teknisi_id INT NOT NULL,
    nama_suku_cadang VARCHAR(100) NOT NULL COMMENT 'Busi Iridium, Filter Udara, Kampas Rem, Piston Kit, dll',
    jumlah INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    foto_komponen VARCHAR(255) DEFAULT NULL COMMENT 'Path upload foto fisik part/mesin yang diperbaiki',
    catatan_perbaikan TEXT DEFAULT NULL COMMENT 'Keterangan teknis kerusakan/penggantian komponen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wo_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi_servis(id) ON DELETE CASCADE,
    CONSTRAINT fk_wo_teknisi FOREIGN KEY (teknisi_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================================
-- 5. SEEDER DATA AWAL (DML)
-- =============================================================================

-- 5.1 Akun Demo (Password bawaan: password123)
-- Hash password: $2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y
INSERT INTO users (id, nama, email, password, no_telp, role, status_aktif) VALUES
(1, 'Super Administrator Bengkel', 'admin@bengkel.com', '$2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y', '08117200001', 'admin', 1),
(2, 'Agus Santoso (Teknisi Mesin 1)', 'agus.teknisi@bengkel.com', '$2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y', '08127300002', 'teknisi', 1),
(3, 'Bambang Irawan (Teknisi Mesin 2)', 'bambang.teknisi@bengkel.com', '$2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y', '08127300003', 'teknisi', 1),
(4, 'Fareluna Nazwa Hafeiz (Customer)', 'fareluna@customer.com', '$2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y', '08527900004', 'customer', 1),
(5, 'Taufiq Kusumawibowo (Customer)', 'taufiq@customer.com', '$2y$10$OjbCeeKJifF5.LeVnFh1NeOZ6ZMxktFlkd.RUR/aCzTPTGEs16l/y', '08527900005', 'customer', 1);

-- 5.2 Kategori Kendaraan
INSERT INTO kategori_kendaraan (id, jenis, merk, tipe) VALUES
(1, 'Roda 2', 'Honda', 'Matic'),
(2, 'Roda 2', 'Yamaha', 'Sport'),
(3, 'Roda 4', 'Toyota', 'MPV'),
(4, 'Roda 4', 'Daihatsu', 'City Car'),
(5, 'Roda 4', 'Suzuki', 'SUV'),
(6, 'Roda 2', 'Yamaha', 'Matic');

-- 5.3 Paket Servis Mesin & Referensi Tarif/Durasi
INSERT INTO layanan_servis (id, kategori_id, nama_paket, deskripsi, tarif_dasar, estimasi_durasi_menit, status_layanan) VALUES
(1, 1, 'Tune Up Injeksi Motor', 'Pembersihan injektor, throttle body, filter udara, dan scan ECU', 85000.00, 45, 'Tersedia'),
(2, 1, 'Ganti Oli Mesin & Gardan', 'Penggantian oli mesin full synthetic + oli gardan matic', 75000.00, 20, 'Tersedia'),
(3, 1, 'Kalibrasi Throttle Body & Reset TP', 'Setting bukaan katup gas dan setting ulang parameter sensor ECU', 50000.00, 30, 'Tersedia'),
(4, 2, 'Skir Klep & Penyetelan Celah Katup', 'Mengikis kerak karbon di payung klep dan menyetel ulang celah shim klep', 175000.00, 120, 'Tersedia'),
(5, 3, 'Tune Up Mesin Bensin Mobil', 'Pembersihan ruang bakar (carbon clean), busi, throttle body, dan intake', 350000.00, 90, 'Tersedia'),
(6, 3, 'Turun Mesin / Overhaul Total', 'Pembongkaran total blok silinder, ganti ring seher, paking set, dan metal jalan', 2500000.00, 480, 'Tersedia'),
(7, 6, 'Ganti Oli Mesin & Gardan', 'Penggantian oli mesin dan oli gardan untuk motor matic Yamaha', 75000.00, 20, 'Tersedia'),
(8, 6, 'Tune Up Injeksi Motor', 'Pembersihan injektor, throttle body, filter udara, dan pemeriksaan sistem injeksi', 85000.00, 45, 'Tersedia'),
(9, 6, 'Kalibrasi Throttle Body & Reset TP', 'Penyetelan throttle body dan reset parameter sensor ECU', 50000.00, 30, 'Tersedia'),
(10, 6, 'Skir Klep & Penyetelan Celah Katup', 'Pembersihan dudukan klep dan penyetelan ulang celah katup', 175000.00, 120, 'Tersedia');

-- 5.4 Master Kendaraan Pelanggan
INSERT INTO kendaraan (id, user_id, kategori_id, no_polisi, nama_model, tahun_pembuatan, warna) VALUES
(1, 4, 1, 'BE 2026 NA', 'Honda Vario 160 CBS', 2023, 'Matte Black'),
(2, 4, 3, 'BE 1313 FH', 'Toyota Avanza 1.5 G', 2021, 'Silver Metallic'),
(3, 5, 2, 'BE 4444 TK', 'Yamaha R15 V4', 2022, 'Racing Blue');

-- 5.5 Transaksi Pemesanan Servis (Dummy untuk Menguji Stepper & Dashboard)
INSERT INTO transaksi_servis (id, kode_transaksi, user_id, kendaraan_id, layanan_id, teknisi_id, keluhan_mesin, foto_kendala, status_servis, total_biaya, catatan_admin, tanggal_pesan, tanggal_selesai) VALUES
(1, 'SRV-20261030-0001', 4, 1, 1, 2, 'Tarikan awal terasa brebet dan gas sering tersendat di putaran bawah.', 'uploads/kendala_vario.jpg', 'Dikerjakan', 85000.00, 'Ditugaskan ke Teknisi Agus', '2026-10-30 08:30:00', NULL),
(2, 'SRV-20261030-0002', 4, 2, 5, 3, 'Mesin pincang saat tanjakan dan konsumsi bensin terasa sangat boros.', 'uploads/kendala_avanza.jpg', 'Antre', 350000.00, 'Menunggu antrean stall mobil 2', '2026-10-30 09:15:00', NULL),
(3, 'SRV-20261029-0003', 5, 3, 4, 2, 'Suara mesin kasar menggelitik (knocking) di bagian cylinder head.', 'uploads/kendala_r15.jpg', 'Siap Diambil', 325000.00, 'Pengerjaan skir klep dan ganti busi selesai.', '2026-10-29 10:00:00', '2026-10-29 14:30:00'),
(4, 'SRV-20261028-0004', 5, 3, 2, 2, 'Ganti oli berkala 3000 km.', NULL, 'Selesai', 75000.00, 'Pembayaran lunas via kasir.', '2026-10-28 13:00:00', '2026-10-28 13:30:00');

-- 5.6 Catatan Work Order Komponen & Suku Cadang (Teknisi)
INSERT INTO work_order_komponen (id, transaksi_id, teknisi_id, nama_suku_cadang, jumlah, harga_satuan, foto_komponen, catatan_perbaikan) VALUES
(1, 1, 2, 'Busi NGK Laser Iridium CPR9EAIX-9', 1, 120000.00, 'uploads/part_busi.jpg', 'Busi lama sudah gosong dan elektroda aus sehingga menimbulkan gejala brebet.'),
(2, 3, 2, 'Gasket / Paking Cylinder Head R15', 1, 75000.00, 'uploads/part_paking.jpg', 'Penggantian paking head baru setelah proses skir klep selesai.'),
(3, 3, 2, 'Shim Klep Original Yamaha (2 pcs)', 2, 37500.00, 'uploads/part_shim.jpg', 'Penyesuaian celah katup intake dan exhaust sesuai standar pabrikan.');