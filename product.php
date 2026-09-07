<?php

declare(strict_types=1);

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$slug = trim($_GET['slug'] ?? '');


/*
|--------------------------------------------------------------------------
| Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.slug,
        p.description,
        p.image,
        p.price,
        p.stock,
        p.low_stock_threshold,

        c.name AS category_name,
        c.slug AS category_slug,

        u.name AS unit_name,
        u.short_name AS unit_short_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN units u
        ON u.id = p.unit_id

    WHERE p.slug = :slug
      AND p.status = 'active'
      AND c.status = 'active'

    LIMIT 1
");

$stmt->execute([
    ':slug' => $slug
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Product Not Found
|--------------------------------------------------------------------------
*/

if (!$product) {

    http_response_code(404);

    $pageTitle = 'Product Not Found';
    $activePage = '';

    require 'includes/store-header.php';

    echo '
        <section class="section">
            <div class="container empty-state">

                <h2>Product not found</h2>

                <p>
                    This product is unavailable or has been removed.
                </p>

                <a
                    class="btn-primary"
                    href="' . BASE_URL . 'shop.php"
                >
                    Back to Shop
                </a>

            </div>
        </section>
    ';

    require 'includes/store-footer.php';

    exit;
}


/*
|--------------------------------------------------------------------------
| Packs
|--------------------------------------------------------------------------
|
| product_variants are used ONLY as customer-facing pack definitions.
|
| Example:
|
| 0.25 Kg
| 0.50 Kg
| 1 Kg
| 2 Kg
|
| Stock remains on products.stock.
| Price remains on products.price as price per base unit.
|
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        quantity,
        status

    FROM product_variants

    WHERE product_id = :product_id
      AND status = 'active'

    ORDER BY quantity ASC, id ASC
");

$stmt->execute([
    ':product_id' => $product['id']
]);

$packs = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Prepare Available Packs
|--------------------------------------------------------------------------
*/

function productQty(float $value): string
{
    return rtrim(
        rtrim(
            number_format($value, 3, '.', ''),
            '0'
        ),
        '.'
    );
}


$availablePacks = [];

$basePrice = (float) $product['price'];
$stock = (float) $product['stock'];


foreach ($packs as $pack) {

    $packQuantity = (float) $pack['quantity'];

    if ($packQuantity <= 0) {
        continue;
    }


    /*
     * Shared product stock.
     *
     * Example:
     * Stock = 10 Kg
     * Pack = 0.25 Kg
     *
     * Maximum packs = 40
     */

    $maxPacks = (int) floor(
        ($stock + 0.000001) / $packQuantity
    );


    if ($maxPacks <= 0) {
        continue;
    }


    /*
     * Product price is per base unit.
     *
     * Example:
     *
     * Base price = ₹40 / Kg
     * Pack       = 0.25 Kg
     *
     * Pack price = ₹10
     */

    $packPrice = round(
        $basePrice * $packQuantity,
        2
    );


    $availablePacks[] = [

        'id' => (int) $pack['id'],

        'quantity' => $packQuantity,

        'max_packs' => $maxPacks,

        'pack_price' => $packPrice

    ];
}


$pageTitle = $product['name'];

$activePage = $product['category_slug'];


function productImage(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    return BASE_URL . ltrim($path, '/');
}

?>


<?php require 'includes/store-header.php'; ?>


