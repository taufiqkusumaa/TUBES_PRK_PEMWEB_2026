-- ============================================================================
-- SKEMA DATABASE: BENGKEL-SERVIS (Sistem Manajemen Servis & Work Order)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS db_bengkel_servis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_bengkel_servis;

-- 1. Tabel Pelanggan / Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_telepon VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('pelanggan', 'mekanik', 'admin') DEFAULT 'pelanggan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Paket Layanan Servis
CREATE TABLE IF NOT EXISTS paket_layanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_paket VARCHAR(20) NOT NULL UNIQUE,
    nama_paket VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    estimasi_menit INT NOT NULL DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Transaksi Servis & Work Order
CREATE TABLE IF NOT EXISTS transaksi_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_wo VARCHAR(30) NOT NULL UNIQUE COMMENT 'Nomor Work Order unik, misal: WO-20261030-001',
    user_id INT NOT NULL,
    paket_id INT NOT NULL,
    pelat_nomor VARCHAR(20) NOT NULL,
    merk_tipe_kendaraan VARCHAR(100) NOT NULL,
    keluhan_mesin TEXT NOT NULL,
    foto_kendala VARCHAR(255) DEFAULT NULL COMMENT 'Nama file foto di folder public/uploads/',
    status_servis ENUM('Pending', 'Antre', 'Dikerjakan', 'Siap Diambil') DEFAULT 'Pending',
    catatan_mekanik TEXT DEFAULT NULL,
    tanggal_booking DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_paket FOREIGN KEY (paket_id) REFERENCES paket_layanan(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- DATA AWAL (SEEDER) UNTUK PENGUJIAN
-- ============================================================================

INSERT INTO users (id, nama_lengkap, email, no_telepon, password_hash, role) VALUES
(1, 'Budi Santoso', 'budi.santoso@email.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pelanggan'),
(2, 'Agus Mekanik Utama', 'agus.mekanik@bengkel.com', '081987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mekanik');

INSERT INTO paket_layanan (id, kode_paket, nama_paket, deskripsi, harga, estimasi_menit) VALUES
(1, 'PKT-TUNEUP', 'Tune Up Mesin & Injeksi Lengkap', 'Pembersihan throttle body, kalibrasi injektor, cek busi, filter udara, dan scan ECU.', 350000.00, 90),
(2, 'PKT-OLI', 'Ganti Oli Sintetis + Filter Oli', 'Penggantian oli mesin full synthetic, ganti filter oli baru, dan pengecekan 15 titik dasar.', 450000.00, 45),
(3, 'PKT-REM', 'Servis Sistem Pengereman 4 Roda', 'Pembersihan kaliper, kampas rem, kuras minyak rem DOT-4, dan penyetelan rem tangan.', 275000.00, 60),
(4, 'PKT-AC', 'Servis AC & Flushing Freon', 'Cek kebocoran evaporator, ganti oli kompresor, isi ulang freon R134a murni.', 320000.00, 75),
(5, 'PKT-OVERHAUL', 'Inspeksi Berat & Kaki-Kaki', 'Pengecekan menyeluruh tie rod, ball joint, shockbreaker, dan kebocoran paking mesin.', 500000.00, 120);

INSERT INTO transaksi_servis (kode_wo, user_id, paket_id, pelat_nomor, merk_tipe_kendaraan, keluhan_mesin, foto_kendala, status_servis, catatan_mekanik, tanggal_booking) VALUES
('WO-20261030-001', 1, 1, 'BE 1845 YX', 'Honda HR-V 1.5L E CVT 2021', 'Tarikan awal terasa berat dan RPM agak mengayun saat kondisi idle menyalakan AC.', 'sample_engine_bay.jpg', 'Dikerjakan', 'Sedang dilakukan pembersihan Throttle Body & Injektor di Stall 2 oleh Mekanik Agus.', '2026-10-30 08:30:00'),
('WO-20261029-089', 1, 3, 'BE 2910 AA', 'Toyota Avanza Veloz 2020', 'Terdengar bunyi berdecit saat mengerem di kecepatan rendah dan pedal terasa agak dalam.', NULL, 'Siap Diambil', 'Kampas rem depan sudah dibersihkan dan minyak rem sudah di-bleeding ulang. Kendaraan siap diambil di kasir.', '2026-10-29 13:15:00'),
('WO-20261030-004', 1, 2, 'BE 8821 ZP', 'Mitsubishi Xpander Ultimate', 'Sudah lewat jadwal ganti oli rutin 10.000 KM dan indikator pengingat servis menyala.', NULL, 'Antre', 'Menunggu antrean pit nomor 1 selesai (estimasi 15 menit lagi masuk stall).', '2026-10-30 10:00:00');