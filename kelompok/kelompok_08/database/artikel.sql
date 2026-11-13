USE buypanen;

CREATE TABLE IF NOT EXISTS kategori_artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penulis_id INT NOT NULL,
    kategori_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    ringkasan VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    gambar VARCHAR(255),
    status ENUM('draft', 'publish') NOT NULL DEFAULT 'draft',
    dilihat INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_artikel_penulis
    FOREIGN KEY (penulis_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_artikel_kategori
    FOREIGN KEY (kategori_id) REFERENCES kategori_artikel(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

INSERT IGNORE INTO kategori_artikel (nama_kategori, deskripsi) VALUES
('Tips Tani', 'Panduan menanam dan merawat tanaman'),
('Pangan Segar', 'Memilih dan menyimpan hasil panen');

-- Penulis contoh: akun admin pertama, atau akun pertama jika belum ada admin.
INSERT INTO artikel (penulis_id, kategori_id, judul, slug, ringkasan, isi, status)
SELECT u.id, k.id,
 'Cara Menyimpan Sayuran Hijau agar Tetap Segar', 'menyimpan-sayuran-hijau',
 'Bayam, kangkung, dan sawi cepat layu. Ini cara sederhana menyimpannya lebih lama.',
 'Pisahkan daun yang sudah menguning, jangan dicuci sebelum disimpan.\n\nBungkus dengan tisu atau kertas koran bersih, lalu masukkan ke wadah tertutup di bagian bawah kulkas.\n\nSayuran hijau umumnya bertahan 3-5 hari dengan cara ini.',
 'publish'
FROM users u JOIN kategori_artikel k ON k.nama_kategori = 'Pangan Segar'
ORDER BY (u.role = 'admin') DESC, u.id LIMIT 1;

INSERT INTO artikel (penulis_id, kategori_id, judul, slug, ringkasan, isi, status)
SELECT u.id, k.id,
 'Tips Memilih Buah Matang di Pasar', 'memilih-buah-matang',
 'Kenali ciri buah matang dari aroma, warna, dan tekstur kulitnya.',
 'Cium aroma di bagian pangkal buah. Buah matang biasanya beraroma harum.\n\nPerhatikan warna kulit yang merata, dan tekan perlahan: buah matang terasa sedikit empuk, tidak lembek.\n\nHindari buah dengan bercak basah atau kulit yang retak.',
 'publish'
FROM users u JOIN kategori_artikel k ON k.nama_kategori = 'Pangan Segar'
ORDER BY (u.role = 'admin') DESC, u.id LIMIT 1;

INSERT INTO artikel (penulis_id, kategori_id, judul, slug, ringkasan, isi, status)
SELECT u.id, k.id,
 'Dasar-Dasar Menanam Cabai di Lahan Kecil', 'menanam-cabai-lahan-kecil',
 'Cabai bisa tumbuh baik di polybag. Mulai dari media tanam sampai penyiraman.',
 'Gunakan campuran tanah, pupuk kandang, dan sekam dengan perbandingan 2:1:1.\n\nSemai biji lebih dulu selama 2-3 minggu, lalu pindahkan ke polybag berdiameter minimal 35 cm.\n\nSiram pagi atau sore dan pastikan tanaman mendapat sinar matahari 6-8 jam per hari.',
 'publish'
FROM users u JOIN kategori_artikel k ON k.nama_kategori = 'Tips Tani'
ORDER BY (u.role = 'admin') DESC, u.id LIMIT 1;