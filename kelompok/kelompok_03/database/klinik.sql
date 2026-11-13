
CREATE DATABASE IF NOT EXISTS klinik_antrian
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE klinik_antrian;

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pasien') NOT NULL DEFAULT 'pasien',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_users PRIMARY KEY (id),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_nama CHECK (CHAR_LENGTH(TRIM(nama)) > 0),

    INDEX idx_users_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS dokter (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    spesialis VARCHAR(100) NOT NULL,
    kuota_per_hari SMALLINT UNSIGNED NOT NULL,
    hari_praktik VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_dokter PRIMARY KEY (id),
    CONSTRAINT chk_dokter_nama CHECK (CHAR_LENGTH(TRIM(nama)) > 0),
    CONSTRAINT chk_dokter_spesialis CHECK (CHAR_LENGTH(TRIM(spesialis)) > 0),
    CONSTRAINT chk_dokter_kuota CHECK (kuota_per_hari > 0),
    CONSTRAINT chk_dokter_hari CHECK (CHAR_LENGTH(TRIM(hari_praktik)) > 0),

    INDEX idx_dokter_nama (nama)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS antrian (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    dokter_id INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    nomor SMALLINT UNSIGNED NOT NULL,
    status ENUM('menunggu', 'dipanggil', 'selesai')
        NOT NULL DEFAULT 'menunggu',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_antrian PRIMARY KEY (id),

    CONSTRAINT uq_antrian_dokter_tanggal_nomor
        UNIQUE (dokter_id, tanggal, nomor),

    CONSTRAINT uq_antrian_user_dokter_tanggal
        UNIQUE (user_id, dokter_id, tanggal),

    CONSTRAINT chk_antrian_nomor CHECK (nomor > 0),

    CONSTRAINT fk_antrian_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_antrian_dokter
        FOREIGN KEY (dokter_id)
        REFERENCES dokter (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_antrian_dokter_tanggal_status
        (dokter_id, tanggal, status, nomor),

    INDEX idx_antrian_user_tanggal
        (user_id, tanggal)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO users
    (id, nama, email, password, role)
VALUES
(
    1,
    'Administrator Klinik',
    'admin@klinik.local',
    '$2y$12$XHN9nwD6PzV8QGA4wbsquuVnJmkepsy5qoLYxi8Cb9jBNWiJd9PS.',
    'admin'
),
(
    2,
    'Andi Pratama',
    'andi.pasien@klinik.local',
    '$2y$12$m2zIT7uzvlZWHc86BNGdKO3r/mArX4LKLLQ7yuGigRyqAY3mfBmg2',
    'pasien'
),
(
    3,
    'Bunga Lestari',
    'bunga.pasien@klinik.local',
    '$2y$12$RPyTUWFeNqhbIKySeDrD5.ZDLfRtEy1RtJwee1bGQoAJFocPnBpNW',
    'pasien'
),
(
    4,
    'Citra Maharani',
    'citra.pasien@klinik.local',
    '$2y$12$uUGOFfX1PL6ZRQpRej0q3O5q9XXBruFK6uHz1.Wi1GZt1.HwAXdAS',
    'pasien'
);


INSERT IGNORE INTO dokter
    (id, nama, spesialis, kuota_per_hari, hari_praktik)
VALUES
    (1, 'dr. Andi Saputra, Sp.PD', 'Penyakit Dalam', 20, 'Senin,Rabu,Jumat'),
    (2, 'dr. Siti Rahmawati, Sp.A', 'Anak', 15, 'Senin,Selasa,Kamis'),
    (3, 'dr. Budi Santoso, Sp.OG', 'Kebidanan dan Kandungan', 15, 'Selasa,Kamis,Sabtu'),
    (4, 'dr. Maya Putri, Sp.D.V.E', 'Kulit dan Kelamin', 10, 'Rabu,Jumat,Sabtu');


SELECT id, nama, email, role
FROM users
ORDER BY id;

SELECT id, nama, spesialis, kuota_per_hari, hari_praktik
FROM dokter
ORDER BY id;

SELECT
    TABLE_NAME,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'klinik_antrian'
  AND TABLE_NAME IN ('users', 'dokter', 'antrian')
ORDER BY TABLE_NAME;
