<?php

declare(strict_types=1);

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/customer-auth.php';

$user = requireApprovedCustomer($pdo);

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    redirect(BASE_URL . 'account/orders.php');
}

$stmt = $pdo->prepare("
    SELECT id, order_number, total, order_status, payment_method, created_at
    FROM orders
    WHERE id = :id
      AND user_id = :user_id
    LIMIT 1
");
$stmt->execute([
    ':id' => $orderId,
    ':user_id' => (int) $user['id'],
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect(BASE_URL . 'account/orders.php');
}

$pageTitle = 'Order Placed';
$activePage = '';
?>
<?php require 'includes/store-header.php'; ?>

<section class="success-page">
    <div class="container">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <div class="eyebrow">Order Confirmed</div>
            <h1>Thank you, <?= e($user['name']) ?>!</h1>
            <p>Your order has been received successfully.</p>

            <div class="success-order-number">
                Order #<?= e($order['order_number']) ?>
            </div>

            <div class="success-summary">
                <div>
                    <span>Total</span>
                    <strong>₹<?= number_format((float) $order['total'], 2) ?></strong>
                </div>
                <div>
                    <span>Payment</span>
                    <strong>Cash on Delivery</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong>Pending</strong>
                </div>
            </div>

            <div class="success-actions">
                <a class="btn-primary" href="<?= BASE_URL ?>account/order.php?id=<?= (int) $order['id'] ?>">
                    View Order
                </a>
                <a class="btn-secondary" href="<?= BASE_URL ?>shop.php">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</section>

<?php require 'includes/store-footer.php'; ?>
