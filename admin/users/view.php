<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'User Details';
$currentPage = 'users';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {

    $_SESSION['error'] = 'Invalid user.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        mobile,
        email,
        status,
        approved_at,
        approved_by,
        created_at,
        updated_at
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    $_SESSION['error'] = 'User not found.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Addresses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        address_line1,
        district,
        state,
        pincode,
        landmark,
        is_primary,
        approval_status,
        rejection_reason,
        approved_at,
        created_at
    FROM user_addresses
    WHERE user_id = :user_id
    ORDER BY is_primary DESC, created_at DESC
");

$stmt->execute([
    ':user_id' => $id
]);

$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Order Summary
|--------------------------------------------------------------------------
|
| Orders are already related to users in the database.
|
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total), 0) AS total_spent
    FROM orders
    WHERE user_id = :user_id
");

$stmt->execute([
    ':user_id' => $id
]);

$orderSummary = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total,
        payment_method,
        payment_status,
        order_status,
        order_source,
        created_at
    FROM orders
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->execute([
    ':user_id' => $id
]);

$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Flash
|--------------------------------------------------------------------------
*/

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

unset(
    $_SESSION['success'],
    $_SESSION['error']
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?> |
        <?= e(SITE_NAME) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/admin.css"
    >

    <style>

        .profile-header {
            padding: 22px;
            border-bottom: 1px solid #edf0ed;
        }

        .profile-flex {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .large-avatar {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            background: #eaf6e8;
            color: #075B2A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 700;
            color: #252925;
        }

        .profile-mobile {
            color: #777;
            font-size: 12px;
        }

        .detail-section {
            padding: 20px;
            border-bottom: 1px solid #edf0ed;
        }

        .detail-section:last-child {
            border-bottom: 0;
        }

        .section-title {
            color: #075B2A;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 17px;
        }

        .detail-label {
            color: #858b86;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .detail-value {
            color: #292d29;
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }

        .address-box {
            background: #fafcf9;
            border: 1px solid #e7ebe7;
            border-radius: 9px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .address-box:last-child {
            margin-bottom: 0;
        }

        .primary-label {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            background: #e7f4e8;
            color: #176b2d;
            font-size: 10px;
            font-weight: 600;
        }

        .approved-label {
            background: #e6f5e8;
            color: #176b2d;
        }

        .pending-label {
            background: #fff4d6;
            color: #946c00;
        }

        .rejected-label {
            background: #fde8e8;
            color: #a72c2c;
        }

        .blocked-label {
            background: #fde8e8;
            color: #a72c2c;
        }

        .summary-box {
            background: #fafcf9;
            border-radius: 9px;
            padding: 15px;
            height: 100%;
        }

        .summary-label {
            color: #858b86;
            font-size: 11px;
        }

        .summary-value {
            color: #075B2A;
            font-size: 22px;
            font-weight: 700;
            margin-top: 3px;
        }

    </style>

</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>


<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>


    <div class="admin-content">


        <div class="mb-3">

            <a
                href="index.php"
                class="text-decoration-none"
                style="color:#075B2A;font-size:13px;"
            >
                ← Back to Users
            </a>

        </div>


        <!-- FLASH -->

        <?php if ($success): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <?= e($success) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <?= e($error) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <div class="row g-4">


            <!-- ======================================
                 MAIN
            ======================================= -->

            <div class="col-12 col-lg-8">

                <div class="admin-card">


                    <!-- PROFILE -->

                    <div class="profile-header">

                        <div class="profile-flex">

                            <div class="large-avatar">

                                <?= e(
                                    strtoupper(
                                        substr(
                                            $user['name'],
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </div>


                            <div>

                                <div class="profile-name">
                                    <?= e($user['name']) ?>
                                </div>

                                <div class="profile-mobile">
                                    <?= e($user['mobile']) ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PERSONAL -->

                    <div class="detail-section">

                        <div class="section-title">
                            Personal Information
                        </div>


                        <div class="row g-4">

                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Full Name
                                </div>

                                <div class="detail-value">
                                    <?= e($user['name']) ?>
                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Mobile Number
                                </div>

                                <div class="detail-value">
                                    <?= e($user['mobile']) ?>
                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Email
                                </div>

                                <div class="detail-value">

                                    <?= !empty($user['email'])
                                        ? e($user['email'])
                                        : 'Not provided'
                                    ?>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Account Status
                                </div>

                                <div class="detail-value">

                                    <span
                                        class="status-badge
                                        <?=
                                            $user['status'] === 'approved'
                                                ? 'approved-label'
                                                : 'blocked-label'
                                        ?>"
                                    >

                                        <?= e(
                                            ucfirst(
                                                $user['status']
                                            )
                                        ) ?>

                                    </span>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Registered
                                </div>

                                <div class="detail-value">

                                    <?= date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Approved
                                </div>

                                <div class="detail-value">

                                    <?= !empty($user['approved_at'])
                                        ? date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $user['approved_at']
                                            )
                                        )
                                        : '—'
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ADDRESSES -->

                    <div class="detail-section">

                        <div class="section-title">
                            Delivery Addresses
                        </div>


                        <?php if (empty($addresses)): ?>

                            <div class="alert alert-warning mb-0">
                                No delivery address found.
                            </div>

                        <?php else: ?>

                            <?php foreach ($addresses as $address): ?>

                                <div class="address-box">

                                    <div class="d-flex justify-content-between align-items-start mb-2">

                                        <strong
                                            style="font-size:13px;"
                                        >
                                            Address
                                        </strong>


                                        <div>

                                            <?php if ((int) $address['is_primary'] === 1): ?>

                                                <span class="primary-label">
                                                    Primary
                                                </span>

                                            <?php endif; ?>


                                            <?php

                                            $addressStatus =
                                                $address['approval_status'];

                                            ?>

                                            <span
                                                class="primary-label
                                                <?=
                                                    $addressStatus === 'approved'
                                                        ? 'approved-label'
                                                        : (
                                                            $addressStatus === 'rejected'
                                                                ? 'rejected-label'
                                                                : 'pending-label'
                                                        )
                                                ?>"
                                            >

                                                <?= e(
                                                    ucfirst(
                                                        $addressStatus
                                                    )
                                                ) ?>

                                            </span>

                                        </div>

                                    </div>


                                    <?= e($address['address_line1']) ?>


                                    <?php if (!empty('')): ?>

                                        <br>
                                        <?= e('') ?>

                                    <?php endif; ?>


                                    <?php

                                    $location = array_filter([
                                        '',
                                        '',
                                        '',
                                        $address['district'],
                                        $address['state']
                                    ]);

                                    ?>


                                    <?php if (!empty($location)): ?>

                                        <br>

                                        <?= e(
                                            implode(
                                                ', ',
                                                $location
                                            )
                                        ) ?>

                                    <?php endif; ?>


                                    <br>

                                    <strong>
                                        PIN:
                                    </strong>

                                    <?= e($address['pincode']) ?>


                                    <?php if (!empty($address['landmark'])): ?>

                                        <br>

                                        <strong>
                                            Landmark:
                                        </strong>

                                        <?= e($address['landmark']) ?>

                                    <?php endif; ?>


                                    <?php if (
                                        $addressStatus === 'rejected'
                                        && !empty($address['rejection_reason'])
                                    ): ?>

                                        <div class="alert alert-danger mt-3 mb-0 py-2">

                                            <strong>
                                                Rejection:
                                            </strong>

                                            <?= e(
                                                $address['rejection_reason']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                    <!-- RECENT ORDERS -->

                    <div class="detail-section">

                        <div class="section-title">
                            Recent Orders
                        </div>


                        <?php if (empty($recentOrders)): ?>

                            <div class="text-muted">
                                This customer has not placed any orders yet.
                            </div>

                        <?php else: ?>

                            <div class="table-responsive">

                                <table class="table table-hover mb-0">

                                    <thead>

                                        <tr>

                                            <th>
                                                Order
                                            </th>

                                            <th>
                                                Total
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                            <th>
                                                Date
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    <?php foreach ($recentOrders as $order): ?>

                                        <tr>

                                            <td>

                                                <strong>
                                                    <?= e(
                                                        $order['order_number']
                                                    ) ?>
                                                </strong>

                                                <br>

                                                <small class="text-muted">
                                                    <?= e(
                                                        ucfirst(
                                                            $order['order_source']
                                                        )
                                                    ) ?>
                                                </small>

                                            </td>


                                            <td>
                                                ₹<?= number_format(
                                                    (float) $order['total'],
                                                    2
                                                ) ?>
                                            </td>


                                            <td>
                                                <?= e(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $order['order_status']
                                                        )
                                                    )
                                                ) ?>
                                            </td>


                                            <td>

                                                <?= date(
                                                    'd M Y',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- ======================================
                 SIDE PANEL
            ======================================= -->

            <div class="col-12 col-lg-4">


                <!-- ORDER SUMMARY -->

                <div class="admin-card mb-4">

                    <div class="admin-card-header">

                        <h5>
                            Customer Summary
                        </h5>

                    </div>


                    <div class="admin-card-body">

                        <div class="row g-3">

                            <div class="col-6">

                                <div class="summary-box">

                                    <div class="summary-label">
                                        Orders
                                    </div>

                                    <div class="summary-value">
                                        <?= number_format(
                                            (int) $orderSummary['total_orders']
                                        ) ?>
                                    </div>

                                </div>

                            </div>


                            <div class="col-6">

                                <div class="summary-box">

                                    <div class="summary-label">
                                        Total Spent
                                    </div>

                                    <div class="summary-value"
                                         style="font-size:18px;"
                                    >
                                        ₹<?= number_format(
                                            (float) $orderSummary['total_spent'],
                                            2
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ACCOUNT ACTION -->

                <div class="admin-card">

                    <div class="admin-card-header">

                        <h5>
                            Account Actions
                        </h5>

                    </div>


                    <div class="admin-card-body">

                        <?php if ($user['status'] === 'approved'): ?>

                            <p
                                class="text-muted"
                                style="font-size:12px;line-height:1.6;"
                            >
                                Blocking this customer will prevent them from
                                using the shopping system.
                            </p>


                            <form
                                method="POST"
                                action="toggle-status.php"
                                onsubmit="return confirm(
                                    'Block this user? They will no longer be able to place orders.'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int) $user['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="block"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger w-100"
                                >
                                    Block User
                                </button>

                            </form>


                        <?php else: ?>


                            <p
                                class="text-muted"
                                style="font-size:12px;line-height:1.6;"
                            >
                                Unblocking this customer will restore access
                                to the shopping system.
                            </p>


                            <form
                                method="POST"
                                action="toggle-status.php"
                                onsubmit="return confirm(
                                    'Unblock this user?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int) $user['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="unblock"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-approve w-100"
                                >
                                    Unblock User
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>


            </div>

        </div>

    </div>

</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="<?= BASE_URL ?>assets/js/admin.js"
></script>

</body>

</html>