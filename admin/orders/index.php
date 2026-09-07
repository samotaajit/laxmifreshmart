<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Orders';
$currentPage = 'orders';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status = $_GET['status'] ?? '';

$source = $_GET['source'] ?? '';

$dateFrom = trim($_GET['date_from'] ?? '');

$dateTo = trim($_GET['date_to'] ?? '');


$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'out_for_delivery',
    'delivered',
    'cancelled',
    'rejected'
];


if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}


if (!in_array($source, ['website', 'whatsapp'], true)) {
    $source = '';
}


/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.subtotal,
        o.discount,
        o.delivery_charge,
        o.total,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.order_source,
        o.created_at,

        u.id AS user_id,
        u.name AS customer_name,
        u.mobile AS customer_mobile,

        COUNT(oi.id) AS item_count

    FROM orders o

    INNER JOIN users u
        ON u.id = o.user_id

    LEFT JOIN order_items oi
        ON oi.order_id = o.id

    WHERE 1 = 1
";

$where  = [];
$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            o.order_number LIKE :search
            OR u.name LIKE :search
            OR u.mobile LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $where[] = "
        o.order_status = :status
    ";

    $params[':status'] = $status;
}


/*
|--------------------------------------------------------------------------
| Source
|--------------------------------------------------------------------------
*/

if ($source !== '') {

    $where[] = "
        o.order_source = :source
    ";

    $params[':source'] = $source;
}


/*
|--------------------------------------------------------------------------
| Date From
|--------------------------------------------------------------------------
*/

if ($dateFrom !== '') {

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $dateFrom
    );

    if ($date !== false) {

        $where[] = "
            o.created_at >= :date_from
        ";

        $params[':date_from'] =
            $date->format('Y-m-d') . ' 00:00:00';
    }
}


/*
|--------------------------------------------------------------------------
| Date To
|--------------------------------------------------------------------------
*/

if ($dateTo !== '') {

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $dateTo
    );

    if ($date !== false) {

        $where[] = "
            o.created_at <= :date_to
        ";

        $params[':date_to'] =
            $date->format('Y-m-d') . ' 23:59:59';
    }
}


if (!empty($where)) {

    $sql .= "
        AND " . implode(' AND ', $where);
}


$sql .= "
    GROUP BY
        o.id,
        o.order_number,
        o.subtotal,
        o.discount,
        o.delivery_charge,
        o.total,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.order_source,
        o.created_at,
        u.id,
        u.name,
        u.mobile

    ORDER BY
        o.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$stats = [
    'total'       => 0,
    'pending'     => 0,
    'processing'  => 0,
    'out_delivery' => 0,
    'delivered'   => 0,
    'cancelled'   => 0,
];


$stats['total'] = count($orders);


foreach ($orders as $order) {

    switch ($order['order_status']) {

        case 'pending':
            $stats['pending']++;
            break;

        case 'processing':
            $stats['processing']++;
            break;

        case 'out_for_delivery':
            $stats['out_delivery']++;
            break;

        case 'delivered':
            $stats['delivered']++;
            break;

        case 'cancelled':
        case 'rejected':
            $stats['cancelled']++;
            break;
    }
}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;

unset(
    $_SESSION['success'],
    $_SESSION['error']
);


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

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

        .order-stat {
            background: #fff;
            border-radius: 13px;
            padding: 18px 20px;
            box-shadow: 0 3px 18px rgba(0,0,0,.045);
            height: 100%;
        }

        .order-stat-label {
            color: #858b86;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .order-stat-value {
            color: #075B2A;
            font-size: 25px;
            font-weight: 700;
        }

        .order-stat-warning {
            color: #936b00;
        }

        .order-stat-danger {
            color: #b42318;
        }


        .order-number {
            color: #075B2A;
            font-weight: 700;
            font-size: 13px;
        }


        .customer-name {
            font-weight: 600;
            color: #252925;
        }


        .customer-mobile {
            color: #858b86;
            font-size: 11px;
            margin-top: 2px;
        }


        .order-date {
            color: #555d56;
            font-size: 12px;
        }


        .order-time {
            color: #858b86;
            font-size: 11px;
            margin-top: 2px;
        }


        .order-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
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


        .source-badge {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
        }


        .source-website {
            background: #eef6ec;
            color: #075B2A;
        }


        .source-whatsapp {
            background: #e7f7ed;
            color: #16803c;
        }


        .payment-badge {
            font-size: 10px;
            color: #656b66;
        }


        .view-btn {
            border: 1px solid #075B2A;
            color: #075B2A;
            background: #fff;
            border-radius: 7px;
            font-size: 11px;
            padding: 6px 11px;
            font-weight: 600;
        }


        .view-btn:hover {
            background: #075B2A;
            color: #fff;
        }


        .empty-state {
            padding: 65px 20px;
            text-align: center;
        }


        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #eaf6e8;
            color: #075B2A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

    </style>

</head>


<body>


