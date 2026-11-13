-- ============================================================
-- Ruang Hukum - FAQ dan Artikel Contoh (Kelompok 25)
-- Isi FAQ dan 10 artikel hukum umum agar halaman FAQ dan Artikel terlihat lengkap saat demo.
--
-- Cara pakai: jalankan SETELAH schema.sql dan data_contoh.sql.
--   phpMyAdmin > klik database ruang_hukum > tab Import > pilih file ini > Go
--
-- PERHATIAN: file ini MENGGANTI seluruh isi tabel faq (termasuk isi data_linda.sql,
-- yang topiknya sudah dimasukkan ke sini) dan menimpa artikel contoh di bawah (termasuk 6 artikel dari zip perbaikan sebelumnya, agar tidak dobel).
-- Artikel lain yang ditulis lewat halaman admin tidak disentuh. Aman dijalankan berulang kali.
-- Isi artikel berupa informasi hukum umum, bukan nasihat hukum untuk kasus tertentu.
-- ============================================================
SET NAMES utf8mb4;
USE ruang_hukum;

-- 1. FAQ (urutan = kategori x 100 + nomor: 1xx Umum, 2xx Konsultasi, 3xx Akun, 4xx Pengacara)
DELETE FROM faq;
INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES
('Apa itu Ruang Hukum?', 'Ruang Hukum adalah layanan konsultasi hukum online yang mempertemukan kamu dengan pengacara terverifikasi di enam bidang: pidana, perdata, keluarga, ketenagakerjaan, bisnis, dan properti. Kamu menulis masalahmu, pengacara menjawab, dan seluruh percakapan tersimpan rapi di akunmu.', 101),
('Apakah konsultasi di Ruang Hukum gratis?', 'Ya. Ruang Hukum belum memiliki fitur pembayaran, sehingga mengajukan konsultasi dan membaca balasan pengacara berjalan tanpa tagihan. Pendampingan lanjutan, misalnya mewakilimu di pengadilan, berada di luar layanan konsultasi online ini dan disepakati langsung dengan pengacara yang bersangkutan.', 102),
('Apakah jawaban pengacara bisa menggantikan kuasa hukum resmi?', 'Tidak. Jawaban di Ruang Hukum berupa informasi dan pendapat hukum umum berdasarkan cerita yang kamu tulis. Untuk perkara yang sudah berjalan, seperti laporan polisi, gugatan, atau sidang, sebaiknya kamu menunjuk kuasa hukum secara resmi melalui surat kuasa.', 103),
('Bidang hukum apa saja yang tersedia?', 'Ada enam bidang: Hukum Pidana (laporan polisi, pembelaan), Hukum Perdata (utang piutang, kontrak, sengketa), Hukum Keluarga (perceraian, hak asuh, harta bersama, waris), Hukum Ketenagakerjaan (PHK, pesangon, kontrak kerja), Hukum Bisnis (pendirian usaha, kontrak bisnis, merek), dan Hukum Properti (jual beli tanah, sertifikat, sengketa properti).', 104),
('Siapa yang menulis artikel hukum di Ruang Hukum?', 'Artikel disusun dan diterbitkan oleh tim admin Ruang Hukum sebagai informasi umum. Setiap artikel memuat pengingat bahwa isinya bukan nasihat hukum untuk kasus tertentu, karena aturan bisa berubah dan setiap perkara punya fakta yang berbeda.', 105),
('Bagaimana cara mengajukan konsultasi?', 'Masuk ke akun klien, lalu klik tombol Ajukan Konsultasi di dashboard. Pilih bidang hukum, tulis judul singkat, ceritakan masalahmu, lalu kirim. Konsultasi akan berstatus Menunggu sampai ada pengacara yang menjawab.', 201),
('Apakah saya bisa memilih pengacara tertentu?', 'Kamu memilih bidang hukum. Konsultasi akan tampil bagi pengacara di bidang tersebut, dan pengacara pertama yang menjawab akan mendampingi percakapanmu sampai selesai. Profil semua pengacara bisa dilihat di halaman Pengacara untuk membantu kamu memilih bidang yang tepat.', 202),
('Berapa lama saya mendapat jawaban?', 'Waktunya bergantung pada ketersediaan pengacara. Status konsultasimu akan berubah dari Menunggu menjadi Dijawab begitu balasan pertama masuk. Semakin jelas dan lengkap ceritamu, semakin cepat pengacara dapat memberi arahan.', 203),
('Apa arti status Menunggu, Dijawab, Selesai, dan Ditutup?', 'Menunggu: konsultasi belum dijawab pengacara. Dijawab: pengacara sudah membalas dan percakapan bisa dilanjutkan. Selesai: kamu sendiri yang menandai pertanyaanmu sudah terjawab. Ditutup: konsultasi dihentikan oleh admin, misalnya karena melanggar ketentuan.', 204),
('Apa yang sebaiknya saya tulis agar jawabannya tepat?', 'Tulis kronologi secara berurutan, sebutkan tanggal penting dan pihak yang terlibat, jelaskan dokumen atau bukti yang kamu miliki, dan katakan apa yang ingin kamu capai. Hindari menuliskan data sensitif yang tidak perlu, seperti nomor KTP, nomor rekening, atau kata sandi.', 205),
('Bisakah saya membalas lagi setelah pengacara menjawab?', 'Bisa, selama status konsultasi masih Dijawab. Setelah kamu menandainya Selesai, percakapan tidak menerima balasan baru, jadi ajukan konsultasi baru jika kamu punya pertanyaan lain.', 206),
('Bagaimana cara mendaftar sebagai klien?', 'Klik Daftar di pojok kanan atas, pilih peran Klien, lalu isi nama lengkap, username, nomor handphone, email, dan kata sandi (minimal 8 karakter, memuat huruf dan angka), dan centang persetujuan syarat penggunaan. Kamu juga bisa mendaftar cepat lewat Google, LinkedIn, atau Facebook. Akun klien langsung aktif dan bisa dipakai untuk mengajukan konsultasi.', 301),
('Apakah percakapan saya aman dan rahasia?', 'Percakapan hanya dapat dibaca oleh kamu, pengacara di bidang hukum yang kamu pilih, dan admin untuk keperluan moderasi. Kata sandi disimpan dalam bentuk terenkripsi. Tetap hindari mengirim data yang terlalu sensitif lewat percakapan online.', 302),
('Saya lupa kata sandi. Apa yang harus dilakukan?', 'Klik Masuk, lalu pilih Lupa kata sandi. Isi username atau email akunmu, dan kamu akan menerima tautan untuk membuat kata sandi baru. Tautan berlaku 60 menit dan hanya bisa dipakai sekali. Kalau tautannya tidak muncul, hubungi admin Ruang Hukum.', 304),
('Mengapa akun saya tidak bisa masuk?', 'Periksa dulu username atau email dan kata sandimu. Setelah beberapa kali salah, login dikunci sementara selama 15 menit. Jika kamu mendaftar sebagai pengacara, akunmu mungkin masih menunggu verifikasi admin. Akun yang dinonaktifkan atau ditolak juga tidak bisa masuk. Hubungi admin bila kamu merasa ini keliru.', 303),
('Bagaimana cara mendaftar sebagai pengacara?', 'Klik Daftar, pilih peran Pengacara, lalu lengkapi data diri beserta bidang hukum yang kamu tekuni dan nomor izin advokat. Akunmu akan berstatus Menunggu sampai admin memverifikasi datanya.', 401),
('Bagaimana proses verifikasi pengacara?', 'Admin memeriksa data pendaftaran dan nomor izin advokat. Jika disetujui, akunmu aktif dan kamu bisa masuk untuk menjawab konsultasi. Jika ditolak, akun tidak dapat dipakai dan kamu bisa menghubungi admin untuk melengkapi data.', 402),
('Konsultasi apa yang akan saya lihat sebagai pengacara?', 'Di dashboard kamu akan melihat konsultasi berstatus Menunggu pada bidang hukum yang kamu pilih. Saat kamu mengirim balasan pertama, konsultasi itu otomatis menjadi milikmu dan pengacara lain tidak lagi menjawabnya.', 403),
('Apakah profil pengacara tampil untuk umum?', 'Ya, setelah akun aktif. Nama, bidang hukum, kota, pengalaman, dan rating dari klien tampil di halaman Pengacara agar calon klien bisa mengenalmu.', 404);

