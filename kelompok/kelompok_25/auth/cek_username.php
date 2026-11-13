<?php
// Dipakai formulir daftar untuk memeriksa ketersediaan username secara langsung (JSON).
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=UTF-8');
$u = mb_strtolower(trim((string) ($_GET['u'] ?? '')));
if (!auth_skema_siap()) { echo json_encode(['tersedia' => true, 'pesan' => '']); exit; }
if ($pesan = auth_cek_username($u)) { echo json_encode(['tersedia' => false, 'pesan' => $pesan]); exit; }
$dipakai = auth_username_terpakai($u);
echo json_encode(['tersedia' => !$dipakai, 'pesan' => $dipakai ? 'Username ini sudah dipakai.' : 'Username tersedia.']);
