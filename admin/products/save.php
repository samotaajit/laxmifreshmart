<?php
declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$action=$_POST['action']??'';
if(!in_array($action,['create','edit'],true)){$_SESSION['error']='Invalid request.';header('Location:index.php');exit;}

$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
$name=trim($_POST['name']??'');
$categoryId=filter_input(INPUT_POST,'category_id',FILTER_VALIDATE_INT);
$unitId=filter_input(INPUT_POST,'unit_id',FILTER_VALIDATE_INT);
$price=(float)($_POST['price']??0);
$stock=(float)($_POST['stock']??0);
$lowStock=(float)($_POST['low_stock_threshold']??0);
$description=trim($_POST['description']??'');
$featured=isset($_POST['featured'])?1:0;
$status=$_POST['status']??'active';

$packsQty=$_POST['pack_quantity']??[];
$packsStatus=$_POST['pack_status']??[];
$packIds=$_POST['pack_id']??[];

$back=$action==='edit'?'edit.php?id='.(int)$id:'create.php';
if($name===''){$_SESSION['error']='Product name is required.';header('Location:'.$back);exit;}
if(!$categoryId){$_SESSION['error']='Please select a category.';header('Location:'.$back);exit;}
if(!$unitId){$_SESSION['error']='Please select a base unit.';header('Location:'.$back);exit;}
if($price<0){$_SESSION['error']='Price cannot be negative.';header('Location:'.$back);exit;}
if($stock<0){$_SESSION['error']='Stock cannot be negative.';header('Location:'.$back);exit;}
if(!in_array($status,['active','inactive'],true)){$_SESSION['error']='Invalid product status.';header('Location:'.$back);exit;}
if($lowStock<0){$_SESSION['error']='Low stock threshold cannot be negative.';header('Location:'.$back);exit;}
if(!$packsQty){$_SESSION['error']='Add at least one customer pack.';header('Location:'.$back);exit;}

$packs=[];$seen=[];
foreach($packsQty as $i=>$raw){
    $q=(float)$raw;$packStatus=$packsStatus[$i]??'active';
    $existingId=(int)($packIds[$i]??0);
    if($q<=0){$_SESSION['error']='Every pack quantity must be greater than zero.';header('Location:'.$back);exit;}
    if(!in_array($packStatus,['active','inactive'],true)){$_SESSION['error']='Invalid pack status.';header('Location:'.$back);exit;}
    $key=number_format($q,3,'.','');
    if(isset($seen[$key])){$_SESSION['error']='Duplicate pack quantities are not allowed.';header('Location:'.$back);exit;}
    $seen[$key]=true;
    $packs[]=['id'=>$existingId,'quantity'=>$q,'status'=>$packStatus];
}

$stmt=$pdo->prepare("SELECT id FROM categories WHERE id=:id LIMIT 1");$stmt->execute([':id'=>$categoryId]);
if(!$stmt->fetch()){$_SESSION['error']='Selected category does not exist.';header('Location:'.$back);exit;}
$stmt=$pdo->prepare("SELECT id FROM units WHERE id=:id AND status='active' LIMIT 1");$stmt->execute([':id'=>$unitId]);
if(!$stmt->fetch()){$_SESSION['error']='Selected unit is invalid or inactive.';header('Location:'.$back);exit;}

$newImage=null;
if(isset($_FILES['image']) && $_FILES['image']['error']!==UPLOAD_ERR_NO_FILE){
    if($_FILES['image']['error']!==UPLOAD_ERR_OK){$_SESSION['error']='There was a problem uploading the image.';header('Location:'.$back);exit;}
    if($_FILES['image']['size']>2*1024*1024){$_SESSION['error']='Product image must not exceed 2 MB.';header('Location:'.$back);exit;}
    $info=@getimagesize($_FILES['image']['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!$info || !isset($allowed[$info['mime']])){$_SESSION['error']='Only valid JPG, PNG and WebP images are allowed.';header('Location:'.$back);exit;}
    $dir=dirname(__DIR__,2).'/uploads/products/';
    if(!is_dir($dir) && !mkdir($dir,0755,true)){$_SESSION['error']='Could not create the product image directory.';header('Location:'.$back);exit;}
    $file='product_'.bin2hex(random_bytes(12)).'.'.$allowed[$info['mime']];
    if(!move_uploaded_file($_FILES['image']['tmp_name'],$dir.$file)){$_SESSION['error']='Could not save the product image.';header('Location:'.$back);exit;}
    $newImage='uploads/products/'.$file;
}

$baseSlug=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower($name)),'-') ?: 'product';

