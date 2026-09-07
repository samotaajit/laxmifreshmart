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

$action = $_POST['action'] ?? '';


if (!$userId) {

    $_SESSION['error'] = 'Invalid user.';

    header('Location: index.php');
    exit;
}


if (!in_array($action, ['block', 'unblock'], true)) {

    $_SESSION['error'] = 'Invalid action.';

    header('Location: view.php?id=' . $userId);
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
            'User not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Block
    |--------------------------------------------------------------------------
    */

    if ($action === 'block') {

        if ($user['status'] !== 'approved') {

            throw new RuntimeException(
                'Only approved users can be blocked.'
            );
        }


        $stmt = $pdo->prepare("
            UPDATE users

            SET status = 'blocked'

            WHERE id = :id
            AND status = 'approved'
        ");

        $stmt->execute([
            ':id' => $userId
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
            ':title' => 'Account Blocked',
            ':message' =>
                'Your Laxmi Fresh Mart account has been blocked. Please contact the administrator if you believe this was done in error.',
            ':type' => 'account_blocked',
            ':reference_id' => $userId
        ]);


        $pdo->commit();


        $_SESSION['success'] =
            'User blocked successfully.';

    }


    /*
    |--------------------------------------------------------------------------
    | Unblock
    |--------------------------------------------------------------------------
    */

    else {

        if ($user['status'] !== 'blocked') {

            throw new RuntimeException(
                'Only blocked users can be unblocked.'
            );
        }


        $stmt = $pdo->prepare("
            UPDATE users

            SET status = 'approved'

            WHERE id = :id
            AND status = 'blocked'
        ");

        $stmt->execute([
            ':id' => $userId
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
            ':title' => 'Account Restored',
            ':message' =>
                'Your Laxmi Fresh Mart account has been restored. You can use your account again.',
            ':type' => 'account_unblocked',
            ':reference_id' => $userId
        ]);


        $pdo->commit();


        $_SESSION['success'] =
            'User unblocked successfully.';

    }

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