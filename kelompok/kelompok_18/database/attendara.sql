-- ATTENDARA target schema, based on docs/ERD.md.
-- Compatible target: MySQL 8+ / MariaDB 10.5+ with InnoDB.
-- Import into a NEW/EMPTY database. CREATE TABLE IF NOT EXISTS does not migrate
-- tables from the previous ATTENDARA schema.
-- This file creates the schema only; sample records are in attendara_dummy.sql.

CREATE DATABASE IF NOT EXISTS attendara
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendara;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'guru') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guru (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  nomor_induk VARCHAR(50) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  email VARCHAR(254) NULL,
  no_telepon VARCHAR(30) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guru_user_id (user_id),
  UNIQUE KEY uq_guru_nomor_induk (nomor_induk),
  CONSTRAINT fk_guru_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS siswa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nomor_induk VARCHAR(50) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  jenis_kelamin ENUM('L', 'P') NOT NULL,
  tanggal_lahir DATE NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_siswa_nomor_induk (nomor_induk),
  KEY idx_siswa_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mata_pelajaran (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode VARCHAR(30) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mata_pelajaran_kode (kode),
  UNIQUE KEY uq_mata_pelajaran_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS semester (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode VARCHAR(40) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  status ENUM('direncanakan', 'aktif', 'selesai') NOT NULL DEFAULT 'direncanakan',
  semester_aktif_key TINYINT
    GENERATED ALWAYS AS (CASE WHEN status = 'aktif' THEN 1 ELSE NULL END) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_semester_kode (kode),
  UNIQUE KEY uq_satu_semester_aktif (semester_aktif_key),
  KEY idx_semester_tanggal (tanggal_mulai, tanggal_selesai),
  CONSTRAINT chk_semester_tanggal CHECK (tanggal_selesai >= tanggal_mulai)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  semester_id BIGINT UNSIGNED NOT NULL,
  kode VARCHAR(30) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  tingkat TINYINT UNSIGNED NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kelas_semester_kode (semester_id, kode),
  UNIQUE KEY uq_kelas_id_semester (id, semester_id),
  KEY idx_kelas_semester_status (semester_id, is_active),
  CONSTRAINT fk_kelas_semester
    FOREIGN KEY (semester_id) REFERENCES semester (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anggota_kelas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siswa_id BIGINT UNSIGNED NOT NULL,
  kelas_id BIGINT UNSIGNED NOT NULL,
  semester_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_anggota_kelas_siswa_semester (siswa_id, semester_id),
  KEY idx_anggota_kelas_kelas_siswa (kelas_id, siswa_id),
  CONSTRAINT fk_anggota_kelas_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelas_kelas_semester
    FOREIGN KEY (kelas_id, semester_id) REFERENCES kelas (id, semester_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelas_semester
    FOREIGN KEY (semester_id) REFERENCES semester (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penugasan_guru (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guru_id BIGINT UNSIGNED NOT NULL,
  mata_pelajaran_id BIGINT UNSIGNED NOT NULL,
  kelas_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_penugasan_guru_mapel_kelas (guru_id, mata_pelajaran_id, kelas_id),
  KEY idx_penugasan_kelas (kelas_id),
  KEY idx_penugasan_mapel_guru (mata_pelajaran_id, guru_id),
  CONSTRAINT fk_penugasan_guru_guru
    FOREIGN KEY (guru_id) REFERENCES guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_penugasan_guru_mapel
    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_penugasan_guru_kelas
    FOREIGN KEY (kelas_id) REFERENCES kelas (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_mengajar (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penugasan_guru_id BIGINT UNSIGNED NOT NULL,
  hari TINYINT UNSIGNED NOT NULL COMMENT '1=Senin sampai 7=Minggu',
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  berlaku_mulai DATE NULL,
  berlaku_sampai DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_jadwal_id_penugasan (id, penugasan_guru_id),
  KEY idx_jadwal_penugasan_hari_jam (penugasan_guru_id, hari, jam_mulai),
  CONSTRAINT fk_jadwal_penugasan
    FOREIGN KEY (penugasan_guru_id) REFERENCES penugasan_guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_jadwal_hari CHECK (hari BETWEEN 1 AND 7),
  CONSTRAINT chk_jadwal_jam CHECK (jam_selesai > jam_mulai),
  CONSTRAINT chk_jadwal_berlaku CHECK (
    berlaku_mulai IS NULL OR berlaku_sampai IS NULL OR berlaku_sampai >= berlaku_mulai
  )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesi_pembelajaran (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jadwal_mengajar_id BIGINT UNSIGNED NOT NULL,
  mulai_at DATETIME NOT NULL,
  selesai_at DATETIME NULL,
  status ENUM('berlangsung', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'berlangsung',
  jumlah_tap SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  tap_terakhir_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sesi_jadwal_mulai (jadwal_mengajar_id, mulai_at),
  KEY idx_sesi_status (status),
  CONSTRAINT fk_sesi_jadwal
    FOREIGN KEY (jadwal_mengajar_id) REFERENCES jadwal_mengajar (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_sesi_waktu CHECK (selesai_at IS NULL OR selesai_at >= mulai_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS absensi (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sesi_pembelajaran_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  status ENUM('hadir', 'izin', 'sakit', 'alpha') NOT NULL DEFAULT 'hadir',
  dicatat_oleh_user_id BIGINT UNSIGNED NOT NULL,
  waktu_dicatat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  catatan VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_absensi_sesi_siswa (sesi_pembelajaran_id, siswa_id),
  KEY idx_absensi_siswa_sesi (siswa_id, sesi_pembelajaran_id),
  CONSTRAINT fk_absensi_sesi
    FOREIGN KEY (sesi_pembelajaran_id) REFERENCES sesi_pembelajaran (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_absensi_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_absensi_pencatat
    FOREIGN KEY (dicatat_oleh_user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelompok (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penugasan_guru_id BIGINT UNSIGNED NOT NULL,
  nama VARCHAR(100) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kelompok_penugasan_nama (penugasan_guru_id, nama),
  CONSTRAINT fk_kelompok_penugasan
    FOREIGN KEY (penugasan_guru_id) REFERENCES penugasan_guru (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anggota_kelompok (
  kelompok_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (kelompok_id, siswa_id),
  KEY idx_anggota_kelompok_siswa (siswa_id),
  CONSTRAINT fk_anggota_kelompok_kelompok
    FOREIGN KEY (kelompok_id) REFERENCES kelompok (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_anggota_kelompok_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS poin_keaktifan (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sesi_pembelajaran_id BIGINT UNSIGNED NOT NULL,
  siswa_id BIGINT UNSIGNED NOT NULL,
  poin DECIMAL(7,2) NOT NULL,
  dicatat_oleh_user_id BIGINT UNSIGNED NOT NULL,
  waktu_dicatat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  catatan VARCHAR(500) NULL,
  PRIMARY KEY (id),
  KEY idx_poin_sesi_siswa (sesi_pembelajaran_id, siswa_id),
  KEY idx_poin_siswa_waktu (siswa_id, waktu_dicatat),
  CONSTRAINT fk_poin_sesi
    FOREIGN KEY (sesi_pembelajaran_id) REFERENCES sesi_pembelajaran (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_poin_siswa
    FOREIGN KEY (siswa_id) REFERENCES siswa (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_poin_pencatat
    FOREIGN KEY (dicatat_oleh_user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_poin_positif CHECK (poin > 0)
) ENGINE=InnoDB;
