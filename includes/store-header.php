<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? SITE_NAME;
$activePage = $activePage ?? '';

/*
 * The badge shows the number of valid packs in the current cart.
 * It never shows the cart subtotal.
 */
$cartCount = 0;
$cart = $_SESSION['lfm_cart'] ?? [];

if (is_array($cart) && $cart) {
    $ids = [];

    foreach ($cart as $key => $item) {
        $packId = (int) $key;
        $quantity = is_array($item) ? (float) ($item['quantity'] ?? 0) : 0;

        if (
            $packId <= 0 ||
            $quantity < 1 ||
            abs($quantity - round($quantity)) > 0.000001
        ) {
            unset($_SESSION['lfm_cart'][$key]);
            continue;
        }

        $ids[] = $packId;
    }

    $ids = array_values(array_unique($ids));

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("
            SELECT
                pv.id AS pack_id,
                pv.quantity AS pack_quantity,
                p.stock
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN units u ON u.id = p.unit_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE pv.id IN ($placeholders)
              AND pv.status = 'active'
              AND p.status = 'active'
              AND u.status = 'active'
              AND c.status = 'active'
        ");
        $stmt->execute($ids);

        $valid = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['pack_id'];
            $quantity = (float) ($_SESSION['lfm_cart'][$key]['quantity'] ?? 0);
            $packQuantity = (float) $row['pack_quantity'];
            $stock = (float) $row['stock'];

            if ($packQuantity <= 0) {
                unset($_SESSION['lfm_cart'][$key]);
                continue;
            }

            $maxPacks = (int) floor(($stock + 0.000001) / $packQuantity);

            if ($quantity < 1 || $quantity > $maxPacks) {
                unset($_SESSION['lfm_cart'][$key]);
                continue;
            }

            $valid[$key] = true;
            $cartCount += (int) round($quantity);
        }

        foreach (array_keys($_SESSION['lfm_cart']) as $key) {
            if (!isset($valid[(string) $key])) {
                unset($_SESSION['lfm_cart'][$key]);
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fresh vegetables, fruits and dairy products from Laxmi Fresh Mart.">
    <title><?= e((string) $pageTitle) ?> | <?= e((string) SITE_NAME) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/images/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/store.css">
</head>

<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>index.php" class="brand">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="Laxmi Fresh Mart">
            <span>
                <strong>Laxmi Fresh Mart</strong>
                <small>Freshness you can trust</small>
            </span>
        </a>

        <nav class="desktop-nav" aria-label="Main navigation">
            <a class="<?= $activePage === 'home' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">Home</a>
            <a class="<?= $activePage === 'vegetables' ? 'active' : '' ?>" href="<?= BASE_URL ?>category.php?slug=vegetables">Vegetables</a>
            <a class="<?= $activePage === 'fruits' ? 'active' : '' ?>" href="<?= BASE_URL ?>category.php?slug=fruits">Fruits</a>
            <a class="<?= $activePage === 'dairy-products' ? 'active' : '' ?>" href="<?= BASE_URL ?>category.php?slug=dairy-products">Dairy</a>
        </nav>

        <div class="header-actions">
            <a class="icon-link account-link" href="<?= BASE_URL ?>login.php" aria-label="Account">
                <span class="icon">◯</span>
                <span class="action-text">Account</span>
            </a>

            <a class="cart-link" href="<?= BASE_URL ?>cart.php">
                <span class="cart-icon">🛒</span>
                <span class="action-text">Cart</span>
                <span class="cart-count" id="cartCount"><?= (int) $cartCount ?></span>
            </a>

            <button class="mobile-menu-button" type="button" id="mobileMenuButton" aria-label="Open menu">☰</button>
        </div>
    </div>

    <div class="mobile-nav" id="mobileNav">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <a href="<?= BASE_URL ?>category.php?slug=vegetables">Vegetables</a>
        <a href="<?= BASE_URL ?>category.php?slug=fruits">Fruits</a>
        <a href="<?= BASE_URL ?>category.php?slug=dairy-products">Dairy Products</a>
        <a href="<?= BASE_URL ?>login.php">My Account</a>
        <a href="<?= BASE_URL ?>cart.php">Cart (<?= (int) $cartCount ?>)</a>
    </div>
</header>

<main>
