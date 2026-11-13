<?php
session_start();

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function redirectToLogin(): void
{
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $target = (substr($scriptDir, -6) === '/pages') ? '../login.php' : 'login.php';
    redirectTo($target);
}

function redirectToDashboard(): void
{
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $target = (substr($scriptDir, -6) === '/pages') ? 'dashboard.php' : 'pages/dashboard.php';
    redirectTo($target);
}

function requireLogin(): void
{
    if (!isset($_SESSION['user'])) {
        redirectToLogin();
    }
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, $allowedRoles, true)) {
        if ($role === 'admin') {
            redirectTo('admin_dashboard.php');
        }

        redirectToLogin();
    }
}
