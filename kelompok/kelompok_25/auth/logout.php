<?php
require_once __DIR__ . '/../config/app.php';

auth_ingat_cabut();   // hapus token "Ingat akun saya" perangkat ini (database + cookie)
$_SESSION = [];
session_destroy();
session_start();
flash('success', 'Kamu sudah keluar.');
redirect('auth/login.php');
