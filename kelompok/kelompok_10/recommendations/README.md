# Rekomendasi Restock dan Simulasi Diskon

Halaman (hanya admin):
- `recommendations/restock.php` : rekomendasi restock.
- `recommendations/discount.php` : simulasi diskon.

## Rekomendasi restock
| Besaran | Rumus |
|---|---|
| Periode data | 30 hari terakhir sampai hari ini (hanya `sales.status = 'completed'`) |
| ADS | total `sale_items.qty` periode tersebut / 30 |
| Stok tersedia | jumlah `qty_remaining` batch yang belum kedaluwarsa |
| ROP | (ADS x lead time) + safety stock |
| Status | "Perlu restock" jika stok tersedia <= ROP, selain itu "Stok cukup" |
| Jumlah beli | max(0, target stok - stok tersedia) |

Lead time (hari), safety stock, dan target stok diisi pengguna per produk. Baris yang dikosongkan tidak dihitung.
Jika produk tidak punya penjualan dalam 30 hari, sistem menampilkan bahwa riwayat penjualan belum cukup dan tidak membuat angka rekomendasi.

## Simulasi diskon
- HPP = rata-rata tertimbang `cost_per_unit` dari batch yang masih punya stok dan belum kedaluwarsa.
- Harga setelah diskon = harga jual - (harga jual x diskon / 100).
- Laba per unit = harga setelah diskon - HPP; margin = laba / harga setelah diskon.
- Peringatan ditampilkan jika laba per unit negatif (harga di bawah HPP).

