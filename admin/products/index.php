<?php
declare(strict_types=1);
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
requireAdmin();
$pageTitle='Products';$currentPage='products';
$search=trim($_GET['search']??'');$categoryId=filter_input(INPUT_GET,'category_id',FILTER_VALIDATE_INT);$status=$_GET['status']??'';
if(!in_array($status,['','active','inactive'],true))$status='';
$categories=$pdo->query("SELECT id,name FROM categories ORDER BY display_order ASC,name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sql="SELECT p.id,p.name,p.slug,p.image,p.featured,p.status,p.price,p.stock,p.low_stock_threshold,c.name AS category_name,u.short_name AS unit_short_name,
COUNT(CASE WHEN pv.status='active' THEN 1 END) AS pack_count
FROM products p INNER JOIN categories c ON c.id=p.category_id LEFT JOIN units u ON u.id=p.unit_id LEFT JOIN product_variants pv ON pv.product_id=p.id";
$where=[];$params=[];
if($search!==''){$where[]="(p.name LIKE :search OR p.slug LIKE :search)";$params[':search']='%'.$search.'%';}
if($categoryId){$where[]="p.category_id=:category_id";$params[':category_id']=$categoryId;}
if($status!==''){$where[]="p.status=:status";$params[':status']=$status;}
if($where)$sql.=" WHERE ".implode(' AND ',$where);
$sql.=" GROUP BY p.id,p.name,p.slug,p.image,p.featured,p.status,p.price,p.stock,p.low_stock_threshold,c.name,u.short_name ORDER BY p.created_at DESC,p.id DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$products=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalProducts=count($products);$activeProducts=$inactiveProducts=$featuredProducts=0;
foreach($products as $p){$p['status']==='active'?$activeProducts++:$inactiveProducts++;if((int)$p['featured']===1)$featuredProducts++;}
$success=$_SESSION['success']??null;$error=$_SESSION['error']??null;unset($_SESSION['success'],$_SESSION['error']);
function qtyText(float $v):string{return rtrim(rtrim(number_format($v,3,'.',''),'0'),'.');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($pageTitle)?> | <?=e(SITE_NAME)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="<?=BASE_URL?>assets/css/admin.css">
<style>.product-thumb{width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #edf0ed}.product-placeholder{width:52px;height:52px;border-radius:10px;background:#edf5ea;color:#075B2A;display:flex;align-items:center;justify-content:center;font-weight:700}.product-name{font-weight:600;color:#252925}.status-badge{display:inline-flex;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:600}.status-active{background:#e6f5e8;color:#176b2d}.status-inactive{background:#f0f1f0;color:#6e756e}.featured-badge{display:inline-flex;padding:4px 8px;border-radius:15px;background:#fff4d7;color:#936b00;font-size:10px;font-weight:600}.price-text{color:#075B2A;font-weight:700}.empty-state{padding:65px 20px;text-align:center}</style></head><body>
<?php require_once '../../includes/admin-sidebar.php';?><main class="admin-main"><?php require_once '../../includes/admin-header.php';?><div class="admin-content">
<div class="row g-4 mb-4"><?php foreach([['Total Products',$totalProducts],['Active',$activeProducts],['Inactive',$inactiveProducts],['Featured',$featuredProducts]] as $s):?><div class="col-12 col-md-3"><div class="admin-card p-3 h-100"><div class="text-muted" style="font-size:12px"><?=$s[0]?></div><div style="color:#075B2A;font-size:25px;font-weight:700"><?=number_format($s[1])?></div></div></div><?php endforeach;?></div>
<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="admin-card"><div class="admin-card-header"><div><h5>Products</h5><div class="text-muted" style="font-size:11px;margin-top:3px;">One stock and one price per product, with selectable customer packs.</div></div><a href="create.php" class="btn btn-lfm">+ Add Product</a></div>
<div class="px-4 py-3" style="border-bottom:1px solid #edf0ed"><form method="get"><div class="row g-2"><div class="col-12 col-md-5"><input class="form-control" name="search" value="<?=e($search)?>" placeholder="Search products..."></div><div class="col-12 col-md-3"><select class="form-select" name="category_id"><option value="">All Categories</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=$categoryId===$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></div><div class="col-12 col-md-2"><select class="form-select" name="status"><option value="">All Status</option><option value="active" <?=$status==='active'?'selected':''?>>Active</option><option value="inactive" <?=$status==='inactive'?'selected':''?>>Inactive</option></select></div><div class="col-6 col-md-1"><button class="btn btn-lfm w-100">Search</button></div><div class="col-6 col-md-1"><a href="index.php" class="btn btn-light border w-100">Clear</a></div></div></form></div>
<div class="admin-card-body p-0"><?php if(!$products):?><div class="empty-state"><h6>No products found</h6><p class="text-muted">Start adding products to your store.</p><a href="create.php" class="btn btn-lfm">Add Product</a></div><?php else:?><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Packs</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach($products as $p):?><tr><td><div class="d-flex align-items-center"><?php if($p['image']):?><img class="product-thumb" src="<?=BASE_URL.e($p['image'])?>" alt="<?=e($p['name'])?>"><?php else:?><div class="product-placeholder"><?=e(strtoupper(mb_substr($p['name'],0,1)))?></div><?php endif;?><div class="ms-3"><div class="product-name"><?=e($p['name'])?></div><?php if($p['featured']):?><span class="featured-badge">Featured</span><?php endif;?></div></div></td><td><?=e($p['category_name'])?></td><td><span class="price-text">₹<?=number_format((float)$p['price'],2)?> / <?=e($p['unit_short_name']??'unit')?></span></td><td><?=e(qtyText((float)$p['stock']))?> <?=e($p['unit_short_name']??'')?><div class="text-muted" style="font-size:10px">Alert at <?=e(qtyText((float)$p['low_stock_threshold']))?></div></td><td><?=number_format((int)$p['pack_count'])?></td><td><span class="status-badge status-<?=e($p['status'])?>"><?=e(ucfirst($p['status']))?></span></td><td class="text-end"><a href="edit.php?id=<?=$p['id']?>" class="btn btn-sm btn-lfm-light">Edit</a> <a href="delete.php?id=<?=$p['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">Delete</a></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?></div></div></div></main></body></html>
