<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

require_admin();

$adminSuccess = pull_flash('admin_success');
$adminError = pull_flash('admin_error');
$editProduct = null;
$products = [];
$categories = [];

function product_upload_path($slug, $file)
{
    if (empty($file['tmp_name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ((int) $file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Image must be 3 MB or smaller.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Image must be JPG, PNG, WEBP, or GIF.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Uploaded file is not a valid image.');
    }

    $uploadDir = __DIR__ . '/../public/uploads/products';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new RuntimeException('Product upload folder could not be created.');
    }

    $fileName = slugify($slug) . '-' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Image could not be saved.');
    }

    return 'uploads/products/' . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('admin_error', 'Your session expired. Please try again.');
        redirect_to('products.php');
    }

    if (!($pdo instanceof PDO)) {
        flash('admin_error', 'Database is unavailable.');
        redirect_to('products.php');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_product') {
            $id = (int) ($_POST['id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = substr(trim($_POST['name'] ?? ''), 0, 150);
            $slug = substr(slugify($_POST['slug'] ?? $name), 0, 180);
            $description = trim($_POST['description'] ?? '');
            $price = round((float) ($_POST['price'] ?? 0), 2);
            $imagePath = substr(str_replace('\\', '/', trim($_POST['image_path'] ?? '')), 0, 255);
            $uploadedImagePath = product_upload_path($slug, $_FILES['product_image'] ?? []);
            if ($uploadedImagePath !== '') {
                $imagePath = $uploadedImagePath;
            }
            $isAvailable = !empty($_POST['is_available']) ? 1 : 0;

            if ($name === '' || $categoryId <= 0 || $price < 0) {
                throw new RuntimeException('Name, category, and a valid price are required.');
            }

            if ($imagePath !== '' && strpos($imagePath, 'uploads/products/') !== 0) {
                throw new RuntimeException('Image path must start with uploads/products/.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE products
                    SET category_id = :category_id, name = :name, slug = :slug, description = :description,
                        price = :price, image_path = :image_path, is_available = :is_available, updated_at = NOW()
                    WHERE id = :id'
                );
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => $price,
                    ':image_path' => $imagePath !== '' ? $imagePath : null,
                    ':is_available' => $isAvailable,
                    ':id' => $id,
                ]);
                flash('admin_success', 'Product updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, name, slug, description, price, image_path, is_available)
                    VALUES (:category_id, :name, :slug, :description, :price, :image_path, :is_available)'
                );
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => $price,
                    ':image_path' => $imagePath !== '' ? $imagePath : null,
                    ':is_available' => $isAvailable,
                ]);
                flash('admin_success', 'Product added.');
            }
        } elseif ($action === 'toggle_product') {
            $id = (int) ($_POST['id'] ?? 0);
            $isAvailable = (int) ($_POST['is_available'] ?? 0);
            $stmt = $pdo->prepare('UPDATE products SET is_available = :is_available, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':is_available' => $isAvailable,
                ':id' => $id,
            ]);
            flash('admin_success', 'Product availability updated.');
        }
    } catch (Throwable $exception) {
        flash('admin_error', $exception->getMessage() ?: 'Product could not be saved.');
    }

    redirect_to('products.php');
}

if ($pdo instanceof PDO) {
    try {
        $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

        $editId = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $editId]);
            $editProduct = $stmt->fetch() ?: null;
        }

        $stmt = $pdo->query(
            'SELECT p.id, p.name, p.slug, p.description, p.price, p.image_path, p.is_available, c.name AS category_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            ORDER BY c.name ASC, p.name ASC'
        );
        $products = $stmt->fetchAll();
    } catch (PDOException $exception) {
        $adminError = 'Products are temporarily unavailable.';
    }
} else {
    $adminError = 'Products are unavailable until the database is connected.';
}

