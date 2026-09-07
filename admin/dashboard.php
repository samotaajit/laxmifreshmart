<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';


/* =========================================
   STATISTICS
========================================= */

$totalUsers = (int) $pdo
    ->query("SELECT COUNT(*) FROM users")
    ->fetchColumn();

$pendingUsers = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'pending'
    ")
    ->fetchColumn();

$totalProducts = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE status = 'active'
    ")
    ->fetchColumn();

$totalOrders = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM orders
    ")
    ->fetchColumn();

$pendingOrders = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'pending'
    ")
    ->fetchColumn();

$todaySales = (float) $pdo
    ->query("
        SELECT COALESCE(SUM(total), 0)
        FROM orders
        WHERE DATE(created_at) = CURDATE()
        AND order_status NOT IN ('cancelled', 'rejected')
    ")
    ->fetchColumn();


/* =========================================
   RECENT ORDERS
========================================= */

$recentOrders = $pdo
    ->query("
        SELECT
            o.id,
            o.order_number,
            o.total,
            o.order_status,
            o.created_at,
            u.name AS customer_name
        FROM orders o
        INNER JOIN users u
            ON u.id = o.user_id
        ORDER BY o.created_at DESC
        LIMIT 8
    ")
    ->fetchAll();

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
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/admin.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

    <!-- SIDEBAR -->

    <?php require_once '../includes/admin-sidebar.php'; ?>


    <!-- MAIN -->

    <main class="admin-main">

        <!-- HEADER -->

        <?php require_once '../includes/admin-header.php'; ?>


        <!-- CONTENT -->

        <div class="admin-content">


            <!-- =====================================
                 STAT CARDS
            ====================================== -->

            <div class="row g-4 mb-4">

                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Total Users
                                </div>

                                <div class="stat-value">
                                    <?= number_format($totalUsers) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ♟
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Pending Requests
                                </div>

                                <div class="stat-value">
                                    <?= number_format($pendingUsers) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ♙
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Active Products
                                </div>

                                <div class="stat-value">
                                    <?= number_format($totalProducts) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ▣
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Total Orders
                                </div>

                                <div class="stat-value">
                                    <?= number_format($totalOrders) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ◈
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 SALES / PENDING
            ====================================== -->

            <div class="row g-4 mb-4">

                <div class="col-12 col-lg-6">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Pending Orders
                                </div>

                                <div class="stat-value">
                                    <?= number_format($pendingOrders) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ⏳
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12 col-lg-6">

                    <div class="stat-card">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="stat-label">
                                    Today's Sales
                                </div>

                                <div class="stat-value">
                                    ₹<?= number_format(
                                        $todaySales,
                                        2
                                    ) ?>
                                </div>

                            </div>

                            <div class="stat-icon">
                                ₹
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RECENT ORDERS
            ====================================== -->

            <div class="admin-card">

                <div class="admin-card-header">

                    <h5>
                        Recent Orders
                    </h5>

                    <a
                        href="<?= ADMIN_URL ?>orders/"
                        class="btn btn-sm btn-lfm"
                    >
                        View All
                    </a>

                </div>


                <div class="admin-card-body p-0">

                    <?php if (empty($recentOrders)): ?>

                        <div class="text-center py-5">

                            <div
                                class="text-muted mb-2"
                                style="font-size: 32px;"
                            >
                                🛒
                            </div>

                            <div class="text-muted">
                                No orders have been placed yet.
                            </div>

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
                                            Customer
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
                                        </td>

                                        <td>
                                            <?= e(
                                                $order['customer_name']
                                            ) ?>
                                        </td>

                                        <td>
                                            ₹<?= number_format(
                                                (float) $order['total'],
                                                2
                                            ) ?>
                                        </td>

                                        <td>

                                            <span class="badge text-bg-secondary">

                                                <?= e(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $order['order_status']
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>
                                            <?= date(
                                                'd M Y, h:i A',
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

    </main>


    <!-- JAVASCRIPT -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <script
        src="<?= BASE_URL ?>assets/js/admin.js"
    ></script>

</body>

</html>