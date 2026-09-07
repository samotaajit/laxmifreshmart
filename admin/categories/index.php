<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle   = 'Categories';
$currentPage = 'categories';


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.display_order,
        c.status,
        c.created_at,
        c.updated_at,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON p.category_id = c.id
";

$params = [];

if ($search !== '') {

    $sql .= "
        WHERE
            c.name LIKE :search
            OR c.slug LIKE :search
            OR c.description LIKE :search
    ";

    $params[':search'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.display_order,
        c.status,
        c.created_at,
        c.updated_at

    ORDER BY
        c.display_order ASC,
        c.name ASC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalCategories = count($categories);

$activeCategories = 0;
$inactiveCategories = 0;

foreach ($categories as $category) {

    if ($category['status'] === 'active') {
        $activeCategories++;
    } else {
        $inactiveCategories++;
    }
}


/*
|--------------------------------------------------------------------------
| Flash
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

        .category-stat {
            background: #fff;
            border: 0;
            border-radius: 13px;
            padding: 18px 20px;
            box-shadow: 0 3px 18px rgba(0,0,0,.045);
            height: 100%;
        }

        .category-stat-label {
            color: #858b86;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .category-stat-value {
            color: #075B2A;
            font-size: 25px;
            font-weight: 700;
        }

        .category-image {
            width: 52px;
            height: 52px;
            border-radius: 9px;
            object-fit: cover;
            background: #edf5ea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #075B2A;
            font-size: 20px;
            font-weight: 700;
            overflow: hidden;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-name {
            font-weight: 600;
            color: #252925;
        }

        .category-slug {
            color: #8a908a;
            font-size: 11px;
            margin-top: 2px;
        }

        .category-description {
            max-width: 300px;
            color: #666;
            font-size: 12px;
            line-height: 1.45;
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

                <div class="category-stat">

                    <div class="category-stat-label">
                        Total Categories
                    </div>

                    <div class="category-stat-value">
                        <?= number_format($totalCategories) ?>
                    </div>

                </div>

            </div>


            <div class="col-12 col-md-4">

                <div class="category-stat">

                    <div class="category-stat-label">
                        Active Categories
                    </div>

                    <div class="category-stat-value">
                        <?= number_format($activeCategories) ?>
                    </div>

                </div>

            </div>


            <div class="col-12 col-md-4">

                <div class="category-stat">

                    <div class="category-stat-label">
                        Inactive Categories
                    </div>

                    <div class="category-stat-value">
                        <?= number_format($inactiveCategories) ?>
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


        <!-- CATEGORY CARD -->

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h5>
                        Categories
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Manage product categories
                    </div>

                </div>


                <a
                    href="create.php"
                    class="btn btn-lfm"
                >
                    + Add Category
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
                                placeholder="Search category..."
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

                <?php if (empty($categories)): ?>

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            +
                        </div>

                        <h6>
                            No categories found
                        </h6>

                        <p class="text-muted mb-3">
                            Create your first product category.
                        </p>

                        <a
                            href="create.php"
                            class="btn btn-lfm"
                        >
                            Add Category
                        </a>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Description
                                    </th>

                                    <th>
                                        Products
                                    </th>

                                    <th>
                                        Order
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

                            <?php foreach ($categories as $category): ?>

                                <tr>

                                    <!-- CATEGORY -->

                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div class="category-image">

                                                <?php if (!empty($category['image'])): ?>

                                                    <img
                                                        src="<?= BASE_URL . e($category['image']) ?>"
                                                        alt="<?= e($category['name']) ?>"
                                                    >

                                                <?php else: ?>

                                                    <?= e(
                                                        strtoupper(
                                                            substr(
                                                                $category['name'],
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>


                                            <div class="ms-3">

                                                <div class="category-name">
                                                    <?= e($category['name']) ?>
                                                </div>

                                                <div class="category-slug">
                                                    <?= e($category['slug']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <?php if (!empty($category['description'])): ?>

                                            <div class="category-description">

                                                <?= e(
                                                    strlen($category['description']) > 100
                                                        ? substr(
                                                            $category['description'],
                                                            0,
                                                            100
                                                        ) . '...'
                                                        : $category['description']
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRODUCTS -->

                                    <td>

                                        <strong>
                                            <?= number_format(
                                                (int) $category['product_count']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <!-- DISPLAY ORDER -->

                                    <td>

                                        <?= (int) $category['display_order'] ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-badge
                                            status-<?= e($category['status']) ?>"
                                        >

                                            <?= e(
                                                ucfirst(
                                                    $category['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td class="text-end">

                                        <a
                                            href="edit.php?id=<?= (int) $category['id'] ?>"
                                            class="btn btn-sm btn-lfm-light"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete.php?id=<?= (int) $category['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm(
                                                'Are you sure you want to delete this category?'
                                            );"
                                        >
                                            Delete
                                        </a>

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


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="<?= BASE_URL ?>assets/js/admin.js"
></script>

</body>

</html>