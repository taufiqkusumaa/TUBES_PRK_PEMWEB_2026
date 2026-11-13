<?php
session_start();

require_once __DIR__ . '/../includes/role_guard.php';
requireRole(['Admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helper.php';

$kategori = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$error = '';