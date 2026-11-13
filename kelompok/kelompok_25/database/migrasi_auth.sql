-- ============================================================
-- Ruang Hukum - Migrasi fitur Masuk & Daftar (Kelompok 25)
-- Untuk database yang SUDAH berisi data. Data lama TIDAK dihapus.
-- Cara pakai: phpMyAdmin > klik database ruang_hukum > tab SQL > tempel semua isi file ini > Go
-- Aman dijalankan berulang kali. Butuh MariaDB (bawaan XAMPP dan Laragon).
-- Database baru? Cukup jalankan schema.sql yang sudah memuat semua ini.
-- ============================================================
USE ruang_hukum;

-- 1. Kolom baru di tabel users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS username VARCHAR(30) NULL AFTER nama,
  ADD COLUMN IF NOT EXISTS perusahaan VARCHAR(120) NULL AFTER role,
  ADD COLUMN IF NOT EXISTS terms_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL;

-- 2. Username untuk akun yang sudah ada
UPDATE users SET username = 'admin' WHERE email = 'admin@ruanghukum.test'     AND username IS NULL;
UPDATE users SET username = 'budi'  WHERE email = 'klien@ruanghukum.test'     AND username IS NULL;
UPDATE users SET username = 'sari'  WHERE email = 'pengacara@ruanghukum.test' AND username IS NULL;
UPDATE users SET username = LOWER(REPLACE(REPLACE(SUBSTRING_INDEX(email, '@', 1), '-', '_'), ' ', '_')) WHERE username IS NULL;
UPDATE users SET username = CONCAT(username, id) WHERE CHAR_LENGTH(username) < 4;
-- Bila ada dua akun yang username-nya kembar, yang lebih baru diberi angka id di belakangnya
UPDATE users u
  JOIN (SELECT username, MIN(id) AS id_awal FROM users GROUP BY username HAVING COUNT(*) > 1) d
    ON d.username = u.username AND u.id <> d.id_awal
  SET u.username = CONCAT(u.username, u.id);

-- 3. Nomor HP contoh untuk tiga akun bawaan (hanya bila masih kosong)
UPDATE users SET telepon = '081200000001' WHERE email = 'admin@ruanghukum.test'     AND telepon IS NULL;
UPDATE users SET telepon = '081200000002' WHERE email = 'klien@ruanghukum.test'     AND telepon IS NULL;
UPDATE users SET telepon = '081200000003' WHERE email = 'pengacara@ruanghukum.test' AND telepon IS NULL;

-- 4. Username harus unik
ALTER TABLE users ADD UNIQUE INDEX IF NOT EXISTS uq_users_username (username);

-- 5. Tabel baru
CREATE TABLE IF NOT EXISTS remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  selector CHAR(24) NOT NULL UNIQUE,
  validator_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  user_agent VARCHAR(200) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS social_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  provider_uid VARCHAR(191) NOT NULL,
  email VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_uid (provider, provider_uid),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_gagal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identitas VARCHAR(120) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_gagal_identitas (identitas, created_at),
  INDEX idx_gagal_ip (ip, created_at)
) ENGINE=InnoDB;

-- Selesai. Ingin FAQ terbaru (tentang daftar dan lupa kata sandi)? Jalankan juga database/data_artikel_faq.sql.