<section class="product-detail">

    <div class="container">

        <div class="detail-grid">


            <!-- PRODUCT IMAGE -->

            <div class="detail-image">

                <?php if (!empty($product['image'])): ?>

                <img src="<?= productImage($product['image']) ?>" alt="<?= e($product['name']) ?>">

                <?php else: ?>

                <div class="product-placeholder">
                    ✦
                </div>

                <?php endif; ?>

            </div>


            <!-- PRODUCT INFORMATION -->

            <div class="detail-info">


                <!-- CATEGORY -->

                <div class="product-category">

                    <a href="<?= BASE_URL ?>category.php?slug=<?= urlencode($product['category_slug']) ?>">
                        <?= e($product['category_name']) ?>
                    </a>

                </div>


                <!-- NAME -->

                <h1>
                    <?= e($product['name']) ?>
                </h1>


                <!-- DESCRIPTION -->

                <?php if (!empty($product['description'])): ?>

                <p class="detail-description">
                    <?= nl2br(e($product['description'])) ?>
                </p>

                <?php endif; ?>


                <?php if (!$availablePacks): ?>

                <p style="
                            color:#a4473f;
                            font-weight:700;
                        ">
                    Currently out of stock.
                </p>


                <?php else: ?>


                <!-- PACK SECTION -->

                <div class="pack-system-label">
                    Select Packs
                </div>


                <p style="
                            margin:0 0 14px;
                            color:#68736c;
                            font-size:14px;
                        ">
                    Select one or more pack sizes and choose the
                    quantity you need.
                </p>


                <!--
                    |--------------------------------------------------------------------------
                    | MULTIPLE PACK SELECTION
                    |--------------------------------------------------------------------------
                    -->

                <form method="post" action="<?= BASE_URL ?>cart.php" id="addToCartForm">

                    <input type="hidden" name="action" value="add_multiple">


                    <div class="pack-selection-list" id="packSelectionList">


                        <?php foreach ($availablePacks as $index => $pack): ?>

                        <?php

                                $packId = (int) $pack['id'];

                                $packQuantity = (float) $pack['quantity'];

                                $packPrice = (float) $pack['pack_price'];

                                $maxPacks = (int) $pack['max_packs'];

                                ?>

                        <div class="pack-selection-row" data-pack-id="<?= $packId ?>"
                            data-pack-price="<?= $packPrice ?>" data-pack-quantity="<?= $packQuantity ?>"
                            data-max-packs="<?= $maxPacks ?>">


                            <!-- CHECKBOX -->

                            <label class="pack-selection-check">

                                <input type="checkbox" class="pack-checkbox" name="packs[<?= $packId ?>][selected]"
                                    value="1" data-pack-id="<?= $packId ?>">

                            </label>


                            <!-- PACK INFORMATION -->

                            <div class="pack-selection-info">

                                <strong>

                                    <?= e(
                                                productQty(
                                                    $packQuantity
                                                )
                                            ) ?>

                                    <?= e(
                                                $product['unit_short_name']
                                            ) ?>

                                </strong>

                                <span>

                                    ₹<?= number_format(
                                                $packPrice,
                                                2
                                            ) ?>

                                    / pack

                                </span>

                            </div>


                            <!-- QUANTITY -->

                            <div class="pack-selection-quantity">

                                <button type="button" class="pack-qty-minus" data-pack-id="<?= $packId ?>" disabled>
                                    −
                                </button>


                                <input type="number" class="pack-qty-input" name="packs[<?= $packId ?>][quantity]"
                                    value="1" min="1" max="<?= $maxPacks ?>" step="1" data-pack-id="<?= $packId ?>"
                                    disabled>


                                <button type="button" class="pack-qty-plus" data-pack-id="<?= $packId ?>" disabled>
                                    +
                                </button>

                            </div>


                            <!-- LINE TOTAL -->

                            <div class="pack-line-total" data-pack-id="<?= $packId ?>">
                                ₹0.00
                            </div>


                        </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- CALCULATION -->

                    <div class="pack-calculation" id="packCalculation">

                        <div class="pack-calculation-title">
                            Your Selection
                        </div>


                        <div class="pack-calculation-items" id="packCalculationItems">

                            <div class="pack-calculation-empty">
                                Select a pack to see the calculation.
                            </div>

                        </div>


                        <div class="pack-calculation-total">

                            <div>
                                <span>Total Quantity</span>
                                <strong id="packFinalQuantity">
                                    0 <?= e($product['unit_short_name']) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Total</span>
                                <strong id="packFinalTotal">
                                    ₹0.00
                                </strong>
                            </div>

                        </div>

                    </div>


                    <!-- ADD TO CART -->

                    <div class="detail-actions">

                        <button class="btn-primary" type="submit" id="addSelectedPacks" disabled>
                            Add Selected Packs to Cart
                        </button>

                    </div>


                </form>


                <!-- BASE PRICE -->

                <div class="pack-subtext">

                    Base price:
                    ₹<?= number_format($basePrice, 2) ?>

                    /
                    <?= e($product['unit_short_name']) ?>

                </div>


                <?php endif; ?>


            </div>

        </div>

    </div>

