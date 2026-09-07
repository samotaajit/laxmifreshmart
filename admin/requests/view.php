

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'Review User Request';
$currentPage = 'requests';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    $_SESSION['error'] = 'Invalid user request.';

    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get User + Primary Address
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.mobile,
        u.email,
        u.status,
        u.rejection_reason,
        u.approved_at,
        u.created_at,
        u.updated_at,

        a.id AS address_id,
        a.address_line1,
        a.district,
        a.state,
        a.pincode,
        a.landmark,
        a.is_primary,
        a.approval_status AS address_status,
        a.rejection_reason AS address_rejection_reason,
        a.approved_at AS address_approved_at

    FROM users u

    LEFT JOIN user_addresses a
        ON a.user_id = u.id
        AND a.is_primary = 1

    WHERE u.id = :id

    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    $_SESSION['error'] = 'User request not found.';

    header('Location: index.php');
    exit;
}


$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

unset($_SESSION['success'], $_SESSION['error']);

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

        .review-section {
            padding: 20px;
            border-bottom: 1px solid #edf0ed;
        }

        .review-section:last-child {
            border-bottom: 0;
        }

        .review-title {
            color: #075B2A;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .detail-label {
            color: #858b86;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .detail-value {
            color: #292d29;
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }

        .user-profile-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .large-avatar {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: #eaf6e8;
            color: #075B2A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            font-weight: 700;
        }

        .review-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-approve {
            background: #075B2A;
            border-color: #075B2A;
            color: #fff;
        }

        .btn-approve:hover {
            background: #043F1D;
            border-color: #043F1D;
            color: #fff;
        }

        .address-box {
            background: #fafcf9;
            border: 1px solid #e7ebe7;
            border-radius: 9px;
            padding: 15px;
            line-height: 1.6;
            color: #444;
        }

    </style>

</head>

<body>

<?php require_once '../../includes/admin-sidebar.php'; ?>


