<?php
declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$pageTitle = 'Add Product';
$currentPage = 'products';

$categories = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'active'
    ORDER BY display_order ASC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$units = $pdo->query("
    SELECT id, name, short_name
    FROM units
    WHERE status = 'active'
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .pack-card{border:1px solid #e5e9e5;border-radius:12px;padding:16px;background:#fbfcfb;margin-bottom:10px}
        .pack-number{width:30px;height:30px;border-radius:50%;background:#075B2A;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
        .remove-pack{border:0;background:transparent;color:#b42318;font-size:12px;font-weight:600}
        .image-preview{width:150px;height:150px;border-radius:12px;border:1px dashed #cdd4cd;background:#f7f9f7;display:none;object-fit:cover;margin-top:12px}
        .pack-help{background:#f2f8f0;border:1px solid #dcebd8;border-radius:10px;padding:13px 15px;font-size:12px;color:#4d5b4d}
    </style>
</head>
<body>
<?php require_once '../../includes/admin-sidebar.php'; ?>
<main class="admin-main">
<?php require_once '../../includes/admin-header.php'; ?>
<div class="admin-content">
    <div class="mb-3"><a href="index.php" class="text-decoration-none" style="color:#075B2A;font-size:13px;">← Back to Products</a></div>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="save.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <div><h5>Product Information</h5><div class="text-muted" style="font-size:11px;margin-top:3px;">One price, one stock and one base unit per product.</div></div>
            </div>
            <div class="admin-card-body">
                <div class="row g-4">
                    <div class="col-12 col-md-7">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="150" placeholder="e.g. Fresh Tomato" required autofocus>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>"><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Base Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" id="unit_id" class="form-select" required>
                            <option value="">Select Unit</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= (int)$unit['id'] ?>"><?= e($unit['name']) ?> (<?= e($unit['short_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">All packs use this unit.</small>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Price per Unit <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="e.g. 60.00" required>
                        <small class="text-muted">Pack price is calculated from this base rate.</small>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control" min="0" step="0.001" value="0" required>
                        <small class="text-muted">Total stock in the selected base unit.</small>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Low Stock Alert</label>
                        <input type="number" name="low_stock_threshold" class="form-control" min="0" step="0.001" value="5">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">JPG, PNG or WebP. Maximum 2 MB.</small>
                        <img id="imagePreview" class="image-preview" alt="Preview">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the product..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="featured">
                            <label class="form-check-label" for="featured">Show this product as featured</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <div><h5>Customer Packs</h5><div class="text-muted" style="font-size:11px;margin-top:3px;">Add the quantities customers can select.</div></div>
                <button type="button" class="btn btn-lfm" id="addPack">+ Add Pack</button>
            </div>
            <div class="admin-card-body">
                <div class="pack-help mb-3">
                    Example: if the base unit is <strong>Kg</strong> and the price is <strong>₹60/Kg</strong>,
                    packs can be <strong>0.25, 0.5, 1 and 2 Kg</strong>. The stock remains one shared stock of Kg.
                </div>
                <div id="packsContainer">
                    <div class="pack-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2"><span class="pack-number">1</span><strong>Pack</strong></div>
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Pack Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="pack_quantity[]" class="form-control" min="0.001" step="0.001" value="1" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Status</label>
                                <select name="pack_status[]" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-5">
            <a href="index.php" class="btn btn-light border">Cancel</a>
            <button type="submit" class="btn btn-lfm">Save Product</button>
        </div>
    </form>
</div>
</main>
<script>
const container=document.getElementById('packsContainer');
const add=document.getElementById('addPack');
function renumber(){
    container.querySelectorAll('.pack-card').forEach((card,i)=>{
        card.querySelector('.pack-number').textContent=i+1;
        const old=card.querySelector('.remove-pack');
        if(old) old.remove();
        if(i>0){
            const btn=document.createElement('button');
            btn.type='button'; btn.className='remove-pack'; btn.textContent='Remove';
            btn.addEventListener('click',()=>{card.remove();renumber();});
            card.querySelector('.d-flex').appendChild(btn);
        }
    });
}
add.addEventListener('click',()=>{
    const card=document.createElement('div');
    card.className='pack-card';
    card.innerHTML=`<div class="d-flex justify-content-between align-items-center mb-3"><div class="d-flex align-items-center gap-2"><span class="pack-number"></span><strong>Pack</strong></div></div>
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-6"><label class="form-label">Pack Quantity *</label><input type="number" name="pack_quantity[]" class="form-control" min="0.001" step="0.001" required></div>
      <div class="col-12 col-md-6"><label class="form-label">Status</label><select name="pack_status[]" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
    </div>`;
    container.appendChild(card); renumber();
});
document.getElementById('image')?.addEventListener('change',e=>{
    const f=e.target.files[0], img=document.getElementById('imagePreview');
    if(f){img.src=URL.createObjectURL(f);img.style.display='block';}else img.style.display='none';
});
</script>
</body>
</html>
