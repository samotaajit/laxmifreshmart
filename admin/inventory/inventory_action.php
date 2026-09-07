<?php
declare(strict_types=1);
require_once '../../config/database.php';require_once '../../config/config.php';require_once '../../includes/functions.php';require_once '../../includes/auth.php';
requireAdmin();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location:index.php');exit;}
$productId=filter_input(INPUT_POST,'product_id',FILTER_VALIDATE_INT);$type=$_POST['adjustment_type']??'';$quantity=filter_input(INPUT_POST,'quantity',FILTER_VALIDATE_FLOAT);$reason=trim($_POST['reason']??'');
if(!$productId||!in_array($type,['add','remove','set'],true)||$quantity===false||$quantity<0){$_SESSION['error']='Please provide valid inventory details.';header('Location:index.php');exit;}
try{
$pdo->beginTransaction();
$s=$pdo->prepare("SELECT id,name,stock FROM products WHERE id=:id LIMIT 1 FOR UPDATE");$s->execute([':id'=>$productId]);$p=$s->fetch(PDO::FETCH_ASSOC);if(!$p)throw new RuntimeException('Product not found.');
$old=(float)$p['stock'];$new=$type==='add'?$old+$quantity:($type==='remove'?$old-$quantity:$quantity);if($new<0)throw new RuntimeException('Stock cannot become negative.');
$s=$pdo->prepare("UPDATE products SET stock=:stock WHERE id=:id");$s->execute([':stock'=>number_format($new,3,'.',''),':id'=>$productId]);

// Log only when the optional history table has a product_id column.
$check=$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='inventory_adjustments' AND column_name='product_id'");
if((int)$check->fetchColumn()>0){
    $s=$pdo->prepare("INSERT INTO inventory_adjustments(product_id,adjustment_type,quantity,previous_stock,new_stock,reason,admin_id) VALUES(:product_id,:type,:quantity,:old,:new,:reason,:admin_id)");
    $s->execute([':product_id'=>$productId,':type'=>$type,':quantity'=>$quantity,':old'=>$old,':new'=>$new,':reason'=>$reason?:null,':admin_id'=>(int)($_SESSION['admin_id']??0)]);
}
$pdo->commit();$_SESSION['success']='Stock updated successfully.';
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['error']=$e->getMessage();}
header('Location:index.php');exit;