-- 2. Artikel contoh (penulis: akun admin bawaan)
DELETE FROM artikel WHERE slug IN ('hak-anda-saat-dipanggil-dan-diperiksa-polisi', 'utang-piutang-tanpa-surat-perjanjian', 'hak-asuh-anak-setelah-perceraian', 'hak-pekerja-saat-terkena-phk', 'memilih-bentuk-usaha-pt-cv-atau-perorangan', 'checklist-sebelum-membeli-tanah-dan-rumah', 'harta-gono-gini-apa-yang-dibagi', 'memahami-kontrak-kerja-pkwt-dan-pkwtt', 'somasi-langkah-awal-menagih-hak', 'melindungi-merek-usaha-anda', 'hak-anda-saat-diperiksa-polisi', 'mengenal-somasi-peringatan-sebelum-gugatan', 'hak-asuh-anak-dan-harta-bersama-saat-bercerai', 'pkwt-dan-pkwtt-apa-bedanya', 'mendaftarkan-merek-usaha-anda', 'hal-yang-perlu-dicek-sebelum-membeli-tanah');
INSERT INTO artikel (penulis_id, bidang_id, judul, slug, ringkasan, isi, status, created_at) VALUES
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 1, 'Hak Anda Saat Dipanggil dan Diperiksa Polisi', 'hak-anda-saat-dipanggil-dan-diperiksa-polisi', 'Menerima surat panggilan polisi memang menegangkan. Kenali perbedaan saksi dan tersangka, serta hak-hak dasar yang melindungimu selama pemeriksaan.', 'Menerima surat panggilan dari kepolisian membuat banyak orang panik. Padahal, dipanggil belum berarti bersalah. Memahami posisi dan hakmu sejak awal membuat kamu lebih tenang dan terlindungi.

