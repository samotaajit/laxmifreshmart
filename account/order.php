<?php

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/customer-auth.php';

$user = requireCustomer($pdo);

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$orderId) {
    redirect(BASE_URL . 'account/orders.php');
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.*,

        ua.address_line1,
        ua.district,
        ua.state,
        ua.pincode,
        ua.landmark

    FROM orders o

    INNER JOIN user_addresses ua
        ON ua.id = o.address_id

    WHERE o.id = :id
      AND o.user_id = :user_id

    LIMIT 1
");

$stmt->execute([
    ':id'      => $orderId,
    ':user_id' => (int) $user['id'],
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect(BASE_URL . 'account/orders.php');
}


/*
|--------------------------------------------------------------------------
| Order Items
|--------------------------------------------------------------------------
|
| Pack system:
|
| pack_count    = number of packs ordered
| pack_quantity = quantity contained in one pack
| unit_price    = price of one pack
| subtotal      = pack_count × unit_price
|
*/

$stmt = $pdo->prepare("
    SELECT
        product_name,

        quantity,

        pack_quantity,
        pack_count,

        unit_name,
        unit_short_name,

        unit_price,
        subtotal

    FROM order_items

    WHERE order_id = :order_id

    ORDER BY id ASC
");

$stmt->execute([
    ':order_id' => $orderId,
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Status History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        status,
        note,
        created_at

    FROM order_status_history

    WHERE order_id = :order_id

    ORDER BY created_at DESC, id DESC
");

$stmt->execute([
    ':order_id' => $orderId,
]);

$history = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function orderFormatQuantity(float $value): string
{
    return rtrim(
        rtrim(
            number_format($value, 3, '.', ''),
            '0'
        ),
        '.'
    );
}

function orderPackLabel(array $item): string
{
    $packCount = isset($item['pack_count'])
        ? (float) $item['pack_count']
        : 1;

    $packQuantity = isset($item['pack_quantity'])
        ? (float) $item['pack_quantity']
        : (float) ($item['quantity'] ?? 0);

    $unit = (string) (
        $item['unit_short_name']
        ?? $item['unit_name']
        ?? ''
    );

    /*
     * New pack records
     */
    if ($packQuantity > 0) {
        return orderFormatQuantity($packCount)
            . ' × '
            . orderFormatQuantity($packQuantity)
            . ' '
            . $unit
            . ' pack';
    }

    /*
     * Old orders created before pack_quantity existed.
     */
    return orderFormatQuantity(
        (float) ($item['quantity'] ?? 0)
    ) . ' ' . $unit;
}

$pageTitle = 'Order #' . $order['order_number'];
$activePage = '';

?>

<?php require '../includes/store-header.php'; ?>


<section class="page-hero">

    <div class="container">

        <h1>
            Order #<?= e($order['order_number']) ?>
        </h1>

        <div class="breadcrumb">
            Home / My Account / Orders / Order Details
        </div>

    </div>

</section>


<section class="account-page">

    <div class="container">

        <div class="account-layout">


            <!-- SIDEBAR -->

            <aside class="account-sidebar">

                <div class="account-user-mini">

                    <div class="account-avatar">
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

                    <strong>
                        <?= e($user['name']) ?>
                    </strong>

                    <span>
                        <?= e($user['mobile']) ?>
                    </span>

                </div>


                <nav class="account-nav">

                    <a
                        href="<?= BASE_URL ?>account/index.php"
                    >
                        Overview
                    </a>

                    <a
                        href="<?= BASE_URL ?>account/profile.php"
                    >
                        Profile & Address
                    </a>

                    <a
                        href="<?= BASE_URL ?>account/change-password.php"
                    >
                        Change Password
                    </a>

                    <a
                        class="active"
                        href="<?= BASE_URL ?>account/orders.php"
                    >
                        Order History
                    </a>

                    <a
                        href="<?= BASE_URL ?>logout.php"
                    >
                        Logout
                    </a>

                </nav>

            </aside>


            <!-- MAIN -->

            <div class="account-main">


                <!-- ORDER HEADER -->

                <div class="order-detail-head">

                    <div>

                        <strong>
                            ₹<?= number_format(
                                (float) $order['total'],
                                2
                            ) ?>
                        </strong>

                        <span>
                            <?= e(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                )
                            ) ?>
                        </span>

                    </div>


                    <span class="status-pill">

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

                </div>


                <!-- ITEMS -->

                <div class="account-card">

                    <div class="account-card-head">

                        <h2>
                            Items
                        </h2>

                    </div>


                    <div class="order-items">

                        <?php foreach ($items as $item): ?>

                            <?php
                            $packCount = isset($item['pack_count'])
                                ? (float) $item['pack_count']
                                : 1;

                            $packQuantity = isset($item['pack_quantity'])
                                ? (float) $item['pack_quantity']
                                : (float) ($item['quantity'] ?? 0);

                            $unitShortName = (string) (
                                $item['unit_short_name']
                                ?? $item['unit_name']
                                ?? ''
                            );

                            $packPrice = (float) $item['unit_price'];

                            $lineSubtotal = (float) $item['subtotal'];
                            ?>

                            <div class="order-item-row">

                                <div>

                                    <strong>
                                        <?= e(
                                            $item['product_name']
                                        ) ?>
                                    </strong>

                                    <span>

                                        <?= e(
                                            orderFormatQuantity(
                                                $packCount
                                            )
                                        ) ?>

                                        ×

                                        <?= e(
                                            orderFormatQuantity(
                                                $packQuantity
                                            )
                                        ) ?>

                                        <?= e(
                                            $unitShortName
                                        ) ?>

                                        pack

                                        ·

                                        ₹<?= number_format(
                                            $packPrice,
                                            2
                                        ) ?>

                                        / pack

                                    </span>

                                </div>


                                <strong>

                                    ₹<?= number_format(
                                        $lineSubtotal,
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- DELIVERY & PAYMENT -->

                <div class="account-card">

                    <div class="account-card-head">

                        <h2>
                            Delivery & Payment
                        </h2>

                    </div>


                    <div class="info-grid">

                        <div>

                            <small>
                                Delivery Address
                            </small>

                            <strong>

                                <?= e(
                                    formatAddress($order)
                                ) ?>

                                <?php if (!empty($order['landmark'])): ?>

                                    <br>

                                    <span>
                                        Landmark:
                                        <?= e(
                                            $order['landmark']
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div>

                            <small>
                                Payment
                            </small>

                            <strong>
                                Cash on Delivery
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- ORDER SUMMARY -->

                <div class="account-card">

                    <div class="account-card-head">

                        <h2>
                            Order Summary
                        </h2>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span>
                            ₹<?= number_format(
                                (float) $order['subtotal'],
                                2
                            ) ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Discount
                        </span>

                        <span>
                            − ₹<?= number_format(
                                (float) $order['discount'],
                                2
                            ) ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Delivery
                        </span>

                        <span>
                            ₹<?= number_format(
                                (float) $order['delivery_charge'],
                                2
                            ) ?>
                        </span>

                    </div>


                    <div class="summary-row summary-total">

                        <span>
                            Total
                        </span>

                        <span>
                            ₹<?= number_format(
                                (float) $order['total'],
                                2
                            ) ?>
                        </span>

                    </div>

                </div>


                <!-- STATUS HISTORY -->

                <div class="account-card">

                    <div class="account-card-head">

                        <h2>
                            Status History
                        </h2>

                    </div>


                    <?php if (!$history): ?>

                        <div class="empty-inline">
                            Status history will appear here.
                        </div>

                    <?php else: ?>

                        <div class="account-timeline">

                            <?php foreach ($history as $entry): ?>

                                <div class="account-timeline-item">

                                    <span class="timeline-dot"></span>

                                    <strong>
                                        <?= e(
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $entry['status']
                                                )
                                            )
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= e(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $entry['created_at']
                                                )
                                            )
                                        ) ?>
                                    </small>

                                    <?php if (!empty($entry['note'])): ?>

                                        <p>
                                            <?= e(
                                                $entry['note']
                                            ) ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>


            </div>

        </div>

    </div>

</section>


<?php require '../includes/store-footer.php'; ?>