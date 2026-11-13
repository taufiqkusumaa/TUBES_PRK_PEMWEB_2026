---
name: artisan-pos-design
description: Design system, UI/UX guidelines, color palettes, and multi-method modal checkout patterns for artisanal snack e-commerce and POS cashier systems inspired by NutrAmla (nutramla.com / YouTube Shorts cwdA4L5-9nI), Ecavo (ecavo.in), and Majoo.
---

# NutrAmla & Ecavo Modern Agro-Shop POS Design System

Panduan desain visual, arsitektur antarmuka (UI), pengalaman pengguna (UX), dan pola interaksi kasir point-of-sale modern untuk UMKM ritel makanan, camilan, dan sembako, mengadopsi prinsip kemurnian dan etalase **NutrAmla (`nutramla.com`)**, **Ecavo (`ecavo.in`)**, dan **Majoo Design**.

---

## 1. Core Visual Foundations (NutrAmla Herbal & Forest Palette)

* **Canvas Primer (Warm Organic Ivory):** `#FAF9F6` &mdash; Nuansa alami, hangat, dan ramah pembeli.
* **Surface Card:** `#FFFFFF` &mdash; Kontras bersih dengan border halus `#E2E8F0` atau `#EAEFEA`.
* **Primary Brand (Herbal & Deep Forest Green):**
  * Utama: `#428445` / `#2D6A4F` (Nuansa herbal, kemurnian daun amla dan pangan alami).
  * Aksen & Hover: `#166534` / `#1B4332`.
  * Soft Tint (Badge & Pill Active): `#F4F9F4` / `#ECFDF5`.
  * Gold / Honey Accent: `#E0B252` / `#D4A373`.
* **Dark Contrast Ink:** `#151515` / `#0F172A` untuk judul produk, teks utama, dan tombol primer.

---

## 2. Signature "Shop Collections" 4-Card Grid (NutrAmla Style)

Etalase utama menampilkan 4 kartu koleksi visual horizontal berdampingan:
1. **Camilan & Snack (Candy):** Foto croissant, keripik balado, dan kue kering.
2. **Sembako & Pangan Pokok (Gifting Kits):** Foto minyak goreng sawit, beras ramos, gula tebu.
3. **Minuman Segar & Kopi (Juice):** Foto kopi gula aren, jamu kunyit asam, teh melati.
4. **Oleh-Oleh Tradisional (All Products):** Foto dodol garut, abon sapi, lapis legit, sambal bawang.

Setiap kartu memiliki:
- Foto proporsional dengan sudut membulat besar (`border-radius: 20px`).
- Efek *hover subtle zoom* (`transform: scale(1.03)`).
- Judul elegan berhuruf serif (`Playfair Display`).
- Tautan aksi *"Jelajahi Koleksi &rarr;"* (*"Shop Collection"*).

---

## 3. Solusi Tuntas Modal Checkout Kasir ("Anti-Clipped Footer")

Untuk mengatasi kendala tombol pembayaran tidak dapat diklik atau terpotong:
- `.modal-dialog`: `max-height: 88vh; display: flex; flex-direction: column; overflow: hidden;`
- `#posPaymentForm`: `display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;`
- `.modal-body`: `overflow-y: auto; flex: 1; min-height: 0;` (area formulir dapat digeser dengan scroll halus).
- `.modal-footer`: `flex-shrink: 0; position: sticky; bottom: 0;`
  - Tombol **"Konfirmasi Pembayaran & Cetak Struk"** selalu tampak di layar (*always visible*), berwarna hijau daun tegas `#166534`, dengan teks tebal kontras tinggi.
  - Nilai nominal uang tunai default otomatis disesuaikan dengan total tagihan (*uang pas*), sehingga tombol langsung aktif.

---

## 4. Multi-Metode Pembayaran Kasir
1. **Tunai (Cash):** Nominal input besar + tombol nominal cepat (`Uang Pas`, `+10rb`, `+20rb`, `+50rb`, `+100rb`) & kalkulasi kembalian real-time.
2. **QRIS:** Frame QR Code resmi dengan input nomor RRN / referensi.
3. **Transfer Bank:** Pilihan rekening resmi (BCA, Mandiri, BRI) dengan fitur salin instan.
4. **Kartu Debit / EDC:** Pilihan bank & input nomor kartu.
5. **Cetak Struk:** Konfirmasi instan langsung menerbitkan struk POS kasir format thermal.