## Saksi atau tersangka?

Bacalah surat panggilan dengan teliti. Orang yang dipanggil sebagai **saksi** diminta menjelaskan apa yang ia lihat, dengar, atau alami. Orang yang dipanggil sebagai **tersangka** sedang diduga melakukan tindak pidana. Statusmu menentukan hak dan cara pemeriksaan, jadi pastikan kamu tahu mana yang berlaku untukmu.

## Surat panggilan yang layak

Surat panggilan yang baik dibuat tertulis dan memuat:

- identitas orang yang dipanggil,
- alasan pemanggilan atau perkara yang diperiksa,
- hari, tanggal, jam, dan tempat pemeriksaan,
- jangka waktu yang wajar antara surat diterima dan hari pemeriksaan.

Jika isi surat tidak jelas, kamu berhak menanyakannya secara sopan sebelum hadir.

## Hak dasar saat pemeriksaan

- Didampingi penasihat hukum, dan untuk perkara tertentu pendampingan ini bahkan wajib.
- Memberikan keterangan tanpa tekanan, paksaan, atau kekerasan dalam bentuk apa pun.
- Membaca kembali berita acara pemeriksaan (BAP) sebelum menandatanganinya.
- Meminta perbaikan jika isi BAP tidak sesuai dengan ucapanmu.
- Mengajukan keberatan secara tertulis bila merasa diperlakukan tidak patut.

## Yang sebaiknya kamu lakukan

1. Datang tepat waktu dan bawa identitas.
2. Jawab sesuai fakta yang kamu ketahui, jangan menebak atau mengarang.
3. Jangan menandatangani dokumen yang belum kamu baca dan pahami.
4. Catat nama penyidik, waktu mulai, dan waktu selesai pemeriksaan.
5. Hubungi pengacara sedini mungkin, bahkan sebelum hari pemeriksaan.

Ketentuan acara pidana dapat diperbarui dari waktu ke waktu, jadi pastikan kamu merujuk pada aturan yang berlaku saat ini dan berkonsultasi dengan pengacara untuk kasusmu.', 'terbit', '2026-11-08 09:00:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 2, 'Utang Piutang Tanpa Surat Perjanjian: Masih Bisa Ditagih?', 'utang-piutang-tanpa-surat-perjanjian', 'Meminjamkan uang hanya berdasarkan kepercayaan memang umum terjadi. Ketahui bukti apa yang bisa dipakai dan langkah menagih yang aman secara hukum.', 'Banyak orang meminjamkan uang kepada teman atau keluarga hanya dengan lisan. Ketika pinjaman tidak kunjung dikembalikan, muncul pertanyaan: apakah utang tanpa surat masih bisa ditagih? Jawabannya bisa, selama kamu dapat membuktikan bahwa utang itu ada.

