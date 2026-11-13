# Aturan Pengerjaan ATTENDARA

Dokumen ini menetapkan aturan perilaku aplikasi yang melengkapi [ERD](ERD.md) dan skema [database](../database/attendara.sql). Implementasi PHP yang ada masih perlu dimigrasikan ke alur dan skema ini.

## 1. Semester, kelas, dan roster

- Admin menetapkan tepat satu semester berstatus `aktif`. Semester lain berstatus `direncanakan` atau `selesai`.
- Setiap baris `kelas` adalah rombongan belajar pada satu semester. Kode kelas boleh digunakan kembali pada semester berbeda; pasangan semester dan kode kelas harus unik.
- Form penempatan siswa, penugasan, dan jadwal hanya menawarkan kelas dari semester aktif. Data semester lalu tetap disimpan untuk arsip.
- Satu siswa hanya masuk ke satu kelas pada semester yang sama. Perpindahan kelas tengah semester belum termasuk alur standar; bila diperlukan, riwayat tanggal perpindahan harus dirancang.
- Roster sesi dibuat dari anggota kelas yang aktif saat sesi dimulai. Baris absensi yang dibuat saat itu menjadi roster tetap untuk sesi tersebut.

## 2. Penugasan dan jadwal

- Satu `penugasan_guru` menghubungkan satu Guru, satu mata pelajaran, dan satu kelas. Guru boleh memiliki banyak penugasan untuk berbagai mata pelajaran maupun kelas.
- Satu penugasan dapat memiliki beberapa jadwal. Sesi harus dimulai dari salah satu jadwal penugasan tersebut.
- Jadwal Guru atau kelas tidak boleh bertabrakan dengan jadwal lain yang berbagi Guru atau kelas yang sama.
- Bentrok terjadi jika jadwal mempunyai hari yang sama, waktu beririsan, dan masa berlakunya memiliki setidaknya satu tanggal pelaksanaan yang sama.
- Rentang waktu dibaca sebagai `[jam_mulai, jam_selesai)`. Contoh: jadwal 08.00–09.00 dan 09.00–10.00 tidak bentrok.
- Masa berlaku kosong mengikuti tanggal awal dan akhir semester kelas. Bila masa berlaku diisi, jadwal hanya berlaku pada rentang tersebut.
- Pemeriksaan bentrok mencakup semua mata pelajaran untuk Guru yang sama dan semua Guru/mata pelajaran untuk kelas yang sama. Kelas semester lampau tidak menjadi pilihan jadwal aktif.
- Pemeriksaan bentrok dilakukan di server saat jadwal dibuat atau diubah. Pemeriksaan dan penyimpanan harus berada dalam transaksi agar dua permintaan bersamaan tidak bisa menyimpan bentrok.
- Jadwal yang sudah memiliki sesi tidak dihapus secara fisik. Jadwal lama dinonaktifkan atau diarsipkan agar sesi terdahulu tetap dapat ditelusuri.

## 3. Sesi pembelajaran

- Guru hanya dapat memulai sesi dari jadwal milik penugasannya pada semester aktif dan dalam masa berlaku jadwal.
- Saat sesi dimulai, simpan waktu mulai aktual, status `berlangsung`, jumlah tap `0`, dan roster absensi dari kelas penugasan.
- Sesi berjalan mencatat waktu selesai aktual saat diselesaikan. Waktu rencana tetap berasal dari jadwal.
- Sesi berstatus `selesai` tidak dapat diubah lagi: jumlah tap, waktu, absensi, dan poin dikunci.
- Interpretasi pengecualian pembatalan: sesi selesai boleh diubah statusnya menjadi `dibatalkan`, tetapi pembatalan tidak membuka kembali data sesi untuk diedit. Sesi batal tetap menjadi riwayat dan tidak dihitung sebagai sesi selesai. Jika perlu memperbaiki catatan, batalkan sesi lama lalu buat sesi pengganti melalui jadwal yang sesuai.
- Sesi berlangsung dapat dibatalkan Guru pemilik penugasan. Sesi batal tidak menerima tap, perubahan absensi, atau poin.
- Kode saat ini hanya mempunyai status dan belum menyimpan metadata pembatalan. Sebelum implementasi pembatalan, tambahkan pencatat pembatalan seperti waktu, user yang membatalkan, dan alasan bila diperlukan untuk audit.

## 4. Tap anonim dan absensi

- Tap adalah hitungan anonim per sesi, bukan identitas siswa. Sistem tidak menghubungkan tap dengan baris siswa tertentu dan tidak menyimpan riwayat setiap tap.
- Setiap tap yang diterima menambah `sesi_pembelajaran.jumlah_tap` satu kali. Jumlah tap dibatasi dari `0` sampai jumlah siswa pada roster sesi.
- Jeda minimum dua detik berlaku global untuk satu sesi, termasuk bila permintaan datang dari perangkat berbeda. Server memeriksa `tap_terakhir_at` dan menaikkan hitungan secara atomik; jeda di browser hanya membantu tampilan, bukan pengaman utama.
- Tap yang terlalu cepat atau jumlah yang sudah mencapai roster ditolak. Hitungan dapat dikoreksi manual naik atau turun dalam rentang `0..jumlah_siswa_roster`.
- Saat sesi dimulai, setiap siswa pada roster mendapat baris `absensi` dengan status awal `hadir`. Tap hanya mengubah hitungan sesi, bukan status siswa.
- Guru menanyakan langsung siswa yang tidak hadir, lalu mengganti status siswa yang sesuai menjadi `izin`, `sakit`, atau `alpha`. Semua siswa lain tetap `hadir`.
- Sesi hanya boleh diselesaikan jika:

  `jumlah_tap + jumlah_absensi_non_hadir = jumlah_siswa_roster`

  Contoh: roster 36 siswa, 34 tap, dan dua siswa diberi status `izin`/`sakit`/`alpha` memenuhi syarat. Jika hasilnya kurang atau lebih dari roster, penyelesaian ditolak sampai hitungan atau status dikoreksi.
