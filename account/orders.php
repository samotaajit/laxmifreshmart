<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/customer-auth.php';

$user = requireCustomer($pdo);

$stmt = $pdo->prepare("
    SELECT id, order_number, subtotal, discount, delivery_charge, total,
           payment_method, payment_status, order_status, order_source, created_at
    FROM orders
    WHERE user_id = :user_id
    ORDER BY created_at DESC
");
$stmt->execute([':user_id' => (int) $user['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Order History';
$activePage = '';
?>
<?php require '../includes/store-header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Order History</h1>
        <div class="breadcrumb">Home / My Account / Orders</div>
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
                    <a href="<?= BASE_URL ?>account/change-password.php">Change Password</a>
                    <a class="active" href="<?= BASE_URL ?>account/orders.php">Order History</a>
                    <a href="<?= BASE_URL ?>logout.php">Logout</a>
                </nav>
            </aside>

            <div class="account-main">
                <div class="account-card">
                    <div class="account-card-head">
                        <h2>Your Orders</h2>
                        <p>View your previous and current orders.</p>
                    </div>

                    <?php if (!$orders): ?>
                        <div class="empty-state">
                            <h2>No orders yet</h2>
                            <p>Once you place an order, it will appear here.</p>
                            <a class="btn-primary" href="<?= BASE_URL ?>shop.php">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="account-order-list">
                            <?php foreach ($orders as $order): ?>
                                <a class="account-order-row"
                                   href="<?= BASE_URL ?>account/order.php?id=<?= (int) $order['id'] ?>">
                                    <div>
                                        <strong>#<?= e($order['order_number']) ?></strong>
                                        <span><?= e(date('d M Y, h:i A', strtotime($order['created_at']))) ?></span>
                                        <span><?= e(ucfirst($order['order_source'])) ?> order</span>
                                    </div>
                                    <div>
                                        <strong>₹<?= number_format((float) $order['total'], 2) ?></strong>
                                        <span class="status-text"><?= e(ucwords(str_replace('_', ' ', $order['order_status']))) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require '../includes/store-footer.php'; ?>
