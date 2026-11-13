-- STRUKTUR TABEL (INFRASTRUKTUR)

CREATE DATABASE IF NOT EXISTS pena_kampus
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pena_kampus;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS faculties (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL UNIQUE,
  description TEXT NULL,
  website_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_faculties_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  faculty_id TINYINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_users_faculty_role (faculty_id, role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_id INT UNSIGNED NOT NULL,
  faculty_id TINYINT UNSIGNED NULL,
  category_id SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(280) NOT NULL UNIQUE,
  excerpt TEXT NULL,
  content LONGTEXT NOT NULL,
  featured_image VARCHAR(255) NULL,
  image_caption VARCHAR(255) NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  view_count INT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_articles_author FOREIGN KEY (author_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_articles_faculty FOREIGN KEY (faculty_id) REFERENCES faculties(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_articles_status_date (status, published_at),
  INDEX idx_articles_faculty_status (faculty_id, status),
  INDEX idx_articles_category_status (category_id, status),
  INDEX idx_articles_featured (is_featured, status, published_at),
  INDEX idx_articles_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  commenter_name VARCHAR(120) NOT NULL,
  commenter_email VARCHAR(190) NULL,
  content TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_article FOREIGN KEY (article_id) REFERENCES articles(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_comments_article_status (article_id, status, created_at),
  INDEX idx_comments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  replied_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contact_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DATA AWAL DATABASE - ORANG 5
INSERT INTO faculties (id, code, name, slug, description) VALUES
(1,'FEB','Fakultas Ekonomi dan Bisnis','fakultas-ekonomi-dan-bisnis','Berita dan informasi Fakultas Ekonomi dan Bisnis.'),
(2,'FH','Fakultas Hukum','fakultas-hukum','Berita dan informasi Fakultas Hukum.'),
(3,'FKIP','Fakultas Keguruan dan Ilmu Pendidikan','fakultas-keguruan-dan-ilmu-pendidikan','Berita dan informasi Fakultas Keguruan dan Ilmu Pendidikan.'),
(4,'FP','Fakultas Pertanian','fakultas-pertanian','Berita dan informasi Fakultas Pertanian.'),
(5,'FT','Fakultas Teknik','fakultas-teknik','Berita dan informasi Fakultas Teknik.'),
(6,'FISIP','Fakultas Ilmu Sosial dan Ilmu Politik','fakultas-ilmu-sosial-dan-ilmu-politik','Berita dan informasi Fakultas Ilmu Sosial dan Ilmu Politik.'),
(7,'FMIPA','Fakultas Matematika dan Ilmu Pengetahuan Alam','fakultas-matematika-dan-ilmu-pengetahuan-alam','Berita dan informasi Fakultas Matematika dan Ilmu Pengetahuan Alam.'),
(8,'FK','Fakultas Kedokteran','fakultas-kedokteran','Berita dan informasi Fakultas Kedokteran.')
ON DUPLICATE KEY UPDATE code=VALUES(code), name=VALUES(name), slug=VALUES(slug), description=VALUES(description);

INSERT INTO categories (id,name,slug,description) VALUES
(1,'Akademik','akademik','Informasi kegiatan akademik dan perkuliahan.'),
(2,'Kemahasiswaan','kemahasiswaan','Kegiatan dan informasi kemahasiswaan.'),
(3,'Organisasi','organisasi','Informasi organisasi dan kegiatan mahasiswa.'),
(4,'Prestasi','prestasi','Prestasi mahasiswa dan sivitas akademika.'),
(5,'Riset dan Inovasi','riset-dan-inovasi','Penelitian dan inovasi.'),
(6,'Pengabdian Masyarakat','pengabdian-masyarakat','Kegiatan pengabdian kepada masyarakat.'),
(7,'Pengumuman','pengumuman','Pengumuman dan informasi kampus.'),
(8,'Opini','opini','Artikel opini dan gagasan sivitas akademika.')
ON DUPLICATE KEY UPDATE name=VALUES(name), slug=VALUES(slug), description=VALUES(description);

INSERT INTO users (id,faculty_id,name,email,password_hash,role,status) VALUES
(1,NULL,'Administrator Pena Kampus','admin@penakampus.test','$2y$12$G840lFzBm0VCuq4gypeLRumYdiBRhtOk5fqOifw.lD3wFi0d2Y2JC','admin','active'),
(2,6,'Editor Demo FISIP','editor.fisip@penakampus.test','$2y$12$G840lFzBm0VCuq4gypeLRumYdiBRhtOk5fqOifw.lD3wFi0d2Y2JC','editor','active')
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role), status=VALUES(status);

INSERT INTO articles (id,author_id,faculty_id,category_id,title,slug,excerpt,content,featured_image,image_caption,status,is_featured,view_count,published_at) VALUES
(1,1,NULL,7,'Contoh: Kolaborasi Riset Mahasiswa dan Dosen Kembangkan Inovasi Kampus','contoh-kolaborasi-riset-mahasiswa-dan-dosen','Contoh berita tingkat universitas untuk menguji tampilan halaman utama Pena Kampus.','Ini adalah artikel demonstrasi untuk menguji tampilan portal. Ganti konten ini dengan berita yang telah diverifikasi sebelum website dipublikasikan.\n\nParagraf kedua dapat menjelaskan latar belakang kegiatan, pihak yang terlibat, dan hasil yang diperoleh.','https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&q=80','Foto ilustrasi kegiatan kampus','published',1,25,NOW()),
(2,2,6,2,'Contoh: Kegiatan Kemahasiswaan di Lingkungan FISIP','contoh-kegiatan-kemahasiswaan-fisip','Artikel contoh untuk menguji filter berita berdasarkan fakultas.','Konten ini merupakan data dummy untuk keperluan pengujian. Editor dapat mengganti artikel melalui dashboard.','https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1200&q=80','Foto ilustrasi mahasiswa','published',0,12,NOW()),
(3,1,5,5,'Contoh: Mahasiswa Fakultas Teknik Gelar Pameran Proyek Inovasi','contoh-pameran-proyek-inovasi-teknik','Artikel contoh untuk menguji halaman berita fakultas dan kategori riset.','Ini merupakan data dummy. Pastikan setiap berita yang diterbitkan memiliki sumber dan informasi yang sudah diperiksa.','https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80','Foto ilustrasi proyek mahasiswa','published',0,8,NOW()),
(4,1,NULL,1,'Contoh: Draft Informasi Akademik Universitas','contoh-draft-informasi-akademik','Contoh artikel yang belum dipublikasikan.','Artikel ini berstatus draft dan tidak boleh muncul pada halaman publik.',NULL,'','draft',0,0,NULL)
ON DUPLICATE KEY UPDATE title=VALUES(title), excerpt=VALUES(excerpt);

INSERT INTO comments (article_id,commenter_name,commenter_email,content,status)
SELECT 1,'Pengunjung Demo','pengunjung@example.test','Komentar contoh untuk menguji moderasi komentar.','pending'
WHERE NOT EXISTS (
  SELECT 1 FROM comments
  WHERE article_id=1 AND commenter_email='pengunjung@example.test'
    AND content='Komentar contoh untuk menguji moderasi komentar.'
);
