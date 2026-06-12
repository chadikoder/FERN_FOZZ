<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$adminSuccess = pull_flash('admin_success');
$adminError = pull_flash('admin_error');
$editCategory = null;
$categories = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('admin_error', 'Your session expired. Please try again.');
        redirect_to('categories.php');
    }

    if (!($pdo instanceof PDO)) {
        flash('admin_error', 'Database is unavailable.');
        redirect_to('categories.php');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = substr(trim($_POST['name'] ?? ''), 0, 120);
            $slug = substr(slugify($_POST['slug'] ?? $name), 0, 150);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;

            if ($name === '') {
                throw new RuntimeException('Category name is required.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET name = :name, slug = :slug, is_active = :is_active WHERE id = :id');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':is_active' => $isActive,
                    ':id' => $id,
                ]);
                flash('admin_success', 'Category updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug, is_active) VALUES (:name, :slug, :is_active)');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':is_active' => $isActive,
                ]);
                flash('admin_success', 'Category added.');
            }
        } elseif ($action === 'toggle_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $isActive = (int) ($_POST['is_active'] ?? 0);
            $stmt = $pdo->prepare('UPDATE categories SET is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            flash('admin_success', 'Category status updated.');
        }
    } catch (Throwable $exception) {
        flash('admin_error', $exception->getMessage() ?: 'Category could not be saved.');
    }

    redirect_to('categories.php');
}

if ($pdo instanceof PDO) {
    try {
        $editId = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT id, name, slug, is_active FROM categories WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $editId]);
            $editCategory = $stmt->fetch() ?: null;
        }

        $stmt = $pdo->query(
            'SELECT c.id, c.name, c.slug, c.is_active, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.slug, c.is_active
            ORDER BY c.name ASC'
        );
        $categories = $stmt->fetchAll();
    } catch (PDOException $exception) {
        $adminError = 'Categories are temporarily unavailable.';
    }
} else {
    $adminError = 'Categories are unavailable until the database is connected.';
}

$pageTitle = 'Manage Categories - Furn Fawz';
include __DIR__ . '/../includes/admin_header.php';
?>
<section class="panel-card">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Menu setup</p>
            <h1>Categories</h1>
            <p class="page-copy">Organize products for the public menu.</p>
        </div>
    </div>

    <?php if ($adminSuccess): ?>
        <div class="alert alert-success"><?php echo esc($adminSuccess); ?></div>
    <?php endif; ?>
    <?php if ($adminError): ?>
        <div class="alert alert-error"><?php echo esc($adminError); ?></div>
    <?php endif; ?>

    <form class="admin-form admin-form-card" method="post" action="categories.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save_category">
        <input type="hidden" name="id" value="<?php echo esc($editCategory['id'] ?? 0); ?>">
        <label>
            Name
            <input type="text" name="name" maxlength="120" value="<?php echo esc($editCategory['name'] ?? ''); ?>" required>
        </label>
        <label>
            Slug
            <input type="text" name="slug" maxlength="150" value="<?php echo esc($editCategory['slug'] ?? ''); ?>" placeholder="auto from name">
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="is_active" value="1" <?php echo !$editCategory || (int) $editCategory['is_active'] === 1 ? 'checked' : ''; ?>>
            Active
        </label>
        <button type="submit" class="button button-primary"><?php echo $editCategory ? 'Update category' : 'Add category'; ?></button>
        <?php if ($editCategory): ?>
            <a class="button button-secondary" href="categories.php">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="recent-orders">
    <h2>All Categories</h2>
    <?php if (!$categories): ?>
        <p class="page-copy">No categories yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo esc($category['name']); ?></td>
                            <td><?php echo esc($category['slug']); ?></td>
                            <td><?php echo esc($category['product_count']); ?></td>
                            <td><span class="status-pill"><?php echo (int) $category['is_active'] === 1 ? 'Active' : 'Inactive'; ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-secondary button-small" href="categories.php?edit=<?php echo esc($category['id']); ?>">Edit</a>
                                    <form method="post" action="categories.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle_category">
                                        <input type="hidden" name="id" value="<?php echo esc($category['id']); ?>">
                                        <input type="hidden" name="is_active" value="<?php echo (int) $category['is_active'] === 1 ? 0 : 1; ?>">
                                        <button type="submit" class="button button-secondary button-small">
                                            <?php echo (int) $category['is_active'] === 1 ? 'Disable' : 'Enable'; ?>
                                        </button>
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