- Perubahan absensi hanya boleh dilakukan pada sesi `berlangsung` oleh Guru pemilik penugasan. Setelah sesi selesai atau dibatalkan, status absensi dikunci.

## 5. Pengacakan dan pengelolaan kelompok

- Kelompok terikat pada `penugasan_guru`, bukan pada sesi. Guru dapat melihat dan mengelola kelompok di luar waktu sesi.
- Anggota hanya boleh berasal dari roster aktif kelas pada penugasan tersebut. Setiap siswa paling banyak menjadi anggota satu kali dalam kelompok yang sama.
- Guru dapat memilih jumlah kelompok atau batas jumlah anggota per kelompok, serta jumlah minimum laki-laki dan perempuan yang diinginkan per kelompok.
- Aplikasi mengupayakan agar sebanyak mungkin kelompok memenuhi minimum gender yang diminta. Jika jumlah siswa dari suatu gender tidak cukup, kelompok yang tidak memperoleh wakil gender tersebut tetap dibuat; siswa yang tersisa tetap dibagikan.
- Pengacakan berusaha menjaga ukuran kelompok berbeda paling banyak satu siswa bila jumlah siswa memungkinkan. Semua siswa roster harus masuk tepat satu kelompok dan kelompok tidak boleh kosong. Jika jumlah kelompok melebihi jumlah siswa, aplikasi meminta Guru mengurangi jumlah kelompok.
- Jika kombinasi minimum gender secara matematis melebihi kapasitas kelompok, pengaturan ditolak dan Guru diminta menyesuaikannya. Kekurangan jumlah siswa pada gender tertentu adalah kondisi yang boleh dilonggarkan, bukan alasan untuk mengeluarkan siswa dari hasil.
- Hasil acak belum tersimpan sampai Guru memilih Simpan. Sesudah disimpan, kelompok dan anggota dapat diedit selama semester/penugasan masih aktif. Riwayat semester lampau ditampilkan sebagai arsip.

## 6. Poin keaktifan

- Poin dicatat untuk satu siswa dalam satu sesi yang sedang berlangsung dan hanya oleh Guru pemilik penugasan.
- Setiap penambahan adalah baris baru di `poin_keaktifan`; siswa boleh menerima beberapa penambahan dalam sesi yang sama.
- Nilai poin harus lebih besar dari nol. Nilai total ditampilkan sebagai penjumlahan catatan poin pada sesi atau rentang laporan yang dipilih.
- Poin pada sesi selesai atau dibatalkan tidak dapat diedit atau dihapus melalui alur Guru biasa. Poin sesi batal tidak masuk ke rekap poin pembelajaran.

## 7. Ringkasan izin perubahan

| Data | Admin | Guru pemilik penugasan |
|---|---|---|
| Semester, kelas, roster, mata pelajaran | Kelola | Lihat sesuai penugasan |
| Penugasan dan jadwal | Kelola | Lihat jadwal sendiri |
| Sesi berlangsung | Lihat/kelola sesuai kebijakan Admin | Mulai, catat, selesaikan, atau batalkan |
| Tap dan koreksi hitungan | Lihat/koreksi sesuai kebijakan Admin | Koreksi pada sesi sendiri yang berlangsung |
| Status absensi | Lihat/koreksi sesuai kebijakan Admin | Ubah pada sesi sendiri yang berlangsung |
| Kelompok dan anggota | Lihat | Kelola untuk penugasan sendiri yang aktif |
| Poin keaktifan | Lihat | Tambah pada sesi sendiri yang berlangsung |

Hak Admin untuk mengoreksi sesi, tap, absensi, dan poin perlu diputuskan sebelum implementasi. Nilai dalam tabel yang bertuliskan “sesuai kebijakan Admin” belum memberikan hak tulis otomatis.

## 8. Urutan implementasi

1. Sesuaikan halaman Admin dan Guru dengan skema baru, termasuk semester, kelas per semester, mata pelajaran, dan penugasan.
2. Implementasikan validasi bentrok jadwal dengan pemeriksaan rentang tanggal, hari, kelas, dan Guru.
3. Implementasikan pembuatan sesi dan snapshot roster absensi.
4. Implementasikan tap anonim pada server dengan cooldown dua detik, batas roster, transaksi, dan koreksi manual.
5. Implementasikan perubahan status absensi dan syarat penutupan sesi.
6. Implementasikan status selesai/batal dan penguncian data sesi terminal.
7. Implementasikan pengacakan kelompok, validasi gender/roster, penyimpanan, dan pengeditan kelompok.
8. Implementasikan catatan poin per siswa per sesi dan rekapnya.
9. Perbarui data demo dan dokumentasi setelah alur aplikasi menggunakan skema revisi.

## 9. Perbedaan dengan kode saat ini

Dokumen ini adalah aturan target. Skema SQL telah diperbarui, tetapi halaman PHP masih menggunakan perilaku lama. Secara khusus, kode saat ini belum menerapkan jadwal bentrok lintas Guru/kelas, hitungan tap anonim persisten dengan cooldown global, syarat penutupan sesi berdasarkan roster, penguncian sesi terminal sesuai aturan di atas, atau minimum komposisi gender saat pengacakan.
