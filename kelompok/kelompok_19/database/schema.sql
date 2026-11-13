-- Active: 1776135014811@@localhost@3306@kebunku
CREATE DATABASE IF NOT EXISTS kebunku;
USE kebunku;

-- tabel lahan
CREATE TABLE lahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    luas DECIMAL(10,2) NOT NULL COMMENT 'Luas dalam hektar/meter',
    jenis_tanah ENUM('Aluvial', 'Andosol', 'Latosol', 'Grumosol', 'Regosol', 'Lainnya') DEFAULT 'Lainnya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- tabel siklus           
CREATE TABLE siklus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lahan_id INT NOT NULL,
    nama_tanaman VARCHAR(100) NOT NULL,
    tanggal_tanam DATE NOT NULL,
    tanggal_panen_estimasi DATE,
    status ENUM('Aktif', 'Panen', 'Selesai', 'Gagal') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lahan_id) REFERENCES lahan(id) ON DELETE RESTRICT
);
-- tabel inventaris
CREATE TABLE inventaris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kategori ENUM('Bibit', 'Pupuk', 'Pestisida', 'Alat') NOT NULL,
    stok DECIMAL(10,2) DEFAULT 0,
    satuan VARCHAR(20) NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transaksi_inventaris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaris_id INT NOT NULL,
    siklus_id INT NULL COMMENT 'NULL jika Kulakan/Barang Masuk',
    jenis ENUM('Masuk', 'Keluar') NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    tanggal DATE NOT NULL,
    FOREIGN KEY (inventaris_id) REFERENCES inventaris(id) ON DELETE CASCADE,
    FOREIGN KEY (siklus_id) REFERENCES siklus(id) ON DELETE SET NULL
);
-- tabel pekerja
CREATE TABLE pekerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    jabatan VARCHAR(50),
    gaji_pokok DECIMAL(15,2) NOT NULL,
    tanggal_bergabung DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE presensi_pekerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pekerja_id INT NOT NULL,
    siklus_id INT NOT NULL,
    tanggal DATE NOT NULL,
    upah_harian DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (pekerja_id) REFERENCES pekerja(id) ON DELETE CASCADE,
    FOREIGN KEY (siklus_id) REFERENCES siklus(id) ON DELETE CASCADE
);
-- tabel panen
CREATE TABLE panen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siklus_id INT NOT NULL,
    tanggal_panen DATE NOT NULL,
    jumlah_kg DECIMAL(10,2) NOT NULL,
    kualitas ENUM('A', 'B', 'C', 'Reject') DEFAULT 'A',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siklus_id) REFERENCES siklus(id) ON DELETE CASCADE
);
-- seeder data awal
INSERT INTO lahan (nama, lokasi, luas, jenis_tanah) VALUES
('Blok A Utara', 'Sektor 1', 1.50, 'Aluvial'),
('Blok B Selatan', 'Sektor 2', 2.00, 'Andosol');

INSERT INTO siklus (lahan_id, nama_tanaman, tanggal_tanam, tanggal_panen_estimasi, status) VALUES
(1, 'Tomat Cherry', '2026-10-01', '2026-12-15', 'Aktif'),
(2, 'Cabai Rawit', '2026-09-15', '2026-12-01', 'Aktif');

INSERT INTO inventaris (nama, kategori, stok, satuan, harga_satuan) VALUES
('Bibit Cabai', 'Bibit', 100.00, 'Sachet', 75000.00),
('Pupuk NPK', 'Pupuk', 150.50, 'Kg', 18000.00);

INSERT INTO transaksi_inventaris (inventaris_id, siklus_id, jenis, jumlah, tanggal) VALUES
(1, 1, 'Keluar', 10.00, '2026-10-01'), 
(2, NULL, 'Masuk', 50.00, '2026-09-25');

INSERT INTO pekerja (nama, no_telepon, jabatan, gaji_pokok, tanggal_bergabung) VALUES
('Pak Udin', '081234567890', 'Kepala Kebun', 3500000.00, '2025-01-10');

INSERT INTO presensi_pekerja (pekerja_id, siklus_id, tanggal, upah_harian) VALUES
(1, 1, '2026-10-01', 120000.00);

INSERT INTO panen (siklus_id, tanggal_panen, jumlah_kg, kualitas, catatan) VALUES
(2, '2026-11-01', 45.50, 'A', 'Panen cabai awal');

