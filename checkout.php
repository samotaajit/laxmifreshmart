<?php
declare(strict_types=1);

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/customer-auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user = requireApprovedCustomer($pdo);
$userId = (int) $user['id'];

$address = approvedPrimaryAddress($pdo, $userId);

if (!$address) {
    setFlash('error', 'Your delivery address is awaiting admin approval.');
    redirect(BASE_URL . 'account/profile.php');
}

$sessionCart = $_SESSION['lfm_cart'] ?? [];

if (!is_array($sessionCart) || !$sessionCart) {
    setFlash('error', 'Your cart is empty.');
    redirect(BASE_URL . 'cart.php');
}

/*
 * Cart keys are product-pack IDs (product_variants.id).
 * Quantity is the number of packs, always a whole number.
 */
$cartIds = [];

foreach ($sessionCart as $key => $cartItem) {
    $packId = (int) $key;
    $packCount = is_array($cartItem) ? (float) ($cartItem['quantity'] ?? 0) : 0;

    if ($packId <= 0 || $packCount < 1 || abs($packCount - round($packCount)) > 0.000001) {
        unset($_SESSION['lfm_cart'][$key]);
        continue;
    }

    $cartIds[] = $packId;
}

$cartIds = array_values(array_unique($cartIds));

if (!$cartIds) {
    setFlash('error', 'Your cart is empty.');
    redirect(BASE_URL . 'cart.php');
}

$placeholders = implode(',', array_fill(0, count($cartIds), '?'));

$stmt = $pdo->prepare("
    SELECT
        pv.id AS pack_id,
        pv.quantity AS pack_quantity,
        p.id AS product_id,
        p.name,
        p.slug,
        p.image,
        p.price,
        p.stock,
        u.name AS unit_name,
        u.short_name
    FROM product_variants pv
    INNER JOIN products p ON p.id = pv.product_id
    INNER JOIN units u ON u.id = p.unit_id
    INNER JOIN categories c ON c.id = p.category_id
    WHERE pv.id IN ($placeholders)
      AND pv.status = 'active'
      AND p.status = 'active'
      AND c.status = 'active'
      AND u.status = 'active'
");
$stmt->execute($cartIds);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$items = [];
$subtotal = 0.00;
$errors = [];
$foundIds = [];

foreach ($rows as $row) {
    $packId = (int) $row['pack_id'];
    $key = (string) $packId;
    $foundIds[$key] = true;

    $packCount = (float) ($sessionCart[$key]['quantity'] ?? 0);
    $packQuantity = (float) $row['pack_quantity'];
    $stock = (float) $row['stock'];

    if ($packQuantity <= 0) {
        unset($_SESSION['lfm_cart'][$key]);
        $errors[] = (string) $row['name'] . ' has an invalid pack size.';
        continue;
    }

    $maxPacks = (int) floor(($stock + 0.000001) / $packQuantity);

    if ($packCount < 1 || abs($packCount - round($packCount)) > 0.000001) {
        unset($_SESSION['lfm_cart'][$key]);
        $errors[] = (string) $row['name'] . ' has an invalid quantity.';
        continue;
    }

    if ($maxPacks < 1 || $packCount > $maxPacks) {
        unset($_SESSION['lfm_cart'][$key]);
        $errors[] = (string) $row['name'] . ' does not have enough stock for the selected quantity.';
        continue;
    }

    $packPrice = round((float) $row['price'] * $packQuantity, 2);
    $lineTotal = round($packCount * $packPrice, 2);

    $row['cart_quantity'] = (int) round($packCount);
    $row['pack_quantity'] = $packQuantity;
    $row['pack_price'] = $packPrice;
    $row['line_total'] = $lineTotal;

    $items[] = $row;
    $subtotal += $lineTotal;
}

/* Remove cart entries whose packs disappeared from the database. */
foreach (array_keys($_SESSION['lfm_cart']) as $key) {
    if (!isset($foundIds[(string) $key])) {
        unset($_SESSION['lfm_cart'][$key]);
    }
}

$subtotal = round($subtotal, 2);

if (!$items) {
    setFlash('error', $errors ? implode(' ', $errors) : 'Your cart is empty.');
    redirect(BASE_URL . 'cart.php');
}

$pageTitle = 'Checkout';
$activePage = '';
?>
<?php require 'includes/store-header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Checkout</h1>
        <div class="breadcrumb">Home / Cart / Checkout</div>
    </div>
</section>

<section class="checkout-page">
    <div class="container">

        <?php if ($errors): ?>
            <div class="form-alert form-alert-error">
                <?php foreach ($errors as $message): ?>
                    <div><?= e((string) $message) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>place-order.php" class="checkout-layout">
            <div class="checkout-main">

                <div class="account-card">
                    <div class="account-card-head account-card-head-row">
                        <div>
                            <h2>Delivery Address</h2>
                            <p>Only your approved delivery address can be used for this order.</p>
                        </div>
                        <a class="text-link" href="<?= BASE_URL ?>account/profile.php">Change</a>
                    </div>

                    <div class="address-box">
                        <strong><?= e((string) $user['name']) ?></strong><br>
                        <?= e((string) $user['mobile']) ?><br>
                        <?= e(formatAddress($address)) ?>
                        <?php if (trim((string) ($address['landmark'] ?? '')) !== ''): ?>
                            <br>Landmark: <?= e((string) $address['landmark']) ?>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                </div>

                <div class="account-card">
                    <div class="account-card-head">
                        <h2>Order Through WhatsApp</h2>
                        <p>Your order will first be saved in Laxmi Fresh Mart and then opened in WhatsApp.</p>
                    </div>

                    <div class="payment-option">
                        <span>
                            <strong>WhatsApp Order</strong>
                            <small>Review the order in WhatsApp and send it to Laxmi Fresh Mart.</small>
                        </span>
                    </div>
                </div>

                <div class="account-card">
                    <div class="account-card-head">
                        <h2>Order Note</h2>
                        <p>Optional delivery instructions.</p>
                    </div>

                    <textarea
                        name="customer_note"
                        class="checkout-note"
                        rows="4"
                        maxlength="2000"
                        placeholder="Any delivery instructions..."
                    ></textarea>
                </div>

                <div class="checkout-whatsapp-note">
                    After you place the order, your order will be saved and WhatsApp will open with the complete order details.
                </div>

            </div>

            <aside class="summary-card checkout-summary">
                <h3>Your Order</h3>

                <?php foreach ($items as $item): ?>
                    <div class="checkout-product-row">
                        <div>
                            <strong><?= e((string) $item['name']) ?></strong>
                            <span>
                                <?= (int) $item['cart_quantity'] ?>
                                ×
                                <?= e(rtrim(rtrim(number_format((float) $item['pack_quantity'], 3, '.', ''), '0'), '.')) ?>
                                <?= e((string) $item['short_name']) ?> pack
                            </span>
                        </div>
                        <strong>₹<?= number_format((float) $item['line_total'], 2) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="summary-row">
                    <span>Delivery</span>
                    <span>₹0.00</span>
                </div>

                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>

                <button class="btn-primary checkout-submit" type="submit">
                    Place Order &amp; Continue to WhatsApp
                </button>

                <a class="checkout-back" href="<?= BASE_URL ?>cart.php">
                    ← Back to Cart
                </a>
            </aside>
        </form>

    </div>
</section>

<?php require 'includes/store-footer.php'; ?>
