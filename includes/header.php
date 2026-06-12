<?php
$pageTitle = $pageTitle ?? ($config['app_name'] ?? 'Furn Fawz');
$bodyClass = trim($bodyClass ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo esc(asset_url('assets/css/style.css')); ?>">
</head>
<body<?php echo $bodyClass !== '' ? ' class="' . esc($bodyClass) . '"' : ''; ?>>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?php echo esc(url('index.php')); ?>" class="logo"><?php echo esc($config['app_name'] ?? 'Furn Fawz'); ?></a>
        <nav class="main-nav" aria-label="Primary navigation">
            <a href="<?php echo esc(url('index.php')); ?>">Menu</a>
            <a href="<?php echo esc(url('cart.php')); ?>">
                Cart
                <span class="cart-count"><?php echo esc(cart_item_count()); ?></span>
            </a>
        </nav>
    </div>
</header>
<main>
