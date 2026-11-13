---
name: ecavo-modern-agro
description: Modern Agro-Food & FMCG Staples UI/UX design system inspired by Ecavo (ecavo.in), Majoo, and Pano. Features clean forest green (#2D5A3D / #0F5132) aesthetics, clutter-free layouts, high-contrast dashboards, verified real product images, and seamless POS checkout.
---

# Ecavo Modern Agro-Food & POS Design System

Standar arsitektur antarmuka pengguna (UI/UX) modern untuk platform ritel, sembako, makanan olahan, dan kasir POS UMKM, terinspirasi langsung dari **Ecavo Staples (`ecavo.in`)**, **Majoo**, dan **Pano SaaS**.

---

## 1. Filosofi Desain: Purity, Simplicity & High-Contrast

1. **Bebas Polusi Informasi (Zero Clutter):**
   - Tidak ada banner pengumuman WhatsApp/telepon yang menempel di bagian paling atas layar (*"ga usah ada wa"*).
   - Halaman login dan register tidak dipenuhi teks panjang berbelit-belit (*"jangan banyak tulisan di kiri itu"*). Sisi kiri dibuat minimalis, bersih, dan berwibawa dengan logo autentik, tagline singkat, dan kartu ringkas berestetika tinggi.
2. **Palet Warna Segar & Berwibawa (Ecavo Agro Palette):**
   - **Deep Agro Forest Green:** `#0F5132` / `#2D5A3D` &mdash; Melambangkan kemurnian pangan, stabilitas wirausaha, dan hasil tani berkualitas.
   - **Vibrant Emerald & Leaf:** `#16A34A` / `#22C55E` &mdash; Aksen tombol aktif, badge stok cukup, dan highlight harga.
   - **Warm Organic Canvas:** `#FAF5E8` / `#FAF9F6` &mdash; Memberikan rasa hangat, bersih, dan natural.
   - **Card & Surface Pure White:** `#FFFFFF` dengan bayangan lembut `0 4px 20px -2px rgba(15, 81, 50, 0.08)`.
3. **Tipografi Bersih & Elegan:**
   - Heading: `Playfair Display` &mdash; Memberikan sentuhan artisanal dan kualitas produk unggulan.
   - UI & Body: `Plus Jakarta Sans` &mdash; Modern, sangat mudah dibaca (*high-legibility*) di layar kasir maupun mobile.
   - Monospace: `JetBrains Mono` untuk nomor SKU, invoice, dan kalkulasi uang.

---

## 2. Standar Visual Produk (100% Real & Verified)

Setiap produk UMKM **wajib sinkron 100% antara nama barang dan foto visualnya**:
- **Minyak Goreng Sawit:** Menampilkan botol/kemasan minyak goreng jernih keemasan (Bukan foto rumah atau properti).
- **Madu Hutan Murni:** Menampilkan toples madu murni keemasan dengan honey dipper (Bukan buah semangka).
- **Beras, Gula, Keripik, Kopi, dsb:** Menggunakan gambar riil berkualitas tinggi yang menggugah selera.
- Semua URL foto diverifikasi mengembalikan HTTP 200 dan diuji responsivitasnya.

---

## 3. Komponen Utama Antarmuka

### A. Minimalist Auth Screen (Login & Register)
- **Kolom Kiri (Brand Showcase):**
  - Logo SisaSense Agro dengan sentuhan hijau tua alami.
  - Judul tegas: *"SisaSense Agro & POS"*.
  - Satu kalimat deskripsi ringkas tanpa paragraf panjang.
  - Kartu preview visual ringkas (status POS aktif & jaminan stok FEFO).
- **Kolom Kanan (Formulir Masuk):**
  - Form username/email dan kata sandi dengan toggle intip kata sandi (`reveal password`).
  - Checkbox *"Ingat sesi saya"* & jaminan enkripsi aman.
  - Tombol aksi utama berbentuk pill gelap berkelas.
  - Kata sandi default demo disederhanakan menjadi `123` untuk kemudahan pengujian.

### B. High-Contrast Dashboard Hero Banner
- Menggunakan gradient hijau hutan pekat (`linear-gradient(135deg, #0D3E24 0%, #175432 60%, #0F3820 100%)`).
- Teks judul dan deskripsi berwarna putih kontras tinggi dengan bayangan tipis agar selalu terbaca tajam pada segala jenis layar.
- Tombol aksi "+ Stok Masuk" dan "Buka Kasir POS" memiliki latar belakang dan batas yang kontras dan jelas (tidak pernah putih di atas putih).

### C. Kasir POS & Pop-up Modal Checkout
- **Katalog Produk:** Grid responsif dengan kartu berfoto riil, label harga jelas, dan badge stok FEFO.
- **Drawer Ringkasan Kasir:** Mengakumulasi belanjaan dengan kontrol kuantitas `[-] [qty] [+]`.
- **Tombol Checkout & Modal Multi-Metode:**
  - Menampilkan modal pop-up bersih dengan tab metode bayar:
    1. **Tunai (Cash):** Kalkulasi kembalian otomatis real-time dengan tombol cepat nominal.
    2. **QRIS:** QR code standar nasional dengan input nomor referensi.
    3. **Transfer Bank:** Pilihan rekening resmi (BCA, Mandiri, BRI) dengan fitur salin instan.
    4. **Kartu Debit / EDC:** Pilihan mesin EDC bank dan input nomor kartu.
  - Penyelesaian transaksi langsung menghasilkan struk nota thermal resmi dan mengurangi stok sesuai alokasi batch kedaluwarsa terdekat (FEFO).

