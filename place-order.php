<?php
declare(strict_types=1);

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/customer-auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'checkout.php');
}

$user = requireApprovedCustomer($pdo);
$userId = (int) $user['id'];

$cart = $_SESSION['lfm_cart'] ?? [];

if (!is_array($cart) || !$cart) {
    setFlash('error', 'Your cart is empty.');
    redirect(BASE_URL . 'cart.php');
}

$addressId = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);

if (!$addressId) {
    setFlash('error', 'Please use your approved delivery address.');
    redirect(BASE_URL . 'checkout.php');
}

$note = trim((string) ($_POST['customer_note'] ?? ''));

if (mb_strlen($note) > 2000) {
    $note = mb_substr($note, 0, 2000);
}

$stmt = $pdo->prepare("
    SELECT
        id,
        address_line1,
        district,
        state,
        pincode,
        landmark
    FROM user_addresses
    WHERE id = :id
      AND user_id = :user_id
      AND approval_status = 'approved'
    LIMIT 1
");
$stmt->execute([
    ':id' => $addressId,
    ':user_id' => $userId,
]);

$address = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$address) {
    setFlash('error', 'That delivery address is not approved.');
    redirect(BASE_URL . 'checkout.php');
}

$packIds = [];

foreach ($cart as $key => $cartItem) {
    $packId = (int) $key;
    $packCount = is_array($cartItem) ? (float) ($cartItem['quantity'] ?? 0) : 0;

    if ($packId <= 0 || $packCount < 1 || abs($packCount - round($packCount)) > 0.000001) {
        setFlash('error', 'Your cart contains an invalid item. Please review your cart.');
        redirect(BASE_URL . 'cart.php');
    }

    $packIds[] = $packId;
}

$packIds = array_values(array_unique($packIds));
$placeholders = implode(',', array_fill(0, count($packIds), '?'));

