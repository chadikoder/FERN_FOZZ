<?php
function esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url($path)
{
    global $config;

    if (preg_match('/^(https?:)?\/\//', $path)) {
        return $path;
    }

    $baseUrl = rtrim($config['base_url'] ?? '', '/');
    $path = ltrim($path, '/');

    return $baseUrl === '' ? $path : $baseUrl . '/' . $path;
}

function asset_url($path)
{
    return url($path);
}

function format_money($amount)
{
    return number_format((float) $amount, 2);
}

function redirect_to($path)
{
    header('Location: ' . $path);
    exit;
}

function slugify($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value, '-');

    return $value !== '' ? $value : 'item';
}

function app_setting($pdo, $name, $fallback = '')
{
    if (!($pdo instanceof PDO)) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $value = $stmt->fetchColumn();

        return $value !== false && trim((string) $value) !== '' ? $value : $fallback;
    } catch (PDOException $exception) {
        return $fallback;
    }
}

function require_admin()
{
    if (empty($_SESSION['admin_id'])) {
        $_SESSION['admin_error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

function verify_csrf_token($token)
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function cart_item_count()
{
    return array_sum(normalize_cart());
}

function normalize_cart()
{
    $cart = $_SESSION['cart'] ?? [];
    $normalized = [];

    if (!is_array($cart)) {
        $_SESSION['cart'] = [];

        return [];
    }

    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = min(99, max(0, (int) $quantity));

        if ($productId > 0 && $quantity > 0) {
            $normalized[(string) $productId] = $quantity;
        }
    }

    $_SESSION['cart'] = $normalized;

    return $normalized;
}

function cart_quantity($productId)
{
    $cart = normalize_cart();
    $productId = (string) (int) $productId;

    return $cart[$productId] ?? 0;
}

function add_cart_item($productId, $quantity = 1)
{
    $cart = normalize_cart();
    $productId = (string) (int) $productId;
    $quantity = min(99, max(1, (int) $quantity));

    if ((int) $productId <= 0) {
        return;
    }

    $cart[$productId] = min(99, ($cart[$productId] ?? 0) + $quantity);
    $_SESSION['cart'] = $cart;
}

function update_cart_item($productId, $quantity)
{
    $cart = normalize_cart();
    $productId = (string) (int) $productId;
    $quantity = min(99, max(0, (int) $quantity));

    if ((int) $productId <= 0) {
        return;
    }

    if ($quantity === 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }

    $_SESSION['cart'] = $cart;
}

function remove_cart_item($productId)
{
    update_cart_item($productId, 0);
}

function clear_cart()
{
    $_SESSION['cart'] = [];
}

function cart_details($pdo)
{
    $cart = normalize_cart();

    if (!$cart) {
        return [
            'items' => [],
            'total' => 0,
            'count' => 0,
        ];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.price, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.id IN ($placeholders)
            AND p.is_available = 1
            AND c.is_active = 1"
    );
    $stmt->execute($ids);

    $products = [];
    foreach ($stmt->fetchAll() as $product) {
        $products[(string) $product['id']] = $product;
    }

    $items = [];
    $total = 0;

    foreach ($cart as $productId => $quantity) {
        if (empty($products[$productId])) {
            remove_cart_item($productId);
            continue;
        }

        $product = $products[$productId];
        $lineTotal = (float) $product['price'] * $quantity;
        $total += $lineTotal;
        $items[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'category_name' => $product['category_name'],
            'unit_price' => (float) $product['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    return [
        'items' => $items,
        'total' => $total,
        'count' => cart_item_count(),
    ];
}

function flash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function pull_flash($type)
{
    $message = $_SESSION['flash'][$type] ?? '';
    unset($_SESSION['flash'][$type]);

    return $message;
}

function normalize_whatsapp_phone($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    return $digits;
}

function build_order_whatsapp_message($orderId, array $customer, array $items, $total)
{
    $lines = [
        'New Furn Fawz order #' . $orderId,
        '',
        'Customer: ' . $customer['name'],
        'Phone: ' . $customer['phone'],
        'Address: ' . $customer['address'],
    ];

    if (!empty($customer['note'])) {
        $lines[] = 'Note: ' . $customer['note'];
    }

    $lines[] = '';
    $lines[] = 'Items:';

    foreach ($items as $item) {
        $lines[] = '- ' . $item['quantity'] . ' x ' . $item['name'] . ' - AED ' . format_money($item['line_total']);
    }

    $lines[] = '';
    $lines[] = 'Total: AED ' . format_money($total);

    return implode("\n", $lines);
}

function whatsapp_url($phone, $message)
{
    $phone = normalize_whatsapp_phone($phone);

    if ($phone === '') {
        return '';
    }

    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}
