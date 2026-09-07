<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Edit Category';
$currentPage = 'categories';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {

    $_SESSION['error'] = 'Invalid category.';

    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        slug,
        description,
        image,
        display_order,
        status
    FROM categories
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$category) {

    $_SESSION['error'] = 'Category not found.';

    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE category_id = :category_id
");

$stmt->execute([
    ':category_id' => $id
]);

$productCount = (int) $stmt->fetchColumn();


$error = $_SESSION['error'] ?? null;

unset($_SESSION['error']);

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

</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>

<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>

    <div class="admin-content">


        <div class="mb-3">

            <a
                href="index.php"
                class="text-decoration-none"
                style="color:#075B2A;font-size:13px;"
            >
                ← Back to Categories
            </a>

        </div>


        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h5>
                        Edit Category
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Update category information
                    </div>

                </div>

            </div>


            <div class="admin-card-body">

                <form
                    method="POST"
                    action="save.php"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $category['id'] ?>"
                    >


                    <div class="row g-4">


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Category Name
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="category_name"
                                class="form-control"
                                maxlength="100"
                                value="<?= e($category['name']) ?>"
                                required
                            >

                        </div>


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Slug
                            </label>

                            <input
                                type="text"
                                name="slug"
                                id="category_slug"
                                class="form-control"
                                maxlength="120"
                                value="<?= e($category['slug']) ?>"
                            >

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                maxlength="500"
                            ><?= e($category['description'] ?? '') ?></textarea>

                        </div>


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Category Image
                            </label>

                            <input
                                type="file"
                                name="image"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <small class="text-muted">
                                Leave empty to keep the current image.
                            </small>


                            <?php if (!empty($category['image'])): ?>

                                <div class="mt-3">

                                    <img
                                        src="<?= BASE_URL . e($category['image']) ?>"
                                        alt="<?= e($category['name']) ?>"
                                        style="
                                            width:100px;
                                            height:100px;
                                            object-fit:cover;
                                            border-radius:10px;
                                            border:1px solid #ddd;
                                        "
                                    >

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                Display Order
                            </label>

                            <input
                                type="number"
                                name="display_order"
                                class="form-control"
                                value="<?= (int) $category['display_order'] ?>"
                                min="0"
                                max="9999"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="active"
                                    <?= $category['status'] === 'active' ? 'selected' : '' ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    <?= $category['status'] === 'inactive' ? 'selected' : '' ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <div
                                class="alert alert-info mb-0"
                                style="font-size:12px;"
                            >

                                This category currently has
                                <strong>
                                    <?= number_format($productCount) ?>
                                </strong>
                                product(s).

                            </div>

                        </div>


                        <div class="col-12">

                            <hr>

                            <div class="d-flex gap-2 justify-content-end">

                                <a
                                    href="index.php"
                                    class="btn btn-light border"
                                >
                                    Cancel
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-lfm"
                                >
                                    Update Category
                                </button>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</main>


<script>

document.getElementById('category_slug')
    .addEventListener('input', function () {

        this.value = this.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');

    });

</script>

</body>

</html>