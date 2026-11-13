create database if not exists buypanen;
USE buypanen;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('petani') NOT NULL DEFAULT 'petani',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE petani (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    no_whatsapp VARCHAR(20) NOT NULL,
    lokasi_kebun TEXT NOT NULL,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- TABEL KATEGORI

CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

-- TABEL PRODUK

CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kategori_id INT NOT NULL,

    nama_produk VARCHAR(150) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    satuan VARCHAR(30) NOT NULL DEFAULT 'kg',
    stok INT NOT NULL DEFAULT 0,
    deskripsi TEXT,
    foto VARCHAR(255),

    status ENUM('tayang', 'disembunyikan')
    NOT NULL DEFAULT 'tayang',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_produk_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

    CONSTRAINT fk_produk_kategori
    FOREIGN KEY (kategori_id)
    REFERENCES kategori(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);

-- DATA KATEGORI AWAL

INSERT INTO kategori (nama_kategori, deskripsi)
VALUES
('Sayur', 'Berbagai jenis hasil panen sayuran'),
('Buah', 'Berbagai jenis hasil panen buah'),
('Pangan', 'Berbagai jenis hasil panen pangan');

ALTER TABLE produk
DROP FOREIGN KEY fk_produk_user;

ALTER TABLE produk
ADD CONSTRAINT fk_produk_petani
FOREIGN KEY (user_id)
REFERENCES petani(user_id)
ON DELETE CASCADE
ON UPDATE CASCADE;
ALTER TABLE produk
ADD CONSTRAINT chk_harga CHECK (harga >= 0),
ADD CONSTRAINT chk_stok CHECK (stok >= 0);

-- Mengambil ID akun petani pertama
SET @petani_id = (
    SELECT user_id
    FROM petani
    ORDER BY id ASC
    LIMIT 1
);

-- Memasukkan data produk
INSERT INTO produk
(user_id, kategori_id, nama_produk, harga, satuan, stok, deskripsi, status)
VALUES
(
    @petani_id,
    (SELECT id FROM kategori WHERE nama_kategori = 'Sayur'),
    'Cabai Merah',
    45000,
    'kg',
    50,
    'Cabai merah segar hasil panen petani lokal.',
    'tayang'
),
(
    @petani_id,
    (SELECT id FROM kategori WHERE nama_kategori = 'Buah'),
    'Pisang Kepok',
    18000,
    'sisir',
    40,
    'Pisang kepok matang dan berkualitas.',
    'tayang'
),
(
    @petani_id,
    (SELECT id FROM kategori WHERE nama_kategori = 'Pangan'),
    'Jagung Manis',
    8000,
    'kg',
    70,
    'Jagung manis segar hasil panen lokal.',
    'tayang'
);