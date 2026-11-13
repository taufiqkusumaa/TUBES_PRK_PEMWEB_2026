# Dokumentasi CRUD Obat (Admin) - KlikObat

## 1. Ringkasan Fitur

CRUD Obat merupakan fitur yang digunakan admin untuk mengelola data obat pada sistem KlikObat, mulai dari menambahkan, melihat, mengubah, hingga menghapus data.

| Operasi | File | Fungsi |
|---|---|---|
| Create | `tambah.php` | Menambahkan data obat |
| Read | `index.php` | Menampilkan daftar obat |
| Update | `ubah.php` | Mengubah data obat |
| Delete | `hapus.php` | Menghapus data obat |

Modul ini menggunakan `config/koneksi.php` untuk menghubungkan sistem ke database dan `config/helper.php` untuk menentukan status stok obat.

## 2. Data Obat

Data obat disimpan dalam tabel `obat` dengan beberapa kolom berikut:

- `id_obat`: ID setiap obat.
- `nama_obat`: Nama obat.
- `id_kategori`: Kategori obat.
- `harga`: Harga per kemasan dalam Rupiah.
- `stok`: Jumlah stok obat.
- `indikasi`: Deskripsi dan kegunaan obat.
- `aturan_pakai`: Aturan penggunaan dan dosis.
- `peringatan`: Informasi peringatan penggunaan obat.
- `gambar`: Nama file gambar obat.

## 3. Penjelasan Fitur CRUD

**a. Daftar Obat (`index.php`)**

Halaman ini menampilkan daftar obat yang sudah tersimpan di database. Informasi yang ditampilkan meliputi gambar (thumbnail), nama obat, kategori, harga, stok, status stok, serta tombol untuk mengubah dan menghapus data.

**b. Tambah Obat (`tambah.php`)**

Admin dapat menambahkan obat melalui form yang berisi nama obat, kategori, harga, stok, gambar (opsional), indikasi, aturan pakai, dan peringatan. Data akan diperiksa terlebih dahulu sebelum disimpan ke database.

**c. Ubah Obat (`ubah.php`)**

Halaman ini digunakan untuk memperbarui data obat. Form akan menampilkan data sebelumnya sehingga admin bisa langsung mengubah bagian yang diperlukan, termasuk mengganti gambar obat.

**d. Hapus Obat (`hapus.php`)**

Fitur ini digunakan untuk menghapus data obat berdasarkan ID. Proses penghapusan dilakukan melalui metode POST dan dilengkapi konfirmasi sebelum data dihapus.

## 4. Upload Gambar

Proses upload gambar ditangani oleh `gambar_helper.php`. Beberapa ketentuannya yaitu:

- Gambar boleh tidak diisi.
- Format yang diterima adalah JPG, JPEG, dan PNG.
- Ukuran gambar maksimal 2 MB.
- Nama file dibuat secara acak agar tidak terjadi kesamaan nama.
- Gambar disimpan di folder `assets/images/obat/`.
- Jika admin tidak mengganti gambar saat mengubah data, gambar sebelumnya tetap digunakan.
- Gambar lama akan dihapus ketika diganti atau data obat dihapus, kecuali gambar `default.jpg`.

## 5. Status Stok

Status stok ditentukan secara otomatis menggunakan fungsi `getStatusStok()` yang terdapat pada `helper.php`.

| Kondisi Stok | Status |
|---|---|
| 0 | Habis |
| 1–10 | Menipis |
| Lebih dari 10 | Tersedia |

Status tersebut dihitung berdasarkan jumlah stok obat, sehingga tidak perlu disimpan sebagai kolom tersendiri di database.

## 6. Validasi dan Keamanan

Beberapa pemeriksaan dan pengamanan yang diterapkan pada modul ini:

- Memastikan data yang wajib diisi tidak kosong.
- Memeriksa agar stok berupa angka bulat dan tidak bernilai negatif.
- Memeriksa agar harga berupa angka bulat 0 sampai 99.999.999 (tanpa titik atau koma).
- Memastikan kategori yang dipilih benar-benar ada di database.
- Membatasi nama obat maksimal 150 karakter.
- Menggunakan prepared statement pada query database.
- Menggunakan `htmlspecialchars()` untuk mengamankan data yang ditampilkan.
- Membatasi proses penghapusan melalui metode POST.
- Memeriksa format dan ukuran gambar sebelum disimpan.
- Seluruh halaman CRUD obat dilindungi `admin/auth.php`; admin yang belum login dialihkan ke halaman login.

## 7. Struktur File

```text
admin/
└── obat/
    ├── index.php
    ├── tambah.php
    ├── ubah.php
    ├── hapus.php
    └── gambar_helper.php

config/
├── koneksi.php
└── helper.php

assets/
└── images/
    └── obat/
        └── default.jpg
```

## 8. Alur Penggunaan

```text
Daftar Obat
   ├── Tambah Obat → Isi Form → Simpan
   ├── Ubah Obat   → Edit Data → Simpan
   └── Hapus Obat  → Konfirmasi → Hapus
```

## 9. Prasyarat Menjalankan

- Database sudah diimpor dari `database/klikobat.sql`. Berkas ini sudah memuat kolom `gambar` pada tabel `obat`, sehingga **tidak diperlukan migrasi tambahan**.
- File `assets/images/obat/default.jpg` harus tersedia sebagai gambar bawaan.
- Folder `assets/images/obat/` harus dapat ditulis oleh server (agar upload gambar berfungsi).
- Login admin: akses `admin/login.php` dengan akun default `admin` / `password`, lalu buka `admin/obat/index.php`.
- Apache dan MySQL pada XAMPP/Laragon harus aktif.
