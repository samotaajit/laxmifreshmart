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
        'Invalid product.';

    header('Location: index.php');
    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            image
        FROM products
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $product =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        throw new RuntimeException(
            'Product not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check Orders
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM order_items
        WHERE product_id = :product_id
    ");

    $stmt->execute([
        ':product_id' => $id
    ]);

    $orderCount =
        (int) $stmt->fetchColumn();


    if ($orderCount > 0) {

        throw new RuntimeException(
            'This product cannot be deleted because it exists in ' .
            $orderCount .
            ' order item(s). Deactivate it instead.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    |
    | product_variants will be deleted automatically because
    | product_variants.product_id has ON DELETE CASCADE.
    |
    */

    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);


    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Delete Image
    |--------------------------------------------------------------------------
    */

    if (!empty($product['image'])) {

        $imagePath =
            dirname(__DIR__, 2) .
            '/' .
            ltrim(
                $product['image'],
                '/'
            );

        if (is_file($imagePath)) {
            @unlink($imagePath);
        }
    }


    $_SESSION['success'] =
        'Product deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] =
        $e->getMessage();
}


header('Location: index.php');
exit;