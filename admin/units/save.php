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


$action = $_POST['action'] ?? '';

if (!in_array($action, ['create', 'edit'], true)) {

    $_SESSION['error'] = 'Invalid request.';

    header('Location: index.php');
    exit;
}


$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);


$name = trim($_POST['name'] ?? '');

$shortName = trim(
    $_POST['short_name'] ?? ''
);

$status = $_POST['status'] ?? 'active';


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($name === '') {

    $_SESSION['error'] =
        'Unit name is required.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


if ($shortName === '') {

    $_SESSION['error'] =
        'Short name is required.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


if (mb_strlen($name) > 50) {

    $_SESSION['error'] =
        'Unit name cannot exceed 50 characters.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


if (mb_strlen($shortName) > 20) {

    $_SESSION['error'] =
        'Short name cannot exceed 20 characters.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


if (!in_array($status, ['active', 'inactive'], true)) {

    $_SESSION['error'] =
        'Invalid unit status.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Name Uniqueness
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    $stmt = $pdo->prepare("
        SELECT id
        FROM units
        WHERE name = :name
        LIMIT 1
    ");

    $stmt->execute([
        ':name' => $name
    ]);

} else {

    if (!$id) {

        $_SESSION['error'] =
            'Invalid unit.';

        header('Location: index.php');
        exit;
    }


    $stmt = $pdo->prepare("
        SELECT id
        FROM units
        WHERE name = :name
        AND id != :id
        LIMIT 1
    ");

    $stmt->execute([
        ':name' => $name,
        ':id'   => $id
    ]);
}


if ($stmt->fetch()) {

    $_SESSION['error'] =
        'A unit with this name already exists.';

    header(
        'Location: ' .
        (
            $action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php'
        )
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CREATE
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    $stmt = $pdo->prepare("
        INSERT INTO units (
            name,
            short_name,
            status
        )
        VALUES (
            :name,
            :short_name,
            :status
        )
    ");

    $stmt->execute([
        ':name'       => $name,
        ':short_name' => $shortName,
        ':status'     => $status
    ]);


    $_SESSION['success'] =
        'Unit created successfully.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM units
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

if (!$stmt->fetch()) {

    $_SESSION['error'] =
        'Unit not found.';

    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    UPDATE units

    SET
        name = :name,
        short_name = :short_name,
        status = :status

    WHERE id = :id
");

$stmt->execute([
    ':name'       => $name,
    ':short_name' => $shortName,
    ':status'     => $status,
    ':id'         => $id
]);


$_SESSION['success'] =
    'Unit updated successfully.';

header('Location: index.php');
exit;