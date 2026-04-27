<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'];
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $newPassword = password_hash('Admin@123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password WHERE id = 1');
    $stmt->execute(['password' => $newPassword]);
    
    echo "Password updated successfully for user ID 1.\n";
    echo "New credentials:\n";
    echo "Email: admin@example.com\n";
    echo "Password: Admin@123\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
