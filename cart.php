<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(!isset($_SESSION['lfm_cart'])||!is_array($_SESSION['lfm_cart']))$_SESSION['lfm_cart']=[];

function cartRedirect():never{header('Location: '.BASE_URL.'cart.php');exit;}
function cartQty(float $v):string{return rtrim(rtrim(number_format($v,3,'.',''),'0'),'.');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['remove_id'])){unset($_SESSION['lfm_cart'][(string)(int)$_POST['remove_id']]);setFlash('success','Item removed from cart.');cartRedirect();}
    $action=$_POST['action']??'';
    if ($action === 'add_multiple') {

    $submittedPacks = $_POST['packs'] ?? [];

    if (!is_array($submittedPacks) || !$submittedPacks) {

        setFlash(
            'error',
            'Please select at least one pack.'
        );

        cartRedirect();
    }


    $packIds = [];

    foreach ($submittedPacks as $packId => $data) {

        $packId = (int) $packId;

        if ($packId <= 0 || !is_array($data)) {
            continue;
        }

        if (empty($data['selected'])) {
            continue;
        }

        $packCount = (float) ($data['quantity'] ?? 0);

        if (
            $packCount < 1 ||
            abs($packCount - round($packCount)) > 0.000001
        ) {
            setFlash(
                'error',
                'Please enter a valid quantity for every selected pack.'
            );

            cartRedirect();
        }

        $packIds[$packId] = $packCount;
    }


    if (!$packIds) {

        setFlash(
            'error',
            'Please select at least one pack.'
        );

        cartRedirect();
    }


    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($packIds),
            '?'
        )
    );


    $stmt = $pdo->prepare("
        SELECT
            pv.id,
            pv.quantity AS pack_quantity,
            pv.status AS pack_status,

            p.name,
            p.stock,
            p.status AS product_status

        FROM product_variants pv

        INNER JOIN products p
            ON p.id = pv.product_id

        WHERE pv.id IN ($placeholders)

          AND pv.status = 'active'
          AND p.status = 'active'
    ");


    $stmt->execute(
        array_keys($packIds)
    );


    $packsFromDb = [];

    foreach (
        $stmt->fetchAll(PDO::FETCH_ASSOC)
        as $pack
    ) {

        $packsFromDb[
            (int) $pack['id']
        ] = $pack;

    }


    foreach ($packIds as $packId => $packCount) {

        if (!isset($packsFromDb[$packId])) {

            setFlash(
                'error',
                'One of the selected packs is no longer available.'
            );

            cartRedirect();
        }


        $pack = $packsFromDb[$packId];

        $packQuantity =
            (float) $pack['pack_quantity'];


        $maxPacks = (int) floor(
            ((float) $pack['stock'] + 0.000001)
            / $packQuantity
        );


        $existing =
            (float) (
                $_SESSION['lfm_cart'][(string) $packId]['quantity']
                ?? 0
            );


        $newQuantity =
            $existing + $packCount;


        if (
            $maxPacks < 1 ||
            $newQuantity > $maxPacks
        ) {

            setFlash(
                'error',
                'The requested quantity for ' .
                $pack['name'] .
                ' is not available in stock.'
            );

            cartRedirect();
        }


        $_SESSION['lfm_cart'][
            (string) $packId
        ] = [
            'quantity' => $newQuantity
        ];

    }


    setFlash(
        'success',
        'Selected packs were added to your cart.'
    );

    cartRedirect();
}
    if($action==='update'){
        foreach($_SESSION['lfm_cart'] as $key=>$item){
            $id=(int)$key;$count=(float)($_POST['quantity'][$key]??0);
            $s=$pdo->prepare("SELECT pv.quantity,pv.status,p.stock,p.status AS product_status FROM product_variants pv INNER JOIN products p ON p.id=pv.product_id WHERE pv.id=:id LIMIT 1");$s->execute([':id'=>$id]);$p=$s->fetch(PDO::FETCH_ASSOC);
            if(!$p||$p['status']!=='active'||$p['product_status']!=='active'||$count<1||abs($count-round($count))>0.000001){unset($_SESSION['lfm_cart'][$key]);continue;}
            $max=(int)floor(((float)$p['stock']+0.000001)/(float)$p['quantity']);if($count>$max)continue;$_SESSION['lfm_cart'][$key]['quantity']=$count;
        }
        setFlash('success','Cart updated.');cartRedirect();
    }
}
$items=[];$subtotal=0;
if($_SESSION['lfm_cart']){
    $ids=array_values(array_filter(array_map('intval',array_keys($_SESSION['lfm_cart']))));
    if($ids){$ph=implode(',',array_fill(0,count($ids),'?'));$s=$pdo->prepare("SELECT pv.id,pv.quantity AS pack_quantity,pv.status AS pack_status,p.name,p.slug,p.image,p.price,p.stock,u.short_name,c.name AS category_name FROM product_variants pv INNER JOIN products p ON p.id=pv.product_id INNER JOIN units u ON u.id=p.unit_id INNER JOIN categories c ON c.id=p.category_id WHERE pv.id IN ($ph) AND pv.status='active' AND p.status='active' AND c.status='active'");$s->execute($ids);foreach($s->fetchAll(PDO::FETCH_ASSOC) as $row){$key=(string)$row['id'];$count=(float)($_SESSION['lfm_cart'][$key]['quantity']??0);$packQty=(float)$row['pack_quantity'];$packPrice=round((float)$row['price']*$packQty,2);$row['cart_quantity']=$count;$row['pack_price']=$packPrice;$row['total_quantity']=$count*$packQty;$row['line_total']=$count*$packPrice;$items[]=$row;$subtotal+=$row['line_total'];}}
}
$subtotal=round($subtotal,2);$flash=getFlash();$pageTitle='Your Cart';$activePage='';
?>
<?php require 'includes/store-header.php';?>
<section class="page-hero">
    <div class="container">
        <h1>Your Cart</h1>
        <div class="breadcrumb">Home / Cart</div>
    </div>
