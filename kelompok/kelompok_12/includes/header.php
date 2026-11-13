<?php
// Variabel opsional yang bisa di-set sebelum include header:
//   $pageTitle : judul tab browser
//   $extraCss  : array file CSS tambahan, contoh ['assets/css/articles.css']
//   $bodyClass : class tambahan pada <body>, contoh 'page-articles'
//   $cssRoot   : '' untuk halaman root, '../' untuk halaman di folder admin
$cssRoot   = $cssRoot   ?? '';
$pageTitle = $pageTitle ?? 'Sehatin - Portal Informasi Kesehatan';
$extraCss  = $extraCss  ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $cssRoot ?>assets/css/theme.css">
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= $cssRoot . htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">