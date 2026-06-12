<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$stats = [
    'orders_today' => 0,
    'pending_orders' => 0,
    'available_products' => 0,
    'revenue_today' => 0,
];
$recentOrders = [];
$adminError = '';

if ($pdo instanceof PDO) {
    try {
        $stats['orders_today'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $stats['pending_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'whatsapp_pending'")->fetchColumn();
        $stats['available_products'] = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_available = 1')->fetchColumn();
        $stats['revenue_today'] = (float) $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

        $stmt = $pdo->query('SELECT id, customer_name, customer_phone, total_price, status, created_at FROM orders ORDER BY created_at DESC LIMIT 8');
        $recentOrders = $stmt->fetchAll();
    } catch (PDOException $exception) {
        $adminError = 'Dashboard data is temporarily unavailable.';
    }
} else {
    $adminError = 'Dashboard data is unavailable until the database is connected.';
}

$pageTitle = 'Admin Dashboard - Furn Fawz';
include __DIR__ . '/../includes/admin_header.php';
?>
<section class="panel-header">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Dashboard</h1>
        <p class="page-copy">Track today&apos;s orders and menu activity.</p>
    </div>
</section>

<?php if ($adminError): ?>
    <div class="alert alert-error"><?php echo esc($adminError); ?></div>
<?php endif; ?>

<section class="analytics-cards" aria-label="Dashboard summary">
    <article class="status-card">
        <p class="eyebrow">Today</p>
        <h2><?php echo esc($stats['orders_today']); ?></h2>
        <p class="page-copy">Orders received</p>
    </article>
    <article class="status-card">
        <p class="eyebrow">Pending</p>
        <h2><?php echo esc($stats['pending_orders']); ?></h2>
        <p class="page-copy">WhatsApp follow-ups</p>
    </article>
    <article class="status-card">
        <p class="eyebrow">Menu</p>
        <h2><?php echo esc($stats['available_products']); ?></h2>
        <p class="page-copy">Available products</p>
    </article>
    <article class="status-card">
        <p class="eyebrow">Sales</p>
        <h2>AED <?php echo esc(format_money($stats['revenue_today'])); ?></h2>
        <p class="page-copy">Revenue today</p>
    </article>
</section>

<section class="recent-orders">
    <div class="panel-header">
        <div>
            <h2>Recent Orders</h2>
            <p class="page-copy">Latest customer orders.</p>
        </div>
        <a class="button button-secondary" href="orders.php">View all</a>
    </div>
    <?php if (!$recentOrders): ?>
        <p class="page-copy">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo esc($order['id']); ?></td>
                            <td><?php echo esc($order['customer_name']); ?></td>
                            <td><?php echo esc($order['customer_phone']); ?></td>
                            <td>AED <?php echo esc(format_money($order['total_price'])); ?></td>
                            <td><span class="status-pill"><?php echo esc(str_replace('_', ' ', $order['status'])); ?></span></td>
                            <td><?php echo esc($order['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
