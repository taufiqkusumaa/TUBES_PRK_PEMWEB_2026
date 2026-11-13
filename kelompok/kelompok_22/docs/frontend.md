# Frontend Katalog KlikObat

Dokumen ini menjelaskan struktur dan perilaku halaman katalog publik di `frontend/`.

## Teknologi dan berkas

Frontend menggunakan HTML5, CSS3, dan Vanilla JavaScript tanpa framework.

- `frontend/index.html` — struktur halaman, navigasi, pencarian, kategori, area katalog, dan modal edukasi obat.
- `frontend/assets/css/style.css` — gaya visual, layout responsif, kartu obat, status stok, state loading, harga, gambar, dan modal.
- `frontend/assets/js/katalog.js` — pemuatan data, fallback lokal, filter, pencarian, pengurutan, render kartu, gambar produk, harga, dan interaksi modal.

## Alur pemuatan data

Saat halaman dibuka, `katalog.js` mengambil kategori dari `../api/kategori.php` dan daftar obat dari `../api/obat.php` menggunakan Fetch API. Respons API diharapkan memiliki array pada properti `data`. Data obat dapat mencakup harga dan nama file gambar.

Jika salah satu endpoint gagal, timeout, mengembalikan JSON tidak valid, atau mengirim `status: "error"`, frontend menggunakan fallback lokal untuk kategori dan obat. Fallback tersebut mengikuti data seed pada `database/klikobat.sql`. Dalam kondisi ini, halaman menampilkan pemberitahuan bahwa data lokal digunakan dan jumlah stok mungkin tidak terbaru. Harga tidak ditampilkan pada mode fallback karena data fallback tidak menyediakan harga yang dapat diandalkan.

Untuk melihat data terkini dari database, jalankan halaman melalui server PHP yang dapat mengakses API dan database. Jika hanya dibuka lewat server file statis seperti Live Server, permintaan PHP dapat gagal sehingga katalog beralih ke fallback lokal.

## Pencarian, filter, dan urutan

Setelah daftar obat dimuat, pencarian berlangsung di browser dengan debounce 300 ms. Pencarian lokal mencocokkan nama obat, indikasi, dan nama kategori. Tombol bersihkan mengosongkan kata kunci; tombol `/` pada keyboard memfokuskan kolom pencarian.

Kategori dirender dari data API atau fallback. Memilih kategori memperbarui filter katalog tanpa memuat ulang halaman. Filter kategori dan kata kunci dapat digunakan bersamaan. Hasil yang terlihat diurutkan alfabetis berdasarkan nama obat dengan aturan pengurutan bahasa Indonesia. Detail parameter pencarian API `q` tersedia di [`api.md`](api.md); pencarian interaktif halaman saat ini difilter di sisi frontend setelah data daftar dimuat.

## Informasi pada kartu dan status stok

Kartu obat menampilkan kategori, foto produk jika tersedia, nama dan sediaan obat, harga per kemasan jika tersedia dari API, indikasi, aturan pakai, jumlah stok, dan tombol **Detail**. Foto menggunakan nama file yang dikembalikan API. Jika record masih memakai `default.jpg`, katalog menggunakan pemetaan aset lokal yang tersedia untuk obat seed; jika foto tidak ditemukan atau gagal dimuat, katalog menampilkan ilustrasi botol SVG sebagai fallback. Ilustrasi tersebut bersifat generik dan bukan representasi kemasan obat tertentu.

Harga ditampilkan dalam format Rupiah hanya jika katalog berhasil mengambil data API dan harga bernilai lebih dari nol. Harga disembunyikan pada mode fallback atau jika nilainya kosong/tidak valid.

Status stok dihitung berdasarkan jumlah unit:

- **Tersedia** jika stok lebih dari 10 unit.
- **Menipis** jika stok 1–10 unit.
- **Habis** jika stok 0 unit.

## Detail edukasi obat

Modal detail hanya terbuka melalui tombol **Detail** pada kartu. Modal menampilkan kategori, foto produk atau fallback ilustrasi, nama dan sediaan obat, harga jika tersedia, status dan jumlah stok, indikasi, aturan pakai, serta peringatan. Modal dapat ditutup melalui tombol silang, klik pada backdrop di luar kotak modal, atau tombol `Escape`.

Saat modal terbuka, scroll halaman dikunci dan lebar scrollbar dikompensasikan untuk mencegah layout bergeser. Ketika modal ditutup, penguncian scroll serta kompensasi lebar dipulihkan.

## State katalog

Saat daftar sedang dimuat, halaman menampilkan skeleton kartu. Jika filter atau pencarian tidak menemukan obat, halaman menampilkan empty state dengan tombol untuk menghapus filter dan menampilkan kembali semua obat.

## Batasan data

Harga dan gambar bergantung pada data yang tersimpan pada API/database. Record obat yang masih menggunakan nama gambar `default.jpg` akan memakai pemetaan foto lokal jika tersedia; obat tanpa foto yang cocok akan memakai ilustrasi fallback. Harga bernilai nol, kosong, atau tidak valid tidak ditampilkan. Informasi aturan pakai dan peringatan bersifat edukatif, bukan pengganti saran tenaga kesehatan.
