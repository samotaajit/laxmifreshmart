<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'Users';
$currentPage = 'users';


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$status = $_GET['status'] ?? 'approved';

$allowedStatuses = [
    'approved',
    'blocked'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'approved';
}

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$approvedCount = (int) $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'approved'
")->fetchColumn();

$blockedCount = (int) $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'blocked'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
|
| We use the primary address only for the listing.
| The complete address list is shown on view.php.
|
*/

$sql = "
    SELECT
        u.id,
        u.name,
        u.mobile,
        u.email,
        u.status,
        u.approved_at,
        u.created_at,

        a.id AS address_id,
                                a.district,
        a.state,
        a.pincode,
        a.approval_status AS address_status

    FROM users u

    LEFT JOIN user_addresses a
        ON a.user_id = u.id
        AND a.is_primary = 1

    WHERE u.status = :status
";

$params = [
    ':status' => $status
];


if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE :search
            OR u.mobile LIKE :search
            OR u.email LIKE :search
            OR a.district LIKE :search
            OR a.pincode LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


$sql .= "
    ORDER BY u.created_at DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

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

        .user-stat {
            background: #fff;
            border: 0;
            border-radius: 13px;
            padding: 18px 20px;
            box-shadow: 0 3px 18px rgba(0,0,0,.045);
            height: 100%;
        }

        .user-stat-label {
            color: #858b86;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .user-stat-value {
            color: #075B2A;
            font-size: 25px;
            font-weight: 700;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eaf6e8;
            color: #075B2A;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 600;
            color: #252925;
        }

        .user-mobile {
            color: #8a908a;
            font-size: 11px;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-approved {
            background: #e6f5e8;
            color: #176b2d;
        }

        .status-blocked {
            background: #fde8e8;
            color: #a72c2c;
        }

        .address-cell {
            max-width: 230px;
            color: #555;
            line-height: 1.45;
        }

        .filter-tabs {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .filter-tab {
            text-decoration: none;
            padding: 7px 13px;
            border-radius: 7px;
            font-size: 12px;
            color: #667066;
            background: #f1f4f1;
        }

        .filter-tab:hover {
            color: #075B2A;
            background: #e7f1e5;
        }

        .filter-tab.active {
            color: #fff;
            background: #075B2A;
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
            font-size: 28px;
        }

    </style>

</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>


<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>


    <div class="admin-content">


        <!-- ==========================================
             STATISTICS
        =========================================== -->

        <div class="row g-4 mb-4">

            <div class="col-12 col-md-6">

                <div class="user-stat">

                    <div class="user-stat-label">
                        Active Users
                    </div>

                    <div class="user-stat-value">
                        <?= number_format($approvedCount) ?>
                    </div>

                </div>

            </div>


            <div class="col-12 col-md-6">

                <div class="user-stat">

                    <div class="user-stat-label">
                        Blocked Users
                    </div>

                    <div class="user-stat-value">
                        <?= number_format($blockedCount) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             FLASH
        =========================================== -->

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


        <!-- ==========================================
             USERS
        =========================================== -->

        <div class="admin-card">

            <div class="admin-card-header">

                <div>

                    <h5>
                        Users
                    </h5>

                    <div
                        class="text-muted"
                        style="font-size:11px;margin-top:3px;"
                    >
                        Manage approved Laxmi Fresh Mart customers
                    </div>

                </div>


                <div class="filter-tabs">

                    <a
                        href="?status=approved"
                        class="filter-tab <?= $status === 'approved' ? 'active' : '' ?>"
                    >
                        Active
                    </a>

                    <a
                        href="?status=blocked"
                        class="filter-tab <?= $status === 'blocked' ? 'active' : '' ?>"
                    >
                        Blocked
                    </a>

                </div>

            </div>


            <!-- SEARCH -->

            <div
                class="px-4 py-3"
                style="border-bottom:1px solid #edf0ed;"
            >

                <form method="GET">

                    <input
                        type="hidden"
                        name="status"
                        value="<?= e($status) ?>"
                    >

                    <div class="row g-2">

                        <div class="col-12 col-md-8">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="<?= e($search) ?>"
                                placeholder="Search by name, mobile, email, district or pincode..."
                            >

                        </div>

                        <div class="col-6 col-md-2">

                            <button
                                type="submit"
                                class="btn btn-lfm w-100"
                            >
                                Search
                            </button>

                        </div>

                        <div class="col-6 col-md-2">

                            <a
                                href="?status=<?= e($status) ?>"
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

                <?php if (empty($users)): ?>

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            ✓
                        </div>

                        <h6>
                            No <?= $status === 'approved' ? 'active' : 'blocked' ?> users
                        </h6>

                        <p class="text-muted mb-0">
                            No customers were found.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead>

                                <tr>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Delivery Area
                                    </th>

                                    <th>
                                        Account
                                    </th>

                                    <th>
                                        Registered
                                    </th>

                                    <th class="text-end">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <!-- CUSTOMER -->

                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div class="user-avatar">

                                                <?= e(
                                                    strtoupper(
                                                        substr(
                                                            $user['name'],
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ) ?>

                                            </div>

                                            <div>

                                                <div class="user-name">
                                                    <?= e($user['name']) ?>
                                                </div>

                                                <div class="user-mobile">
                                                    <?= e($user['mobile']) ?>
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <?= !empty($user['email'])
                                            ? e($user['email'])
                                            : '<span class="text-muted">—</span>'
                                        ?>

                                    </td>


                                    <!-- ADDRESS -->

                                    <td>

                                        <div class="address-cell">

                                            <?php

                                            $location = array_filter([
                                                '',
                                                '',
                                                $user['district']
                                            ]);

                                            ?>

                                            <?= !empty($location)
                                                ? e(
                                                    implode(
                                                        ', ',
                                                        $location
                                                    )
                                                )
                                                : '<span class="text-muted">No address</span>'
                                            ?>

                                            <?php if (!empty($user['pincode'])): ?>

                                                <br>

                                                <small class="text-muted">
                                                    PIN:
                                                    <?= e($user['pincode']) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-badge status-<?= e($user['status']) ?>"
                                        >
                                            <?= e(
                                                ucfirst(
                                                    $user['status']
                                                )
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <div style="font-size:12px;">

                                            <?= date(
                                                'd M Y',
                                                strtotime(
                                                    $user['created_at']
                                                )
                                            ) ?>

                                        </div>

                                        <small class="text-muted">

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $user['created_at']
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="text-end">

                                        <a
                                            href="view.php?id=<?= (int) $user['id'] ?>"
                                            class="btn btn-sm btn-lfm-light"
                                        >
                                            View
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