## Bukti selain surat perjanjian

Surat bukan satu-satunya bukti. Kumpulkan apa pun yang menunjukkan adanya pinjaman:

- bukti transfer bank atau dompet digital,
- percakapan WhatsApp atau pesan lain yang berisi pengakuan utang atau janji membayar,
- kuitansi atau catatan tertulis, sekalipun sederhana,
- saksi yang mengetahui peminjaman tersebut.

Simpan semuanya dengan rapi dan jangan mengubah isi percakapan.

## Langkah menagih yang aman

1. Tagih dengan baik-baik dan tertulis, misalnya lewat pesan, agar ada jejaknya.
2. Jika tidak ada respons, kirim surat teguran resmi (somasi) dengan tenggat yang jelas.
3. Tawarkan penyelesaian damai, misalnya mencicil atau menjadwalkan ulang pembayaran.
4. Bila tetap tidak berhasil, ajukan gugatan ke pengadilan negeri.

## Gugatan sederhana

Untuk sengketa dengan nilai tertentu, tersedia jalur gugatan sederhana yang prosesnya lebih singkat dan biayanya lebih ringan, serta tidak mewajibkan kamu memakai pengacara. Saat ini batas nilai gugatannya Rp500 juta berdasarkan peraturan Mahkamah Agung, dan batas itu bisa berubah.

## Jangan salah langkah

- Utang yang tidak dibayar umumnya masuk wilayah perdata, bukan otomatis pidana. Tidak semua kegagalan membayar adalah penipuan.
- Jangan menagih dengan ancaman, kekerasan, atau mempermalukan di media sosial. Tindakan itu justru bisa membuatmu dilaporkan.

Kekuatan bukti sangat menentukan, jadi sebelum melangkah ke pengadilan, konsultasikan dokumen yang kamu miliki dengan pengacara.', 'terbit', '2026-11-05 10:30:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 3, 'Hak Asuh Anak Setelah Perceraian: Apa yang Dipertimbangkan Hakim?', 'hak-asuh-anak-setelah-perceraian', 'Hak asuh bukan soal siapa yang menang. Pengadilan menimbang kepentingan terbaik anak. Pahami faktor yang dinilai dan kewajiban kedua orang tua.', 'Dalam perceraian yang melibatkan anak, hak asuh sering menjadi bagian yang paling emosional. Penting diingat bahwa pengadilan tidak sedang memilih orang tua yang menang, melainkan menentukan pengasuhan yang paling baik bagi anak.

## Kepentingan terbaik anak

Prinsip utamanya adalah kepentingan terbaik bagi anak. Hakim menilai kasus per kasus dengan mempertimbangkan antara lain:

- usia dan kebutuhan anak,
- kedekatan emosional anak dengan masing-masing orang tua,
- kemampuan merawat, mendidik, dan menyediakan lingkungan yang stabil,
- kondisi tempat tinggal dan keseharian anak, termasuk sekolahnya,
- pendapat anak yang sudah cukup dewasa untuk menyampaikannya.

## Anak yang masih kecil

Bagi yang beragama Islam, Kompilasi Hukum Islam menyebut pemeliharaan anak yang belum berusia 12 tahun adalah hak ibunya. Ketentuan ini menjadi pedoman, tetapi hakim tetap menilai keadaan nyata. Jika ibu dinilai tidak mampu atau membahayakan anak, hak asuh dapat diberikan kepada pihak lain.

## Nafkah anak tetap berjalan

Orang tua yang tidak memegang hak asuh tetap berkewajiban membiayai anak. Pada umumnya biaya pemeliharaan dan pendidikan anak menjadi tanggung jawab ayah, kecuali kenyataannya ayah tidak mampu. Orang tua yang tidak memegang hak asuh juga tetap berhak bertemu dan menjalin hubungan dengan anak.

## Bisa diubah kemudian hari

Putusan hak asuh bukan harga mati. Jika keadaan berubah secara berarti, misalnya salah satu pihak menelantarkan anak, putusan dapat dimohonkan untuk ditinjau.

## Persiapan yang membantu

