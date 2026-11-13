# Dokumentasi API Publik KlikObat (Kelompok 22)

API ini menyediakan data katalog obat dalam format JSON. Seluruh endpoint bersifat **publik** (tanpa login), hanya menerima **GET**, dan dipakai oleh frontend (`frontend/assets/js/katalog.js` dan `modal.js`) melalui Fetch API.

- **Lokasi file:** folder `api/`
- **Format respons:** `application/json; charset=UTF-8`
- **Basis data:** MySQL `klikobat` (lihat [`database.md`](database.md))
- **Keamanan:** semua query memakai *prepared statement* sehingga aman dari SQL Injection.

## Format respons umum

Semua endpoint mengembalikan objek dengan tiga properti yang sama:

| Properti | Tipe | Keterangan |
|---|---|---|
| `status` | string | `"success"` jika berhasil, `"error"` jika gagal |
| `message` | string | Pesan singkat tentang hasil permintaan |
| `data` | array / object / null | Isi data. Pada kegagalan: `[]` (daftar) atau `null` (detail) |

> **Catatan:** Respons kegagalan aplikasi (misalnya data tidak ditemukan) tetap berstatus HTTP `200`. Pemanggil harus memeriksa properti `status`, bukan hanya kode HTTP. Pengecualian: jika koneksi database gagal, `config/koneksi.php` mengirim HTTP `500` dengan `status: "error"`.

---

## 1. Daftar Kategori

| | |
|---|---|
| **URL** | `/api/kategori.php` |
| **Method** | `GET` |
| **Parameter** | Tidak ada |
| **Deskripsi** | Mengambil seluruh kategori obat |

**Contoh permintaan**

```
GET /api/kategori.php
```

**Contoh respons (`200`)**

```json
{
  "status": "success",
  "message": "Data kategori berhasil diambil",
  "data": [
    { "id_kategori": 1, "nama_kategori": "Pereda Nyeri & Demam" },
    { "id_kategori": 2, "nama_kategori": "Obat Batuk, Pilek & Flu" },
    { "id_kategori": 3, "nama_kategori": "Saluran Pencernaan" },
    { "id_kategori": 4, "nama_kategori": "Vitamin & Suplemen" },
    { "id_kategori": 5, "nama_kategori": "Antiseptik & Perawatan Luka" }
  ]
}
```

---

## 2. Daftar Obat (dengan filter dan pencarian)

| | |
|---|---|
| **URL** | `/api/obat.php` |
| **Method** | `GET` |
| **Deskripsi** | Mengambil daftar obat, dapat disaring berdasarkan kategori dan kata kunci |
| **Urutan** | `id_obat` menurun (obat terbaru lebih dulu) |

**Parameter query (semuanya opsional)**

| Parameter | Tipe | Keterangan | Contoh |
|---|---|---|---|
| `id_kategori` | integer | Hanya tampilkan obat pada kategori tersebut | `/api/obat.php?id_kategori=1` |
| `q` | string | Kata kunci; dicocokkan (`LIKE %kata%`) dengan **`nama_obat` atau `indikasi`** | `/api/obat.php?q=demam` |

Kedua parameter dapat digabung, contoh: `/api/obat.php?id_kategori=1&q=nyeri`. Jika tidak ada obat yang cocok, `status` tetap `"success"` dengan `data` berupa array kosong.

**Contoh respons (`200`)**

```json
{
  "status": "success",
  "message": "Data obat berhasil diambil",
  "data": [
    {
      "id_obat": 1,
      "nama_obat": "Paracetamol 500 mg (Strip 10 Tablet)",
      "id_kategori": 1,
      "harga": "3500.00",
      "gambar": "paracetamol.png",
      "stok": 45,
      "indikasi": "Meredakan demam serta nyeri ringan hingga sedang seperti sakit kepala, sakit gigi, dan nyeri otot.",
      "aturan_pakai": "Dewasa: 1-2 tablet, 3-4 kali sehari sesudah makan. Maksimal 8 tablet per hari.",
      "peringatan": "Hati-hati penggunaan pada penderita gangguan fungsi hati dan ginjal. Jangan melebihi dosis yang dianjurkan."
    }
  ]
}
```

**Penjelasan field**

