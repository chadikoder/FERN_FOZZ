<?php
$pageTitle = $pageTitle ?? 'Admin - Furn Fawz';
$bodyClass = trim(($bodyClass ?? '') . ' admin-body');
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminName = $_SESSION['admin_name'] ?? 'Admin';

if (!function_exists('admin_nav_class')) {
    function admin_nav_class($page, $currentPage)
    {
        return $page === $currentPage ? ' class="active"' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?></title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="<?php echo esc($bodyClass); ?>">
<header class="site-header admin-header">
    <div class="container header-inner">
        <div>
            <a href="dashboard.php" class="logo">Furn Fawz Admin</a>
            <p class="admin-welcome">Signed in as <?php echo esc($adminName); ?></p>
        </div>
        <nav class="main-nav admin-nav" aria-label="Admin navigation">
            <a href="dashboard.php"<?php echo admin_nav_class('dashboard.php', $currentPage); ?>>Dashboard</a>
            <a href="orders.php"<?php echo admin_nav_class('orders.php', $currentPage); ?>>Orders</a>
            <a href="products.php"<?php echo admin_nav_class('products.php', $currentPage); ?>>Products</a>
            <a href="categories.php"<?php echo admin_nav_class('categories.php', $currentPage); ?>>Categories</a>
            <form method="post" action="logout.php" class="logout-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-button">Logout</button>
            </form>
        </nav>
    </div>
</header>
<main class="admin-page">
    <div class="container admin-grid">
