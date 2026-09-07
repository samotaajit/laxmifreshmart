<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/customer-auth.php';

$user = requireCustomer($pdo);
$userId = (int)$user['id'];
$stmt = $pdo->prepare("SELECT id,address_line1,district,state,pincode,landmark,approval_status,rejection_reason FROM user_addresses WHERE user_id=:user_id ORDER BY is_primary DESC,id ASC LIMIT 1");
$stmt->execute([':user_id'=>$userId]);
$address = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['name']??'');
    $email=trim($_POST['email']??'');
    $addressLine1=trim($_POST['address_line1']??'');
    $district=trim($_POST['district']??'');
    $state=trim($_POST['state']??'');
    $pincode=trim($_POST['pincode']??'');
    $landmark=trim($_POST['landmark']??'');

    if($name==='')$errors[]='Name is required.';
    if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Please enter a valid email address.';
    if($addressLine1==='')$errors[]='Address line 1 is required.';
    if(!preg_match('/^\d{6}$/',$pincode))$errors[]='Please enter a valid 6-digit pincode.';

    if(!$errors){
        try{
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("SELECT id FROM users WHERE email=:email AND email IS NOT NULL AND email<>'' AND id<>:id LIMIT 1");
            $stmt->execute([':email'=>$email,':id'=>$userId]);
            if($email!==''&&$stmt->fetch())throw new RuntimeException('This email address is already in use.');

            $stmt=$pdo->prepare("UPDATE users SET name=:name,email=:email WHERE id=:id");
            $stmt->execute([':name'=>$name,':email'=>$email!==''?$email:null,':id'=>$userId]);

            $addressChanged=false;
            if($address){
                $normalise=static fn($v):string=>trim((string)($v??''));
                $addressChanged=$normalise($address['address_line1'])!==$addressLine1
                    ||$normalise($address['district'])!==$district
                    ||$normalise($address['state'])!==$state
                    ||$normalise($address['pincode'])!==$pincode
                    ||$normalise($address['landmark'])!==$landmark;

                $sql="UPDATE user_addresses SET address_line1=:address_line1,district=:district,state=:state,pincode=:pincode,landmark=:landmark";
                if($addressChanged)$sql.=",approval_status='pending',rejection_reason=NULL,approved_at=NULL,approved_by=NULL";
                $sql.=" WHERE id=:id AND user_id=:user_id";
                $stmt=$pdo->prepare($sql);
                $stmt->execute([':address_line1'=>$addressLine1,':district'=>$district!==''?$district:null,':state'=>$state!==''?$state:null,':pincode'=>$pincode,':landmark'=>$landmark!==''?$landmark:null,':id'=>(int)$address['id'],':user_id'=>$userId]);
            }else{
                $stmt=$pdo->prepare("INSERT INTO user_addresses(user_id,address_line1,district,state,pincode,landmark,is_primary,approval_status) VALUES(:user_id,:address_line1,:district,:state,:pincode,:landmark,1,'pending')");
                $stmt->execute([':user_id'=>$userId,':address_line1'=>$addressLine1,':district'=>$district!==''?$district:null,':state'=>$state!==''?$state:null,':pincode'=>$pincode,':landmark'=>$landmark!==''?$landmark:null]);
                $addressChanged=true;
            }
            $pdo->commit();
            setFlash('success',$addressChanged?'Profile updated. Your delivery address has been sent for admin approval.':'Profile updated successfully.');
            redirect(BASE_URL.'account/profile.php');
        }catch(Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            $errors[]=$e->getMessage();
        }
    }
}

if(!$address)$address=['address_line1'=>'','district'=>'','state'=>'','pincode'=>'','landmark'=>'','approval_status'=>'pending','rejection_reason'=>null];
$pageTitle='Profile';$activePage='';
?>
<?php require '../includes/store-header.php'; ?>
<section class="page-hero">
    <div class="container">
        <h1>Profile & Address</h1>
        <div class="breadcrumb">Home / My Account / Profile</div>
    </div>
</section>
<section class="account-page">
    <div class="container">
        <div class="account-layout">
            <aside class="account-sidebar">
                <div class="account-user-mini">
                    <div class="account-avatar"><?=e(strtoupper(substr($user['name'],0,1)))?></div>
                    <strong><?=e($user['name'])?></strong><span><?=e($user['mobile'])?></span>
                </div>
                <nav class="account-nav"><a href="<?=BASE_URL?>account/index.php">Overview</a><a class="active"
                        href="<?=BASE_URL?>account/profile.php">Profile & Address</a><a
                        href="<?=BASE_URL?>account/change-password.php">Change Password</a><a
                        href="<?=BASE_URL?>account/orders.php">Order History</a><a
                        href="<?=BASE_URL?>logout.php">Logout</a></nav>
            </aside>
            <div class="account-main">
                <?php $flash=getFlash();if($flash):?><div class="form-alert form-alert-<?=e($flash['type'])?>">
                    <?=e($flash['message'])?></div><?php endif;?>
                <?php if($errors):?><div class="form-alert form-alert-error"><?php foreach($errors as $error):?><div>
                        <?=e($error)?></div><?php endforeach;?></div><?php endif;?>
                <div class="account-card">
                    <div class="account-card-head">
                        <h2>Account Details</h2>
                        <p>Your mobile number is your login identifier.</p>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Full Name</label><input value="<?=e($user['name'])?>" disabled>
                        </div>
                        <div class="form-group"><label>Mobile Number</label><input value="<?=e($user['mobile'])?>"
                                disabled></div>
                    </div>
                </div>
                <form method="post" class="account-card store-form">
                    <div class="account-card-head">
                        <h2>Update Profile & Delivery Address</h2>
                        <p>Changing your delivery address sends it for admin approval again.</p>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label for="name">Full Name *</label><input id="name" name="name"
                                value="<?=e($user['name'])?>" required></div>
                        <div class="form-group"><label for="email">Email</label><input id="email" name="email"
                                type="email" value="<?=e($user['email']??'')?>"></div>
                        <div class="form-group form-span-2"><label for="address_line1">Address Line 1 *</label><input
                                id="address_line1" name="address_line1" value="<?=e($address['address_line1'])?>"
                                required></div>
                        <div class="form-group"><label for="district">District</label><input id="district"
                                name="district" value="<?=e($address['district'])?>"></div>
                        <div class="form-group"><label for="state">State</label><input id="state" name="state"
                                value="<?=e($address['state'])?>"></div>
                        <div class="form-group"><label for="pincode">Pincode *</label><input id="pincode" name="pincode"
                                inputmode="numeric" maxlength="6" value="<?=e($address['pincode'])?>" required></div>
                        <div class="form-group form-span-2"><label for="landmark">Landmark</label><input id="landmark"
                                name="landmark" value="<?=e($address['landmark'])?>"></div>
                    </div>
                    <div class="address-status">Current address status:
                        <strong><?=e(ucfirst($address['approval_status']))?></strong><?php if(!empty($primaryAddress['rejection_reason']) && $primaryAddress['approval_status'] == 'rejected'):?>
                        <div class="text-danger" style="margin-top:5px">Reason: <?=e($address['rejection_reason'])?>
                        </div><?php endif;?></div><button class="btn-primary form-submit" type="submit">Save
                        Changes</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php require '../includes/store-footer.php'; ?>