| Field | Tipe | Keterangan |
|---|---|---|
| `id_obat` | integer | ID obat |
| `nama_obat` | string | Nama dan sediaan obat |
| `id_kategori` | integer | ID kategori (relasi ke `kategori`) |
| `harga` | string (desimal) | Harga per kemasan dalam Rupiah, misalnya `"3500.00"`. Dikirim sebagai string karena kolom bertipe `DECIMAL`; ubah dengan `Number()` jika perlu |
| `gambar` | string | **Nama file** gambar (bukan URL), misalnya `paracetamol.png`. Berkas berada di `assets/images/obat/` |
| `stok` | integer | Jumlah stok. Status stok dihitung di sisi aplikasi: `0` = Habis, `1-10` = Menipis, `>10` = Tersedia |
| `indikasi` | string | Kegunaan obat |
| `aturan_pakai` | string | Dosis dan cara pakai |
| `peringatan` | string | Peringatan dan kontraindikasi |

---

## 3. Detail Obat

| | |
|---|---|
| **URL** | `/api/obat_detail.php` |
| **Method** | `GET` |
| **Deskripsi** | Mengambil detail satu obat untuk ditampilkan pada modal edukasi |

**Parameter query**

| Parameter | Tipe | Wajib | Keterangan | Contoh |
|---|---|---|---|---|
| `id` | integer | Ya | ID obat | `/api/obat_detail.php?id=1` |

Field yang dikembalikan sama dengan endpoint daftar obat, ditambah **`gambar_url`**: lokasi gambar relatif terhadap folder utama proyek (`assets/images/obat/<gambar>`). Jika kolom `gambar` kosong, nilainya `assets/images/obat/default.jpg`.

**Contoh respons berhasil (`200`)**

```json
{
  "status": "success",
  "message": "Detail obat berhasil diambil",
  "data": {
    "id_obat": 1,
    "nama_obat": "Paracetamol 500 mg (Strip 10 Tablet)",
    "id_kategori": 1,
    "harga": "3500.00",
    "gambar": "paracetamol.png",
    "stok": 45,
    "indikasi": "Meredakan demam serta nyeri ringan hingga sedang seperti sakit kepala, sakit gigi, dan nyeri otot.",
    "aturan_pakai": "Dewasa: 1-2 tablet, 3-4 kali sehari sesudah makan. Maksimal 8 tablet per hari.",
    "peringatan": "Hati-hati penggunaan pada penderita gangguan fungsi hati dan ginjal. Jangan melebihi dosis yang dianjurkan.",
    "gambar_url": "assets/images/obat/paracetamol.png"
  }
}
```

**Contoh respons gagal**

ID tidak dikirim:

```json
{ "status": "error", "message": "Parameter ID obat tidak ditemukan", "data": null }
```

ID tidak ada di database (misalnya `?id=999`):

```json
{ "status": "error", "message": "Data obat tidak ditemukan", "data": null }
```

---

## Cara menguji cepat

Setelah Apache dan MySQL berjalan dan `database/klikobat.sql` telah diimpor, buka URL berikut di browser (sesuaikan alamat dasarnya dengan lokasi proyek Anda):

```
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/api/kategori.php
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/api/obat.php
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/api/obat.php?id_kategori=1&q=nyeri
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/api/obat_detail.php?id=1
```

Atau dengan `curl`:

```bash
curl "http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_22/api/obat_detail.php?id=1"
```

## Catatan penggunaan oleh frontend

- `frontend/index.html` memuat `frontend/assets/js/katalog.js`, yang mengambil `kategori.php` dan `obat.php` satu kali saat halaman dibuka, lalu melakukan pencarian dan penyaringan **di sisi browser**. Parameter `q` dan `id_kategori` tetap tersedia untuk pemanggil lain, tetapi tidak dipakai oleh pencarian interaktif halaman katalog.
- Modal edukasi pada katalog saat ini menampilkan data dari daftar obat yang sudah dimuat (`window.bukaModalDetail(o)` di `katalog.js`). Endpoint `obat_detail.php` dan berkas `frontend/assets/js/modal.js` (yang memanggil `obat_detail.php?id=<id_obat>`) tersedia, tetapi **belum disambungkan** ke `index.html`.
