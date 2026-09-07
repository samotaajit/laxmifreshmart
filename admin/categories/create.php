<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Add Category';
$currentPage = 'categories';

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
                        Add Category
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Create a new product category
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
                        value="create"
                    >


                    <div class="row g-4">


                        <!-- NAME -->

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
                                required
                                autofocus
                            >

                        </div>


                        <!-- SLUG -->

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
                                placeholder="Automatically generated"
                            >

                            <small class="text-muted">
                                Leave blank to generate automatically.
                            </small>

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="col-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                maxlength="500"
                                placeholder="Enter a short description..."
                            ></textarea>

                        </div>


                        <!-- IMAGE -->

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
                                JPG, PNG or WebP. Maximum 2 MB.
                            </small>

                        </div>


                        <!-- DISPLAY ORDER -->

                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                Display Order
                            </label>

                            <input
                                type="number"
                                name="display_order"
                                class="form-control"
                                value="0"
                                min="0"
                                max="9999"
                            >

                        </div>


                        <!-- STATUS -->

                        <div class="col-12 col-md-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option value="active">
                                    Active
                                </option>

                                <option value="inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <!-- BUTTONS -->

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
                                    Save Category
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

document.getElementById('category_name')
    .addEventListener('input', function () {

        const slugField =
            document.getElementById('category_slug');

        if (slugField.dataset.manual === '1') {
            return;
        }

        slugField.value = this.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');

    });


document.getElementById('category_slug')
    .addEventListener('input', function () {

        this.dataset.manual = '1';

    });

</script>

</body>

</html> 