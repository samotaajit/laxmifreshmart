<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Order Details';
$currentPage = 'orders';


$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$orderId) {

    $_SESSION['error'] = 'Invalid order.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        o.*,

        u.name AS customer_name,
        u.mobile AS customer_mobile,
        u.email AS customer_email,

        ua.address_line1,
        ua.district,
        ua.state,
        ua.pincode,
        ua.landmark

    FROM orders o

    INNER JOIN users u
        ON u.id = o.user_id

    INNER JOIN user_addresses ua
        ON ua.id = o.address_id

    WHERE o.id = :id

    LIMIT 1
");


$stmt->execute([
    ':id' => $orderId
]);


$order = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {

    $_SESSION['error'] = 'Order not found.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Order Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        product_id,
        product_variant_id,
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
    ':order_id' => $orderId
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Status History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        osh.id,
        osh.status,
        osh.note,
        osh.created_at,

        a.name AS admin_name

    FROM order_status_history osh

    LEFT JOIN admins a
        ON a.id = osh.changed_by

    WHERE osh.order_id = :order_id

    ORDER BY osh.created_at DESC, osh.id DESC
");


$stmt->execute([
    ':order_id' => $orderId
]);


$history = $stmt->fetchAll(PDO::FETCH_ASSOC);


$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'out_for_delivery',
    'delivered',
    'cancelled',
    'rejected'
];


function orderStatusLabel(string $status): string
{
    return match ($status) {

        'pending' =>
            'Pending',

        'confirmed' =>
            'Confirmed',

        'processing' =>
            'Processing',

        'out_for_delivery' =>
            'Out for Delivery',

        'delivered' =>
            'Delivered',

        'cancelled' =>
            'Cancelled',

        'rejected' =>
            'Rejected',

        default =>
            ucfirst(str_replace('_', ' ', $status))
    };
}


function orderStatusClass(string $status): string
{
    return match ($status) {

        'pending' =>
            'status-pending',

        'confirmed' =>
            'status-confirmed',

        'processing' =>
            'status-processing',

        'out_for_delivery' =>
            'status-delivery',

        'delivered' =>
            'status-delivered',

        'cancelled',
        'rejected' =>
            'status-cancelled',

        default =>
            'status-pending'
    };
}

function adminOrderFormatQuantity(float $value): string
{
    return rtrim(
        rtrim(
            number_format($value, 3, '.', ''),
            '0'
        ),
        '.'
    );
}

/*
|--------------------------------------------------------------------------
| Address
|--------------------------------------------------------------------------
*/

$addressParts = array_filter([
    $order['address_line1'],
    '',
    '',
    '',
    '',
    $order['district'],
    $order['state'],
    $order['pincode']
]);


$address = implode(', ', $addressParts);

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

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }


        .order-title {
            font-size: 20px;
            font-weight: 700;
            color: #075B2A;
        }


        .order-subtitle {
            color: #858b86;
            font-size: 12px;
            margin-top: 4px;
        }


        .status-badge {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }


        .status-pending {
            background: #fff4d7;
            color: #936b00;
        }


        .status-confirmed {
            background: #e8f1ff;
            color: #245a9b;
        }


        .status-processing {
            background: #eee9ff;
            color: #6547a8;
        }


        .status-delivery {
            background: #e7f5ff;
            color: #16709b;
        }


        .status-delivered {
            background: #e6f5e8;
            color: #176b2d;
        }


        .status-cancelled {
            background: #fde8e8;
            color: #b42318;
        }


        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #252925;
            margin-bottom: 15px;
        }


        .info-box {
            background: #f7f9f6;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }


        .info-label {
            font-size: 10px;
            color: #858b86;
            margin-bottom: 4px;
        }


        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #252925;
        }


        .info-value-light {
            font-weight: 400;
        }


        .product-name {
            font-weight: 600;
            color: #252925;
        }


        .product-option {
            color: #858b86;
            font-size: 11px;
            margin-top: 3px;
        }


        .price-value {
            font-weight: 600;
        }


        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            color: #656b66;
            font-size: 13px;
        }


        .summary-total {
            border-top: 1px solid #e4e8e3;
            margin-top: 8px;
            padding-top: 12px;
            color: #075B2A;
            font-size: 16px;
            font-weight: 700;
        }


        .timeline {
            position: relative;
        }


        .timeline-item {
            position: relative;
            padding-left: 27px;
            padding-bottom: 22px;
        }


        .timeline-item:last-child {
            padding-bottom: 0;
        }


        .timeline-item::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 17px;
            bottom: -3px;
            width: 1px;
            background: #dfe5df;
        }


        .timeline-item:last-child::before {
            display: none;
        }


        .timeline-dot {
            position: absolute;
            left: 0;
            top: 3px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #075B2A;
        }


        .timeline-status {
            font-size: 12px;
            font-weight: 700;
            color: #252925;
        }


        .timeline-meta {
            color: #858b86;
            font-size: 10px;
            margin-top: 3px;
        }


        .timeline-note {
            color: #555d56;
            font-size: 11px;
            margin-top: 5px;
        }


        .customer-link {
            color: #075B2A;
            text-decoration: none;
            font-weight: 600;
        }


        .customer-link:hover {
            text-decoration: underline;
        }

    </style>

