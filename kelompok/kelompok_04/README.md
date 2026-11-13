# Kelompok 04
1. Hafidz Azka Rikzi                      2415061051
2. Angga Saputra                          2415061071
3. M. Darunnadwah Al-Qushai Sutrawhana    2455061005
4. Annisa Khairina                        2415061103
5. Yunia Cantika Sari                     2155061017


# Sistem Log Data Kesehatan & Pakan Ternak — Kelompok 04

Aplikasi web untuk mengelola data ternak, kandang, kesehatan, bobot, stok dan pemberian pakan, serta laporan. Aplikasi dibuat dengan PHP native (mysqli prosedural), MySQL, Bootstrap 5, Bootstrap Icons, dan Chart.js.

## Fitur

- Dashboard ringkasan ternak, ternak sakit, kandang, dan stok pakan.
- Grafik distribusi status ternak yang dapat difilter berdasarkan jenis ternak.
- Grafik pemakaian pakan berdasarkan rentang tanggal.
- Filter ringkasan stok berdasarkan kategori pakan.
- Pengelolaan pengguna, kandang, data ternak, dan master pakan.
- Pencatatan pemeriksaan kesehatan dan riwayat penimbangan.
- Log pemberian pakan yang memperbarui stok: pencatatan mengurangi stok, sedangkan penghapusan log mengembalikan stok.
- Laporan yang dapat difilter berdasarkan periode dan jenis ternak.

## Persyaratan

- PHP 8.x dengan ekstensi `mysqli`.
- MySQL atau MariaDB.
- Apache atau web server PHP lain.
- Browser modern dan koneksi internet untuk memuat Bootstrap, Bootstrap Icons, dan Chart.js dari CDN.

Laragon atau XAMPP dapat digunakan sebagai paket Apache, PHP, dan MySQL lokal.

## Instalasi lokal

1. Salin atau clone repository ke folder web server. Untuk Laragon, contoh lokasinya:

   ```text
   C:\laragon\www\TUBES_PRK_PEMWEB_2026
   ```

2. Jalankan Apache dan MySQL melalui Laragon/XAMPP.

3. Buat database bernama `db_log_ternak_k04` di phpMyAdmin atau MySQL.

4. Pilih database tersebut, lalu impor [`database/schema.sql`](./database/schema.sql). Script ini membuat tabel dan tiga akun awal; impor pada database kosong. Script tidak membuat database secara otomatis.

5. Periksa konfigurasi database di [`config/koneksi.php`](./config/koneksi.php). Nilai bawaan:

   ```text
   Host     : localhost
   Username : root
   Password : (kosong)
   Database : db_log_ternak_k04
   ```

   Sesuaikan host, username, password, atau nama database jika konfigurasi MySQL lokal berbeda.

6. Buka aplikasi melalui web server, misalnya:

   ```text
   http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_04/
   ```

   Jangan membuka file PHP langsung dari file explorer; aplikasi memerlukan Apache/PHP dan koneksi database.

## Akun awal

`schema.sql` menyediakan akun pengujian berikut. Semua menggunakan password `password123`.

| Username | Peran |
|---|---|
| `admin` | Admin |
| `peternak1` | Peternak |
| `dokter1` | Dokter |

Ganti kredensial bawaan sebelum aplikasi digunakan di lingkungan bersama atau produksi. Jangan gunakan akun contoh untuk data nyata.

## Panduan penggunaan

1. **Login** menggunakan salah satu akun awal atau akun yang sudah dibuat oleh admin. Untuk keluar, gunakan menu **Logout**.
2. **Dashboard** menampilkan ringkasan dan grafik. Grafik status dapat difilter menurut jenis ternak; ringkasan stok dapat difilter menurut kategori pakan; grafik pemakaian pakan menggunakan pilihan tanggal **Dari–Sampai**.
3. **Siapkan data dasar**:
   - Pada **Kandang**, buat kandang beserta kapasitasnya.
   - Pada **Ternak**, tambahkan hewan, kode/tag unik, jenis/ras, dan kandangnya.
   - Pada **Data Pakan Ternak**, tambahkan nama, kategori/jenis, peruntukan, dan stok awal.
4. **Catat operasional**:
   - Pada **Log Pemberian Pakan**, pilih pakan dan kandang, lalu isi tanggal serta jumlah. Sistem memeriksa ketersediaan stok dan mengurangi stok ketika catatan berhasil disimpan.
   - Pada **Rekam Medis**, catat pemeriksaan, diagnosis, tindakan, dan informasi obat/vaksin.
   - Pada **Bobot**, catat hasil penimbangan berdasarkan ternak dan tanggal.
5. **Tinjau data** di masing-masing daftar. Gunakan filter jenis ternak/periode atau pencarian yang tersedia. Menghapus log pemberian pakan mengembalikan jumlahnya ke stok.
6. **Laporan** menampilkan rekap data sesuai periode dan filter jenis ternak yang dipilih.
7. **Pengguna** dapat dikelola melalui menu User yang tampil untuk admin. Menu lain yang terlihat menyesuaikan peran akun.

Untuk memakai aplikasi dari komputer lain, jalankan web server pada komputer host dan akses alamat jaringan host; pastikan firewall serta konfigurasi MySQL mengizinkan koneksi yang diperlukan.

## Struktur data utama

Database menggunakan tabel `users`, `kandang`, `ternak`, `log_kesehatan`, `riwayat_bobot`, `pakan`, dan `log_pemberian_pakan`. Relasi antar tabel memakai foreign key. Kolom status kesehatan ternak mendukung status **Sehat**, **Sakit**, dan **Karantina**.

## Catatan pengembangan

- Tidak ada dependency manager PHP/JavaScript yang diperlukan untuk menjalankan aplikasi; aset UI dan grafik dimuat melalui CDN.
- Data contoh yang disediakan oleh `schema.sql` hanya akun pengguna awal. Data kandang, ternak, pakan, dan aktivitas perlu dimasukkan melalui aplikasi atau disiapkan terpisah.
- Jangan mengimpor ulang `schema.sql` ke database yang sudah berisi akun awal tanpa memeriksa data terlebih dahulu karena proses insert akun dapat gagal akibat username duplikat.