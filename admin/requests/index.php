<?php
declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'User Requests';
$currentPage = 'requests';

$type = $_GET['type'] ?? 'users';
if (!in_array($type, ['users', 'addresses'], true)) {
    $type = 'users';
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $status = 'pending';
}

$search = trim($_GET['search'] ?? '');

$pendingUsers = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE status = 'pending'"
)->fetchColumn();

$pendingAddresses = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM user_addresses a
     INNER JOIN users u ON u.id = a.user_id
     WHERE a.approval_status = 'pending'
       AND u.status = 'approved'"
)->fetchColumn();

$params = [':status' => $status];

if ($type === 'users') {
    $sql = "SELECT
                u.id AS user_id,
                u.name,
                u.mobile,
                u.email,
                u.status AS user_status,
                u.rejection_reason AS user_rejection_reason,
                u.approved_at,
                u.created_at,
                a.id AS address_id,
                a.address_line1,
                a.district,
                a.state,
                a.pincode,
                a.landmark,
                a.approval_status AS address_status,
                a.rejection_reason AS address_rejection_reason
            FROM users u
            LEFT JOIN user_addresses a
                ON a.user_id = u.id
               AND a.is_primary = 1
            WHERE u.status = :status";

    if ($search !== '') {
        $sql .= " AND (
            u.name LIKE :search
            OR u.mobile LIKE :search
            OR u.email LIKE :search
            OR a.address_line1 LIKE :search
            OR a.district LIKE :search
            OR a.state LIKE :search
            OR a.pincode LIKE :search
        )";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY u.created_at DESC';
} else {
    $sql = "SELECT
                a.id AS address_id,
                a.address_line1,
                a.district,
                a.state,
                a.pincode,
                a.landmark,
                a.approval_status,
                a.rejection_reason,
                a.created_at,
                u.id AS user_id,
                u.name,
                u.mobile,
                u.email,
                u.status AS user_status
            FROM user_addresses a
            INNER JOIN users u ON u.id = a.user_id
            WHERE a.approval_status = :status
              AND u.status = 'approved'";

    if ($search !== '') {
        $sql .= " AND (
            u.name LIKE :search
            OR u.mobile LIKE :search
            OR u.email LIKE :search
            OR a.address_line1 LIKE :search
            OR a.district LIKE :search
            OR a.state LIKE :search
            OR a.pincode LIKE :search
        )";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY a.created_at DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

unset($_SESSION['success'], $_SESSION['error']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

    <style>
        .request-stat {
            background: #fff;
            border-radius: 13px;
            padding: 18px 20px;
            box-shadow: 0 3px 18px rgba(0, 0, 0, .045);
        }

        .request-stat-label {
            color: #858b86;
            font-size: 12px;
        }

        .request-stat-value {
            color: #075B2A;
            font-size: 25px;
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff4d6;
            color: #946c00;
        }

        .status-approved {
            background: #e6f5e8;
            color: #176b2d;
        }

        .status-rejected {
            background: #fde8e8;
            color: #a72c2c;
        }

        .address-cell {
            line-height: 1.45;
            font-size: 12px;
        }
    </style>
</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>

<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>

    <div class="admin-content">

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="request-stat">
                    <div class="request-stat-label">Pending User Registrations</div>
                    <div class="request-stat-value"><?= number_format($pendingUsers) ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="request-stat">
                    <div class="request-stat-label">Pending Address Changes</div>
                    <div class="request-stat-value"><?= number_format($pendingAddresses) ?></div>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">

            <div class="admin-card-header">
                <div>
                    <h5>User Requests</h5>
                    <div class="text-muted" style="font-size:11px">
                        Manage account approvals and delivery-address approvals.
                    </div>
                </div>

                <div class="filter-tabs">
                    <a class="filter-tab <?= $type === 'users' ? 'active' : '' ?>"
                       href="?type=users&status=pending">
                        User Requests
                        <span class="badge bg-secondary"><?= number_format($pendingUsers) ?></span>
                    </a>

                    <a class="filter-tab <?= $type === 'addresses' ? 'active' : '' ?>"
                       href="?type=addresses&status=pending">
                        Address Requests
                        <span class="badge bg-secondary"><?= number_format($pendingAddresses) ?></span>
                    </a>
                </div>
            </div>

            <div class="px-4 py-3" style="border-bottom:1px solid #edf0ed">
                <form method="get">
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                    <input type="hidden" name="status" value="<?= e($status) ?>">

                    <div class="row g-2">
                        <div class="col-md-8">
                            <input
                                class="form-control"
                                name="search"
                                value="<?= e($search) ?>"
                                placeholder="Search by name, mobile, email, district or pincode..."
                            >
                        </div>

                        <div class="col-6 col-md-2">
                            <button type="submit" class="btn btn-lfm w-100">Search</button>
                        </div>

                        <div class="col-6 col-md-2">
                            <a
                                class="btn btn-light border w-100"
                                href="?type=<?= e($type) ?>&status=<?= e($status) ?>"
                            >Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="px-4 py-3">
                <div class="filter-tabs">

                    <a
                        class="filter-tab <?= $status === 'pending' ? 'active' : '' ?>"
                        href="?type=<?= e($type) ?>&status=pending"
                    >Pending</a>

                    <a
                        class="filter-tab <?= $status === 'approved' ? 'active' : '' ?>"
                        href="?type=<?= e($type) ?>&status=approved"
                    >Approved</a>

                    <a
                        class="filter-tab <?= $status === 'rejected' ? 'active' : '' ?>"
                        href="?type=<?= e($type) ?>&status=rejected"
                    >Rejected</a>

                </div>
            </div>

            <div class="admin-card-body p-0">

                <div class="table-responsive">
                    <table class="table table-hover mb-0">

                        <thead>
                        <tr>

                            <?php if ($type === 'users'): ?>

                                <th>Customer</th>
                                <th>Email</th>
                                <th>Delivery Address</th>
                                <th>Address Status</th>
                                <th>Registered</th>
                                <th class="text-end">Action</th>

                            <?php else: ?>

                                <th>Customer</th>
                                <th>Requested Address</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th class="text-end">Action</th>

                            <?php endif; ?>

                        </tr>
                        </thead>

                        <tbody>

                        <?php if (!$rows): ?>

                            <tr>
                                <td colspan="<?= $type === 'users' ? '6' : '5' ?>"
                                    class="text-center text-muted py-5">
                                    No <?= e($status) ?>
                                    <?= e($type === 'users' ? 'user' : 'address') ?>
                                    requests found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>

                            <?php if ($type === 'users'): ?>

                                <tr>

                                    <td>
                                        <strong><?= e($row['name']) ?></strong>
                                        <div class="text-muted" style="font-size:11px">
                                            <?= e($row['mobile']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($row['email'] ?: '—') ?>
                                    </td>

                                    <td class="address-cell">
                                        <?php if (!empty($row['address_line1'])): ?>
                                            <?= e(formatAddress($row)) ?>

                                            <?php if (!empty($row['landmark'])): ?>
                                                <br>
                                                <small>
                                                    Landmark: <?= e($row['landmark']) ?>
                                                </small>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <span class="text-muted">No primary address</span>
                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php
                                        $addressStatus = $row['address_status'] ?? 'pending';
                                        ?>
                                        <span class="status-badge status-<?= e($addressStatus) ?>">
                                            <?= e(ucfirst($addressStatus)) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(date('d M Y', strtotime($row['created_at']))) ?>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            class="btn btn-sm btn-lfm"
                                            href="view.php?id=<?= (int) $row['user_id'] ?>"
                                        >Review</a>
                                    </td>

                                </tr>

                            <?php else: ?>

                                <tr>

                                    <td>
                                        <strong><?= e($row['name']) ?></strong>
                                        <div class="text-muted" style="font-size:11px">
                                            <?= e($row['mobile']) ?>
                                        </div>
                                    </td>

                                    <td class="address-cell">

                                        <?= e(formatAddress($row)) ?>

                                        <?php if (!empty($row['landmark'])): ?>
                                            <br>
                                            <small>
                                                Landmark: <?= e($row['landmark']) ?>
                                            </small>
                                        <?php endif; ?>

                                        <?php if (!empty($row['rejection_reason'])): ?>
                                            <br>
                                            <small class="text-danger">
                                                Reason: <?= e($row['rejection_reason']) ?>
                                            </small>
                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= e($row['approval_status']) ?>">
                                            <?= e(ucfirst($row['approval_status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(date('d M Y, h:i A', strtotime($row['created_at']))) ?>
                                    </td>

                                    <td class="text-end">

                                        <?php if ($status === 'pending'): ?>

                                            <form
                                                method="post"
                                                action="approve-address.php"
                                                class="d-inline"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="address_id"
                                                    value="<?= (int) $row['address_id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-lfm"
                                                    onclick="return confirm('Approve this delivery address?')"
                                                >Approve</button>
                                            </form>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reject<?= (int) $row['address_id'] ?>"
                                            >Reject</button>

                                            <div
                                                class="modal fade"
                                                id="reject<?= (int) $row['address_id'] ?>"
                                                tabindex="-1"
                                                aria-hidden="true"
                                            >
                                                <div class="modal-dialog">
                                                    <div class="modal-content">

                                                        <form method="post" action="reject-address.php">

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reject Address</h5>

                                                                <button
                                                                    type="button"
                                                                    class="btn-close"
                                                                    data-bs-dismiss="modal"
                                                                    aria-label="Close"
                                                                ></button>
                                                            </div>

                                                            <div class="modal-body">

                                                                <input
                                                                    type="hidden"
                                                                    name="address_id"
                                                                    value="<?= (int) $row['address_id'] ?>"
                                                                >

                                                                <label class="form-label">
                                                                    Reason
                                                                </label>

                                                                <textarea
                                                                    name="rejection_reason"
                                                                    class="form-control"
                                                                    rows="4"
                                                                    required
                                                                ></textarea>

                                                            </div>

                                                            <div class="modal-footer">

                                                                <button
                                                                    type="button"
                                                                    class="btn btn-light border"
                                                                    data-bs-dismiss="modal"
                                                                >Cancel</button>

                                                                <button
                                                                    type="submit"
                                                                    class="btn btn-danger"
                                                                >Reject Address</button>

                                                            </div>

                                                        </form>

                                                    </div>
                                                </div>
                                            </div>

                                        <?php else: ?>

                                            <a
                                                class="btn btn-sm btn-light border"
                                                href="view.php?id=<?= (int) $row['user_id'] ?>"
                                            >View User</a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endif; ?>

                        <?php endforeach; ?>

                        </tbody>

                    </table>
                </div>

            </div>

        </div>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html
