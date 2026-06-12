<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

$pageTitle = 'Menu - Furn Fawz';
$selectedCategory = substr(trim($_GET['category'] ?? ''), 0, 150);
$search = substr(trim($_GET['q'] ?? ''), 0, 100);
$categories = [];
$products = [];
$catalogError = '';
$cartSuccess = pull_flash('cart_success');
$cartError = pull_flash('cart_error');
$cartActionUrl = '../api/cart_action.php';
$catalogReturnUrl = ($_SERVER['REQUEST_URI'] ?? 'index.php') . '#menu';

function catalog_url($categorySlug = null, $search = '')
{
    $params = [];

    if ($categorySlug !== null && $categorySlug !== '') {
        $params['category'] = $categorySlug;
    }

    if ($search !== '') {
        $params['q'] = $search;
    }

    $query = http_build_query($params);

    return url('index.php' . ($query !== '' ? '?' . $query : ''));
}

function product_image_url($imagePath)
{
    $imagePath = str_replace('\\', '/', trim((string) $imagePath));

    if ($imagePath === '' || strpos($imagePath, 'uploads/products/') !== 0) {
        return '';
    }

    $localPath = __DIR__ . '/' . ltrim($imagePath, '/');

    if (!is_file($localPath)) {
        return '';
    }

    return asset_url($imagePath);
}

function product_initials($name)
{
    $words = preg_split('/\s+/', trim((string) $name));
    $letters = '';

    foreach ($words as $word) {
        if ($word !== '') {
            $letters .= strtoupper(substr($word, 0, 1));
        }

        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'FF';
}

if ($pdo instanceof PDO) {
    try {
        $categoryStmt = $pdo->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name ASC');
        $categories = $categoryStmt->fetchAll();

        $sql = 'SELECT p.id, p.name, p.slug, p.description, p.price, p.image_path, c.name AS category_name, c.slug AS category_slug
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE p.is_available = 1 AND c.is_active = 1';
        $params = [];

        if ($selectedCategory !== '') {
            $sql .= ' AND c.slug = :category';
            $params[':category'] = $selectedCategory;
        }

        if ($search !== '') {
            $sql .= ' AND (p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY c.name ASC, p.name ASC';
        $productStmt = $pdo->prepare($sql);
        $productStmt->execute($params);
        $products = $productStmt->fetchAll();
    } catch (PDOException $exception) {
        $catalogError = 'Menu is temporarily unavailable.';
    }
} else {
    $catalogError = 'Menu is temporarily unavailable.';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="hero menu-hero">
    <div class="container hero-panel">
        <div class="hero-copy">
            <p class="eyebrow">Fresh from the oven</p>
            <h1>Furn Fawz</h1>
            <p>Warm manakish, sweet crepes, soft kaak, and cold drinks prepared for fast everyday ordering.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="#menu">View menu</a>
                <a class="button button-secondary" href="<?php echo esc(url('cart.php')); ?>">Open cart</a>
            </div>
        </div>
        <aside class="hero-summary" aria-label="Menu summary">
            <div>
                <span><?php echo esc(count($products)); ?></span>
                <p>Available items</p>
            </div>
            <div>
                <span><?php echo esc(count($categories)); ?></span>
                <p>Categories</p>
            </div>
        </aside>
    </div>
</section>

<section class="search-panel" id="menu">
    <div class="container search-grid">
        <form class="search-form" method="get" action="<?php echo esc(url('index.php')); ?>">
            <?php if ($selectedCategory !== ''): ?>
                <input type="hidden" name="category" value="<?php echo esc($selectedCategory); ?>">
            <?php endif; ?>
            <input type="search" name="q" value="<?php echo esc($search); ?>" placeholder="Search manakish, crepes, drinks" aria-label="Search menu">
            <button type="submit">Search</button>
            <?php if ($search !== '' || $selectedCategory !== ''): ?>
                <a class="button button-secondary" href="<?php echo esc(url('index.php')); ?>">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($categories): ?>
            <nav class="category-chips" aria-label="Menu categories">
                <a class="chip<?php echo $selectedCategory === '' ? ' active' : ''; ?>" href="<?php echo esc(catalog_url(null, $search)); ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a class="chip<?php echo $selectedCategory === $category['slug'] ? ' active' : ''; ?>" href="<?php echo esc(catalog_url($category['slug'], $search)); ?>">
                        <?php echo esc($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<section class="menu-section">
    <div class="container">
        <div class="cart-feedback<?php echo (!$cartSuccess && !$cartError) ? ' is-hidden' : ''; ?>" data-cart-feedback aria-live="polite">
            <?php if ($cartSuccess): ?>
                <div class="alert alert-success"><?php echo esc($cartSuccess); ?></div>
            <?php elseif ($cartError): ?>
                <div class="alert alert-error"><?php echo esc($cartError); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($catalogError): ?>
            <div class="alert alert-error">
                <h2>Menu unavailable</h2>
                <p><?php echo esc($catalogError); ?></p>
            </div>
        <?php elseif (!$products): ?>
            <div class="empty-state">
                <h2>No items found</h2>
                <p>Try another category or search term.</p>
            </div>
        <?php else: ?>
            <div class="menu-heading">
                <div>
                    <p class="eyebrow">Menu</p>
                    <h2>Choose your favorites</h2>
                </div>
                <p class="page-copy"><?php echo esc(count($products)); ?> item<?php echo count($products) === 1 ? '' : 's'; ?> shown</p>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $imageUrl = product_image_url($product['image_path'] ?? ''); ?>
                    <?php $productId = (int) $product['id']; ?>
                    <?php $quantityInCart = cart_quantity($productId); ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if ($imageUrl !== ''): ?>
                                <img src="<?php echo esc($imageUrl); ?>" alt="<?php echo esc($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-image-fallback">
                                    <span><?php echo esc(product_initials($product['name'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <p class="product-category"><?php echo esc($product['category_name']); ?></p>
                            <h3><?php echo esc($product['name']); ?></h3>
                            <p class="product-description"><?php echo esc($product['description'] ?: 'Fresh bakery item.'); ?></p>
                            <div class="product-footer">
                                <span class="price">AED <?php echo esc(format_money($product['price'])); ?></span>
                                <span class="availability-pill">Available</span>
                            </div>
                            <form class="product-action-form" method="post" action="<?php echo esc($cartActionUrl); ?>" data-cart-form>
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo esc($productId); ?>">
                                <input type="hidden" name="redirect" value="<?php echo esc($catalogReturnUrl); ?>">
                                <label for="quantity-<?php echo esc($productId); ?>">Qty</label>
                                <input class="quantity-input product-quantity" id="quantity-<?php echo esc($productId); ?>" type="number" name="quantity" min="1" max="99" value="1" inputmode="numeric">
                                <button type="submit" class="button button-primary">Add</button>
                            </form>
                            <?php if ($quantityInCart > 0): ?>
                                <p class="cart-note"><?php echo esc($quantityInCart); ?> already in cart</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
