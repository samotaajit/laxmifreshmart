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

if (!$userId) {

    $_SESSION['error'] = 'Invalid user request.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Admin ID
|--------------------------------------------------------------------------
*/

$adminId = (int) ($_SESSION['admin_id'] ?? 0);

if ($adminId <= 0) {

    $_SESSION['error'] = 'Admin session expired. Please login again.';

    header('Location: ../login.php');
    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Get User
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
    | Get Primary Address
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            approval_status
        FROM user_addresses
        WHERE user_id = :user_id
        AND is_primary = 1
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':user_id' => $userId
    ]);

    $address = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$address) {

        throw new RuntimeException(
            'This user cannot be approved because no primary delivery address was found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Approve User
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE users

        SET
            status = 'approved',
            rejection_reason = NULL,
            approved_at = NOW(),
            approved_by = :approved_by

        WHERE id = :id
        AND status = 'pending'
    ");

    $stmt->execute([
        ':approved_by' => $adminId,
        ':id' => $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Approve Primary Address
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE user_addresses

        SET
            approval_status = 'approved',
            rejection_reason = NULL,
            approved_at = NOW(),
            approved_by = :approved_by

        WHERE id = :id
    ");

    $stmt->execute([
        ':approved_by' => $adminId,
        ':id' => $address['id']
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
        ':title' => 'Account Approved',
        ':message' =>
            'Your Laxmi Fresh Mart account has been approved. You can now login and place orders.',
        ':type' => 'account_approved',
        ':reference_id' => $userId
    ]);


    $pdo->commit();


    $_SESSION['success'] =
        'User approved successfully. The user and primary delivery address are now approved.';

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