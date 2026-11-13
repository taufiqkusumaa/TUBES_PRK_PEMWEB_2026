<?php
// Data kontak dan media sosial Ruang Hukum. Dipakai oleh footer (includes/footer.php).
// Mau ganti alamat, nomor, atau tautan sosmed? Cukup ubah di file ini, semua halaman ikut berubah.

return [
    'alamat'      => 'Jl. Darusalam, Gg. Langgar, No 90, Kecamatan Langkapura, Kota Bandar Lampung 35111, Lampung, Indonesia',
    'telepon'     => '+62 821-2577-3292',   // teks yang tampil
    'telepon_tel' => '+6282125773292',      // untuk tautan tel: (tanpa spasi dan strip)
    'email'       => 'ruanghukumidn@gmail.com',

    // Hanya tiga media sosial resmi Ruang Hukum.
    // Kunci 'ikon' memakai Bootstrap Icons (bi-instagram, bi-tiktok, bi-facebook).
    'sosial' => [
        ['nama' => 'Instagram', 'ikon' => 'bi-instagram', 'url' => 'https://www.instagram.com/ruanghukumidn'],
        ['nama' => 'TikTok',    'ikon' => 'bi-tiktok',    'url' => 'https://www.tiktok.com/@ruanghukumindonesia'],
        ['nama' => 'Facebook',  'ikon' => 'bi-facebook',  'url' => 'https://www.facebook.com/share/1ACeHsNsQn/'],
    ],
];
