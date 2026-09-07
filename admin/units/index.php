<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Units';
$currentPage = 'units';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        u.id,
        u.name,
        u.short_name,
        u.status,
        u.created_at,
        u.updated_at,
        COUNT(DISTINCT pv.id) + COUNT(DISTINCT p.id) AS usage_count
    FROM units u
    LEFT JOIN product_variants pv
        ON pv.unit_id = u.id
    LEFT JOIN products p
        ON p.unit_id = u.id
";

$params = [];

if ($search !== '') {

    $sql .= "
        WHERE
            u.name LIKE :search
            OR u.short_name LIKE :search
    ";

    $params[':search'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY
        u.id,
        u.name,
        u.short_name,
        u.status,
        u.created_at,
        u.updated_at

    ORDER BY
        u.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$units = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUnits = count($units);

$activeUnits   = 0;
$inactiveUnits = 0;

foreach ($units as $unit) {

    if ($unit['status'] === 'active') {
        $activeUnits++;
    } else {
        $inactiveUnits++;
    }
}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error'] ?? null;

unset(
    $_SESSION['success'],
    $_SESSION['error']
);

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

    <style>

        .unit-stat {
            background: #fff;
            border: 0;
            border-radius: 13px;
            padding: 18px 20px;
            box-shadow: 0 3px 18px rgba(0,0,0,.045);
            height: 100%;
        }

        .unit-stat-label {
            color: #858b86;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .unit-stat-value {
            color: #075B2A;
            font-size: 25px;
            font-weight: 700;
        }

        .unit-symbol {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            background: #edf5ea;
            color: #075B2A;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        .unit-name {
            font-weight: 600;
            color: #252925;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #e6f5e8;
            color: #176b2d;
        }

        .status-inactive {
            background: #f0f1f0;
            color: #6e756e;
        }

        .empty-state {
            padding: 65px 20px;
            text-align: center;
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #eaf6e8;
            color: #075B2A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
        }

    </style>

</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>

<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>

    <div class="admin-content">


        <!-- STATISTICS -->

        <div class="row g-4 mb-4">

            <div class="col-12 col-md-4">

                <div class="unit-stat">

                    <div class="unit-stat-label">
                        Total Units
                    </div>

                    <div class="unit-stat-value">
                        <?= number_format($totalUnits) ?>
                    </div>

                </div>

            </div>


            <div class="col-12 col-md-4">

                <div class="unit-stat">

                    <div class="unit-stat-label">
                        Active Units
                    </div>

                    <div class="unit-stat-value">
                        <?= number_format($activeUnits) ?>
                    </div>

                </div>

            </div>


            <div class="col-12 col-md-4">

                <div class="unit-stat">

                    <div class="unit-stat-label">
                        Inactive Units
                    </div>

                    <div class="unit-stat-value">
                        <?= number_format($inactiveUnits) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- FLASH -->

        <?php if ($success): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <?= e($success) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <?= e($error) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- MAIN CARD -->

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h5>
                        Units
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Manage product measurement units
                    </div>

                </div>


                <a
                    href="create.php"
                    class="btn btn-lfm"
                >
                    + Add Unit
                </a>

            </div>


            <!-- SEARCH -->

            <div
                class="px-4 py-3"
                style="border-bottom:1px solid #edf0ed;"
            >

                <form method="GET">

                    <div class="row g-2">

                        <div class="col-12 col-md-9">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Search unit or short name..."
                            >

                        </div>


                        <div class="col-6 col-md-1">

                            <button
                                type="submit"
                                class="btn btn-lfm w-100"
                            >
                                Search
                            </button>

                        </div>


                        <div class="col-6 col-md-2">

                            <a
                                href="index.php"
                                class="btn btn-light border w-100"
                            >
                                Clear
                            </a>

                        </div>

                    </div>

                </form>

            </div>


            <!-- TABLE -->

            <div class="admin-card-body p-0">

                <?php if (empty($units)): ?>

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            +
                        </div>

                        <h6>
                            No units found
                        </h6>

                        <p class="text-muted mb-3">
                            Create your first measurement unit.
                        </p>

                        <a
                            href="create.php"
                            class="btn btn-lfm"
                        >
                            Add Unit
                        </a>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Unit
                                    </th>

                                    <th>
                                        Short Name
                                    </th>

                                    <th>
                                        Products / Packs
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th class="text-end">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach ($units as $unit): ?>

                                <tr>

                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div class="unit-symbol">

                                                <?= e(
                                                    $unit['short_name']
                                                ) ?>

                                            </div>

                                            <div class="ms-3">

                                                <div class="unit-name">
                                                    <?= e($unit['name']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <code>
                                            <?= e($unit['short_name']) ?>
                                        </code>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= number_format(
                                                (int) $unit['usage_count']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge status-<?= e(
                                                $unit['status']
                                            ) ?>"
                                        >

                                            <?= e(
                                                ucfirst(
                                                    $unit['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td class="text-end">

                                        <a
                                            href="edit.php?id=<?= (int) $unit['id'] ?>"
                                            class="btn btn-sm btn-lfm-light"
                                        >
                                            Edit
                                        </a>


                                        <?php if ((int) $unit['usage_count'] === 0): ?>

                                            <a
                                                href="delete.php?id=<?= (int) $unit['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this unit?');"
                                            >
                                                Delete
                                            </a>

                                        <?php else: ?>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                disabled
                                                title="This unit is being used by product variants"
                                            >
                                                Delete
                                            </button>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

</body>

</html>