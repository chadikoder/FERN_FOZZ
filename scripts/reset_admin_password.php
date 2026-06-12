<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/reset_admin_password.php admin@example.com NewStrongPassword\n");
    exit(1);
}

$email = trim($argv[1]);
$password = (string) $argv[2];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n");
    exit(1);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$dbConfig = require __DIR__ . '/../config/database.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $dbConfig['charset']);

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $stmt = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE email = :email');
    $stmt->execute([
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':email' => $email,
    ]);

    if ($stmt->rowCount() === 0) {
        fwrite(STDERR, "No admin found with that email.\n");
        exit(1);
    }

    echo "Password updated for {$email}.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Password reset failed: " . $exception->getMessage() . "\n");
    exit(1);
}