1. Kumpulkan bukti keterlibatanmu dalam pengasuhan, seperti rapor, catatan kesehatan, dan foto kegiatan.
2. Jaga komunikasi yang baik dan jangan menghalangi anak bertemu orang tua lainnya.
3. Utamakan penyelesaian lewat mediasi bila memungkinkan, karena prosesnya lebih tenang bagi anak.

Perkara keluarga bagi umat Islam diperiksa di Pengadilan Agama, sedangkan bagi non-Muslim di Pengadilan Negeri.', 'terbit', '2026-11-02 08:15:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 4, 'Hak Pekerja Saat Terkena PHK', 'hak-pekerja-saat-terkena-phk', 'Terkena PHK tidak berarti kamu kehilangan semua hak. Pahami tahapan yang harus dilalui perusahaan dan hak yang mungkin kamu terima.', 'Pemutusan hubungan kerja (PHK) adalah kabar yang berat. Namun, perusahaan tidak bisa memutus hubungan kerja sesuka hati. Ada alasan yang sah dan tahapan yang harus dilalui, dan sebagai pekerja kamu punya hak yang dijamin aturan.

## Tahapan yang seharusnya ditempuh

1. Perusahaan menyampaikan maksud dan alasan PHK secara tertulis.
2. Dilakukan perundingan bipartit antara pekerja dan perusahaan.
3. Jika tidak sepakat, perselisihan dibawa ke mediasi di dinas ketenagakerjaan.
4. Bila mediasi gagal, perselisihan dapat diajukan ke Pengadilan Hubungan Industrial.

## Hak yang mungkin kamu terima

Besarnya bergantung pada alasan PHK dan masa kerjamu. Komponen yang lazim adalah:

- **Uang pesangon**, yang dihitung berdasarkan masa kerja.
- **Uang penghargaan masa kerja**, untuk pekerja yang telah bekerja cukup lama.
- **Uang penggantian hak**, misalnya cuti tahunan yang belum diambil dan biaya pulang ke tempat asal.

Perhitungan rincinya diatur dalam Peraturan Pemerintah Nomor 35 Tahun 2021 setelah berlakunya Undang-Undang Cipta Kerja. Karena angkanya berbeda untuk tiap alasan PHK, hitunglah bersama pengacara atau serikat pekerja.

## Hati-hati sebelum menandatangani

- Jangan buru-buru menandatangani surat pengunduran diri atau perjanjian bersama jika kamu belum memahami isinya.
- Simpan bukti: kontrak kerja, slip gaji, surat PHK, dan percakapan penting dengan atasan atau HRD.
- Jangan menunda. Ada batas waktu untuk mengajukan perselisihan, sehingga terlambat bisa merugikanmu.

Setiap kasus PHK punya fakta berbeda, jadi konsultasikan dokumenmu sebelum memutuskan langkah berikutnya.', 'terbit', '2026-10-29 13:00:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 5, 'Memilih Bentuk Usaha: PT, CV, atau Usaha Perorangan?', 'memilih-bentuk-usaha-pt-cv-atau-perorangan', 'Bentuk usaha menentukan tanggung jawab, pajak, dan kemudahan mencari modal. Bandingkan usaha perorangan, CV, PT, dan PT Perorangan sebelum memulai.', 'Memilih bentuk usaha sering dianggap urusan administratif. Padahal pilihanmu menentukan siapa yang menanggung utang, bagaimana mengajak mitra, dan seberapa mudah usahamu berkembang.

## Usaha perorangan

Dijalankan sendiri dan paling sederhana untuk dimulai. Kelemahannya, tidak ada pemisahan antara harta pribadi dan harta usaha. Jika usaha berutang, hartamu pribadi ikut menjadi taruhannya.

## CV (persekutuan komanditer)

Didirikan minimal dua pihak: sekutu aktif yang mengelola usaha dan sekutu pasif yang menyetor modal. Sekutu aktif menanggung kewajiban usaha secara pribadi tanpa batas. CV cocok untuk usaha menengah yang dijalankan bersama, tetapi bukan badan hukum.

## PT (perseroan terbatas)

PT adalah badan hukum yang terpisah dari pemiliknya. Tanggung jawab pemegang saham pada dasarnya terbatas pada modal yang disetor. PT lebih dipercaya mitra dan bank, serta lebih mudah menerima investor, tetapi persyaratan dan pengurusannya lebih panjang.

