<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id'])
        && is_numeric($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . 'login.php');
        exit;
    }
}

function adminId(): ?int
{
    return isAdminLoggedIn()
        ? (int) $_SESSION['admin_id']
        : null;
}

function adminName(): string
{
    return $_SESSION['admin_name'] ?? 'Administrator';
}