try{
    $pdo->beginTransaction();

    if($action==='create'){
        $slug=$baseSlug;$n=2;
        while(true){$s=$pdo->prepare("SELECT id FROM products WHERE slug=:slug LIMIT 1");$s->execute([':slug'=>$slug]);if(!$s->fetch())break;$slug=$baseSlug.'-'.$n++;}
        $stmt=$pdo->prepare("INSERT INTO products(category_id,unit_id,name,slug,description,image,price,stock,low_stock_threshold,featured,status) VALUES(:category_id,:unit_id,:name,:slug,:description,:image,:price,:stock,:low_stock,:featured,:status)");
        $stmt->execute([':category_id'=>$categoryId,':unit_id'=>$unitId,':name'=>$name,':slug'=>$slug,':description'=>$description?:null,':image'=>$newImage,':price'=>$price,':stock'=>$stock,':low_stock'=>$lowStock,':featured'=>$featured,':status'=>$status]);
        $productId=(int)$pdo->lastInsertId();
    }else{
        if(!$id)throw new RuntimeException('Invalid product.');
        $stmt=$pdo->prepare("SELECT id,image FROM products WHERE id=:id LIMIT 1 FOR UPDATE");$stmt->execute([':id'=>$id]);$existing=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$existing)throw new RuntimeException('Product not found.');
        $slug=$baseSlug;$n=2;
        while(true){$s=$pdo->prepare("SELECT id FROM products WHERE slug=:slug AND id!=:id LIMIT 1");$s->execute([':slug'=>$slug,':id'=>$id]);if(!$s->fetch())break;$slug=$baseSlug.'-'.$n++;}
        $image=$newImage??$existing['image'];
        $stmt=$pdo->prepare("UPDATE products SET category_id=:category_id,unit_id=:unit_id,name=:name,slug=:slug,description=:description,image=:image,price=:price,stock=:stock,low_stock_threshold=:low_stock,featured=:featured,status=:status WHERE id=:id");
        $stmt->execute([':category_id'=>$categoryId,':unit_id'=>$unitId,':name'=>$name,':slug'=>$slug,':description'=>$description?:null,':image'=>$image,':price'=>$price,':stock'=>$stock,':low_stock'=>$lowStock,':featured'=>$featured,':status'=>$status,':id'=>$id]);
        $productId=$id;
    }

    $orderItemCountStmt=$pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id=:product_id");$orderItemCountStmt->execute([':product_id'=>$productId]);$hasOrders=(int)$orderItemCountStmt->fetchColumn()>0;

    if(!$hasOrders){
        $pdo->prepare("DELETE FROM product_variants WHERE product_id=:product_id")->execute([':product_id'=>$productId]);
        $insert=$pdo->prepare("INSERT INTO product_variants(product_id,unit_id,quantity,price,sale_price,stock,low_stock_threshold,min_order_quantity,quantity_step,status) VALUES(:product_id,:unit_id,:quantity,0,NULL,0,0,1,1,:status)");
        foreach($packs as $pack){$insert->execute([':product_id'=>$productId,':unit_id'=>$unitId,':quantity'=>$pack['quantity'],':status'=>$pack['status']]);}
    }else{
        // Preserve existing pack IDs because historical order_items may reference them.
        $existingIds=[];
        $s=$pdo->prepare("SELECT id FROM product_variants WHERE product_id=:product_id ORDER BY id ASC");$s->execute([':product_id'=>$productId]);
        foreach($s->fetchAll(PDO::FETCH_COLUMN) as $vid)$existingIds[]=(int)$vid;
        $update=$pdo->prepare("UPDATE product_variants SET unit_id=:unit_id,quantity=:quantity,status=:status WHERE id=:id AND product_id=:product_id");
        $insert=$pdo->prepare("INSERT INTO product_variants(product_id,unit_id,quantity,price,sale_price,stock,low_stock_threshold,min_order_quantity,quantity_step,status) VALUES(:product_id,:unit_id,:quantity,0,NULL,0,0,1,1,:status)");
        $used=[];
        foreach($packs as $i=>$pack){
            $pid=(int)($pack['id']??0);
            if(!$pid && isset($existingIds[$i]))$pid=$existingIds[$i];
            if($pid && in_array($pid,$existingIds,true)){
                $update->execute([':unit_id'=>$unitId,':quantity'=>$pack['quantity'],':status'=>$pack['status'],':id'=>$pid,':product_id'=>$productId]);$used[]=$pid;
            }else{
                $insert->execute([':product_id'=>$productId,':unit_id'=>$unitId,':quantity'=>$pack['quantity'],':status'=>$pack['status']]);
            }
        }
        if($existingIds){
            $disable=$pdo->prepare("UPDATE product_variants SET status='inactive' WHERE product_id=:product_id AND id NOT IN (".implode(',',array_map('intval',$used ?: [0])).")");
            $disable->execute([':product_id'=>$productId]);
        }
    }

    $pdo->commit();

    if($newImage && !empty($existing['image']??null)){
        $old=dirname(__DIR__,2).'/'.ltrim($existing['image'],'/');
        if(is_file($old))@unlink($old);
    }
    $_SESSION['success']=$action==='create'?'Product created successfully.':'Product updated successfully.';
    header('Location:index.php');exit;
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    if($newImage){$file=dirname(__DIR__,2).'/'.ltrim($newImage,'/');if(is_file($file))@unlink($file);}
    $_SESSION['error']=$e->getMessage();header('Location:'.$back);exit;
}
