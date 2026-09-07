<?php
declare(strict_types=1);
require_once '../../config/database.php';require_once '../../config/config.php';require_once '../../includes/functions.php';require_once '../../includes/auth.php';
requireAdmin();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location:index.php?type=addresses');exit;}
$addressId=filter_input(INPUT_POST,'address_id',FILTER_VALIDATE_INT);$reason=trim($_POST['rejection_reason']??'');
if(!$addressId){$_SESSION['error']='Invalid address request.';header('Location:index.php?type=addresses');exit;}
if($reason===''){$_SESSION['error']='Please provide a reason for rejection.';header('Location:index.php?type=addresses');exit;}
if(mb_strlen($reason)>2000){$_SESSION['error']='The rejection reason is too long.';header('Location:index.php?type=addresses');exit;}
$adminId=(int)($_SESSION['admin_id']??0);
try{
$pdo->beginTransaction();
$s=$pdo->prepare("SELECT a.id,a.user_id,a.approval_status FROM user_addresses a WHERE a.id=:id LIMIT 1 FOR UPDATE");$s->execute([':id'=>$addressId]);$a=$s->fetch(PDO::FETCH_ASSOC);
if(!$a)throw new RuntimeException('Address request not found.');if($a['approval_status']!=='pending')throw new RuntimeException('This address request has already been processed.');
$s=$pdo->prepare("UPDATE user_addresses SET approval_status='rejected',rejection_reason=:reason,approved_at=NULL,approved_by=NULL WHERE id=:id AND approval_status='pending'");$s->execute([':reason'=>$reason,':id'=>$addressId]);
$s=$pdo->prepare("INSERT INTO notifications(user_id,title,message,type,reference_id) VALUES(:user_id,'Delivery Address Rejected',:message,'address_rejected',:reference_id)");$s->execute([':user_id'=>$a['user_id'],':message'=>'Your delivery address was rejected. Reason: '.$reason,':reference_id'=>$addressId]);
$pdo->commit();$_SESSION['success']='Delivery address rejected.';
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['error']=$e->getMessage();}
header('Location:index.php?type=addresses&status=pending');exit;
