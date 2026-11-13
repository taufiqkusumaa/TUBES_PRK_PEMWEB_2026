# Pembagian Jobdesk 5 Anggota — Pena Kampus

Pembagian menggunakan pendekatan **feature ownership**. Setiap anggota wajib mengerjakan HTML/Tailwind, JavaScript, API PHP, integrasi database, validasi, dan pengujian pada modulnya. Satu branch fitur per anggota cukup untuk modul yang tercantum di bawah; buat beberapa commit untuk setiap bagian pekerjaan yang selesai.

## Anggota 1 — Portal Publik dan Kontak

**Halaman:**
1. Beranda — `index.html`
13. Tentang Kami — `tentang.html`
14. Kontak — `kontak.html`
15. Inbox Pesan Kontak — `admin/pesan.html` dan `api/admin_messages_action.php` (Admin)

**Tanggung jawab coding:**
- Menjaga komponen publik `templates/public.html` bersama tim.
- Mengimplementasikan homepage: berita unggulan, populer, terbaru, kategori, dan tautan ke kanal fakultas.
- Mengimplementasikan halaman Tentang Kami.
- Membuat validasi formulir kontak dan menyimpan pesan ke `contact_messages`.
- Membuat inbox pesan, pencarian sederhana, melihat detail, mengubah status `new/read/replied/archived`.
- Memastikan seluruh link navbar/footer mengarah ke halaman yang benar.

**Database utama:** `contact_messages`; membaca `articles`, `faculties`, `categories`, `users` untuk homepage.

**Branch contoh:** `feat/kelompok-XX-public`.

## Anggota 2 — Penelusuran Berita

**Halaman:**
2. Semua Berita — `berita.html`
3. Berita Fakultas — `fakultas.html`

**Tanggung jawab coding:**
- Menampilkan artikel `published` dari database.
- Pencarian judul, ringkasan, dan isi artikel.
- Filter gabungan fakultas dan kategori.
- Sorting terbaru, terlama, dan terpopuler.
- Pagination.
- Template berita fakultas yang dipakai untuk delapan fakultas melalui parameter `slug`.
- Empty state saat hasil pencarian kosong.

**Database utama:** membaca `articles`, `faculties`, `categories`, `users`.

**Branch contoh:** `feat/kelompok-XX-news`.

## Anggota 3 — Detail Artikel dan Komentar

**Halaman:**
4. Detail Berita — `detail.html`
12. Moderasi Komentar — `admin/komentar.html` dan `api/admin_comments_action.php`

**Tanggung jawab coding:**
- Mengambil artikel berdasarkan `slug` dan hanya menampilkan status `published`.
- Menampilkan isi artikel, penulis, fakultas, kategori, tanggal, jumlah pembaca, dan artikel terkait.
- Menaikkan `view_count` maksimal satu kali per sesi per artikel.
- Memproses komentar pengunjung ke status `pending`.
- Menampilkan komentar yang sudah `approved` saja.
- Membuat moderasi komentar: approve, reject, delete.
- Membatasi editor hanya pada komentar untuk artikel fakultas yang ditugaskan kepadanya.

**Database utama:** `comments`, membaca/memperbarui `articles`, `faculties`, `users`.

**Branch contoh:** `feat/kelompok-XX-article`.

## Anggota 4 — Autentikasi dan Administrasi

**Halaman:**
5. Login Admin/Editor — `admin/login.html` dan `api/admin_logout.php`
6. Dashboard — `admin/dashboard.html`
10. Manajemen Fakultas — `admin/fakultas.html` dan `api/admin_faculties_save.php`
11. Manajemen Pengguna — `admin/akun.html`, `admin/akun-form.html`, `api/admin_users_toggle.php`

**Tanggung jawab coding:**
- Login menggunakan `password_verify()` dan password hash.
- Session management, logout, dan regenerasi session ID setelah login.
- Role `admin` dan `editor`.
- Dashboard statistik yang mengikuti cakupan role.
- CRUD edit informasi fakultas dan pengaktifan/nonaktifkan fakultas.
- Membuat, mengubah, dan menonaktifkan akun editor/admin.
- Memastikan editor ditugaskan ke satu fakultas.
- Membatasi modul administrasi hanya untuk Admin.
- Menjadi pemilik komponen bersama `auth_check`/fungsi hak akses dan sidebar CMS.

**Database utama:** `users`, `faculties`; membaca `articles`, `categories`, `comments` untuk statistik.

**Branch contoh:** `feat/kelompok-XX-admin`.

## Anggota 5 — CMS dan Kategori

**Halaman:**
7. Manajemen Berita — `admin/berita.html`
8. Tambah/Edit Berita — `admin/berita-form.html`
9. Manajemen Kategori — `admin/kategori.html`, `api/admin_categories_save.php`, `api/admin_categories_toggle.php`

**Tanggung jawab coding:**
- Menampilkan daftar artikel beserta filter status dan kategori.
- CRUD artikel.
- Slug artikel unik.
- Upload gambar JPG/PNG/WebP maksimal 2 MB.
- Mengelola status `draft/published/archived` dan tanggal publikasi.
- Menentukan fakultas artikel: Admin boleh memilih berita universitas atau fakultas; Editor hanya boleh membuat artikel untuk fakultasnya sendiri.
- CRUD kategori dan nonaktifkan kategori yang tidak digunakan.
- Memastikan query menggunakan prepared statements dan validasi server-side.
- Menyiapkan SQL utama serta bekerja sama dengan anggota lain bila skema berubah.

**Database utama:** `articles`, `categories`; membaca `users`, `faculties`.

**Branch contoh:** `feat/kelompok-XX-cms`.

## Komponen bersama dan aturan antarmodul

| Komponen | Pemilik utama | Aturan |
|---|---|---|
| Koneksi PDO `config/database.php` | Anggota 5 | Semua halaman menggunakan fungsi `db()` yang sama. Jangan buat koneksi kedua. |
| Bootstrap/session/fungsi hak akses | Anggota 4 | Semua halaman admin melewati pemeriksaan login/role. |
| Header/footer publik | Anggota 1 | Jangan membuat navbar/footer sendiri pada halaman publik. |
| Header/footer CMS | Anggota 4 | Semua halaman admin menggunakan sidebar/topbar yang sama. |
| Skema SQL/ERD | Tim, dikoordinasikan Anggota 5 | Perubahan kolom harus disepakati dan diperbarui di file SQL. |
| Upload artikel | Anggota 5 | Simpan nama/path relatif di database, file masuk `uploads/`. |

## Definition of Done setiap fitur

1. Halaman memakai komponen desain bersama dan Tailwind.
2. PHP berjalan tanpa syntax error.
3. Input divalidasi di server, bukan hanya browser.
4. Query database menggunakan prepared statement untuk nilai input.
5. Form perubahan data memakai CSRF token.
6. Hak akses admin/editor diuji.
7. Fitur dites di lokal sebelum commit dan Pull Request.
