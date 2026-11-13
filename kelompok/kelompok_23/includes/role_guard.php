<?php
function requireRole(array $allowedRoles): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth/login.php");
        exit;
    }

    $userRole = strtolower(trim($_SESSION['role'] ?? ''));

    $allowedRoles = array_map('strtolower', $allowedRoles);

    if (!in_array($userRole, $allowedRoles, true)) {
        header("Location: dashboard.php");
        exit;
    }
}