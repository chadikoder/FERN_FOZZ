<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

function cart_action_wants_json()
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return stripos($accept, 'application/json') !== false
        || strtolower($requestedWith) === 'xmlhttprequest';
}

function safe_redirect_path($redirect, $fallback)
{
    $redirect = str_replace(["\r", "\n"], '', trim((string) $redirect));

    if ($redirect === '' || preg_match('/^(https?:)?\/\//i', $redirect) || preg_match('/^[a-z][a-z0-9+.-]*:/i', $redirect)) {
        return $fallback;
    }

    return $redirect;
}

function cart_action_response($payload, $statusCode = 200)
{
    $payload['cart_count'] = cart_item_count();

    if (cart_action_wants_json()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }

    $type = $statusCode >= 400 ? 'cart_error' : 'cart_success';
    flash($type, $payload['message'] ?? 'Cart updated.');
    header('Location: ' . safe_redirect_path($_POST['redirect'] ?? '', '../public/cart.php'));
    exit;
}

function require_available_product($pdo, $productId)
{
    if (!($pdo instanceof PDO)) {
        cart_action_response(['message' => 'Cart is unavailable until the menu database is connected.'], 503);
    }

    $stmt = $pdo->prepare(
        'SELECT p.id
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id AND p.is_available = 1 AND c.is_active = 1
        LIMIT 1'
    );
    $stmt->execute([':id' => $productId]);

    if (!$stmt->fetch()) {
        cart_action_response(['message' => 'This item is no longer available.'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cart_action_response(['message' => 'Invalid cart request.'], 405);
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    cart_action_response(['message' => 'Your session expired. Please try again.'], 419);
}

$action = trim($_POST['action'] ?? '');
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

try {
    switch ($action) {
        case 'add':
            require_available_product($pdo, $productId);
            add_cart_item($productId, $quantity);
            cart_action_response(['message' => 'Added to cart.']);
            break;

        case 'update':
            require_available_product($pdo, $productId);
            update_cart_item($productId, $quantity);
            cart_action_response(['message' => 'Cart updated.']);
            break;

        case 'remove':
            remove_cart_item($productId);
            cart_action_response(['message' => 'Item removed from cart.']);
            break;

        case 'clear':
            clear_cart();
            cart_action_response(['message' => 'Cart cleared.']);
            break;

        default:
            cart_action_response(['message' => 'Unknown cart action.'], 400);
    }
} catch (PDOException $exception) {
    cart_action_response(['message' => 'Cart is unavailable right now.'], 500);
}
