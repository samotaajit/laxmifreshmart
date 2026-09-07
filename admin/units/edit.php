<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Edit Unit';
$currentPage = 'units';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {

    $_SESSION['error'] = 'Invalid unit.';

    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        short_name,
        status
    FROM units
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$unit = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$unit) {

    $_SESSION['error'] = 'Unit not found.';

    header('Location: index.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM products WHERE unit_id = :unit_id1)
        +
        (SELECT COUNT(*) FROM product_variants WHERE unit_id = :unit_id2)
");
$stmt->execute([
    ':unit_id1' => $id,
    ':unit_id2' => $id
]);
$usageCount = (int) $stmt->fetchColumn();


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
                        Edit Unit
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Update measurement unit
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
                        value="edit"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $unit['id'] ?>"
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
                                value="<?= e($unit['name']) ?>"
                                required
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
                                value="<?= e($unit['short_name']) ?>"
                                required
                            >

                        </div>


                        <div class="col-12 col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="active"
                                    <?= $unit['status'] === 'active'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    <?= $unit['status'] === 'inactive'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <div class="alert alert-info mb-0">

                                This unit is currently used by
                                <strong>
                                    <?= number_format($usageCount) ?>
                                </strong>
                                product/pack reference(s).

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
                                    Update Unit
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