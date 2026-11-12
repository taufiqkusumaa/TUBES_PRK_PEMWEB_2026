<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userName = $_SESSION['user']['nama'] ?? 'Ezzra Rei Setiawan';
$userRole = strtolower($_SESSION['user']['role'] ?? 'admin');
$pageTitle = $pageTitle ?? 'Sistem Manajemen Servis Bengkel';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Machinify</title>
    <script>
        (function() {
            const saved = localStorage.getItem('bengkel_theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <nav class="app-navbar d-flex align-items-center justify-content-between sticky-top no-print">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm text-light d-lg-none p-1" type="button" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a class="brand-title d-flex align-items-center" href="index.php?page=dashboard">
                <span class="brand-dot"></span>
                <span>Machinify</span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Sakelar Tema: Menampilkan Mode Saat Ini Dengan Jelas -->
            <button type="button" class="theme-switch-btn d-flex align-items-center gap-2" id="btnToggleTheme">
                <i class="bi bi-sun" id="themeIcon"></i>
                <span id="themeLabel">Mode: Terang</span>
            </button>

            <div class="vr bg-secondary opacity-25" style="height: 20px;"></div>

            <div class="d-flex align-items-center gap-2">
                <span class="text-light small fw-medium d-none d-sm-inline"><?= htmlspecialchars($userName) ?></span>
                <span class="role-pill"><?= htmlspecialchars($userRole) ?></span>
            </div>

            <div class="dropdown">
                <button class="theme-switch-btn px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-chevron-down" style="font-size: 11px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                    <li><a class="dropdown-item" href="index.php?page=pelanggan">Pengaturan Akun</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" id="btnLogout">Keluar Sesi</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btnToggleTheme');
        const icon = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        const logoutLink = document.getElementById('btnLogout');

        function renderThemeState(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('bengkel_theme', theme);
            if (theme === 'dark') {
                icon.className = 'bi bi-moon-stars';
                label.textContent = 'Mode: Gelap';
            } else {
                icon.className = 'bi bi-sun';
                label.textContent = 'Mode: Terang';
            }
        }

        renderThemeState(localStorage.getItem('bengkel_theme') || 'light');

        btn.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-theme');
            renderThemeState(current === 'dark' ? 'light' : 'dark');
        });

        if (logoutLink) {
            logoutLink.addEventListener('click', async function (event) {
                event.preventDefault();
                try {
                    const response = await fetch('api.php?endpoint=auth&action=logout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' }
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Tidak dapat keluar dari sesi.');
                    }
                    window.location.href = 'index.php?page=pelanggan&mode=login';
                } catch (error) {
                    window.alert(error.message || 'Tidak dapat menghubungi server.');
                }
            });
        }
    });
    </script>

    <div class="app-wrapper">