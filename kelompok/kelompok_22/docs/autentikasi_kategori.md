# Dokumentasi Teknis Modul Autentikasi Admin & CRUD Kategori

**Proyek:** KlikObat, Sistem Informasi Katalog Farmasi & Edukasi
**Kelompok:** 22
**Penanggung jawab modul:** Irma Hayyu Sani (Autentikasi Admin & CRUD Kategori)

## 1. Pendahuluan

Modul autentikasi admin dan manajemen kategori merupakan fondasi sistem informasi KlikObat. Modul ini memastikan bahwa pengelolaan data kategori obat hanya dapat dilakukan oleh pihak yang memiliki hak akses sah (admin terverifikasi). Dokumen ini merinci struktur file, logika keamanan, implementasi basis data, dan validasi bisnis yang telah dibangun.

## 2. Struktur File Modul

```text
admin/
├── index.php        # Dashboard admin (statistik & peringatan stok)
├── auth.php         # Penjaga sesi (session guard)
├── login.php        # Form login & proses autentikasi
├── logout.php       # Penghancuran sesi
└── kategori/        # Manajemen kategori obat
    ├── index.php    # Daftar kategori (Read)
    ├── tambah.php   # Tambah kategori (Create)
    ├── ubah.php     # Ubah kategori (Update)
    └── hapus.php    # Hapus kategori & validasi relasi (Delete)

config/
└── koneksi.php      # Koneksi database MySQLi (memuat backend/koneksi.php)
```

## 3. Detail Implementasi dan Keamanan

### A. Autentikasi Admin (`admin/`)

**`auth.php` (session guard)**
Wajib disisipkan (`require_once`) di setiap halaman admin. Skrip memeriksa apakah `$_SESSION['id_admin']` sudah terdaftar. Jika belum, pengguna dialihkan ke halaman login sehingga halaman internal tidak dapat dibuka lewat URL langsung.

**`login.php` (validasi kredensial)**
- Menyediakan form login dan memvalidasi kredensial admin.
- Memakai *prepared statement* (`$conn->prepare` dengan `bind_param`) untuk mencegah SQL Injection.
- Memakai `password_verify($password, $row['password'])` untuk mencocokkan password input dengan hash bcrypt di database.
- Jika berhasil, `id_admin` dan `username` disimpan ke sesi, lalu pengguna diarahkan ke dashboard.

**`logout.php` (pengakhiran sesi)**
Membersihkan sesi dengan `session_unset()` dan `session_destroy()`, lalu mengalihkan pengguna ke form login.

### B. Dashboard Admin (`admin/index.php`)

- **Summary cards:** total kategori, total obat, dan jumlah obat dengan stok kritis (Menipis atau Habis).
- **Low stock alert:** tabel obat yang berstatus Menipis atau Habis agar admin dapat segera melakukan restock.
- **Recent additions:** 5 obat yang paling baru ditambahkan.

### C. CRUD Kategori (`admin/kategori/`)

**`index.php` (Read)**
Menjalankan `SELECT * FROM kategori ORDER BY id_kategori DESC` dan menampilkan hasilnya dalam tabel HTML responsif dengan tombol aksi Tambah, Ubah, dan Hapus.

**`tambah.php` (Create)**
Form berbasis POST. Nama kategori yang kosong ditolak sebelum `INSERT INTO kategori` dijalankan. Karena kolom `nama_kategori` bersifat `UNIQUE`, nama yang sudah ada juga ditolak dengan pesan kesalahan.

**`ubah.php` (Update)**
Mengambil `id` dari URL (`$_GET['id']`) untuk memuat data lama ke dalam form, lalu menjalankan `UPDATE kategori SET ... WHERE id_kategori = ?` saat tombol simpan ditekan.

**`hapus.php` (Delete dan integritas relasional)**
- Aturan bisnis: kategori yang masih dipakai oleh data obat **tidak boleh dihapus** agar integritas referensial terjaga.
- Sebelum `DELETE`, sistem memeriksa relasi dengan `SELECT COUNT(*) AS total FROM obat WHERE id_kategori = ?`.
- Jika `total > 0`, penghapusan dibatalkan dan muncul peringatan: *"Gagal Hapus! Kategori ini masih digunakan oleh data obat."* Jika `0`, kategori dihapus.
- Penghapusan dipicu dari tautan pada tabel kategori yang dilengkapi konfirmasi `confirm()` di browser.
- Foreign key `fk_obat_kategori` pada database (`ON DELETE RESTRICT`) menjadi lapisan pengaman kedua.

## 4. Penanganan Bug dan Pengujian

**Perbaikan path redirect.** Redirect pada `auth.php` sempat menghasilkan error 404 saat diakses dari subfolder `kategori/` karena jalur relatif salah. Jalur diubah menjadi `../login.php`, sehingga halaman di subfolder (`kategori/`, `obat/`) kini dialihkan dengan benar ke `admin/login.php`.

**Pengujian lokal.** Fitur diuji dengan server lokal Laragon (PHP dan MySQL): login, pembatasan akses URL tanpa login, operasi CRUD, dan penolakan hapus kategori yang masih berelasi.
