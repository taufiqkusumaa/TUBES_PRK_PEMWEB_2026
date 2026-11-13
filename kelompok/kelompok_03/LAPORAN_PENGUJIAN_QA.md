# LAPORAN PENGUJIAN & QUALITY ASSURANCE (QA)
## Praktikum Pemrograman Web 2026 - Kelompok 03 (Fakultas Kedokteran)
### Sistem Antrian Pasien Klinik

---

## 1. Identitas & Informasi Proyek
- **Kelompok:** Kelompok 03
- **Tema Fakultas:** Fakultas Kedokteran
- **Judul Sistem:** Sistem Antrian Klinik Berbasis Web
- **Fokus Modul:** Frontend Pasien (`index.php`, `pasien/`) & Quality Assurance (Testing & Validasi Sistem)
- **Teknologi:** PHP 8.x Native (PDO), MySQL 8.0 (InnoDB), Bootstrap 5, JavaScript Vanilla, CSS3

---

## 2. Ruang Lingkup Pengujian (Scope of Testing)
Pengujian dilakukan secara menyeluruh (End-to-End) mencakup aspek fungsionalitas, validasi input, konkurensi data, dan keamanan:
1. **Modul Autentikasi & Hak Akses:** Registrasi akun pasien baru, validasi formulir, hashing password bcrypt, login berbasis peran (Admin & Pasien), regenerasi sesi, dan pencegahan akses tanpa izin (*unauthorized access*).
2. **Modul Tampilan Jadwal Dokter Hari Ini (`index.php`):** Pengambilan data dinamis dari database, penyesuaian jadwal praktik dengan hari berjalan (WIB), kalkulasi sisa kuota, serta filter spesialis/poli berbasis JavaScript.
3. **Modul Pendaftaran & Pengambilan Antrean (`pasien/ambil_antrian.php`):** Uji konkurensi nomor antrean menggunakan transaksi database (`BEGIN TRANSACTION`, `SELECT FOR UPDATE`, `COMMIT`), validasi kuota harian, dan validasi pencegahan nomor antrean ganda.
4. **Modul Portal & Tiket Pasien (`pasien/pasien.php`):** Tampilan nomor tiket aktif, status antrean (*menunggu*, *dipanggil*, *selesai*), nomor panggilan dokter saat ini, estimasi antrean di depannya, dan riwayat kunjungan terdahulu.
5. **Modul Pemanggilan & Penyelesaian Antrean Admin:** Integrasi pemanggilan antrean oleh pihak admin klinik dan pembaruan status real-time pada antarmuka pasien.

---

## 3. Matriks Hasil Pengujian (Test Matrix)