try {
    $pdo->beginTransaction();

    /*
     * Lock the product rows because stock belongs to the product,
     * not to the individual pack.
     */
    $stmt = $pdo->prepare("
        SELECT
            pv.id AS pack_id,
            pv.product_id,
            pv.quantity AS pack_quantity,
            p.name,
            p.price,
            p.stock,
            u.name AS unit_name,
            u.short_name
        FROM product_variants pv
        INNER JOIN products p ON p.id = pv.product_id
        INNER JOIN units u ON u.id = p.unit_id
        INNER JOIN categories c ON c.id = p.category_id
        WHERE pv.id IN ($placeholders)
          AND pv.status = 'active'
          AND p.status = 'active'
          AND u.status = 'active'
          AND c.status = 'active'
        FOR UPDATE
    ");
    $stmt->execute($packIds);

    $products = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $products[(string) $row['pack_id']] = $row;
    }

    $orderItems = [];
    $subtotal = 0.00;

    foreach ($cart as $key => $cartItem) {
        $packId = (int) $key;
        $lookupKey = (string) $packId;

        if (!isset($products[$lookupKey])) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }

        $product = $products[$lookupKey];
        $packCount = (float) ($cartItem['quantity'] ?? 0);
        $packQuantity = (float) $product['pack_quantity'];
        $stock = (float) $product['stock'];

        if ($packQuantity <= 0) {
            throw new RuntimeException('A pack has an invalid quantity.');
        }

        if ($packCount < 1 || abs($packCount - round($packCount)) > 0.000001) {
            throw new RuntimeException('Invalid quantity for ' . (string) $product['name'] . '.');
        }

        $maxPacks = (int) floor(($stock + 0.000001) / $packQuantity);

        if ($packCount > $maxPacks) {
            throw new RuntimeException(
                (string) $product['name'] . ' does not have enough stock for the selected quantity.'
            );
        }

        $packPrice = round((float) $product['price'] * $packQuantity, 2);
        $lineTotal = round($packCount * $packPrice, 2);

        $orderItems[] = [
            'product_id' => (int) $product['product_id'],
            'pack_id' => $packId,
            'name' => (string) $product['name'],
            'pack_quantity' => $packQuantity,
            'pack_count' => (int) round($packCount),
            'unit_name' => (string) $product['unit_name'],
            'short_name' => (string) $product['short_name'],
            'unit_price' => $packPrice,
            'subtotal' => $lineTotal,
        ];

        $subtotal += $lineTotal;
    }

    if (!$orderItems) {
        throw new RuntimeException('Your cart is empty.');
    }

    $subtotal = round($subtotal, 2);
    $orderNumber = generateOrderNumber();

    /*
     * The order is persisted BEFORE WhatsApp is opened.
     * WhatsApp itself is not treated as the source of truth.
     */
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            address_id,
            order_number,
            subtotal,
            discount,
            delivery_charge,
            total,
            payment_method,
            payment_status,
            order_status,
            order_source,
            customer_note
        )
        VALUES (
            :user_id,
            :address_id,
            :order_number,
            :subtotal,
            0,
            0,
            :total,
            'cod',
            'pending',
            'pending',
            'whatsapp',
            :note
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':address_id' => $addressId,
        ':order_number' => $orderNumber,
        ':subtotal' => $subtotal,
        ':total' => $subtotal,
        ':note' => $note !== '' ? $note : null,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_variant_id,
            product_name,
            quantity,
            pack_quantity,
            pack_count,
            unit_name,
            unit_short_name,
            unit_price,
            subtotal
        )
        VALUES (
            :order_id,
            :product_id,
            :pack_id,
            :product_name,
            :quantity,
            :pack_quantity,
            :pack_count,
            :unit_name,
            :unit_short_name,
            :unit_price,
            :subtotal
        )
    ");

    foreach ($orderItems as $item) {
        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':pack_id' => $item['pack_id'],
            ':product_name' => $item['name'],
            ':quantity' => $item['pack_count'],
            ':pack_quantity' => $item['pack_quantity'],
            ':pack_count' => $item['pack_count'],
            ':unit_name' => $item['unit_name'],
            ':unit_short_name' => $item['short_name'],
            ':unit_price' => $item['unit_price'],
            ':subtotal' => $item['subtotal'],
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (
            order_id,
            status,
            note,
            changed_by
        )
        VALUES (
            :order_id,
            'pending',
            'Order placed through WhatsApp.',
            NULL
        )
    ");
    $stmt->execute([':order_id' => $orderId]);

    $pdo->commit();

    unset($_SESSION['lfm_cart']);
    $_SESSION['lfm_last_order_id'] = $orderId;

    $addressText = formatAddress($address);
    if (trim((string) ($address['landmark'] ?? '')) !== '') {
        $addressText .= ', Landmark: ' . (string) $address['landmark'];
    }

    $messageLines = [
        'Laxmi Fresh Mart - New Order',
        '',
        'Order: ' . $orderNumber,
        'Customer: ' . (string) $user['name'],
        'Mobile: ' . (string) $user['mobile'],
        '',
        'Delivery Address:',
        $addressText,
        '',
        'Items:',
    ];

    foreach ($orderItems as $item) {
        $packText = rtrim(
            rtrim(number_format((float) $item['pack_quantity'], 3, '.', ''), '0'),
            '.'
        );

        $messageLines[] =
            '- ' . $item['name'] .
            ' | ' . $item['pack_count'] . ' x ' .
            $packText . ' ' . $item['short_name'] .
            ' | ₹' . number_format((float) $item['subtotal'], 2);
    }

    $messageLines[] = '';
    $messageLines[] = 'Payment: Cash on Delivery';
    $messageLines[] = 'Total: ₹' . number_format($subtotal, 2);

    if ($note !== '') {
        $messageLines[] = 'Order Note: ' . $note;
    }

    $message = implode("\n", $messageLines);

    $whatsappNumber = preg_replace('/\D+/', '', (string) WHATSAPP_NUMBER);

    if ($whatsappNumber === '' || str_contains($whatsappNumber, 'XXXXXXXX')) {
        /*
         * Order is already safely saved. Do not roll it back because
         * the WhatsApp number is not configured.
         */
        setFlash(
            'success',
            'Order ' . $orderNumber . ' was saved successfully. Please configure the LFM WhatsApp number in config/config.php.'
        );
        redirect(BASE_URL . 'account/order.php?id=' . $orderId);
    }

    $whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($message);

    header('Location: ' . $whatsappUrl);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    setFlash('error', $e->getMessage());
    redirect(BASE_URL . 'checkout.php');
}