</section>


<style>
.pack-selection-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 12px;
}

.pack-selection-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 12px;

    padding: 12px 14px;

    border: 1px solid var(--border);
    border-radius: 10px;

    background: #fff;

    transition:
        border-color .15s ease,
        background .15s ease,
        box-shadow .15s ease;
}

.pack-selection-row.selected {
    border-color: #238b25;
    background: #f5fbf4;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
}

.pack-selection-check input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #238b25;
}

.pack-selection-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.pack-selection-info strong {
    font-size: 16px;
    color: #143d26;
}

.pack-selection-info span {
    font-size: 13px;
    color: #6c756f;
}

.pack-selection-quantity {
    display: flex;
    align-items: center;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.pack-selection-quantity button {
    width: 32px;
    height: 32px;

    border: 0;
    background: #238b25;
    color: #fff;

    font-size: 18px;
    font-weight: 700;

    cursor: pointer;
}

.pack-selection-quantity button:disabled {
    background: #d8ddd9;
    cursor: not-allowed;
}

.pack-qty-input {
    width: 45px;
    height: 32px;

    border: 0;

    text-align: center;

    font-size: 14px;
    font-weight: 600;

    outline: none;
}

.pack-qty-input::-webkit-inner-spin-button,
.pack-qty-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.pack-line-total {
    min-width: 82px;

    text-align: right;

    font-weight: 700;
    color: #143d26;
}

.pack-calculation {
    margin-top: 18px;

    padding: 16px;

    border-radius: 10px;

    background: #f6f8f5;
    border: 1px solid var(--border);
}

.pack-calculation-title {
    margin-bottom: 10px;

    font-size: 15px;
    font-weight: 700;

    color: #143d26;
}

.pack-calculation-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pack-calculation-row {
    display: flex;
    justify-content: space-between;
    gap: 15px;

    font-size: 14px;
}

.pack-calculation-row span:first-child {
    color: #68736c;
}

.pack-calculation-row span:last-child {
    font-weight: 600;
    color: #143d26;
}

.pack-calculation-empty {
    color: #7b837e;
    font-size: 13px;
}

.pack-calculation-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;

    margin-top: 14px;
    padding-top: 13px;

    border-top: 1px solid var(--border);
}

.pack-calculation-total > div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.pack-calculation-total > div:last-child {
    text-align: right;
}

.pack-calculation-total span {
    font-size: 13px;
    color: #68736c;
}

.pack-calculation-total strong {
    color: #08752d;
    font-size: 19px;
}

@media (max-width: 600px) {

    .pack-calculation-total {
        flex-direction: column;
        align-items: stretch;
    }

    .pack-calculation-total > div:last-child {
        text-align: left;
    }

}

#addSelectedPacks:disabled {
    opacity: .55;
    cursor: not-allowed;
}


@media (max-width: 600px) {

    .pack-selection-row {
        grid-template-columns: 30px 1fr;
        gap: 8px 10px;
    }

    .pack-selection-quantity {
        grid-column: 2;
        justify-self: start;
    }

    .pack-line-total {
        grid-column: 2;
        text-align: left;
    }

}
</style>


