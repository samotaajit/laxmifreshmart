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


$userId = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT
);

$rejectionReason = trim(
    $_POST['rejection_reason'] ?? ''
);


if (!$userId) {

    $_SESSION['error'] = 'Invalid user request.';

    header('Location: index.php');
    exit;
}


if ($rejectionReason === '') {

    $_SESSION['error'] =
        'Please provide a reason for rejection.';

    header(
        'Location: view.php?id=' . $userId
    );

    exit;
}


if (mb_strlen($rejectionReason) > 2000) {

    $_SESSION['error'] =
        'The rejection reason is too long.';

    header(
        'Location: view.php?id=' . $userId
    );

    exit;
}


$adminId = (int) ($_SESSION['admin_id'] ?? 0);

if ($adminId <= 0) {

    $_SESSION['error'] =
        'Admin session expired. Please login again.';

    header('Location: ../login.php');

    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Lock User
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            status
        FROM users
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':id' => $userId
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$user) {

        throw new RuntimeException(
            'User request not found.'
        );
    }


    if ($user['status'] !== 'pending') {

        throw new RuntimeException(
            'This user request has already been processed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reject User
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE users

        SET
            status = 'rejected',
            rejection_reason = :reason,
            approved_at = NULL,
            approved_by = NULL

        WHERE id = :id
        AND status = 'pending'
    ");

    $stmt->execute([
        ':reason' => $rejectionReason,
        ':id' => $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Reject Primary Address
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE user_addresses

        SET
            approval_status = 'rejected',
            rejection_reason = :reason,
            approved_at = NULL,
            approved_by = NULL

        WHERE user_id = :user_id
        AND is_primary = 1
    ");

    $stmt->execute([
        ':reason' => $rejectionReason,
        ':user_id' => $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            title,
            message,
            type,
            reference_id
        )
        VALUES (
            :user_id,
            :title,
            :message,
            :type,
            :reference_id
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':title' => 'Account Request Rejected',
        ':message' =>
            'Your Laxmi Fresh Mart registration request was rejected. Reason: '
            . $rejectionReason,
        ':type' => 'account_rejected',
        ':reference_id' => $userId
    ]);


    $pdo->commit();


    $_SESSION['success'] =
        'User request rejected successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] =
        $e->getMessage();
}


header(
    'Location: view.php?id=' . $userId
);

exit;