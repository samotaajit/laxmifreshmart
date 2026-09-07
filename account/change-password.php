<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/customer-auth.php';

$user = requireCustomer($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int) $user['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        $errors[] = 'Your current password is incorrect.';
    }

    if (strlen($newPassword) < 6) {
        $errors[] = 'New password must contain at least 6 characters.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET password = :password
            WHERE id = :id
        ");
        $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => (int) $user['id'],
        ]);

        setFlash('success', 'Your password has been changed successfully.');
        redirect(BASE_URL . 'account/change-password.php');
    }
}

$pageTitle = 'Change Password';
$activePage = '';
?>
<?php require '../includes/store-header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Change Password</h1>
        <div class="breadcrumb">Home / My Account / Change Password</div>
    </div>
</section>

<section class="account-page">
    <div class="container">
        <div class="account-layout">
            <aside class="account-sidebar">
                <div class="account-user-mini">
                    <div class="account-avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></div>
                    <strong><?= e($user['name']) ?></strong>
                    <span><?= e($user['mobile']) ?></span>
                </div>
                <nav class="account-nav">
                    <a href="<?= BASE_URL ?>account/index.php">Overview</a>
                    <a href="<?= BASE_URL ?>account/profile.php">Profile & Address</a>
                    <a class="active" href="<?= BASE_URL ?>account/change-password.php">Change Password</a>
                    <a href="<?= BASE_URL ?>account/orders.php">Order History</a>
                    <a href="<?= BASE_URL ?>logout.php">Logout</a>
                </nav>
            </aside>

            <div class="account-main">
                <?php $flash = getFlash(); ?>
                <?php if ($flash): ?>
                    <div class="form-alert form-alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="account-card account-card-narrow">
                    <div class="account-card-head">
                        <h2>Update Password</h2>
                        <p>Choose a strong password you do not use elsewhere.</p>
                    </div>

                    <form method="post" class="store-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input id="current_password" name="current_password" type="password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input id="new_password" name="new_password" type="password" minlength="6" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" minlength="6" required>
                        </div>

                        <button class="btn-primary form-submit" type="submit">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require '../includes/store-footer.php'; ?>