| ID Uji | Fitur / Skenario | Langkah Pengujian | Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-01** | **Registrasi Pasien Baru** | Buka `auth/register.php`, masukkan nama, email, password >= 6 karakter, konfirmasi password sama. | Nama: `Pasien Baru`, Email: `baru@klinik.local`, Pass: `Rahasia#2026` | Akun tersimpan dengan password hash di tabel `users` (role: `pasien`), muncul notifikasi sukses registrasi. | Akun tersimpan, password ter-hash bcrypt, redirect ke login sukses. | **PASS** |
| **TC-02** | **Validasi Format Registrasi** | Masukkan email tidak valid atau password kurang dari 6 karakter. | Email: `bukan-email`, Pass: `123` | Sistem menolak proses registrasi dan memunculkan pesan error validasi di layar. | Muncul error format email tidak valid dan password minimal 6 karakter. | **PASS** |
| **TC-03** | **Login Pasien Valid** | Buka `auth/login.php`, input email dan password yang benar. | `andi.pasien@klinik.local` / `Pasien01#2026` | Login sukses, regenerasi ID sesi (`session_regenerate_id`), diarahkan otomatis ke `pasien/pasien.php`. | Berhasil masuk ke portal pasien, sesi tercatat dengan role `pasien`. | **PASS** |
| **TC-04** | **Login Password Salah** | Input email terdaftar dengan password salah. | `andi.pasien@klinik.local` / `SalahPass123` | Login ditolak dengan notifikasi *"Email atau password salah"*, sesi tidak dibuat. | Muncul pesan error, sesi tidak terbuat. | **PASS** |
| **TC-05** | **Login Admin & Otorisasi** | Login dengan akun administrator. | `admin@klinik.local` / `AdminKlinik#2026` | Diarahkan ke `admin/admin.php`, dapat mengelola dokter dan memanggil antrean. | Berhasil masuk ke dashboard admin, menu admin aktif. | **PASS** |
| **TC-06** | **Proteksi Halaman Pasien dari Tamu** | Akses langsung URL `pasien/pasien.php` tanpa login terlebih dahulu. | URL: `http://localhost:8888/.../pasien/pasien.php` | Sistem memblokir akses dan mengalihkan pengguna ke `auth/login.php`. | HTTP 302 redirect ke `auth/login.php`. Akses ditolak. | **PASS** |
| **TC-07** | **Daftar Dokter Hari Ini (`index.php`)** | Buka halaman utama `index.php` pada hari Selasa. | Tanggal Server: `2026-11-10` (Selasa) | Dokter dengan jadwal hari Selasa berstatus *"Praktik"*, dokter lain berstatus *"Tidak Praktik Hari Ini"*. | Dokter anak dan kebidanan berstatus Praktik, dokter penyakit dalam berstatus Tidak Praktik. | **PASS** |
| **TC-08** | **Filter Dropdown Poli / Spesialis** | Pilih poli "Anak" pada dropdown filter. | Filter: `Anak` | Hanya baris dokter spesialis Anak yang terlihat, dokter lain disembunyikan tanpa *reload* halaman. | Baris spesialis lain otomatis tersembunyi lewat JavaScript. | **PASS** |
| **TC-09** | **Pengambilan Nomor Antrean Pasien** | Pasien memilih dokter praktik dan klik tombol *"Ambil Nomor"*. | Pasien: Andi, Dokter: dr. Siti Rahmawati, Sp.A | Transaksi database dieksekusi, nomor urut bertambah (misal: #1), sisa kuota berkurang 1. | Tiket #1 diterbitkan, muncul di kartu antrean aktif pasien. | **PASS** |
| **TC-10** | **Pencegahan Pendaftaran Antrean Ganda** | Pasien mencoba mengambil nomor antrean lagi ke dokter yang sama pada hari yang sama. | Pasien: Andi, Dokter: dr. Siti (antrean ke-2) | Ditolak oleh sistem dengan pesan *"Anda sudah mengambil antrian ke dokter ini hari ini"*. | Exception tertangkap, transaksi di-rollback, muncul pesan peringatan. | **PASS** |
| **TC-11** | **Pemanggilan Antrean oleh Admin** | Admin memanggil antrean pasien melalui `admin/antrian_list.php`. | Antrean ID: 1, Aksi: Panggil | Status di database berubah dari `menunggu` menjadi `dipanggil`. | Status terbarui, di portal pasien muncul badge *"Giliran Anda Dipanggil!"* beranimasi pulse. | **PASS** |
| **TC-12** | **Penyelesaian Antrean oleh Admin** | Admin menekan tombol *"Selesai"* setelah pemeriksaan selesai. | Antrean ID: 1, Aksi: Selesai | Status antrean berubah menjadi `selesai`. Di portal pasien berubah menjadi *"Selesai Diperiksa"*. | Status terbarui menjadi selesai, riwayat kunjungan tercatat rapi. | **PASS** |

---

## 4. Kepatuhan Aturan Implementasi & Keamanan (Rule Compliance)

Berdasarkan ketentuan Bab 7 pada aturan tugas besar, berikut evaluasi pemenuhan sistem:

1. **Prepared Statements (Cegah SQL Injection):**
   - Seluruh query yang menerima parameter input (GET/POST/Session) 100% menggunakan `PDO::prepare()` dan *parameterized query*.
2. **Otorisasi Berbasis Peran (Role-Based Access Control):**
   - Middleware `includes/cek_login.php` memeriksa keberadaan sesi dan role pengguna pada setiap halaman terproteksi.
3. **Transaksi Database & Concurrency Control:**
   - Script `pasien/ambil_antrian.php` mengimplementasikan `$pdo->beginTransaction()`, `$pdo->commit()`, dan `$pdo->rollBack()`.
   - Menggunakan query `SELECT ... FOR UPDATE` saat membaca kuota dokter untuk mencegah nomor ganda akibat dua request berbarengan (*race condition*).
4. **Integritas Relasi Database (Foreign Key):**
   - Relasi foreign key menggunakan mesin penyimpanan InnoDB dengan aksi `ON UPDATE CASCADE` dan `ON DELETE RESTRICT` sehingga tidak terjadi penghapusan dokter yang masih memiliki riwayat antrean.
5. **Sanitasi Output (Cegah Cross-Site Scripting / XSS):**
   - Seluruh output data dinamis pengguna dan database di-escape menggunakan fungsi `htmlspecialchars($variabel, ENT_QUOTES, 'UTF-8')`.

---

## 5. Kesimpulan Hasil QA
Seluruh fitur yang ditugaskan pada bagian **Frontend Pasien & Quality Assurance**:
1. Halaman Login & Register
2. Halaman Depan Jadwal Dokter Hari Ini & Filter Poli (`index.php`)
3. Form Pengambilan Nomor Antrean & Validasi Kuota
4. Halaman Dashboard & Status Tiket Pasien (`pasien/pasien.php`)
5. Rangkaian Pengujian Fungsional & Keamanan (12 Test Cases)

Telah diuji pada lingkungan lokal (PHP 8.3 & MySQL 8.0) dan dinyatakan **100% LOLOS (PASSED)** tanpa error maupun bug sintaks (*0 syntax errors pada PHP lint*).