$pageTitle = 'Manage Products - Furn Fawz';
include __DIR__ . '/../includes/admin_header.php';
?>
<section class="panel-card">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Menu setup</p>
            <h1>Products</h1>
            <p class="page-copy">Create and edit items shown on the public menu.</p>
        </div>
    </div>

    <?php if ($adminSuccess): ?>
        <div class="alert alert-success"><?php echo esc($adminSuccess); ?></div>
    <?php endif; ?>
    <?php if ($adminError): ?>
        <div class="alert alert-error"><?php echo esc($adminError); ?></div>
    <?php endif; ?>

    <form class="admin-form admin-form-card" method="post" action="products.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save_product">
        <input type="hidden" name="id" value="<?php echo esc($editProduct['id'] ?? 0); ?>">
        <label>
            Category
            <select name="category_id" required>
                <option value="">Choose category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc($category['id']); ?>" <?php echo isset($editProduct['category_id']) && (int) $editProduct['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>>
                        <?php echo esc($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Name
            <input type="text" name="name" maxlength="150" value="<?php echo esc($editProduct['name'] ?? ''); ?>" required>
        </label>
        <label>
            Slug
            <input type="text" name="slug" maxlength="180" value="<?php echo esc($editProduct['slug'] ?? ''); ?>" placeholder="auto from name">
        </label>
        <label>
            Price
            <input type="number" name="price" min="0" step="0.01" value="<?php echo esc($editProduct['price'] ?? ''); ?>" required>
        </label>
        <label>
            Image
            <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif">
        </label>
        <label>
            Image path
            <input type="text" name="image_path" maxlength="255" value="<?php echo esc($editProduct['image_path'] ?? ''); ?>" placeholder="uploads/products/example.jpg">
        </label>
        <?php $previewPath = $editProduct['image_path'] ?? ''; ?>
        <?php $previewExists = $previewPath !== '' && is_file(__DIR__ . '/../public/' . ltrim($previewPath, '/')); ?>
        <?php if ($previewExists): ?>
            <div class="admin-image-preview">
                <span>Current image</span>
                <img src="../public/<?php echo esc($previewPath); ?>" alt="<?php echo esc($editProduct['name']); ?>">
            </div>
        <?php elseif ($previewPath !== ''): ?>
            <p class="form-note">Current path is saved, but the image file is missing.</p>
        <?php endif; ?>
        <label>
            Description
            <textarea name="description"><?php echo esc($editProduct['description'] ?? ''); ?></textarea>
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="is_available" value="1" <?php echo !$editProduct || (int) $editProduct['is_available'] === 1 ? 'checked' : ''; ?>>
            Available
        </label>
        <button type="submit" class="button button-primary"><?php echo $editProduct ? 'Update product' : 'Add product'; ?></button>
        <?php if ($editProduct): ?>
            <a class="button button-secondary" href="products.php">Cancel edit</a>
        <?php endif; ?>
</form>
</section>

<section class="recent-orders">
    <h2>All Products</h2>
    <?php if (!$products): ?>
        <p class="page-copy">No products yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo esc($product['name']); ?></td>
                            <td><?php echo esc($product['category_name']); ?></td>
                            <td>AED <?php echo esc(format_money($product['price'])); ?></td>
                            <td><span class="status-pill"><?php echo (int) $product['is_available'] === 1 ? 'Available' : 'Hidden'; ?></span></td>
                            <td><?php echo esc($product['image_path'] ?: 'Fallback'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-secondary button-small" href="products.php?edit=<?php echo esc($product['id']); ?>">Edit</a>
                                    <form method="post" action="products.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle_product">
                                        <input type="hidden" name="id" value="<?php echo esc($product['id']); ?>">
                                        <input type="hidden" name="is_available" value="<?php echo (int) $product['is_available'] === 1 ? 0 : 1; ?>">
                                        <button type="submit" class="button button-secondary button-small">
                                            <?php echo (int) $product['is_available'] === 1 ? 'Hide' : 'Show'; ?>
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
