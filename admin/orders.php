<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$allowedStatuses = [
    'whatsapp_pending' => 'WhatsApp pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$adminSuccess = pull_flash('admin_success');
$adminError = pull_flash('admin_error');
$orders = [];
$selectedOrder = null;
$orderItems = [];
$statusFilter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('admin_error', 'Your session expired. Please try again.');
        redirect_to('orders.php');
    }

    if (!($pdo instanceof PDO)) {
        flash('admin_error', 'Database is unavailable.');
        redirect_to('orders.php');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_status') {
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            if (!isset($allowedStatuses[$status])) {
                throw new RuntimeException('Invalid order status.');
            }

            $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $stmt->execute([
                ':status' => $status,
                ':id' => $orderId,
            ]);
            flash('admin_success', 'Order status updated.');
        }
    } catch (Throwable $exception) {
        flash('admin_error', $exception->getMessage() ?: 'Order could not be updated.');
    }

    redirect_to('orders.php');
}

if ($pdo instanceof PDO) {
    try {
        $params = [];
        $sql = 'SELECT o.id, o.customer_name, o.customer_phone, o.customer_address, o.customer_note, o.total_price, o.status, o.whatsapp_message, o.created_at,
                COALESCE(SUM(oi.quantity), 0) AS item_count
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id';

        if ($statusFilter !== '' && isset($allowedStatuses[$statusFilter])) {
            $sql .= ' WHERE o.status = :status';
            $params[':status'] = $statusFilter;
        }

        $sql .= ' GROUP BY o.id, o.customer_name, o.customer_phone, o.customer_address, o.customer_note, o.total_price, o.status, o.whatsapp_message, o.created_at
            ORDER BY o.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        $viewId = (int) ($_GET['view'] ?? 0);
        if ($viewId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $viewId]);
            $selectedOrder = $stmt->fetch() ?: null;

            if ($selectedOrder) {
                $stmt = $pdo->prepare('SELECT product_name, unit_price, quantity, line_total FROM order_items WHERE order_id = :id ORDER BY id ASC');
                $stmt->execute([':id' => $viewId]);
                $orderItems = $stmt->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        $adminError = 'Orders are temporarily unavailable.';
    }
} else {
    $adminError = 'Orders are unavailable until the database is connected.';
}

$pageTitle = 'Manage Orders - Furn Fawz';
include __DIR__ . '/../includes/admin_header.php';
?>
<section class="panel-card">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Operations</p>
            <h1>Orders</h1>
            <p class="page-copy">Review customer details and update fulfillment status.</p>
        </div>
        <form class="filter-form" method="get" action="orders.php">
            <label>
                Status
                <select name="status">
                    <option value="">All orders</option>
                    <?php foreach ($allowedStatuses as $value => $label): ?>
                        <option value="<?php echo esc($value); ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="button button-secondary">Filter</button>
        </form>
    </div>

    <?php if ($adminSuccess): ?>
        <div class="alert alert-success"><?php echo esc($adminSuccess); ?></div>
    <?php endif; ?>
    <?php if ($adminError): ?>
        <div class="alert alert-error"><?php echo esc($adminError); ?></div>
    <?php endif; ?>
</section>

<?php if ($selectedOrder): ?>
    <section class="recent-orders">
        <div class="panel-header">
            <div>
                <h2>Order #<?php echo esc($selectedOrder['id']); ?></h2>
                <p class="page-copy"><?php echo esc($selectedOrder['customer_name']); ?> - AED <?php echo esc(format_money($selectedOrder['total_price'])); ?></p>
            </div>
            <?php $whatsappUrl = whatsapp_url(app_setting($pdo, 'owner_phone', $config['owner_phone'] ?? ''), $selectedOrder['whatsapp_message']); ?>
            <?php if ($whatsappUrl): ?>
                <a class="button button-primary" href="<?php echo esc($whatsappUrl); ?>" target="_blank" rel="noopener">Open WhatsApp</a>
            <?php endif; ?>
        </div>
        <div class="order-detail-grid">
            <div>
                <h3>Customer</h3>
                <p><strong>Name:</strong> <?php echo esc($selectedOrder['customer_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo esc($selectedOrder['customer_phone']); ?></p>
                <p><strong>Address:</strong> <?php echo esc($selectedOrder['customer_address']); ?></p>
                <?php if ($selectedOrder['customer_note']): ?>
                    <p><strong>Note:</strong> <?php echo esc($selectedOrder['customer_note']); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h3>Items</h3>
                <?php foreach ($orderItems as $item): ?>
                    <p><?php echo esc($item['quantity']); ?> x <?php echo esc($item['product_name']); ?> - AED <?php echo esc(format_money($item['line_total'])); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="recent-orders">
    <h2>All Orders</h2>
    <?php if (!$orders): ?>
        <p class="page-copy">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo esc($order['id']); ?></td>
                            <td>
                                <strong><?php echo esc($order['customer_name']); ?></strong><br>
                                <span class="table-muted"><?php echo esc($order['customer_phone']); ?></span>
                            </td>
                            <td><?php echo esc($order['item_count']); ?></td>
                            <td>AED <?php echo esc(format_money($order['total_price'])); ?></td>
                            <td><span class="status-pill"><?php echo esc($allowedStatuses[$order['status']] ?? $order['status']); ?></span></td>
                            <td><?php echo esc($order['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-secondary button-small" href="orders.php?view=<?php echo esc($order['id']); ?>">View</a>
                                    <form class="inline-form" method="post" action="orders.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo esc($order['id']); ?>">
                                        <label>
                                            <select name="status">
                                                <?php foreach ($allowedStatuses as $value => $label): ?>
                                                    <option value="<?php echo esc($value); ?>" <?php echo $order['status'] === $value ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <button type="submit" class="button button-secondary button-small">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
