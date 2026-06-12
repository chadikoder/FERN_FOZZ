<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

function order_wants_json()
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return stripos($accept, 'application/json') !== false
        || strtolower($requestedWith) === 'xmlhttprequest';
}

function order_fail($message, $old = [], $statusCode = 400)
{
    if (order_wants_json()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode(['message' => $message]);
        exit;
    }

    $_SESSION['checkout_old'] = $old;
    flash('checkout_error', $message);
    header('Location: ../public/checkout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    order_fail('Invalid order request.', [], 405);
}

$old = [
    'customer_name' => substr(trim($_POST['customer_name'] ?? ''), 0, 120),
    'customer_phone' => substr(trim($_POST['customer_phone'] ?? ''), 0, 40),
    'customer_address' => substr(trim($_POST['customer_address'] ?? ''), 0, 255),
    'customer_note' => substr(trim($_POST['customer_note'] ?? ''), 0, 1000),
];

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    order_fail('Your session expired. Please try again.', $old, 419);
}

if (!($pdo instanceof PDO)) {
    order_fail('Checkout is unavailable until the menu database is connected.', $old, 503);
}

if ($old['customer_name'] === '' || $old['customer_phone'] === '' || $old['customer_address'] === '') {
    order_fail('Please fill in your name, phone, and delivery address.', $old);
}

if (!preg_match('/^[0-9+()\-\s]{6,40}$/', $old['customer_phone'])) {
    order_fail('Please enter a valid phone number.', $old);
}

try {
    $cartData = cart_details($pdo);

    if (!$cartData['items']) {
        order_fail('Your cart is empty.', $old);
    }

    $ownerPhone = app_setting($pdo, 'owner_phone', $config['owner_phone'] ?? '');

    if (normalize_whatsapp_phone($ownerPhone) === '') {
        order_fail('WhatsApp ordering is not configured yet.', $old, 500);
    }

    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (customer_name, customer_phone, customer_address, customer_note, total_price, status, whatsapp_message)
        VALUES (:customer_name, :customer_phone, :customer_address, :customer_note, :total_price, :status, :whatsapp_message)'
    );

    $orderStmt->execute([
        ':customer_name' => $old['customer_name'],
        ':customer_phone' => $old['customer_phone'],
        ':customer_address' => $old['customer_address'],
        ':customer_note' => $old['customer_note'] !== '' ? $old['customer_note'] : null,
        ':total_price' => $cartData['total'],
        ':status' => 'whatsapp_pending',
        ':whatsapp_message' => '',
    ]);

    $orderId = (int) $pdo->lastInsertId();
    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
        VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :line_total)'
    );

    foreach ($cartData['items'] as $item) {
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['id'],
            ':product_name' => $item['name'],
            ':unit_price' => $item['unit_price'],
            ':quantity' => $item['quantity'],
            ':line_total' => $item['line_total'],
        ]);
    }

    $message = build_order_whatsapp_message($orderId, [
        'name' => $old['customer_name'],
        'phone' => $old['customer_phone'],
        'address' => $old['customer_address'],
        'note' => $old['customer_note'],
    ], $cartData['items'], $cartData['total']);
    $whatsappUrl = whatsapp_url($ownerPhone, $message);

    $updateStmt = $pdo->prepare('UPDATE orders SET whatsapp_message = :message WHERE id = :id');
    $updateStmt->execute([
        ':message' => $message,
        ':id' => $orderId,
    ]);

    $pdo->commit();
    clear_cart();

    $_SESSION['last_order'] = [
        'id' => $orderId,
        'total' => $cartData['total'],
        'whatsapp_url' => $whatsappUrl,
    ];

    if (order_wants_json()) {
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Order saved.',
            'order_id' => $orderId,
            'whatsapp_url' => $whatsappUrl,
        ]);
        exit;
    }

    header('Location: ../public/order-success.php');
    exit;
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    order_fail('Order could not be saved right now.', $old, 500);
}