## PT Perorangan

Untuk usaha mikro dan kecil, tersedia PT Perorangan yang bisa didirikan satu orang melalui pernyataan pendirian secara elektronik. Pemiliknya mendapat perlindungan tanggung jawab terbatas, dengan proses yang lebih ringkas daripada PT biasa.

## Hal yang perlu dipertimbangkan

- Apakah kamu menjalankan usaha sendiri atau bersama mitra?
- Seberapa besar risiko utang dan kerugian yang mungkin timbul?
- Apakah kamu berencana mencari investor atau pinjaman bank?

Apa pun bentuknya, pelaku usaha perlu mengurus Nomor Induk Berusaha (NIB) melalui sistem OSS agar usahanya tercatat dan dapat beroperasi secara sah.

Pilihan terbaik berbeda untuk tiap usaha, jadi diskusikan rencana bisnismu dengan pengacara atau notaris sebelum mendirikan.', 'terbit', '2026-10-25 11:00:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 6, 'Checklist Sebelum Membeli Tanah dan Rumah', 'checklist-sebelum-membeli-tanah-dan-rumah', 'Membeli properti adalah keputusan besar. Periksa sertifikat, keabsahan penjual, dan status tanah agar kamu tidak terjebak sengketa di kemudian hari.', 'Tanah dan rumah biasanya menjadi pembelian terbesar dalam hidup, jadi risiko sengketa harus ditekan sejak awal. Gunakan daftar periksa berikut sebelum membayar uang muka.

## 1. Periksa jenis dan keaslian sertifikat

Pastikan statusnya jelas, misalnya Sertifikat Hak Milik (SHM) atau Hak Guna Bangunan (HGB). Minta penjual menunjukkan sertifikat asli, lalu cocokkan datanya di Kantor Pertanahan setempat. Waspada terhadap tanah yang hanya berbekal girik, letter C, atau surat jual beli di bawah tangan karena pembuktiannya lebih lemah.

## 2. Pastikan penjual berhak menjual

- Nama di sertifikat harus sesuai dengan penjual.
- Jika penjual sudah menikah, biasanya diperlukan persetujuan pasangan karena properti bisa termasuk harta bersama.
- Jika tanah berasal dari warisan, pastikan seluruh ahli waris setuju.

## 3. Cek beban dan sengketa

Tanyakan apakah tanah sedang dijaminkan ke bank (hak tanggungan), disita, atau disengketakan. Datangi lokasinya, kenali batas tanah, dan tanyakan kepada tetangga atau perangkat desa setempat.

## 4. Periksa dokumen pendukung

Pastikan pajak bumi dan bangunan (PBB) lunas, dan untuk bangunan periksa Persetujuan Bangunan Gedung (PBG) atau IMB yang sebelumnya berlaku.

## 5. Lakukan transaksi dengan benar

1. Buat perjanjian pengikatan jual beli bila pembayaran dilakukan bertahap.
2. Lakukan jual beli di hadapan Pejabat Pembuat Akta Tanah (PPAT) hingga terbit Akta Jual Beli (AJB).
3. Ajukan balik nama sertifikat ke atas namamu.
4. Bayar pajak transaksi sesuai ketentuan dan simpan semua bukti pembayaran.

Jangan pernah menyerahkan pelunasan sebelum dokumen diperiksa. Bawa dokumen yang kamu terima ke pengacara atau PPAT agar diperiksa sebelum kamu menandatangani apa pun.', 'terbit', '2026-10-21 15:20:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 3, 'Harta Gono-Gini: Apa yang Dibagi Saat Bercerai?', 'harta-gono-gini-apa-yang-dibagi', 'Tidak semua harta suami istri otomatis dibagi dua. Kenali perbedaan harta bersama dan harta bawaan, serta peran perjanjian pranikah.', 'Saat pasangan bercerai, urusan harta sering menjadi sumber konflik. Istilah harta gono-gini merujuk pada harta bersama, yaitu harta yang diperoleh selama perkawinan.

## Harta bersama

