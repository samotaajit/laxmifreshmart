<?php

declare(strict_types=1);

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/customer-auth.php';

if (customerLoggedIn()) {
    redirect(BASE_URL . 'account/index.php');
}

$errors = [];
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^\d{10,15}$/', $mobile)) {
        $errors[] = 'Please enter a valid mobile number.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT id, name, mobile, email, password, status
            FROM users
            WHERE mobile = :mobile
            LIMIT 1
        ");
        $stmt->execute([':mobile' => $mobile]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid mobile number or password.';
        } elseif ($user['status'] === 'blocked') {
            $errors[] = 'Your account has been blocked. Please contact Laxmi Fresh Mart.';
        } elseif ($user['status'] === 'rejected') {
            $errors[] = 'Your account request was rejected.' .
                (!empty($user['rejection_reason']) ? ' Reason: ' . $user['rejection_reason'] : '');
        } else {
            session_regenerate_id(true);
            $_SESSION['lfm_user_id'] = (int) $user['id'];

            $destination = $_SESSION['lfm_after_login'] ?? BASE_URL . 'account/index.php';
            unset($_SESSION['lfm_after_login']);

            redirect($destination);
        }
    }
}

$pageTitle = 'Login';
$activePage = '';
?>
<?php require 'includes/store-header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Welcome Back</h1>
        <div class="breadcrumb">Home / Login</div>
    </div>
</section>

<section class="account-page">
    <div class="container account-single">
        <div class="account-card">
            <div class="account-card-head">
                <h2>Login to your account</h2>
                <p>Use your registered mobile number and password.</p>
            </div>

            <?php if ($errors): ?>
                <div class="form-alert form-alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="store-form">
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <input id="mobile" name="mobile" type="tel" inputmode="numeric"
                           maxlength="15" value="<?= e($mobile) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <button class="btn-primary form-submit" type="submit">Login</button>
            </form>

            <div class="account-form-footer">
                Don't have an account?
                <a href="<?= BASE_URL ?>signup.php">Create an account</a>
            </div>
        </div>
    </div>
</section>

<?php require 'includes/store-footer.php'; ?>
