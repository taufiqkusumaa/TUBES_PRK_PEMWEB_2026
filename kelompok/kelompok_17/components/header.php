<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KabarSi FISIP - Portal Berita & Aspirasi Mahasiswa</title>
    <?php if (!empty($pageStylesheet)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageStylesheet, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <style>
        /* Reset & Base */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background-color: #F6F2E9;
            color: #141414;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .main-content {
            margin-bottom: auto;
        }

        /* Container Shared */
        .layout-container {
            max-width: 72rem;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        @media (min-width: 640px) {
            .layout-container { padding-left: 1.5rem; padding-right: 1.5rem; }
        }
        @media (min-width: 1024px) {
            .layout-container { padding-left: 2rem; padding-right: 2rem; }
        }

        /* Navbar */
        .nav-kb {
            background-color: #141414;
            border-bottom: 1px solid #141414;
            position: sticky;
            top: 0;
            z-index: 50;
            color: #F6F2E9;
            font-family: inherit;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4rem;
        }

        .nav-brand-wrap {
            display: flex;
            align-items: center;
        }

        .nav-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.025em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .nav-badge {
            background-color: #FF5A1F;
            color: #FFFDF8;
            padding: 0.125rem 0.625rem;
            font-weight: 800;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            transition: opacity 0.15s ease;
        }
        .nav-brand:hover .nav-badge {
            opacity: 0.9;
        }

        .nav-title {
            font-weight: 800;
            font-size: 1.125rem;
            color: #F6F2E9;
            letter-spacing: -0.025em;
        }
        .nav-title-accent {
            color: #FF5A1F;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .nav-links { gap: 1.5rem; }
        }

        .nav-link {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #F6F2E9;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .nav-link:hover {
            color: #FF5A1F;
        }

        .nav-link-desktop {
            display: none;
        }
        @media (min-width: 640px) {
            .nav-link-desktop { display: inline-block; }
        }

        .nav-btn {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.03em;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .nav-btn-or {
            background-color: #FF5A1F;
            color: #ffffff !important;
            border-color: #FF5A1F;
        }
        .nav-btn-or:hover {
            background-color: #e04a13;
            border-color: #e04a13;
        }

        .nav-btn-dark {
            background-color: #FFFDF8;
            color: #141414 !important;
            border-color: #FFFDF8;
        }
        .nav-btn-dark:hover {
            background-color: #FFD9C7;
        }

        .nav-btn-logout {
            background-color: #ef4444;
            color: #ffffff !important;
        }
        .nav-btn-logout:hover {
            background-color: #dc2626;
        }

        .site-footer {
            background-color: #141414;
            color: #F6F2E9;
            border-top: 1px solid #141414;
            margin-top: 5rem;
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
        }

        .footer-inner {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .footer-inner { flex-direction: row; }
        }

        .footer-brand-row {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 0.375rem;
        }

        .footer-badge {
            background-color: #FF5A1F;
            color: #FFFDF8;
            padding: 0.125rem 0.5rem;
            font-weight: 700;
            font-size: 0.75rem;
            border-radius: 0.25rem;
        }

        .footer-title {
            margin: 0;
            font-weight: 800;
            font-size: 1.125rem;
            color: #F6F2E9;
            letter-spacing: -0.025em;
        }

        .footer-tagline {
            margin: 0;
            font-size: 0.75rem;
            color: #a8a296;
            font-weight: 500;
        }

        .footer-copy-wrap {
            text-align: center;
        }
        @media (min-width: 640px) {
            .footer-copy-wrap { text-align: right; }
        }

        .footer-copy {
            margin: 0;
            font-size: 0.75rem;
            color: #F6F2E9;
            font-weight: 500;
        }
        @media (min-width: 640px) {
            .footer-copy { font-size: 0.875rem; }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="nav-kb">
        <div class="layout-container">
            <div class="nav-inner">
                
                <!-- Logo KabarSi -->
                <div class="nav-brand-wrap">
                    <a href="index.php" class="nav-brand">
                        <span class="nav-badge">KS</span>
                        <span class="nav-title">
                            KabarSi <span class="nav-title-accent">FISIP</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="nav-links">
                    <a href="index.php" class="nav-link">Beranda</a>
                    <a href="index.php#berita" class="nav-link nav-link-desktop">Berita</a>
                    <a href="index.php#aspirasi" class="nav-link nav-link-desktop">Aspirasi</a>
                    
                    <?php if (isset($_SESSION['id_user']) || isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                            <a href="admin/index.php" class="nav-btn nav-btn-or">Panel Admin</a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="nav-btn nav-btn-or">Dashboard Saya</a>
                        <?php endif; ?>
                        
                        <a href="auth/logout.php" class="nav-btn nav-btn-logout">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="nav-link">Login</a>
                        <a href="auth/register.php" class="nav-btn nav-btn-or">Daftar</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="main-content">