</head>


<body>


<?php require_once '../../includes/admin-sidebar.php'; ?>


<main class="admin-main">


    <?php require_once '../../includes/admin-header.php'; ?>


    <div class="admin-content">


        <!-- BACK -->


        <div class="mb-3">

            <a
                href="index.php"
                class="text-decoration-none"
                style="color:#075B2A;font-size:12px;font-weight:600;"
            >
                ← Back to Orders
            </a>

        </div>


        <!-- HEADER -->


        <div class="admin-card mb-4">


            <div class="admin-card-body">


                <div class="order-header">


                    <div>

                        <div class="order-title">

                            Order #<?= e(
                                $order['order_number']
                            ) ?>

                        </div>


                        <div class="order-subtitle">

                            Placed on
                            <?= e(
                                date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                )
                            ) ?>

                        </div>

                    </div>


                    <div>

                        <span
                            class="status-badge
                            <?= e(
                                orderStatusClass(
                                    $order['order_status']
                                )
                            ) ?>"
                        >

                            <?= e(
                                orderStatusLabel(
                                    $order['order_status']
                                )
                            ) ?>

                        </span>

                    </div>


                </div>


            </div>


        </div>


        <div class="row g-4">


            <!-- LEFT -->


            <div class="col-12 col-xl-8">


                <!-- CUSTOMER -->


                <div class="admin-card mb-4">


                    <div class="admin-card-body">


                        <div class="section-title">
                            Customer & Delivery
                        </div>


                        <div class="row g-3">


                            <div class="col-md-6">


                                <div class="info-box">

                                    <div class="info-label">
                                        Customer
                                    </div>

                                    <div class="info-value">

                                        <?= e(
                                            $order['customer_name']
                                        ) ?>

                                    </div>

                                    <div
                                        class="info-value info-value-light mt-1"
                                    >

                                        <?= e(
                                            $order['customer_mobile']
                                        ) ?>

                                    </div>


                                    <?php if (!empty($order['customer_email'])): ?>

                                        <div
                                            class="info-value info-value-light mt-1"
                                        >

                                            <?= e(
                                                $order['customer_email']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                            </div>


                            <div class="col-md-6">


                                <div class="info-box">

                                    <div class="info-label">
                                        Delivery Address
                                    </div>

                                    <div class="info-value info-value-light">

                                        <?= e($address) ?>

                                    </div>


                                    <?php if (!empty($order['landmark'])): ?>

                                        <div
                                            class="info-value-light mt-2"
                                            style="font-size:11px;"
                                        >

                                            <strong>
                                                Landmark:
                                            </strong>

                                            <?= e(
                                                $order['landmark']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- ITEMS -->


                <div class="admin-card mb-4">

    <div class="admin-card-body p-0">

        <div class="p-4 pb-2">

            <div class="section-title mb-0">
                Order Items
            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Pack
                        </th>

                        <th>
                            Pack Price
                        </th>

                        <th class="text-end">
                            Subtotal
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($items as $item): ?>

                        <?php

                        /*
                         * New pack system
                         */
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

                        $subtotal = (float) $item['subtotal'];

                        ?>


                        <tr>


                            <!-- PRODUCT -->

                            <td>

                                <div class="product-name">

                                    <?= e(
                                        $item['product_name']
                                    ) ?>

                                </div>

                                <div class="product-option">

                                    <?= e(
                                        $item['unit_name']
                                    ) ?>

                                </div>

                            </td>


                            <!-- PACK -->

                            <td>

                                <div style="font-weight:600;">

                                    <?= e(
                                        adminOrderFormatQuantity(
                                            $packCount
                                        )
                                    ) ?>

                                    ×

                                    <?= e(
                                        adminOrderFormatQuantity(
                                            $packQuantity
                                        )
                                    ) ?>

                                    <?= e(
                                        $unitShortName
                                    ) ?>

                                    pack

                                </div>

                                <div
                                    style="
                                        font-size:11px;
                                        color:#858b86;
                                        margin-top:3px;
                                    "
                                >

                                    Total quantity:

                                    <?= e(
                                        adminOrderFormatQuantity(
                                            $packCount * $packQuantity
                                        )
                                    ) ?>

                                    <?= e(
                                        $unitShortName
                                    ) ?>

                                </div>

                            </td>


                            <!-- PACK PRICE -->

                            <td>

                                ₹<?= number_format(
                                    $packPrice,
                                    2
                                ) ?>

                                <div
                                    style="
                                        font-size:10px;
                                        color:#858b86;
                                        margin-top:2px;
                                    "
                                >
                                    per pack
                                </div>

                            </td>


                            <!-- SUBTOTAL -->

                            <td class="text-end">

                                <span class="price-value">

                                    ₹<?= number_format(
                                        $subtotal,
                                        2
                                    ) ?>

                                </span>

                            </td>


                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


                <!-- CUSTOMER NOTE -->


                <?php if (!empty($order['customer_note'])): ?>


                    <div class="admin-card mb-4">


                        <div class="admin-card-body">


                            <div class="section-title">
                                Customer Note
                            </div>


                            <div
                                style="font-size:12px;color:#555d56;line-height:1.6;"
                            >

                                <?= nl2br(
                                    e(
                                        $order['customer_note']
                                    )
                                ) ?>

                            </div>


                        </div>


                    </div>


                <?php endif; ?>


                <!-- STATUS HISTORY -->


                <div class="admin-card">


                    <div class="admin-card-body">


                        <div class="section-title">
                            Order Timeline
                        </div>


                        <?php if (empty($history)): ?>


                            <div
                                class="text-muted"
                                style="font-size:12px;"
                            >
                                No status history available.
                            </div>


                        <?php else: ?>


                            <div class="timeline">


                                <?php foreach ($history as $entry): ?>


                                    <div class="timeline-item">


                                        <div class="timeline-dot"></div>


                                        <div class="timeline-status">

                                            <?= e(
                                                orderStatusLabel(
                                                    $entry['status']
                                                )
                                            ) ?>

                                        </div>


                                        <div class="timeline-meta">

                                            <?= e(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        $entry['created_at']
                                                    )
                                                )
                                            ) ?>


                                            <?php if (!empty($entry['admin_name'])): ?>

                                                ·
                                                <?= e(
                                                    $entry['admin_name']
                                                ) ?>

                                            <?php endif; ?>


                                        </div>


                                        <?php if (!empty($entry['note'])): ?>

                                            <div class="timeline-note">

                                                <?= e(
                                                    $entry['note']
                                                ) ?>

                                            </div>

                                        <?php endif; ?>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                        <?php endif; ?>


                    </div>


                </div>


            </div>


            <!-- RIGHT -->


            <div class="col-12 col-xl-4">


                <!-- STATUS -->


                <div class="admin-card mb-4">


                    <div class="admin-card-body">


                        <div class="section-title">
                            Update Order
                        </div>


                        <form
                            method="POST"
                            action="update-status.php"
                        >


                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int) $order['id'] ?>"
                            >


                            <div class="mb-3">


                                <label class="form-label">
                                    Order Status
                                </label>


                                <select
                                    name="order_status"
                                    class="form-select"
                                    required
                                >


                                    <?php foreach ($allowedStatuses as $itemStatus): ?>

                                        <option
                                            value="<?= e(
                                                $itemStatus
                                            ) ?>"
                                            <?= $order['order_status'] === $itemStatus
                                                ? 'selected'
                                                : '' ?>
                                        >

                                            <?= e(
                                                orderStatusLabel(
                                                    $itemStatus
                                                )
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>


                                </select>


                            </div>


                            <div class="mb-3">


                                <label class="form-label">
                                    Note
                                </label>


                                <textarea
                                    name="note"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Optional note about this status change..."
                                ></textarea>


                            </div>


                            <button
                                type="submit"
                                class="btn btn-lfm w-100"
                            >
                                Update Status
                            </button>


                        </form>


                    </div>


                </div>


                <!-- ORDER SUMMARY -->


                <div class="admin-card mb-4">


                    <div class="admin-card-body">


                        <div class="section-title">
                            Order Summary
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
                                Grand Total
                            </span>

                            <span>
                                ₹<?= number_format(
                                    (float) $order['total'],
                                    2
                                ) ?>
                            </span>

                        </div>


                    </div>


                </div>


                <!-- PAYMENT -->


                <div class="admin-card">


                    <div class="admin-card-body">


                        <div class="section-title">
                            Payment
                        </div>


                        <div class="info-box">


                            <div class="info-label">
                                Payment Method
                            </div>

                            <div class="info-value">
                                Cash on Delivery
                            </div>


                            <div class="info-label mt-3">
                                Payment Status
                            </div>

                            <div class="info-value">

                                <?= e(
                                    ucfirst(
                                        $order['payment_status']
                                    )
                                ) ?>

                            </div>


                            <div class="info-label mt-3">
                                Order Source
                            </div>

                            <div class="info-value">

                                <?= e(
                                    ucfirst(
                                        $order['order_source']
                                    )
                                ) ?>

                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </div>


    </div>


</main>


<?php require_once '../../includes/admin-footer.php'; ?>

</body>

</html>