Menurut Undang-Undang Perkawinan, harta yang diperoleh selama perkawinan menjadi harta bersama, terlepas dari atas nama siapa harta itu terdaftar. Contohnya gaji, rumah, kendaraan, tabungan, dan usaha yang dibangun setelah menikah.

## Harta bawaan dan hadiah

Harta yang dimiliki sebelum menikah, serta harta yang diperoleh sebagai hadiah atau warisan, tetap berada di bawah penguasaan masing-masing. Pembagian harta bersama pada umumnya dilakukan seimbang. Bagi yang beragama Islam, Kompilasi Hukum Islam menyebut masing-masing mendapat seperdua bagian, meskipun hakim dapat menyesuaikan dengan keadaan perkara.

## Utang bersama

Harta bersama bukan hanya soal aset. Utang yang dibuat untuk kepentingan keluarga, seperti cicilan rumah, juga dapat menjadi tanggungan bersama.

## Perjanjian pranikah

Pasangan dapat membuat perjanjian pemisahan harta. Mahkamah Konstitusi bahkan membolehkan perjanjian semacam ini dibuat setelah perkawinan berlangsung. Dengan perjanjian ini, aturan pembagian harta mengikuti kesepakatan yang telah dibuat.

## Persiapan yang berguna

1. Catat dan kumpulkan bukti kepemilikan, misalnya sertifikat, BPKB, dan rekening koran.
2. Bedakan harta yang diperoleh sebelum dan sesudah menikah.
3. Cobalah mufakat lewat mediasi karena biasanya lebih cepat dan hemat biaya.
4. Gugatan harta bersama dapat diajukan bersamaan dengan perceraian atau setelahnya.

Karena tiap keluarga memiliki keadaan yang berbeda, bawa data hartamu ke konsultasi agar pembagiannya dapat dihitung dengan tepat.', 'terbit', '2026-10-17 09:40:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 4, 'Kontrak Kerja: Memahami Perbedaan PKWT dan PKWTT', 'memahami-kontrak-kerja-pkwt-dan-pkwtt', 'Kontrak waktu tertentu dan karyawan tetap punya aturan yang berbeda. Pahami masa berlaku, masa percobaan, dan kompensasi sebelum menandatangani.', 'Sebelum menandatangani kontrak kerja, kenali dulu jenisnya. Dua istilah yang paling sering muncul adalah PKWT dan PKWTT, dan keduanya membawa hak yang berbeda.

## PKWT: kontrak waktu tertentu

Perjanjian Kerja Waktu Tertentu berlaku untuk jangka waktu atau pekerjaan tertentu, misalnya pekerjaan musiman, pekerjaan sementara, atau proyek yang selesai dalam waktu terbatas. Setelah berlakunya Peraturan Pemerintah Nomor 35 Tahun 2021, masa PKWT termasuk perpanjangannya paling lama lima tahun.

## PKWTT: karyawan tetap

Perjanjian Kerja Waktu Tidak Tertentu tidak memiliki batas waktu. Karyawan dengan status ini dapat dikenai masa percobaan, paling lama tiga bulan.

## Perbedaan penting

- Masa percobaan hanya boleh diberlakukan pada PKWTT, bukan PKWT.
- Pekerja PKWT berhak menerima uang kompensasi ketika kontraknya berakhir.
- Kontrak PKWT harus dibuat tertulis dan dalam bahasa Indonesia.
- Pekerjaan yang bersifat tetap dan terus-menerus pada dasarnya tidak boleh dikontrak berulang-ulang.

## Yang perlu dicek sebelum menandatangani

1. Jenis perjanjian dan masa berlakunya.
2. Besaran gaji, tunjangan, lembur, dan jaminan sosial (BPJS).
3. Jabatan dan uraian tugas.
4. Ketentuan pengakhiran hubungan kerja dan sanksi.
5. Apakah kamu memperoleh salinan kontrak yang sudah ditandatangani kedua pihak.

Jika kamu merasa kontrakmu tidak sesuai aturan, simpan salinan dan tanyakan kepada pengacara atau dinas ketenagakerjaan setempat.', 'terbit', '2026-10-12 14:10:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 2, 'Somasi: Langkah Awal Menagih Hak Sebelum ke Pengadilan', 'somasi-langkah-awal-menagih-hak', 'Somasi adalah teguran tertulis resmi kepada pihak yang lalai. Pelajari isinya, cara mengirim, dan kapan sebaiknya kamu melanjutkan ke gugatan.', 'Ketika pihak lain tidak memenuhi kewajibannya, misalnya tidak membayar utang atau tidak menyerahkan barang yang telah dibeli, langkah yang lazim sebelum menggugat adalah mengirim somasi.

