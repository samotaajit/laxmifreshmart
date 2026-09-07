<?php

declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . ADMIN_URL . 'dashboard.php');
    exit;
}

header('Location: ' . ADMIN_URL . 'login.php');
exit;