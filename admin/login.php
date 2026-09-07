<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Please enter your username and password.';

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, name, username, password, status
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->execute([$username]);

        $admin = $stmt->fetch();

        if (
            $admin &&
            $admin['status'] === 'active' &&
            password_verify($password, $admin['password'])
        ) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_username'] = $admin['username'];

            $update = $pdo->prepare(
                "UPDATE admins
                 SET last_login = NOW()
                 WHERE id = ?"
            );

            $update->execute([$admin['id']]);

            redirect(ADMIN_URL . 'dashboard.php');

        } else {

            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | <?= e(SITE_NAME) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --lfm-green: #075B2A;
            --lfm-light-green: #39A935;
            --lfm-lime: #76C72C;
            --lfm-gold: #F4B400;
            --lfm-cream: #FFFDF3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--lfm-cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: #fff;
            border-radius: 18px;
            padding: 40px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, .10);
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            width: 95px;
            height: 95px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .brand h2 {
            margin: 0;
            color: var(--lfm-green);
            font-weight: 700;
        }

        .brand p {
            margin: 5px 0 0;
            color: #777;
            font-size: 14px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            min-height: 48px;
            border-radius: 9px;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            border-color: var(--lfm-light-green);
            box-shadow: 0 0 0 .2rem rgba(57, 169, 53, .15);
        }

        .btn-login {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 9px;
            background: var(--lfm-green);
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }

        .btn-login:hover {
            background: #064a23;
            color: #fff;
        }

        .alert {
            border-radius: 9px;
            font-size: 14px;
        }

        .copyright {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 13px;
        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <div class="login-card">

        <div class="brand">

            <!-- Put your logo at assets/images/logo.png -->
            <img
                src="<?= BASE_URL ?>assets/images/logo.png"
                alt="<?= e(SITE_NAME) ?>"
                class="brand-logo"
                onerror="this.style.display='none'"
            >

            <h2>Laxmi Fresh Mart</h2>

            <p>Admin Panel</p>

        </div>

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label
                    for="username"
                    class="form-label"
                >
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    autocomplete="username"
                    required
                    autofocus
                >

            </div>

            <div class="mb-4">

                <label
                    for="password"
                    class="form-label"
                >
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn btn-login"
            >
                Login
            </button>

        </form>

    </div>

    <div class="copyright">
        &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>
    </div>

</div>

</body>

</html>