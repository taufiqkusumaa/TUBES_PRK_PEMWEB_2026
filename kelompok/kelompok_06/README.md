# FlashPOS - Sistem Point of Sale (FEB)

**🔗 Link Repository GitHub:** https://github.com/DihanSae29/TUBES_PRK_PEMWEB_2026/tree/main/kelompok/kelompok_06

FlashPOS adalah aplikasi Point of Sale (Kasir) berbasis web yang dirancang khusus untuk memenuhi kebutuhan transaksi bisnis dengan fitur lengkap, termasuk manajemen inventaris, perhitungan pajak/servis, dan pembayaran tagihan pisah (*split bill*). Sistem ini dikembangkan menggunakan PHP Native, Tailwind CSS, dan MySQL dengan perlindungan keamanan *Prepared Statements* dan CSRF Token.

## 👥 Tim Pengembang (Kelompok 06)
- **Dihan Ahmad Al-Hasyir Sae (2415061045)** - Ketua / Integrator, Terminal Kasir & Split Bill
- **Nabila Alya Chalisa** - Modul Autentikasi & Session
- **Delvina Nur** - Modul CRUD Data Master (Kategori & Produk)
- **Fakhry Ramadhan Subur** - Modul Terminal Kasir & Split Bill
- **Caesar Abdurrahman** - Modul Admin Dashboard & Laporan

## 🚀 Fitur Utama
1. **Keamanan Lanjutan:** Form login dan transaksi sepenuhnya kebal dari SQL Injection berkat implementasi `mysqli_prepare()` dan verifikasi *password hashing*.
2. **Manajemen Produk:** Sistem inventaris dinamis dengan auto-generate kode produk berbasis kategori, dilengkapi proteksi `GET_LOCK` pada database.
3. **Sistem Kasir Fleksibel:** Mendukung metode pembayaran tunai, QRIS, dan Debit.
4. **Split Bill (Tagihan Pisah):** Kemampuan membayar satu struk pesanan secara dicicil (nominal bebas) atau berdasarkan item spesifik yang dipilih.
5. **Kalkulasi Otomatis:** Perhitungan subtotal, potongan diskon, PPN, dan *Service Charge* secara *real-time*.

## 🛠️ Persyaratan Sistem
- Web Server: Apache (melalui XAMPP / Laragon)
- PHP: Versi 8.0 atau lebih baru
- Database: MySQL / MariaDB
- Browser: Google Chrome, Mozilla Firefox, atau browser modern lainnya

## ⚙️ Panduan Instalasi
1. Ekstrak file zip ini dan letakkan folder `kelompok_06` ke dalam direktori *document root* web server lokal Anda (`htdocs` untuk XAMPP atau `www` untuk Laragon).
2. Buka aplikasi phpMyAdmin (biasanya melalui `http://localhost/phpmyadmin`).
3. Buat database baru dengan nama: `flashpos`.
4. Pilih tab **Import**, lalu unggah file `database/flashpos.sql` yang telah disediakan.
5. Konfigurasi kredensial database jika diperlukan pada file `config/database.php`.

## 📖 Cara Penggunaan
1. Akses aplikasi melalui browser dengan mengunjungi: `http://localhost/kelompok_06/`
2. Untuk login pertama kali, silakan gunakan fitur pendaftaran (Registrasi Kasir) pada halaman awal untuk membuat akun baru dengan *role* Admin/Kasir.
3. **Data Master:** Akses menu "Produk" untuk menambahkan kategori dan barang jualan.
4. **Transaksi:** Buka menu "Kasir" untuk mulai melayani pelanggan, memasukkan item ke keranjang, dan melakukan *checkout*.
5. **Pembayaran:** Pilih order yang berstatus *Pending* untuk memproses pelunasan (baik secara penuh maupun *Split Bill*).

## 📂 Dokumentasi
- **Entity Relationship Diagram (ERD):** Dapat dilihat pada folder `docs/erd_flashpos.png`
- **Screenshot Aplikasi:** Tersedia di dalam folder `docs/`