<main class="admin-main">

    <?php require_once '../../includes/admin-header.php'; ?>


    <div class="admin-content">


        <!-- BACK -->

        <div class="mb-3">

            <a
                href="index.php"
                class="text-decoration-none"
                style="color:#075B2A;font-size:13px;"
            >
                ← Back to User Requests
            </a>

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


        <div class="row g-4">


            <!-- ======================================
                 USER INFORMATION
            ======================================= -->

            <div class="col-12 col-lg-8">

                <div class="admin-card">


                    <div class="review-section">

                        <div class="user-profile-box">

                            <div class="large-avatar">

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

                                <h4
                                    class="mb-1"
                                    style="font-size:20px;"
                                >
                                    <?= e($user['name']) ?>
                                </h4>

                                <div
                                    class="text-muted"
                                    style="font-size:12px;"
                                >
                                    Registered
                                    <?= date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PERSONAL -->

                    <div class="review-section">

                        <div class="review-title">
                            Personal Information
                        </div>

                        <div class="row g-4">

                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Full Name
                                </div>

                                <div class="detail-value">
                                    <?= e($user['name']) ?>
                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Mobile Number
                                </div>

                                <div class="detail-value">
                                    <?= e($user['mobile']) ?>
                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Email Address
                                </div>

                                <div class="detail-value">

                                    <?= !empty($user['email'])
                                        ? e($user['email'])
                                        : 'Not provided'
                                    ?>

                                </div>

                            </div>


                            <div class="col-12 col-md-6">

                                <div class="detail-label">
                                    Account Status
                                </div>

                                <div class="detail-value">

                                    <?php

                                    $userStatus = $user['status'];

                                    ?>

                                    <span
                                        class="badge rounded-pill
                                        <?=
                                            $userStatus === 'approved'
                                                ? 'text-bg-success'
                                                : (
                                                    $userStatus === 'rejected'
                                                        ? 'text-bg-danger'
                                                        : 'text-bg-warning'
                                                )
                                        ?>"
                                    >
                                        <?= e(
                                            ucfirst($userStatus)
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="review-section">

                        <div class="review-title">
                            Delivery Address
                        </div>


                        <?php if (!empty($user['address_id'])): ?>

                            <div class="address-box">

                                <?= e($user['address_line1']) ?>


                                <?php

                                $location = array_filter([
                                    
                                    $user['district'],
                                    $user['state']
                                ]);

                                ?>

                                <?php if (!empty($location)): ?>

                                    <br>
                                    <?= e(
                                        implode(', ', $location)
                                    ) ?>

                                <?php endif; ?>


                                <br>

                                <strong>
                                    PIN:
                                </strong>

                                <?= e($user['pincode']) ?>


                                <?php if (!empty($user['landmark'])): ?>

                                    <br>

                                    <strong>
                                        Landmark:
                                    </strong>

                                    <?= e($user['landmark']) ?>

                                <?php endif; ?>

                            </div>


                            <div class="mt-3">

                                <span class="detail-label">
                                    Delivery Area Approval
                                </span>

                                <br>

                                <?php

                                $addressStatus =
                                    $user['address_status']
                                    ?? 'pending';

                                ?>

                                <span
                                    class="badge rounded-pill
                                    <?=
                                        $addressStatus === 'approved'
                                            ? 'text-bg-success'
                                            : (
                                                $addressStatus === 'rejected'
                                                    ? 'text-bg-danger'
                                                    : 'text-bg-warning'
                                            )
                                    ?>"
                                >

                                    <?= e(
                                        ucfirst($addressStatus)
                                    ) ?>

                                </span>

                            </div>

                        <?php else: ?>

                            <div class="alert alert-warning mb-0">

                                This user has not provided a primary
                                delivery address.

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- REJECTION INFORMATION -->

                    <?php if (
                        $user['status'] === 'rejected'
                        && !empty($user['rejection_reason'])
                    ): ?>

                        <div class="review-section">

                            <div class="review-title">
                                Rejection Reason
                            </div>

                            <div class="alert alert-danger mb-0">

                                <?= nl2br(
                                    e(
                                        $user['rejection_reason']
                                    )
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>

            </div>


            <!-- ======================================
                 ACTION PANEL
            ======================================= -->

            <div class="col-12 col-lg-4">

                <div class="admin-card">

                    <div class="admin-card-header">

                        <h5>
                            Request Actions
                        </h5>

                    </div>


                    <div class="admin-card-body">


                        <?php if ($user['status'] === 'pending'): ?>

                            <p
                                class="text-muted"
                                style="font-size:12px;line-height:1.6;"
                            >
                                Review the customer's details and delivery
                                area before approving the account.
                            </p>


                            <div class="review-actions">


                                <!-- APPROVE -->

                                <form
                                    method="POST"
                                    action="approve.php"
                                    onsubmit="return confirm(
                                        'Approve this user and their primary delivery address?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $user['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-approve"
                                    >
                                        ✓ Approve User
                                    </button>

                                </form>


                                <!-- REJECT -->

                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal"
                                >
                                    Reject
                                </button>

                            </div>


                            <?php if (empty($user['address_id'])): ?>

                                <div class="alert alert-warning mt-3 mb-0">

                                    Approval is not recommended because
                                    this user does not have a primary
                                    delivery address.

                                </div>

                            <?php endif; ?>


                        <?php elseif ($user['status'] === 'approved'): ?>

                            <div class="alert alert-success mb-0">

                                This user is approved and can access the
                                customer shopping system.

                            </div>


                        <?php elseif ($user['status'] === 'rejected'): ?>

                            <div class="alert alert-danger mb-3">

                                This request was rejected.

                            </div>


                            <a
                                href="index.php?status=rejected"
                                class="btn btn-light border"
                            >
                                Back to Rejected Requests
                            </a>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- REQUEST META -->

                <div class="admin-card mt-4">

                    <div class="admin-card-header">

                        <h5>
                            Request Information
                        </h5>

                    </div>

                    <div class="admin-card-body">

                        <div class="mb-3">

                            <div class="detail-label">
                                Request ID
                            </div>

                            <div class="detail-value">
                                #<?= (int) $user['id'] ?>
                            </div>

                        </div>


                        <div class="mb-3">

                            <div class="detail-label">
                                Registration Date
                            </div>

                            <div class="detail-value">
                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $user['created_at']
                                    )
                                ) ?>
                            </div>

                        </div>


                        <div>

                            <div class="detail-label">
                                Address ID
                            </div>

                            <div class="detail-value">

                                <?= !empty($user['address_id'])
                                    ? '#' . (int) $user['address_id']
                                    : 'Not available'
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>


<!-- ==========================================
     REJECT MODAL
=========================================== -->

<?php if ($user['status'] === 'pending'): ?>

<div
    class="modal fade"
    id="rejectModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                method="POST"
                action="reject.php"
            >

                <div class="modal-header">

                    <h5 class="modal-title">
                        Reject User Request
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <p class="text-muted small">

                        Please provide a reason for rejecting this
                        registration request.

                    </p>


                    <input
                        type="hidden"
                        name="user_id"
                        value="<?= (int) $user['id'] ?>"
                    >


                    <label
                        class="form-label"
                        for="rejection_reason"
                    >
                        Rejection Reason
                    </label>

                    <textarea
                        id="rejection_reason"
                        name="rejection_reason"
                        class="form-control"
                        rows="4"
                        maxlength="2000"
                        required
                        placeholder="For example: Delivery address is outside our service area."
                    ></textarea>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Reject Request
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="<?= BASE_URL ?>assets/js/admin.js"
></script>

</body>

</html>