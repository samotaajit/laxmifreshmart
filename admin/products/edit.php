<?php
declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'Edit Product';
$currentPage = 'products';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['error']='Invalid product.'; header('Location: index.php'); exit; }

$stmt=$pdo->prepare("SELECT p.*,u.name AS unit_name,u.short_name AS unit_short_name FROM products p LEFT JOIN units u ON u.id=p.unit_id WHERE p.id=:id LIMIT 1");
$stmt->execute([':id'=>$id]);
$product=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){$_SESSION['error']='Product not found.';header('Location: index.php');exit;}

$categories=$pdo->query("SELECT id,name FROM categories WHERE status='active' ORDER BY display_order ASC,name ASC")->fetchAll(PDO::FETCH_ASSOC);
$units=$pdo->query("SELECT id,name,short_name FROM units WHERE status='active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt=$pdo->prepare("SELECT id,quantity,status FROM product_variants WHERE product_id=:product_id ORDER BY quantity ASC,id ASC");
$stmt->execute([':product_id'=>$id]);
$packs=$stmt->fetchAll(PDO::FETCH_ASSOC);
if(!$packs){$packs=[['id'=>null,'quantity'=>1,'status'=>'active']];}

$error=$_SESSION['error']??null; unset($_SESSION['error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?> | <?=e(SITE_NAME)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?=BASE_URL?>assets/css/admin.css">
<style>
.pack-card{border:1px solid #e5e9e5;border-radius:12px;padding:16px;background:#fbfcfb;margin-bottom:10px}
.pack-number{width:30px;height:30px;border-radius:50%;background:#075B2A;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.remove-pack{border:0;background:transparent;color:#b42318;font-size:12px;font-weight:600}.current-image{width:150px;height:150px;object-fit:cover;border-radius:12px;border:1px solid #e1e6e1;margin-top:12px}
.pack-help{background:#f2f8f0;border:1px solid #dcebd8;border-radius:10px;padding:13px 15px;font-size:12px;color:#4d5b4d}
</style>
</head>
<body>
<?php require_once '../../includes/admin-sidebar.php'; ?>
<main class="admin-main"><?php require_once '../../includes/admin-header.php'; ?><div class="admin-content">
<div class="mb-3"><a href="index.php" class="text-decoration-none" style="color:#075B2A;font-size:13px;">← Back to Products</a></div>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<form method="post" action="save.php" enctype="multipart/form-data">
<input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

<div class="admin-card mb-4"><div class="admin-card-header"><div><h5>Product Information</h5><div class="text-muted" style="font-size:11px;margin-top:3px;">One price, one stock and one base unit per product.</div></div></div>
<div class="admin-card-body"><div class="row g-4">
<div class="col-12 col-md-7"><label class="form-label">Product Name *</label><input name="name" class="form-control" maxlength="150" value="<?=e($product['name'])?>" required></div>
<div class="col-12 col-md-5"><label class="form-label">Category *</label><select name="category_id" class="form-select" required><option value="">Select Category</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$product['category_id']==$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></div>
<div class="col-12 col-md-4"><label class="form-label">Base Unit *</label><select name="unit_id" class="form-select" required><option value="">Select Unit</option><?php foreach($units as $u):?><option value="<?=$u['id']?>" <?=$product['unit_id']==$u['id']?'selected':''?>><?=e($u['name'])?> (<?=e($u['short_name'])?>)</option><?php endforeach;?></select></div>
<div class="col-12 col-md-4"><label class="form-label">Price per Unit *</label><input type="number" name="price" class="form-control" min="0" step="0.01" value="<?=e((string)$product['price'])?>" required></div>
<div class="col-12 col-md-4"><label class="form-label">Stock *</label><input type="number" name="stock" class="form-control" min="0" step="0.001" value="<?=e((string)$product['stock'])?>" required></div>
<div class="col-12 col-md-4"><label class="form-label">Low Stock Alert</label><input type="number" name="low_stock_threshold" class="form-control" min="0" step="0.001" value="<?=e((string)$product['low_stock_threshold'])?>"></div>
<div class="col-12 col-md-8"><label class="form-label">Product Image</label><input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG or WebP. Maximum 2 MB.</small><?php if(!empty($product['image'])):?><br><img src="<?=BASE_URL.e($product['image'])?>" class="current-image" alt="<?=e($product['name'])?>"><?php endif;?></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?=e($product['description']??'')?></textarea></div>
<div class="col-12 col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?=$product['status']==='active'?'selected':''?>>Active</option><option value="inactive" <?=$product['status']==='inactive'?'selected':''?>>Inactive</option></select></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="featured" id="featured" <?=$product['featured']?'checked':''?>><label class="form-check-label" for="featured">Show this product as featured</label></div></div>
</div></div></div>

<div class="admin-card mb-4"><div class="admin-card-header"><div><h5>Customer Packs</h5><div class="text-muted" style="font-size:11px;margin-top:3px;">Only pack quantities are managed here. Stock and price stay at product level.</div></div><button type="button" class="btn btn-lfm" id="addPack">+ Add Pack</button></div>
<div class="admin-card-body"><div class="pack-help mb-3">All packs use <strong><?=e($product['unit_short_name']??'the selected unit')?></strong>. Example: 0.25, 0.5, 1 and 2.</div>
<div id="packsContainer">
<?php foreach($packs as $pack):?><div class="pack-card">
<div class="d-flex justify-content-between align-items-center mb-3"><div class="d-flex align-items-center gap-2"><span class="pack-number"></span><strong>Pack</strong></div><?php if($pack['id']):?><input type="hidden" name="pack_id[]" value="<?= (int)$pack['id']?>"><?php else:?><input type="hidden" name="pack_id[]" value=""><?php endif;?></div>
<div class="row g-3 align-items-end"><div class="col-12 col-md-6"><label class="form-label">Pack Quantity *</label><input type="number" name="pack_quantity[]" class="form-control" min="0.001" step="0.001" value="<?=e((string)$pack['quantity'])?>" required></div><div class="col-12 col-md-6"><label class="form-label">Status</label><select name="pack_status[]" class="form-select"><option value="active" <?=$pack['status']==='active'?'selected':''?>>Active</option><option value="inactive" <?=$pack['status']==='inactive'?'selected':''?>>Inactive</option></select></div></div>
</div><?php endforeach;?>
</div></div></div>

<div class="d-flex gap-2 justify-content-end mb-5"><a href="index.php" class="btn btn-light border">Cancel</a><button class="btn btn-lfm" type="submit">Update Product</button></div>
</form></div></main>
<script>
const container=document.getElementById('packsContainer'),add=document.getElementById('addPack');
function renumber(){container.querySelectorAll('.pack-card').forEach((card,i)=>{card.querySelector('.pack-number').textContent=i+1;let b=card.querySelector('.remove-pack');if(b)b.remove();if(i>0){b=document.createElement('button');b.type='button';b.className='remove-pack';b.textContent='Remove';b.onclick=()=>{card.remove();renumber()};card.querySelector('.d-flex').appendChild(b)}})}
renumber();
add.onclick=()=>{const c=document.createElement('div');c.className='pack-card';c.innerHTML=`<div class="d-flex justify-content-between align-items-center mb-3"><div class="d-flex align-items-center gap-2"><span class="pack-number"></span><strong>Pack</strong></div><input type="hidden" name="pack_id[]" value=""></div><div class="row g-3 align-items-end"><div class="col-12 col-md-6"><label class="form-label">Pack Quantity *</label><input type="number" name="pack_quantity[]" class="form-control" min="0.001" step="0.001" required></div><div class="col-12 col-md-6"><label class="form-label">Status</label><select name="pack_status[]" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div>`;container.appendChild(c);renumber()};
</script>
</body></html>
