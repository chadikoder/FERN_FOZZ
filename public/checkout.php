<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

$pageTitle = 'Checkout - Furn Fawz';
$orderActionUrl = '../api/order_create.php';
$checkoutError = pull_flash('checkout_error');
$old = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);
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
        $cartLoadError = 'Checkout is temporarily unavailable.';
    }
} elseif (cart_item_count() > 0) {
    $cartLoadError = 'Checkout is unavailable until the menu database is connected.';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="checkout-page">
    <div class="container">
        <div class="cart-page-header">
            <div>
                <p class="eyebrow">Final step</p>
                <h1>Checkout</h1>
                <p class="page-copy">Add your delivery details and send the order to WhatsApp.</p>
            </div>
            <a class="button button-secondary" href="<?php echo esc(url('cart.php')); ?>">Back to cart</a>
        </div>

        <?php if ($checkoutError): ?>
            <div class="alert alert-error"><?php echo esc($checkoutError); ?></div>
        <?php endif; ?>

        <?php if ($cartLoadError): ?>
            <div class="alert alert-error">
                <h2>Checkout unavailable</h2>
                <p><?php echo esc($cartLoadError); ?></p>
            </div>
        <?php elseif (!$cartData['items']): ?>
            <div class="empty-state">
                <h2>Your cart is empty</h2>
                <p>Add items from the menu before checkout.</p>
                <a class="button button-primary" href="<?php echo esc(url('index.php')); ?>">Browse menu</a>
            </div>
        <?php else: ?>
            <div class="checkout-grid">
                <form class="checkout-form-panel" method="post" action="<?php echo esc($orderActionUrl); ?>">
                    <?php echo csrf_field(); ?>
                    <label>
                        Full name
                        <input type="text" name="customer_name" value="<?php echo esc($old['customer_name'] ?? ''); ?>" maxlength="120" autocomplete="name" required>
                    </label>
                    <label>
                        Phone number
                        <input type="tel" name="customer_phone" value="<?php echo esc($old['customer_phone'] ?? ''); ?>" maxlength="40" autocomplete="tel" required>
                    </label>
                    <label>
                        Delivery address
                        <input type="text" name="customer_address" value="<?php echo esc($old['customer_address'] ?? ''); ?>" maxlength="255" autocomplete="street-address" required>
                    </label>
                    <label>
                        Note
                        <textarea name="customer_note" maxlength="1000" placeholder="Optional"><?php echo esc($old['customer_note'] ?? ''); ?></textarea>
                    </label>
                    <button type="submit" class="button button-primary">Place order</button>
                </form>

                <aside class="checkout-summary" aria-label="Order summary">
                    <h2>Order Summary</h2>
                    <div class="checkout-items">
                        <?php foreach ($cartData['items'] as $item): ?>
                            <div class="checkout-item">
                                <span><?php echo esc($item['quantity']); ?> x <?php echo esc($item['name']); ?></span>
                                <strong>AED <?php echo esc(format_money($item['line_total'])); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row">
                        <span>Items</span>
                        <span><?php echo esc($cartData['count']); ?></span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>AED <?php echo esc(format_money($cartData['total'])); ?></span>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
