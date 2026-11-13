-- Database: `db_log_ternak_k04`

CREATE TABLE IF NOT EXISTS `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'peternak', 'dokter') NOT NULL DEFAULT 'peternak',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kandang` (
  `id_kandang` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kandang` VARCHAR(50) NOT NULL,
  `kapasitas` INT NOT NULL,
  `lokasi` VARCHAR(100),
  `keterangan` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ternak` (
  `id_ternak` INT AUTO_INCREMENT PRIMARY KEY,
  `tag_number` VARCHAR(30) NOT NULL UNIQUE,
  `jenis_ras` VARCHAR(50) NOT NULL,
  `jenis_kelamin` ENUM('Jantan', 'Betina') NOT NULL,
  `tgl_lahir` DATE,
  `foto` VARCHAR(255) DEFAULT 'default.jpg',
  `status_kesehatan` ENUM('Sehat', 'Sakit', 'Karantina') NOT NULL DEFAULT 'Sehat',
  `id_kandang` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_kandang`) REFERENCES `kandang`(`id_kandang`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `log_kesehatan` (
  `id_kesehatan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_ternak` INT NOT NULL,
  `id_dokter` INT,
  `tgl_periksa` DATE NOT NULL,
  `diagnosa` TEXT NOT NULL,
  `tindakan_medis` TEXT,
  `obat_vaksin` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_ternak`) REFERENCES `ternak`(`id_ternak`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_dokter`) REFERENCES `users`(`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `riwayat_bobot` (
  `id_bobot` INT AUTO_INCREMENT PRIMARY KEY,
  `id_ternak` INT NOT NULL,
  `tgl_timbang` DATE NOT NULL,
  `bobot_kg` DECIMAL(6,2) NOT NULL,
  `catatan` VARCHAR(255),
  FOREIGN KEY (`id_ternak`) REFERENCES `ternak`(`id_ternak`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pakan` (
  `id_pakan` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pakan` VARCHAR(100) NOT NULL,
  `jenis_pakan` VARCHAR(50) NOT NULL,
  `peruntukan` VARCHAR(100) NOT NULL,
  `stok_kg` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `satuan` VARCHAR(20) DEFAULT 'Kg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `log_pemberian_pakan` (
  `id_log_pakan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pakan` INT NOT NULL,
  `id_kandang` INT NOT NULL,
  `id_peternak` INT,
  `tgl_pemberian` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jumlah_kg` DECIMAL(6,2) NOT NULL,
  FOREIGN KEY (`id_pakan`) REFERENCES `pakan`(`id_pakan`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_kandang`) REFERENCES `kandang`(`id_kandang`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_peternak`) REFERENCES `users`(`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal (Dummy User untuk Login Test)
-- Password default: 'password123' (sudah di-hash password_hash PHP)
INSERT INTO users (username, password, nama_lengkap, role) 
VALUES ('admin', SHA2('password123', 256), 'Administrator', 'admin'),
('peternak1', SHA2('password123',256), 'Peternak 1', 'peternak'),
('dokter1', SHA2('password123', 256), 'Dokter 1', 'dokter');