<script>
(function() {

    const rows = [
        ...document.querySelectorAll('.pack-selection-row')
    ];

    const calculationItems =
        document.getElementById('packCalculationItems');

    const finalTotal =
        document.getElementById('packFinalTotal');

    const addButton =
        document.getElementById('addSelectedPacks');


    function formatMoney(value) {

        return Number(value).toLocaleString(
            'en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    function formatQuantity(value) {

        const number = Number(value);

        return number
            .toLocaleString(
                'en-IN', {
                    maximumFractionDigits: 3
                }
            );

    }


    function updateCalculation() {

        let total = 0;

let totalQuantity = 0;

let selectedCount = 0;

let html = '';


        rows.forEach(function(row) {

            const checkbox =
                row.querySelector('.pack-checkbox');

            const quantityInput =
                row.querySelector('.pack-qty-input');

            const lineTotal =
                row.querySelector('.pack-line-total');

            const packQuantity =
                Number(row.dataset.packQuantity);

            const packPrice =
                Number(row.dataset.packPrice);


            let quantity =
                Number(quantityInput.value || 1);


            if (quantity < 1) {
                quantity = 1;
                quantityInput.value = 1;
            }


            const line =
                quantity * packPrice;


            /*
             * Show line total only when selected.
             */

            if (checkbox.checked) {

    selectedCount++;

    row.classList.add('selected');

    quantityInput.disabled = false;

    row.querySelector('.pack-qty-minus').disabled = false;

    row.querySelector('.pack-qty-plus').disabled = false;

    lineTotal.textContent =
        '₹' + formatMoney(line);

    total += line;

    totalQuantity += quantity * packQuantity;

    html += `
        <div class="pack-calculation-row">

            <span>
                ${formatQuantity(quantity)}
                ×
                ${formatQuantity(packQuantity)}
                <?= e($product['unit_short_name']) ?>

                @ ₹${formatMoney(packPrice)}
            </span>

            <span>
                ₹${formatMoney(line)}
            </span>

        </div>
    `;

} else {

                row.classList.remove('selected');

                quantityInput.disabled = true;

                row.querySelector('.pack-qty-minus').disabled = true;

                row.querySelector('.pack-qty-plus').disabled = true;

                lineTotal.textContent = '₹0.00';

            }

        });


        if (!selectedCount) {

    calculationItems.innerHTML = `
        <div class="pack-calculation-empty">
            Select a pack to see the calculation.
        </div>
    `;

    finalTotal.textContent = '₹0.00';

    document.getElementById('packFinalQuantity').textContent =
        '0 <?= e($product['unit_short_name']) ?>';

    addButton.disabled = true;

    return;

}


        calculationItems.innerHTML = html;

        finalTotal.textContent =
            '₹' + formatMoney(total);

        document.getElementById('packFinalQuantity').textContent =
    formatQuantity(totalQuantity) +
    ' <?= e($product['unit_short_name']) ?>';

        addButton.disabled = false;

    }


    /*
    |--------------------------------------------------------------------------
    | Checkbox
    |--------------------------------------------------------------------------
    */

    rows.forEach(function(row) {

        const checkbox =
            row.querySelector('.pack-checkbox');


        checkbox.addEventListener(
            'change',
            updateCalculation
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Quantity Buttons
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.pack-qty-minus')
        .forEach(function(button) {

            button.addEventListener(
                'click',
                function() {

                    const packId =
                        this.dataset.packId;

                    const input =
                        document.querySelector(
                            '.pack-qty-input[data-pack-id="' +
                            packId +
                            '"]'
                        );

                    if (!input) {
                        return;
                    }


                    const current =
                        Number(input.value || 1);

                    input.value =
                        Math.max(
                            1,
                            current - 1
                        );


                    updateCalculation();

                }
            );

        });


    document.querySelectorAll('.pack-qty-plus')
        .forEach(function(button) {

            button.addEventListener(
                'click',
                function() {

                    const packId =
                        this.dataset.packId;

                    const input =
                        document.querySelector(
                            '.pack-qty-input[data-pack-id="' +
                            packId +
                            '"]'
                        );

                    if (!input) {
                        return;
                    }


                    const max =
                        Number(input.max);

                    const current =
                        Number(input.value || 1);


                    input.value =
                        Math.min(
                            max,
                            current + 1
                        );


                    updateCalculation();

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Manual quantity input
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.pack-qty-input')
        .forEach(function(input) {

            input.addEventListener(
                'input',
                function() {

                    let value =
                        Number(this.value || 1);

                    const max =
                        Number(this.max);


                    value =
                        Math.max(
                            1,
                            Math.min(
                                max,
                                Math.floor(value)
                            )
                        );


                    this.value = value;

                    updateCalculation();

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    updateCalculation();

})();
</script>


<?php require 'includes/store-footer.php'; ?>