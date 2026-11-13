<?php
/**
 * Contoh Konfigurasi Database Lokal / Hosting (InfinityFree)
 * 
 * CARA PAKAI DI INFINITYFREE:
 * 1. Salin file ini dan ubah namanya menjadi: database.local.php (di folder config/)
 * 2. Masukkan data kredensial MySQL yang didapat dari vPanel InfinityFree (Menu: MySQL Databases).
 * 3. File database.local.php ini akan otomatis dibaca oleh sistem tanpa perlu mengubah config/database.php utama.
 */

return [
    // Host MySQL dari vPanel (contoh: sql300.infinityfree.com atau sql105.epizy.com)
    'host'     => 'sqlxxx.infinityfree.com',

    // Nama database lengkap berawalan if0_... (contoh: if0_38123456_sisasense)
    'database' => 'if0_xxxxxxx_sisasense',

    // Username akun InfinityFree berawalan if0_... (contoh: if0_38123456)
    'username' => 'if0_xxxxxxx',

    // Password akun / vPanel InfinityFree Anda
    'password' => 'PASSWORD_VPANEL_ANDA',

    // Port default MySQL
    'port'     => '3306',
];

