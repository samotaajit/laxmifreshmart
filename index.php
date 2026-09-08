<?php
declare(strict_types=1);
ob_start();

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Fresh Vegetables, Fruits & Dairy';
$activePage = 'home';

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        slug,
        description,
        image
    FROM categories
    WHERE status = 'active'
    ORDER BY display_order ASC, name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Featured Products
|--------------------------------------------------------------------------
|
| A product may have more than one selling option / pack in
| product_variants. The homepage must therefore show ONE card per product,
| not one card per variant.
|
| The displayed price is the lowest currently available selling price.
| Customers can open the product page to choose the appropriate selling
| option and quantity.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.slug,
        p.image,
        c.name AS category_name,
        c.slug AS category_slug,
        MIN(
            CASE
                WHEN pv.status = 'active'
                 AND pv.quantity > 0
                 AND p.stock >= pv.quantity
                THEN p.price * pv.quantity
            END
        ) AS starting_price
    FROM products p
    INNER JOIN categories c
        ON c.id = p.category_id
       AND c.status = 'active'
    LEFT JOIN product_variants pv
        ON pv.product_id = p.id
    WHERE p.status = 'active'
      AND p.featured = 1
    GROUP BY
        p.id,
        p.name,
        p.slug,
        p.image,
        c.name,
        c.slug
    HAVING starting_price IS NOT NULL
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 8
");

$featured = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatQuantity(float $value): string
{
    if ($value == 0.0) {
        return '0';
    }

    return rtrim(
        rtrim(number_format($value, 3, '.', ''), '0'),
        '.'
    );
}


function imageUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    return BASE_URL . ltrim($path, '/');
}

?>

<?php require 'includes/store-header.php'; ?>

<section class="hero">

    <div class="container hero-grid">

        <div>

            <div class="eyebrow">
                Laxmi Fresh Mart
            </div>

            <h1>
                Freshness for every meal, right at your doorstep.
            </h1>

            <p>
                Shop fresh vegetables, seasonal fruits and quality dairy
                products. Easy ordering, convenient pack sizes and local delivery.
            </p>

            <div class="hero-actions">

                <a
                    href="<?= BASE_URL ?>shop.php"
                    class="btn-primary"
                >
                    Shop Fresh Produce
                </a>

                <a
                    href="#categories"
                    class="btn-secondary"
                >
                    Explore Categories
                </a>

            </div>

        </div>


        <div class="hero-visual">

            <div class="hero-card">

                <img
                    src="<?= BASE_URL ?>assets/images/logo.png"
                    alt="Laxmi Fresh Mart"
                >

            </div>

        </div>

    </div>

</section>


<section
    class="section"
    id="categories"
>

    <div class="container">

        <div class="section-heading">

            <div>

                <h2>
                    Shop by Category
                </h2>

                <p>
                    Everything fresh, organised simply.
                </p>

            </div>

        </div>


        <?php if ($categories): ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <a
                        class="category-card <?= empty($category['image']) ? 'no-image' : '' ?>"
                        href="<?= BASE_URL ?>category.php?slug=<?= urlencode($category['slug']) ?>"
                    >

                        <?php if (!empty($category['image'])): ?>

                            <img
                                src="<?= imageUrl($category['image']) ?>"
                                alt="<?= e($category['name']) ?>"
                                loading="lazy"
                            >

                        <?php endif; ?>

                        <div class="category-card-content">

                            <h3>
                                <?= e($category['name']) ?>
                            </h3>

                            <span>
                                Browse products →
                            </span>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <h2>Categories are coming soon</h2>
                <p>Our fresh product categories will appear here.</p>
            </div>

        <?php endif; ?>

    </div>

</section>


<section class="section section-soft">

    <div class="container">

        <div class="section-heading">

            <div>

                <h2>
                    Featured Fresh Picks
                </h2>

                <p>
                    Our selected products available right now.
                </p>

            </div>

            <a
                class="view-all"
                href="<?= BASE_URL ?>shop.php"
            >
                View all products →
            </a>

        </div>


        <?php if ($featured): ?>

            <div class="product-grid">

                <?php foreach ($featured as $product): ?>

                    <article class="product-card">

                        <a
                            class="product-image"
                            href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product['slug']) ?>"
                        >

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="<?= imageUrl($product['image']) ?>"
                                    alt="<?= e($product['name']) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="product-placeholder">
                                    ✦
                                </div>

                            <?php endif; ?>

                        </a>


                        <div class="product-body">

                            <div class="product-category">
                                <?= e($product['category_name']) ?>
                            </div>


                            <a
                                class="product-name"
                                href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product['slug']) ?>"
                            >
                                <?= e($product['name']) ?>
                            </a>


                            <div>

                                <span class="product-price">
                                    From ₹<?= number_format((float) $product['starting_price'], 2) ?>
                                </span>

                            </div>


                            <div class="product-footer">

                                <span class="product-unit">
                                    Select pack & quantity
                                </span>

                                <a
                                    class="add-btn"
                                    href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product['slug']) ?>"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <h2>
                    Fresh picks are coming soon
                </h2>

                <p>
                    Products marked as featured by the admin will appear here.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<section class="section">

    <div class="container">

        <div class="section-heading">

            <div>

                <h2>
                    Why Laxmi Fresh Mart?
                </h2>

                <p>
                    A simpler way to buy everyday fresh essentials.
                </p>

            </div>

        </div>


        <div class="feature-strip">

            <div class="feature">
                <div class="feature-icon">✦</div>
                <h3>Fresh Products</h3>
                <p>Carefully selected everyday essentials.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">◷</div>
                <h3>Convenient Packs</h3>
                <p>Choose the pack size that suits you.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">⌂</div>
                <h3>Local Delivery</h3>
                <p>Delivery is available only in approved areas.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">₹</div>
                <h3>Cash on Delivery</h3>
                <p>Pay conveniently when your order arrives.</p>
            </div>

        </div>

    </div>

</section>

<?php require 'includes/store-footer.php'; ?>