<?php require_once '../../includes/admin-sidebar.php'; ?>


<main class="admin-main">


    <?php require_once '../../includes/admin-header.php'; ?>


    <div class="admin-content">


        <!-- STATISTICS -->

        <div class="row g-4 mb-4">


            <div class="col-6 col-md-3">

                <div class="order-stat">

                    <div class="order-stat-label">
                        Total Orders
                    </div>

                    <div class="order-stat-value">
                        <?= number_format($stats['total']) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-md-3">

                <div class="order-stat">

                    <div class="order-stat-label">
                        Pending
                    </div>

                    <div class="order-stat-value order-stat-warning">
                        <?= number_format($stats['pending']) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-md-3">

                <div class="order-stat">

                    <div class="order-stat-label">
                        Processing
                    </div>

                    <div class="order-stat-value">
                        <?= number_format($stats['processing']) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-md-3">

                <div class="order-stat">

                    <div class="order-stat-label">
                        Delivered
                    </div>

                    <div class="order-stat-value">
                        <?= number_format($stats['delivered']) ?>
                    </div>

                </div>

            </div>


        </div>


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


        <div class="admin-card">


            <div class="admin-card-header">

                <div>

                    <h5>
                        Orders
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Manage customer orders and delivery status
                    </div>

                </div>

            </div>


            <!-- FILTERS -->


            <div class="admin-card-body border-bottom">


                <form method="GET" action="index.php">

                    <div class="row g-3">


                        <div class="col-12 col-lg-4">

                            <label class="form-label">
                                Search
                            </label>

                            <input
                                type="search"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Order number, customer or mobile..."
                            >

                        </div>


                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="">
                                    All Statuses
                                </option>

                                <?php foreach ($allowedStatuses as $itemStatus): ?>

                                    <option
                                        value="<?= e($itemStatus) ?>"
                                        <?= $status === $itemStatus
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


                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                Source
                            </label>

                            <select
                                name="source"
                                class="form-select"
                            >

                                <option value="">
                                    All Sources
                                </option>

                                <option
                                    value="website"
                                    <?= $source === 'website'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Website
                                </option>

                                <option
                                    value="whatsapp"
                                    <?= $source === 'whatsapp'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    WhatsApp
                                </option>

                            </select>

                        </div>


                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                From
                            </label>

                            <input
                                type="date"
                                name="date_from"
                                class="form-control"
                                value="<?= e($dateFrom) ?>"
                            >

                        </div>


                        <div class="col-6 col-lg-2">

                            <label class="form-label">
                                To
                            </label>

                            <input
                                type="date"
                                name="date_to"
                                class="form-control"
                                value="<?= e($dateTo) ?>"
                            >

                        </div>


                        <div class="col-6 col-lg-1">

                            <button
                                type="submit"
                                class="btn btn-lfm w-100"
                            >
                                Filter
                            </button>

                        </div>


                        <div class="col-6 col-lg-1">

                            <a
                                href="index.php"
                                class="btn btn-light border w-100"
                            >
                                Clear
                            </a>

                        </div>


                    </div>

                </form>


            </div>


            <!-- TABLE -->


            <div class="admin-card-body p-0">


                <?php if (empty($orders)): ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            ◈
                        </div>

                        <h6>
                            No orders found
                        </h6>

                        <p class="text-muted mb-0">
                            There are no orders matching your filters.
                        </p>

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
                                        Date
                                    </th>

                                    <th>
                                        Items
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Source
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th class="text-end">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($orders as $order): ?>


                                <tr>


                                    <td>

                                        <div class="order-number">

                                            #<?= e(
                                                $order['order_number']
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="customer-name">

                                            <?= e(
                                                $order['customer_name']
                                            ) ?>

                                        </div>

                                        <div class="customer-mobile">

                                            <?= e(
                                                $order['customer_mobile']
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="order-date">

                                            <?= e(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                        <div class="order-time">

                                            <?= e(
                                                date(
                                                    'h:i A',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            (int) $order['item_count']
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>
                                            ₹<?= number_format(
                                                (float) $order['total'],
                                                2
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <div class="payment-badge">

                                            Cash on Delivery

                                        </div>

                                        <div
                                            style="font-size:10px;"
                                            class="text-muted"
                                        >

                                            <?= e(
                                                ucfirst(
                                                    $order['payment_status']
                                                )
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <span
                                            class="source-badge
                                            <?= $order['order_source'] === 'whatsapp'
                                                ? 'source-whatsapp'
                                                : 'source-website' ?>"
                                        >

                                            <?= e(
                                                ucfirst(
                                                    $order['order_source']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="order-badge
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

                                    </td>


                                    <td class="text-end">

                                        <a
                                            href="view.php?id=<?= (int) $order['id'] ?>"
                                            class="btn btn-sm view-btn"
                                        >
                                            View
                                        </a>

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


<?php require_once '../../includes/admin-footer.php'; ?>

</body>

</html>