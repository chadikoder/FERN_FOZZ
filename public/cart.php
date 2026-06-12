<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

$pageTitle = 'Cart - Furn Fawz';
$cartActionUrl = '../api/cart_action.php';
$cartReturnUrl = $_SERVER['REQUEST_URI'] ?? 'cart.php';
$cartSuccess = pull_flash('cart_success');
$cartError = pull_flash('cart_error');
$cartData = [
    'items' => [],
    'total' => 0,
    'count' => 0,
];
$cartLoadError = '';

if ($pdo instanceof PDO) {
    try {
        $cartData = cart_details($pdo);
    } catch (PDOException $exception) {
        $cartLoadError = 'Cart is temporarily unavailable.';
    }
} elseif (cart_item_count() > 0) {
    $cartLoadError = 'Cart is unavailable until the menu database is connected.';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="cart-page">
    <div class="container">
        <div class="cart-page-header">
            <div>
                <p class="eyebrow">Your order</p>
                <h1>Cart</h1>
                <p class="page-copy">Review quantities before moving to checkout.</p>
            </div>
            <a class="button button-secondary" href="<?php echo esc(url('index.php')); ?>">Keep browsing</a>
        </div>

        <div class="cart-feedback<?php echo (!$cartSuccess && !$cartError) ? ' is-hidden' : ''; ?>" data-cart-feedback aria-live="polite">
            <?php if ($cartSuccess): ?>
                <div class="alert alert-success"><?php echo esc($cartSuccess); ?></div>
            <?php elseif ($cartError): ?>
                <div class="alert alert-error"><?php echo esc($cartError); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($cartLoadError): ?>
            <div class="alert alert-error">
                <h2>Cart unavailable</h2>
                <p><?php echo esc($cartLoadError); ?></p>
            </div>
        <?php elseif (!$cartData['items']): ?>
            <div class="empty-state">
                <h2>Your cart is empty</h2>
                <p>Add bakery items from the menu to start an order.</p>
                <a class="button button-primary" href="<?php echo esc(url('index.php')); ?>">Browse menu</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items" aria-label="Cart items">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <article class="cart-item">
                            <div class="cart-item-info">
                                <p class="product-category"><?php echo esc($item['category_name']); ?></p>
                                <h2 class="cart-item-title"><?php echo esc($item['name']); ?></h2>
                                <p class="cart-item-price">AED <?php echo esc(format_money($item['unit_price'])); ?> each</p>
                            </div>
                            <div class="cart-item-controls">
                                <form class="cart-update-form" method="post" action="<?php echo esc($cartActionUrl); ?>" data-cart-form data-cart-refresh>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo esc($item['id']); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo esc($cartReturnUrl); ?>">
                                    <label>
                                        Qty
                                        <input class="quantity-input" type="number" name="quantity" min="0" max="99" value="<?php echo esc($item['quantity']); ?>" inputmode="numeric">
                                    </label>
                                    <button type="submit" class="button button-secondary button-small">Update</button>
                                </form>
                                <p class="cart-item-total">AED <?php echo esc(format_money($item['line_total'])); ?></p>
                                <form method="post" action="<?php echo esc($cartActionUrl); ?>" data-cart-form data-cart-refresh>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo esc($item['id']); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo esc($cartReturnUrl); ?>">
                                    <button type="submit" class="button button-link">Remove</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary" aria-label="Cart summary">
                    <h2>Summary</h2>
                    <div class="summary-row">
                        <span>Items</span>
                        <span><?php echo esc($cartData['count']); ?></span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>AED <?php echo esc(format_money($cartData['total'])); ?></span>
                    </div>
                    <a class="button button-primary" href="<?php echo esc(url('checkout.php')); ?>">Checkout</a>
                    <form method="post" action="<?php echo esc($cartActionUrl); ?>" data-cart-form data-cart-refresh>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="redirect" value="<?php echo esc($cartReturnUrl); ?>">
                        <button type="submit" class="button button-secondary">Clear cart</button>
                    </form>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
