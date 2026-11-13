<?php
// Awal login media sosial: /auth/sosial.php?p=google|linkedin|facebook
require_once __DIR__ . '/../config/app.php';
if (is_logged_in()) { redirect(dashboard_path(current_user()['role'])); }
if (!auth_skema_siap()) { auth_halaman_skema_belum_siap(); }
auth_sosial_mulai((string) ($_GET['p'] ?? ''));
