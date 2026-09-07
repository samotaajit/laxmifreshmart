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

$slug = trim($_POST['slug'] ?? '');

$description = trim(
    $_POST['description'] ?? ''
);

$displayOrder = filter_var(
    $_POST['display_order'] ?? 0,
    FILTER_VALIDATE_INT
);

$status = $_POST['status'] ?? 'active';


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($name === '') {

    $_SESSION['error'] =
        'Category name is required.';

    header(
        'Location: ' .
        ($action === 'edit'
            ? 'edit.php?id=' . (int) $id
            : 'create.php')
    );

    exit;
}


if (mb_strlen($name) > 100) {

    $_SESSION['error'] =
        'Category name cannot exceed 100 characters.';

    header(
        'Location: ' .
        ($action === 'edit'
            ? 'edit.php?id=' . (int) $id
            : 'create.php')
    );

    exit;
}


if ($displayOrder === false || $displayOrder < 0) {
    $displayOrder = 0;
}


if (!in_array($status, ['active', 'inactive'], true)) {

    $_SESSION['error'] =
        'Invalid category status.';

    header(
        'Location: ' .
        ($action === 'edit'
            ? 'edit.php?id=' . (int) $id
            : 'create.php')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Generate Slug
|--------------------------------------------------------------------------
*/

if ($slug === '') {

    $slug = strtolower($name);

    $slug = preg_replace(
        '/[^a-z0-9\s-]/',
        '',
        $slug
    );

    $slug = preg_replace(
        '/[\s-]+/',
        '-',
        $slug
    );

    $slug = trim(
        $slug,
        '-'
    );
}


$slug = strtolower($slug);

$slug = preg_replace(
    '/[^a-z0-9-]/',
    '-',
    $slug
);

$slug = preg_replace(
    '/-+/',
    '-',
    $slug
);

$slug = trim(
    $slug,
    '-'
);


if ($slug === '') {

    $_SESSION['error'] =
        'Unable to generate a valid slug.';

    header(
        'Location: ' .
        ($action === 'edit'
            ? 'edit.php?id=' . (int) $id
            : 'create.php')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Slug Uniqueness
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    $stmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE slug = :slug
        LIMIT 1
    ");

    $stmt->execute([
        ':slug' => $slug
    ]);

} else {

    if (!$id) {

        $_SESSION['error'] =
            'Invalid category.';

        header('Location: index.php');
        exit;
    }


    $stmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE slug = :slug
        AND id != :id
        LIMIT 1
    ");

    $stmt->execute([
        ':slug' => $slug,
        ':id' => $id
    ]);
}


if ($stmt->fetch()) {

    $_SESSION['error'] =
        'Another category already uses this slug.';

    header(
        'Location: ' .
        ($action === 'edit'
            ? 'edit.php?id=' . (int) $id
            : 'create.php')
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Image Upload
|--------------------------------------------------------------------------
*/

$imagePath = null;

if (
    isset($_FILES['image'])
    && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

        $_SESSION['error'] =
            'There was an error uploading the image.';

        header(
            'Location: ' .
            ($action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php')
        );

        exit;
    }


    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {

        $_SESSION['error'] =
            'Category image must not exceed 2 MB.';

        header(
            'Location: ' .
            ($action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php')
        );

        exit;
    }


    $tmpFile = $_FILES['image']['tmp_name'];

    $mime = mime_content_type($tmpFile);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];


    if (!isset($allowed[$mime])) {

        $_SESSION['error'] =
            'Only JPG, PNG and WebP images are allowed.';

        header(
            'Location: ' .
            ($action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php')
        );

        exit;
    }


    $uploadDirectory =
        __DIR__ . '/uploads/';


    if (!is_dir($uploadDirectory)) {

        if (!mkdir(
            $uploadDirectory,
            0755,
            true
        )) {

            $_SESSION['error'] =
                'Unable to create upload directory.';

            header(
                'Location: ' .
                ($action === 'edit'
                    ? 'edit.php?id=' . (int) $id
                    : 'create.php')
            );

            exit;
        }
    }


    $filename =
        'category_' .
        bin2hex(random_bytes(12)) .
        '.' .
        $allowed[$mime];


    $destination =
        $uploadDirectory .
        $filename;


    if (!move_uploaded_file(
        $tmpFile,
        $destination
    )) {

        $_SESSION['error'] =
            'Unable to save category image.';

        header(
            'Location: ' .
            ($action === 'edit'
                ? 'edit.php?id=' . (int) $id
                : 'create.php')
        );

        exit;
    }


    $imagePath =
        'admin/categories/uploads/' .
        $filename;
}


/*
|--------------------------------------------------------------------------
| CREATE
|--------------------------------------------------------------------------
*/

if ($action === 'create') {

    $stmt = $pdo->prepare("
        INSERT INTO categories (
            name,
            slug,
            description,
            image,
            display_order,
            status
        )
        VALUES (
            :name,
            :slug,
            :description,
            :image,
            :display_order,
            :status
        )
    ");


    $stmt->execute([
        ':name'          => $name,
        ':slug'          => $slug,
        ':description'   => $description !== ''
            ? $description
            : null,
        ':image'         => $imagePath,
        ':display_order' => $displayOrder,
        ':status'        => $status
    ]);


    $_SESSION['success'] =
        'Category created successfully.';


    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT image
    FROM categories
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$oldCategory = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$oldCategory) {

    $_SESSION['error'] =
        'Category not found.';

    header('Location: index.php');
    exit;
}


if ($imagePath !== null) {

    $stmt = $pdo->prepare("
        UPDATE categories
        SET
            name = :name,
            slug = :slug,
            description = :description,
            image = :image,
            display_order = :display_order,
            status = :status
        WHERE id = :id
    ");

    $stmt->execute([
        ':name'          => $name,
        ':slug'          => $slug,
        ':description'   => $description !== '' ? $description : null,
        ':image'         => $imagePath,
        ':display_order' => $displayOrder,
        ':status'        => $status,
        ':id'            => $id
    ]);

    if (!empty($oldCategory['image'])) {

        $oldRelative = ltrim(
            $oldCategory['image'],
            '/'
        );

        $prefix = 'admin/categories/uploads/';

        if (str_starts_with($oldRelative, $prefix)) {

            $oldFilename = basename($oldRelative);

            $oldFile = __DIR__ . '/uploads/' . $oldFilename;

            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }
    }

} else {

    $stmt = $pdo->prepare("
        UPDATE categories

        SET
            name = :name,
            slug = :slug,
            description = :description,
            display_order = :display_order,
            status = :status

        WHERE id = :id
    ");

    $stmt->execute([
        ':name'          => $name,
        ':slug'          => $slug,
        ':description'   => $description !== ''
            ? $description
            : null,
        ':display_order' => $displayOrder,
        ':status'        => $status,
        ':id'            => $id
    ]);
}


$_SESSION['success'] =
    'Category updated successfully.';

header('Location: index.php');
exit;