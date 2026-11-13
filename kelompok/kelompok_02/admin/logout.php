<?php
require_once __DIR__ . '/../includes/bootstrap.php';

verify_csrf();
unset($_SESSION['admin']);
set_flash('success', 'Anda telah berhasil keluar.');
redirect('admin/login.php');
