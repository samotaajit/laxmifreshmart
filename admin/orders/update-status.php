<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: index.php');
    exit;
}


$orderId = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);


$newStatus = $_POST['order_status'] ?? '';

$note = trim(
    $_POST['note'] ?? ''
);


$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'out_for_delivery',
    'delivered',
    'cancelled',
    'rejected'
];


if (!$orderId || $orderId <= 0) {

    $_SESSION['error'] =
        'Invalid order.';

    header('Location: index.php');
    exit;
}


if (!in_array($newStatus, $allowedStatuses, true)) {

    $_SESSION['error'] =
        'Invalid order status.';

    header(
        'Location: view.php?id=' .
        $orderId
    );

    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Lock Order
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            order_number,
            order_status

        FROM orders

        WHERE id = :id

        LIMIT 1

        FOR UPDATE
    ");


    $stmt->execute([
        ':id' => $orderId
    ]);


    $order = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        throw new RuntimeException(
            'Order not found.'
        );
    }


    $oldStatus =
        $order['order_status'];


    /*
    |--------------------------------------------------------------------------
    | Nothing Changed
    |--------------------------------------------------------------------------
    */

    if ($oldStatus === $newStatus) {

        $pdo->rollBack();


        $_SESSION['error'] =
            'The order is already marked as ' .
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $newStatus
                )
            ) .
            '.';


        header(
            'Location: view.php?id=' .
            $orderId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Order
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE orders

        SET
            order_status = :status

        WHERE id = :id
    ");


    $stmt->execute([
        ':status' => $newStatus,
        ':id'     => $orderId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Status History
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO order_status_history
        (
            order_id,
            status,
            note,
            changed_by
        )

        VALUES
        (
            :order_id,
            :status,
            :note,
            :changed_by
        )
    ");


    $stmt->execute([
        ':order_id' =>
            $orderId,

        ':status' =>
            $newStatus,

        ':note' =>
            $note !== ''
                ? $note
                : null,

        ':changed_by' =>
            adminId()
    ]);


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    $_SESSION['success'] =
        'Order #' .
        $order['order_number'] .
        ' status updated successfully.';


} catch (Throwable $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    $_SESSION['error'] =
        $e->getMessage();
}


header(
    'Location: view.php?id=' .
    $orderId
);

exit;