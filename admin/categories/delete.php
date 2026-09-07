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
        'Invalid category.';

    header('Location: index.php');
    exit;
}


try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Get Category
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            image
        FROM categories
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $category = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$category) {

        throw new RuntimeException(
            'Category not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Product Protection
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM products
        WHERE category_id = :category_id
    ");

    $stmt->execute([
        ':category_id' => $id
    ]);

    $productCount = (int) $stmt->fetchColumn();


    if ($productCount > 0) {

        throw new RuntimeException(
            'This category cannot be deleted because ' .
            $productCount .
            ' product(s) are assigned to it. ' .
            'Move or delete those products first.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM categories
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

    if (!empty($category['image'])) {

        $relativeImage =
            ltrim(
                $category['image'],
                '/'
            );


        $uploadPrefix =
            'admin/categories/uploads/';


        if (
            str_starts_with(
                $relativeImage,
                $uploadPrefix
            )
        ) {

            $filename =
                basename($relativeImage);


            $imageFile =
                __DIR__ .
                '/uploads/' .
                $filename;


            if (is_file($imageFile)) {
                @unlink($imageFile);
            }
        }
    }


    $_SESSION['success'] =
        'Category deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] =
        $e->getMessage();
}


header('Location: index.php');
exit;