</section>
<section class="cart-page">
    <div class="container">
        <?php if($flash):?><div class="form-alert form-alert-<?=e($flash['type'])?>"><?=e($flash['message'])?></div>
        <?php endif;?>
        <?php if(!$items):?><div class="empty-state">
            <h2>Your cart is empty</h2>
            <p>Add some fresh products to get started.</p><a class="btn-primary" href="<?=BASE_URL?>shop.php">Start
                Shopping</a>
        </div>
        <?php else:?><div class="cart-layout">
            <div class="cart-table">
                <form method="post"><input type="hidden" name="action" value="update">
                    <?php foreach($items as $item):?><div class="cart-item">
                        <div class="cart-item-image"><?php if($item['image']):?><img
                                src="<?=BASE_URL.e($item['image'])?>" alt="<?=e($item['name'])?>"><?php else:?><div
                                class="product-placeholder">✦</div><?php endif;?></div>
                        <div>
                            <div class="cart-item-name"><?=e($item['name'])?></div>
                            <div class="cart-item-meta">Pack: <?=e(cartQty((float)$item['pack_quantity']))?>
                                <?=e($item['short_name'])?> · ₹<?=number_format($item['pack_price'],2)?> / pack</div>
                        </div>
                        <div>
                            <div class="cart-item-meta">Packs</div><input type="number"
                                name="quantity[<?= (int)$item['id']?>]" value="<?=e(cartQty($item['cart_quantity']))?>"
                                min="1" step="1"
                                max="<?=e((string)floor(((float)$item['stock']+0.000001)/(float)$item['pack_quantity']))?>"
                                style="width:95px;padding:7px;border:1px solid var(--border);border-radius:7px;">
                        </div>
                        <div class="cart-item-price">₹<?=number_format($item['line_total'],2)?></div><button
                            class="cart-remove" type="submit" name="remove_id" value="<?= (int)$item['id'] ?>"
                            aria-label="Remove item">×</button>
                    </div><?php endforeach;?>
                    <div style="padding:16px;text-align:right;"><button class="btn-secondary" type="submit">Update
                            Cart</button></div>
                </form>
            </div>
            <aside class="summary-card">
                <h3>Order Summary</h3>
                <div class="summary-row"><span>Subtotal</span><span>₹<?=number_format($subtotal,2)?></span></div>
                <div class="summary-row"><span>Delivery</span><span>Calculated at checkout</span></div>
                <div class="summary-row summary-total"><span>Total</span><span>₹<?=number_format($subtotal,2)?></span>
                </div><a class="btn-primary" style="width:100%;margin-top:17px;"
                    href="<?=BASE_URL?>checkout.php">Proceed to Checkout</a>
            </aside>
        </div><?php endif;?>
    </div>
</section>
<?php require 'includes/store-footer.php';?>