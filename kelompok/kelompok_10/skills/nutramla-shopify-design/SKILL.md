---
name: nutramla-shopify-design
description: UI/UX design system and Shopify e-commerce patterns inspired by NutrAmla (nutramla.com / YouTube Shorts cwdA4L5-9nI). Features the signature top navbar with 3-zone layout (left navigation links, center framed badge logo, right utility icons with lime green count badges), the 'Shop Collections' 4-card responsive visual showcase, organic herbal green (#428445 / #2D6A4F) and warm amber (#E58A1F) palette, Playfair Display serif headings, uncluttered full-width navigation (no sidebars), and fixed-footer modal checkout.
---

# NutrAmla Shopify E-Commerce & Agro-Staples Design System

Standar antarmuka pengguna (UI/UX) e-commerce modern terinspirasi langsung dari **NutrAmla (`nutramla.com`)** sebagaimana ditunjukkan pada referensi YouTube Shorts (`https://www.youtube.com/shorts/cwdA4L5-9nI`) dan crop navigasi navbar atas.

---

## 1. Filosofi Desain: Natural Goodness, Top Navbar & Full-Width Canvas

1. **Eliminasi Sidebar Samping (Full-Width Navigation):**
   - Tidak lagi menggunakan navigasi sidebar vertikal (`aside.sidebar`) yang memotong lebar layar.
   - Menggunakan **Navbar Atas Penuh (Top Header Navbar)** yang bersih, responsif, dan menyatu di semua halaman.
   - Konten memiliki ruang pandang luas (*full-width canvas*), meningkatkan kenyamanan belanja dan kemudahan pengoperasian kasir POS.

2. **Struktur Navbar 3 Zona (Persis NutrAmla Header):**
   - **Zona Kiri (Horizontal Menu Links):**
     - Admin: `Beranda`, `Kasir POS`, `Katalog Belanja`, `Kategori`, `Stok & Batch`, `Riwayat Transaksi`, `Analisis & Laporan`, `Saran Restok`.
     - Kasir: `Beranda`, `Kasir POS`, `Katalog Belanja`, `Riwayat Transaksi`, `Cek Stok`.
     - Pembeli: `Beranda`, `Katalog Belanja`, `Kategori`, `Riwayat Pesanan`.
     - Tamu (Guest): `Beranda`, `Katalog Belanja`, `Kategori`, `Tentang UMKM`, `Masuk Akun`.
     - Tipografi: Sans-serif (`Plus Jakarta Sans`), medium/semi-bold, warna charcoal `#222222`, hover hijau segar `#428445`.
   - **Zona Tengah (Center Framed Badge Logo):**
     - Kotak badge persegi berbingkai lengkung (*framed rectangular badge*):
       - Bagian atas (latar putih): `UMKM NUSANTARA®` dengan subteks `& Pangan Alami Indonesia`.
       - Bagian bawah (latar oranye hangat `#E58A1F` / hijau hutan `#166534`): Teks brand tebal `SisaSense®`.
       - Teks aksen bawah badge: `सिसासेंस • ORGANIC POS & GROCERY`.
   - **Zona Kanan (Utility Action Icons):**
     - Ikon Pencarian (`🔍`) menuju pencarian katalog instan.
     - Ikon Profil Pengguna (`👤`) dengan menu *dropdown* akun (menampilkan nama lengkap, role badge, dan tombol *Keluar*).
     - Ikon Favorit/Peringatan (`🤍`) dengan badge lingkaran hijau limau (*lime green* `#A4D65E`).
     - Ikon Tas Belanja / Keranjang POS (`🛍️`) dengan badge lingkaran hijau limau (*lime green* `#A4D65E`) menampilkan jumlah item aktif.

3. **Section Utama: "Shop Collections" (Koleksi Visual 4 Kolom):**
   - Menampilkan 4 kartu koleksi produk horizontal berdampingan dalam satu baris responsif:
     - **Camilan & Snack (Candy / Snacks):** Camilan olahan, kue, dan biskuit.
     - **Pangan Pokok & Sembako (Gifting Kits / Bahan Pokok):** Beras, minyak sawit, gula pasir tebu.
     - **Minuman Segar & Herbal (Juice / Minuman):** Jamu kunyit, madu murni, kopi gula aren.
     - **Oleh-Oleh Tradisional (All Products / Oleh-Oleh):** Dodol garut, abon sapi, lapis legit.
   - Setiap kartu memiliki sudut membulat elegan (`border-radius: 20px`), efek *hover zoom* gambar yang halus, tipografi judul serif di tengah (*centered title*), dan tautan *"Shop Collection &rarr;"*.

4. **Palet Warna Segar & Alami (NutrAmla & Shopify Palette):**
   - **Navbar Canvas:** `#FEFAF0` (warm ivory) / `#FFFFFF` (clean white).
   - **Navbar Text:** `#222222`.
   - **Count Badge Lime:** `#A4D65E` (latar hijau limau dengan teks putih tebal).
   - **Primary Herbal Green:** `#428445` &mdash; Hijau herbal alami khas daun amla dan pangan organik.
   - **Deep Forest Green:** `#2D6A4F` / `#166534` &mdash; Aksen tombol utama, hero banner, dan identitas brand.
   - **Warm Amber Accent:** `#E58A1F` / `#F59E0B` &mdash; Aksen badge logo, rating bintang, dan aksen madu.
   - **Canvas Background:** `#FAF9F6` &mdash; Warm ivory natural.
   - **Headings & Body Ink:** `#151515` (Heading) & `#334155` (Body).

5. **Tipografi Elegan & Terbaca Jelas:**
   - Headings (`h1`, `h2`, `h3`, Collection Titles): `Playfair Display`, serif, bold, anggun dan ramah pembeli.
   - Body text & UI controls: `Plus Jakarta Sans`, sans-serif, bersih dan tajam.
   - Angka & Monospace: `JetBrains Mono` untuk harga, SKU, nomor invoice, dan struk kasir POS.

---

## 2. Arsitektur Modal Checkout Kasir (Anti-Clipped Footer)

Sesuai kendala *"ini kok ga bisa di bayar ya"*, modal dialog pembayaran kasir **wajib menerapkan struktur flexbox terisolasi**:
1. **Modal Dialog:** `max-height: 88vh; display: flex; flex-direction: column; overflow: hidden;`
2. **Formulir Pembayaran (`#posPaymentForm`):** `display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;`
3. **Modal Body (Konten Input & Pilihan Metode):** `overflow-y: auto; flex: 1; min-height: 0;` sehingga dapat di-scroll lancar pada layar laptop/desktop beresolusi berapa pun tanpa mendorong tombol keluar dari layar.
4. **Modal Footer (Tombol Konfirmasi):** `flex-shrink: 0; position: sticky; bottom: 0; z-index: 10;`
   - Tombol **"Konfirmasi Pembayaran & Cetak Struk"** selalu berada di viewport, berlatar hijau segar `#166534`, dengan teks tebal kontras tinggi dan ikon nota.
   - Tombol aktif otomatis saat nominal tunai &ge; total belanjaan atau saat memilih metode non-tunai (QRIS / Transfer / Debit).

---

## 3. Footer E-Commerce 4 Kolom (NutrAmla Footer)

- Kolom 1: Profil Brand SisaSense dengan logo badge framed.
- Kolom 2: Koleksi Pilihan (*Shop Collections*).
- Kolom 3: Navigasi Cepat & Manajemen Toko (Kasir, Alokasi FEFO, Laporan).
- Kolom 4: Layanan Bantuan, Jam Buka Toko (08:00 - 21:00 WIB), dan Metode Pembayaran Aman (Tunai, QRIS, Bank, Debit).
- Baris Hak Cipta & Identitas Mahasiswa Pemrograman Web Kelompok 10.
