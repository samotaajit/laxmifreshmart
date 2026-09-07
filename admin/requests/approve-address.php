<?php
declare(strict_types=1);
require_once '../../config/database.php';require_once '../../config/config.php';require_once '../../includes/functions.php';require_once '../../includes/auth.php';
requireAdmin();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location:index.php?type=addresses');exit;}
$addressId=filter_input(INPUT_POST,'address_id',FILTER_VALIDATE_INT);$adminId=(int)($_SESSION['admin_id']??0);
if(!$addressId||$adminId<=0){$_SESSION['error']='Invalid address request.';header('Location:index.php?type=addresses');exit;}
try{
$pdo->beginTransaction();
$s=$pdo->prepare("SELECT a.id,a.user_id,a.approval_status,u.status AS user_status FROM user_addresses a INNER JOIN users u ON u.id=a.user_id WHERE a.id=:id LIMIT 1 FOR UPDATE");$s->execute([':id'=>$addressId]);$a=$s->fetch(PDO::FETCH_ASSOC);
if(!$a)throw new RuntimeException('Address request not found.');if($a['approval_status']!=='pending')throw new RuntimeException('This address request has already been processed.');if($a['user_status']!=='approved')throw new RuntimeException('Approve the user account before approving its delivery address.');
$s=$pdo->prepare("UPDATE user_addresses SET approval_status='approved',rejection_reason=NULL,approved_at=NOW(),approved_by=:admin_id WHERE id=:id AND approval_status='pending'");$s->execute([':admin_id'=>$adminId,':id'=>$addressId]);
$s=$pdo->prepare("INSERT INTO notifications(user_id,title,message,type,reference_id) VALUES(:user_id,'Delivery Address Approved','Your delivery address has been approved. You can now use it for orders.','address_approved',:reference_id)");$s->execute([':user_id'=>$a['user_id'],':reference_id'=>$addressId]);
$pdo->commit();$_SESSION['success']='Delivery address approved successfully.';
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['error']=$e->getMessage();}
header('Location:index.php?type=addresses&status=pending');exit;
