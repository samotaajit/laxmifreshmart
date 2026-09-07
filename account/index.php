<?php
declare(strict_types=1);
require_once '../config/database.php';require_once '../config/config.php';require_once '../includes/functions.php';require_once '../includes/customer-auth.php';
$user=requireCustomer($pdo);$userId=(int)$user['id'];
$stmt=$pdo->prepare("SELECT id,address_line1,district,state,pincode,landmark,approval_status,rejection_reason FROM user_addresses WHERE user_id=:user_id AND is_primary=1 LIMIT 1");$stmt->execute([':user_id'=>$userId]);$primaryAddress=$stmt->fetch(PDO::FETCH_ASSOC)?:null;
$stmt=$pdo->prepare("SELECT id,order_number,total,order_status,payment_status,created_at FROM orders WHERE user_id=:user_id ORDER BY created_at DESC LIMIT 5");$stmt->execute([':user_id'=>$userId]);$recentOrders=$stmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='My Account';$activePage='';
?>
<?php require '../includes/store-header.php'; ?>
<section class="page-hero">
    <div class="container">
        <h1>My Account</h1>
        <div class="breadcrumb">Home / My Account</div>
    </div>
</section>
<section class="account-page">
    <div class="container">
        <?php $flash=getFlash();if($flash):?><div class="form-alert form-alert-<?=e($flash['type'])?>">
            <?=e($flash['message'])?></div><?php endif;?>
        <div class="account-status <?=e($user['status'])?>">
            <div><strong><?php if($user['status']==='approved'):?>Account Approved<?php else:?>Account Under
                    Review<?php endif;?></strong><span><?php if($user['status']==='pending'):?>Your account is awaiting
                    approval.<?php elseif($user['status']==='rejected'):?>Your account request was
                    rejected.<?php elseif($user['status']==='blocked'):?>Your account is currently
                    blocked.<?php elseif(!$primaryAddress||$primaryAddress['approval_status']==='pending'):?>Your
                    delivery address is awaiting approval. You can shop after the address is
                    approved.<?php elseif($primaryAddress['approval_status']==='rejected'):?>Your delivery address was
                    rejected. Please update your address and submit it again for approval.<?php else:?>You can shop and
                    place orders in approved delivery areas.<?php endif;?></span></div>
        </div>
        <div class="account-layout">
            <aside class="account-sidebar">
                <div class="account-user-mini">
                    <div class="account-avatar"><?=e(strtoupper(substr($user['name'],0,1)))?></div>
                    <strong><?=e($user['name'])?></strong><span><?=e($user['mobile'])?></span>
                </div>
                <nav class="account-nav"><a class="active" href="<?=BASE_URL?>account/index.php">Overview</a><a
                        href="<?=BASE_URL?>account/profile.php">Profile & Address</a><a
                        href="<?=BASE_URL?>account/change-password.php">Change Password</a><a
                        href="<?=BASE_URL?>account/orders.php">Order History</a><a
                        href="<?=BASE_URL?>logout.php">Logout</a></nav>
            </aside>
            <div class="account-main">
                <div class="account-card">
                    <div class="account-card-head">
                        <h2>Profile</h2>
                        <p>Your registered account information.</p>
                    </div>
                    <div class="info-grid">
                        <div><small>Name</small><strong><?=e($user['name'])?></strong></div>
                        <div><small>Mobile</small><strong><?=e($user['mobile'])?></strong></div>
                        <div><small>Email</small><strong><?=e($user['email']?:'Not provided')?></strong></div>
                        <div><small>Member
                                Since</small><strong><?=e(date('d M Y',strtotime($user['created_at'])))?></strong></div>
                    </div>
                </div>
                <div class="account-card">
                    <div class="account-card-head account-card-head-row">
                        <div>
                            <h2>Delivery Address</h2>
                            <p>Current address and approval status.</p>
                        </div><a class="text-link" href="<?=BASE_URL?>account/profile.php">Manage</a>
                    </div><?php if($primaryAddress):?><div class="address-box">
                        <?=e(formatAddress($primaryAddress))?><?php if(!empty($primaryAddress['landmark'])):?><br><span>Landmark:
                            <?=e($primaryAddress['landmark'])?></span><?php endif;?></div>
                    <div class="address-status">Status:
                        <strong><?=e(ucfirst($primaryAddress['approval_status']))?></strong><?php if(!empty($primaryAddress['rejection_reason']) && $primaryAddress['approval_status'] == 'rejected'):?>
                        <div class="text-danger" style="margin-top:5px">Reason:
                            <?=e($primaryAddress['rejection_reason'])?></div><?php endif;?></div><?php else:?><div
                        class="empty-inline">Your delivery address is not available yet.</div><?php endif;?>
                </div>
                <div class="account-card">
                    <div class="account-card-head account-card-head-row">
                        <div>
                            <h2>Recent Orders</h2>
                            <p>Your latest purchases.</p>
                        </div><a class="text-link" href="<?=BASE_URL?>account/orders.php">View all</a>
                    </div><?php if(!$recentOrders):?><div class="empty-inline">You have not placed any orders yet.</div>
                    <?php else:?><div class="account-order-list"><?php foreach($recentOrders as $order):?><a
                            class="account-order-row" href="<?=BASE_URL?>account/order.php?id=<?=(int)$order['id']?>">
                            <div>
                                <strong>#<?=e($order['order_number'])?></strong><span><?=e(date('d M Y, h:i A',strtotime($order['created_at'])))?></span>
                            </div>
                            <div><strong>₹<?=number_format((float)$order['total'],2)?></strong><span
                                    class="status-text"><?=e(ucwords(str_replace('_',' ',$order['order_status'])))?></span>
                            </div>
                        </a><?php endforeach;?></div><?php endif;?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require '../includes/store-footer.php'; ?>