## Apa itu somasi?

Somasi adalah surat teguran tertulis yang meminta pihak lain memenuhi kewajibannya dalam jangka waktu tertentu. Dalam hukum perdata, teguran semacam ini berperan menyatakan bahwa pihak tersebut telah lalai. Somasi juga menunjukkan kepada hakim bahwa kamu sudah berusaha menyelesaikan masalah dengan cara baik-baik.

## Isi somasi yang baik

- identitas pengirim dan penerima,
- dasar hubungan hukum, misalnya perjanjian, nota, atau kesepakatan,
- kewajiban yang belum dipenuhi,
- tuntutan yang jelas, seperti jumlah uang atau barang yang diminta,
- tenggat waktu yang wajar, sering kali tujuh hingga empat belas hari,
- akibat bila teguran diabaikan, misalnya ditempuhnya jalur hukum.

## Cara mengirim

1. Kirim melalui jasa pos tercatat atau kurir dengan bukti penerimaan.
2. Simpan salinan surat dan bukti pengiriman.
3. Bila memungkinkan, kirim juga lewat email atau pesan agar ada jejak digital.

## Setelah somasi

Jika tenggat berlalu tanpa jawaban, kamu dapat melanjutkan ke gugatan wanprestasi. Pihak lain juga mungkin menjawab dan mengajak berunding, dan penyelesaian damai sering kali lebih cepat serta hemat biaya.

Gunakan bahasa yang sopan dan tegas, hindari ancaman atau kata kasar. Pengacara dapat membantu menyusun somasi agar sesuai fakta dan kuat sebagai bukti.', 'terbit', '2026-10-08 10:00:00'),
((SELECT id FROM users WHERE email = 'admin@ruanghukum.test'), 5, 'Melindungi Merek Usaha Anda Sebelum Dipakai Orang Lain', 'melindungi-merek-usaha-anda', 'Nama dan logo usaha adalah aset berharga. Daftarkan mereknya agar kamu punya perlindungan hukum yang jelas bila ada pihak yang meniru.', 'Nama toko, logo, dan kemasan produk yang sudah dikenal pelanggan adalah aset usaha. Sayangnya, banyak pelaku usaha baru sadar pentingnya perlindungan merek setelah ada pihak lain yang memakai namanya.

## Mengapa merek perlu didaftarkan?

Di Indonesia, perlindungan merek diperoleh melalui pendaftaran, bukan sekadar pemakaian. Prinsip yang dipakai adalah siapa yang mendaftar lebih dulu. Jika orang lain mendaftarkan namamu lebih awal, kamu bisa kesulitan memakainya sendiri.

## Langkah pendaftaran

1. Cek ketersediaan nama dan logo di pangkalan data Direktorat Jenderal Kekayaan Intelektual (DJKI).
2. Tentukan kelas barang atau jasa yang sesuai dengan usahamu.
3. Ajukan permohonan beserta contoh merek dan dokumen pendukung.
4. Ikuti pemeriksaan administrasi, pengumuman, dan pemeriksaan substantif.
5. Setelah disetujui, merek terdaftar dan kamu menerima sertifikat.

## Berapa lama perlindungannya?

Perlindungan merek terdaftar berlaku selama 10 tahun sejak tanggal penerimaan permohonan, dan dapat diperpanjang selama merek itu masih digunakan.

## Merek yang sulit didaftarkan

- nama yang terlalu umum atau hanya menggambarkan jenis barang,
- merek yang mirip dengan merek terdaftar milik pihak lain,
- merek yang bertentangan dengan ketertiban umum atau kesusilaan.

## Jika ada yang meniru

Merek terdaftar memudahkanmu mengambil tindakan, mulai dari teguran, gugatan ganti rugi, hingga laporan pidana pada kasus tertentu.

Sebelum menginvestasikan banyak uang untuk branding, konsultasikan pengecekan dan pendaftaran mereknya agar usahamu aman sejak awal.', 'terbit', '2026-10-03 16:30:00');
