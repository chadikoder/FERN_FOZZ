<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

$pageTitle = 'Order Received - Furn Fawz';
$lastOrder = $_SESSION['last_order'] ?? null;
$whatsappUrl = $lastOrder['whatsapp_url'] ?? '';
$autoOpenWhatsapp = $lastOrder && $whatsappUrl !== '' && empty($lastOrder['auto_opened']);

if ($lastOrder && $autoOpenWhatsapp) {
    $_SESSION['last_order']['auto_opened'] = true;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section class="checkout-page">
    <div class="container">
        <?php if (!$lastOrder): ?>
            <div class="empty-state">
                <h2>No recent order</h2>
                <p>Place an order from the menu to send it through WhatsApp.</p>
                <a class="button button-primary" href="<?php echo esc(url('index.php')); ?>">Browse menu</a>
            </div>
        <?php else: ?>
            <div class="success-card"<?php echo $autoOpenWhatsapp ? ' data-whatsapp-url="' . esc($whatsappUrl) . '"' : ''; ?>>
                <p class="eyebrow">Order received</p>
                <h1>Order #<?php echo esc($lastOrder['id']); ?> is saved</h1>
                <p class="page-copy">Total: AED <?php echo esc(format_money($lastOrder['total'])); ?></p>
                <p>Your order has been saved. Open WhatsApp to send it to Furn Fawz.</p>
                <div class="hero-actions">
                    <?php if (!empty($lastOrder['whatsapp_url'])): ?>
                        <a class="button button-primary" href="<?php echo esc($whatsappUrl); ?>" target="_blank" rel="noopener">Open WhatsApp</a>
                    <?php endif; ?>
                    <a class="button button-secondary" href="<?php echo esc(url('index.php')); ?>">Back to menu</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
