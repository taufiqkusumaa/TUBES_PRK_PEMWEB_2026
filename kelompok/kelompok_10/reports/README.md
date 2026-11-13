# Laporan 

Halaman:
- `reports/index.php` : menu laporan (kasir hanya melihat laporan penjualan).
- `reports/sales.php` : laporan penjualan; filter tanggal, kasir (admin), nomor invoice; ekspor CSV `export-sales.php`.
- `reports/sales-detail.php` : detail item transaksi (harga snapshot, HPP snapshot, diskon, pendapatan, laba kotor item).
- `reports/inventory.php` : laporan persediaan dan kedaluwarsa (admin); ekspor CSV `export-inventory.php`.
- `reports/nav.php` : daftar menu sidebar bersama untuk modul rekomendasi dan laporan.

## Cara menguji manual
Gunakan data Mi Instan dari panduan (stok masuk 100, jual 10).
1. Buka `/reports/sales.php`: invoice muncul. Klik Detail: 10 unit, HPP Rp2.500 per unit, laba kotor Rp10.000.
2. Filter dengan tanggal awal setelah tanggal akhir: muncul pesan kesalahan.
3. Admin memilih kasir tertentu: hanya transaksi kasir itu yang tampil. Export CSV memakai filter yang sama.
4. Login sebagai kasir: filter kasir tidak tampil, hanya transaksi sendiri, dan `/reports/inventory.php` menghasilkan "Akses ditolak".
5. Buka `/reports/inventory.php`: stok Mi Instan 90. Buka Export CSV dan cocokkan angkanya.
6. Void transaksi di modul POS: transaksi hilang dari laporan penjualan.
