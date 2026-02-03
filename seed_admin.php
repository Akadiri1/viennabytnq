<?php
// Load config and DB connection
define("D_PATH", dirname(dirname(dirname(__DIR__))));
const APP_PATH = D_PATH."/v1";
include D_PATH."/.env/config.php";

echo "Connecting to DB...\n";
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Tables
    $sql = "
    CREATE TABLE IF NOT EXISTS `admin_users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL UNIQUE,
      `password_hash` varchar(255) NOT NULL,
      `status` enum('active','pending','suspended') DEFAULT 'pending',
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `admin_invites` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `token` varchar(64) NOT NULL UNIQUE,
      `expires_at` datetime NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->exec($sql);
    echo "Tables created successfully.\n";

    // 2. Check if admin exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        // 3. Seed Default Admin (admin / admin)
        // Using PASSWORD_DEFAULT for hashing
        $passHash = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, status) VALUES (?, ?, 'active')");
        $stmt->execute(['admin', $passHash]);
        echo "Default admin user seeded.\n";
    } else {
        echo "Admin user already exists.\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
