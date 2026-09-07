<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function customerLoggedIn(): bool
{
    return !empty($_SESSION['lfm_user_id']);
}

function customerUserId(): ?int
{
    return customerLoggedIn() ? (int) $_SESSION['lfm_user_id'] : null;
}

function requireCustomer(PDO $pdo): array
{
    if (!customerLoggedIn()) {
        $_SESSION['lfm_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        redirect(BASE_URL . 'login.php');
    }

    $user = currentCustomer($pdo);

    if (!$user) {
        customerLogout();
        redirect(BASE_URL . 'login.php');
    }

    return $user;
}

function currentCustomer(PDO $pdo): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $loaded = true;

    if (!customerLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT id, name, mobile, email, status, rejection_reason,
               approved_at, created_at, updated_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => customerUserId()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$user) {
        unset($_SESSION['lfm_user_id']);
    }

    return $user;
}

function requireApprovedCustomer(PDO $pdo): array
{
    $user = requireCustomer($pdo);

    if ($user['status'] !== 'approved') {
        redirect(BASE_URL . 'account/index.php');
    }

    return $user;
}

function customerLogout(): void
{
    unset($_SESSION['lfm_user_id']);
}

function approvedPrimaryAddress(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, user_id, address_line1, district, state, pincode, landmark, is_primary,
               approval_status, rejection_reason
        FROM user_addresses
        WHERE user_id = :user_id
          AND approval_status = 'approved'
        ORDER BY is_primary DESC, id ASC
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function formatAddress(array $address): string
{
    $parts = array_filter([
        $address['address_line1'] ?? '',
        $address['district'] ?? '',
        $address['state'] ?? '',
        $address['pincode'] ?? '',
    ], static fn ($value) => trim((string)  $value) !== '');

    return implode(', ', $parts);
}