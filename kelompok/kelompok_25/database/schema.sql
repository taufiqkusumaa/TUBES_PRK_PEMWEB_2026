-- ============================================================
-- Ruang Hukum - Skema Database (Kelompok 25)
-- Cara pakai: buka phpMyAdmin > tab SQL > tempel semua isi file ini > Go
-- ============================================================
CREATE DATABASE IF NOT EXISTS ruang_hukum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ruang_hukum;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS login_gagal, social_accounts, password_resets, remember_tokens, ulasan, balasan, konsultasi, artikel, faq, profil_pengacara, bidang_hukum, users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Pengguna (klien, pengacara, admin)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(30) NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('klien','pengacara','admin') NOT NULL DEFAULT 'klien',
  perusahaan VARCHAR(120) NULL,
  telepon VARCHAR(20) NULL,
  foto VARCHAR(255) NULL,
  status ENUM('aktif','menunggu','ditolak','nonaktif') NOT NULL DEFAULT 'aktif',
  terms_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Bidang hukum (pidana, perdata, dst)
CREATE TABLE bidang_hukum (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL UNIQUE,
  deskripsi VARCHAR(255) NULL
) ENGINE=InnoDB;

-- 3. Profil tambahan khusus pengacara
CREATE TABLE profil_pengacara (
  user_id INT PRIMARY KEY,
  bidang_id INT NOT NULL,
  bio TEXT NULL,
  pengalaman_tahun TINYINT UNSIGNED NOT NULL DEFAULT 0,
  kota VARCHAR(60) NULL,
  no_izin VARCHAR(60) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (bidang_id) REFERENCES bidang_hukum(id)
) ENGINE=InnoDB;

-- 4. Konsultasi (pertanyaan dari klien)
CREATE TABLE konsultasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  klien_id INT NOT NULL,
  pengacara_id INT NULL,
  bidang_id INT NOT NULL,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  lampiran VARCHAR(255) NULL,
  status ENUM('menunggu','dijawab','selesai','ditutup') NOT NULL DEFAULT 'menunggu',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (klien_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (pengacara_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (bidang_id) REFERENCES bidang_hukum(id)
) ENGINE=InnoDB;

-- 5. Balasan (percakapan dalam satu konsultasi)
CREATE TABLE balasan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  konsultasi_id INT NOT NULL,
  pengirim_id INT NOT NULL,
  isi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (konsultasi_id) REFERENCES konsultasi(id) ON DELETE CASCADE,
  FOREIGN KEY (pengirim_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Ulasan dan rating (Bonus)
CREATE TABLE ulasan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  konsultasi_id INT NOT NULL UNIQUE,
  klien_id INT NOT NULL,
  pengacara_id INT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  komentar VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (konsultasi_id) REFERENCES konsultasi(id) ON DELETE CASCADE,
  FOREIGN KEY (klien_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (pengacara_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Artikel hukum
CREATE TABLE artikel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penulis_id INT NOT NULL,
  bidang_id INT NULL,
  judul VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  ringkasan VARCHAR(300) NULL,
  isi MEDIUMTEXT NOT NULL,
  gambar VARCHAR(255) NULL,
  status ENUM('draft','terbit') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (penulis_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (bidang_id) REFERENCES bidang_hukum(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. FAQ
CREATE TABLE faq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pertanyaan VARCHAR(255) NOT NULL,
  jawaban TEXT NOT NULL,
  urutan INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- 9. "Ingat akun saya": satu baris per perangkat. Yang disimpan hanya hash dari kode rahasia di cookie.
CREATE TABLE remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  selector CHAR(24) NOT NULL UNIQUE,
  validator_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  user_agent VARCHAR(200) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Lupa kata sandi: token sekali pakai (yang disimpan hanya hash-nya) dengan masa berlaku
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Akun media sosial (Google, LinkedIn, Facebook) yang tertaut ke pengguna
CREATE TABLE social_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  provider_uid VARCHAR(191) NOT NULL,
  email VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_uid (provider, provider_uid),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Catatan percobaan masuk yang gagal (pembatas percobaan agar kata sandi tidak ditebak terus-menerus)
CREATE TABLE login_gagal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identitas VARCHAR(120) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_gagal_identitas (identitas, created_at),
  INDEX idx_gagal_ip (ip, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL
-- Semua akun contoh memakai password: Admin123!
-- ============================================================
INSERT INTO bidang_hukum (nama, deskripsi) VALUES
('Hukum Pidana', 'Kasus kejahatan, laporan polisi, pembelaan di pengadilan'),
('Hukum Perdata', 'Sengketa utang piutang, kontrak, dan perjanjian'),
('Hukum Keluarga', 'Perceraian, hak asuh anak, harta bersama, waris'),
('Hukum Ketenagakerjaan', 'PHK, pesangon, kontrak kerja, hak karyawan'),
('Hukum Bisnis', 'Pendirian usaha, kontrak bisnis, kekayaan intelektual'),
('Hukum Properti', 'Jual beli tanah, sertifikat, sengketa properti');

INSERT INTO users (nama, username, email, password, role, telepon, status) VALUES
('Administrator', 'admin', 'admin@ruanghukum.test', '$2y$10$pKDP0IoBdLyxLKJqDGKavOIcbxWkYQYYGaLpeFS2GJL2lM..jS6Na', 'admin', '081200000001', 'aktif'),
('Budi Klien', 'budi', 'klien@ruanghukum.test', '$2y$10$pKDP0IoBdLyxLKJqDGKavOIcbxWkYQYYGaLpeFS2GJL2lM..jS6Na', 'klien', '081200000002', 'aktif'),
('Sari Wulandari, S.H.', 'sari', 'pengacara@ruanghukum.test', '$2y$10$pKDP0IoBdLyxLKJqDGKavOIcbxWkYQYYGaLpeFS2GJL2lM..jS6Na', 'pengacara', '081200000003', 'aktif');

INSERT INTO profil_pengacara (user_id, bidang_id, bio, pengalaman_tahun, kota, no_izin) VALUES
(3, 3, 'Berpengalaman menangani perkara keluarga dan waris.', 8, 'Bandar Lampung', 'ADV-0001');

INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES
('Apakah konsultasi di Ruang Hukum gratis?', 'Contoh jawaban. Linda mengganti dengan isi FAQ yang asli.', 1);
