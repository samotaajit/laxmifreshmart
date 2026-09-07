<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Add Unit';
$currentPage = 'units';

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
                ← Back to Units
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
                        Add Unit
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Create a new measurement unit
                    </div>

                </div>

            </div>


            <div class="admin-card-body">

                <form
                    method="POST"
                    action="save.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="create"
                    >


                    <div class="row g-4">


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Unit Name
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                maxlength="50"
                                placeholder="e.g. Kilogram"
                                required
                                autofocus
                            >

                        </div>


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Short Name
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="short_name"
                                class="form-control"
                                maxlength="20"
                                placeholder="e.g. Kg"
                                required
                            >

                            <small class="text-muted">
                                This is displayed alongside product quantities.
                            </small>

                        </div>


                        <div class="col-12 col-md-6">

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
                                    Save Unit
                                </button>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</main>

</body>

</html>