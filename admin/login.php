<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_error']);
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } elseif (!($pdo instanceof PDO)) {
        $error = 'Login is unavailable until the database connection is fixed.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Invalid email or password.';
        } catch (PDOException $exception) {
            $error = 'Login is unavailable right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Furn Fawz</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="admin-login-page">
    <main class="auth-page">
        <div class="auth-card">
            <h1>Admin Login</h1>
            <p class="auth-copy">Manage menu items, categories, and incoming orders.</p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo esc($error); ?></div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <?php echo csrf_field(); ?>
                <label>
                    Email
                    <input type="email" name="email" value="<?php echo esc($email); ?>" autocomplete="email" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button type="submit" class="button button-primary">Login</button>
            </form>
        </div>
    </main>
</body>
</html>
