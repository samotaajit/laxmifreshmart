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

$name = '';
$mobile = '';
$email = '';
$addressLine1 = '';
$district = '';
$state = '';
$pincode = '';
$landmark = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name.';
    }

    if (!preg_match('/^\d{10,15}$/', $mobile)) {
        $errors[] = 'Please enter a valid mobile number.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must contain at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($addressLine1 === '') {
        $errors[] = 'Address line 1 is required.';
    }

    if ($pincode === '' || !preg_match('/^\d{6}$/', $pincode)) {
        $errors[] = 'Please enter a valid 6-digit pincode.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE mobile = :mobile
            LIMIT 1
        ");
        $stmt->execute([':mobile' => $mobile]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this mobile number already exists.';
        }
    }

    if (!$errors && $email !== '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO users
                (name, mobile, email, password, status)
                VALUES
                (:name, :mobile, :email, :password, 'pending')
            ");

            $stmt->execute([
                ':name' => $name,
                ':mobile' => $mobile,
                ':email' => $email !== '' ? $email : null,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO user_addresses
                (
                    user_id, address_line1, district, state, pincode, landmark,
                    is_primary, approval_status
                )
                VALUES
                (
                    :user_id, :address_line1, :district, :state, :pincode, :landmark,
                    1, 'pending'
                )
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':address_line1' => $addressLine1,
                ':district' => $district !== '' ? $district : null,
                ':state' => $state !== '' ? $state : null,
                ':pincode' => $pincode,
                ':landmark' => $landmark !== '' ? $landmark : null,
            ]);

            $pdo->commit();

            setFlash(
                'success',
                'Your registration request has been submitted. Lakshmi Fresh Mart will review your account and delivery area before you can place an order.'
            );

            redirect(BASE_URL . 'login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Unable to create your account right now. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
$activePage = '';
?>
<?php require 'includes/store-header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Create Account</h1>
        <div class="breadcrumb">Home / Create Account</div>
    </div>
</section>

<section class="account-page">
    <div class="container">
        <div class="account-card account-card-wide">
            <div class="account-card-head">
                <h2>Join Lakshmi Fresh Mart</h2>
                <p>Your account and delivery address will be reviewed before ordering is enabled.</p>
            </div>

            <?php if ($errors): ?>
                <div class="form-alert form-alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="store-form">
                <div class="form-section-title">Personal Details</div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input id="name" name="name" value="<?= e($name) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number *</label>
                        <input id="mobile" name="mobile" type="tel" inputmode="numeric"
                               maxlength="15" value="<?= e($mobile) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= e($email) ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input id="password" name="password" type="password" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input id="confirm_password" name="confirm_password" type="password" minlength="6" required>
                    </div>
                </div>

                <div class="form-section-title">Delivery Address</div>

                <div class="form-grid">
                    <div class="form-group form-span-2">
                        <label for="address_line1">Address Line 1 *</label>
                        <input id="address_line1" name="address_line1" value="<?= e($addressLine1) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <input id="district" name="district" value="<?= e($district) ?>">
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input id="state" name="state" value="<?= e($state) ?>">
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode *</label>
                        <input id="pincode" name="pincode" inputmode="numeric" maxlength="6"
                               value="<?= e($pincode) ?>" required>
                    </div>

                    <div class="form-group form-span-2">
                        <label for="landmark">Landmark</label>
                        <input id="landmark" name="landmark" value="<?= e($landmark) ?>">
                    </div>
                </div>

                <div class="approval-note">
                    Ordering is available only after your account and delivery address have been approved by Lakshmi Fresh Mart.
                </div>

                <button class="btn-primary form-submit" type="submit">Submit Registration</button>
            </form>
        </div>
    </div>
</section>

<?php require 'includes/store-footer.php'; ?>
