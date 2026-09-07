<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {

    $_SESSION['error'] =
        'Invalid unit.';

    header('Location: index.php');
    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Lock Unit
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name
        FROM units
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $unit = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$unit) {

        throw new RuntimeException(
            'Unit not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check Product Variants
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM product_variants
        WHERE unit_id = :unit_id
    ");

    $stmt->execute([
        ':unit_id' => $id
    ]);

    $usageCount = (int) $stmt->fetchColumn();


    if ($usageCount > 0) {

        throw new RuntimeException(
            'This unit cannot be deleted because ' .
            $usageCount .
            ' product/pack reference(s) are using it.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM units
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);


    $pdo->commit();


    $_SESSION['success'] =
        'Unit deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] =
        $e->getMessage();
}


header('Location: index.php');
exit;