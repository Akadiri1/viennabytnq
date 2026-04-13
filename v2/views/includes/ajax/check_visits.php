<?php
require_once __DIR__ . '/../../../../config/model.php';

echo "Checking site_visits table...\n";

try {
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'site_visits'");
    if ($check->rowCount() == 0) {
        echo "Table 'site_visits' DOES NOT EXIST. Creating it now...\n";
        $sql = "CREATE TABLE IF NOT EXISTS site_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45),
            visit_url VARCHAR(255),
            referrer VARCHAR(255),
            user_agent VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        echo "Table created.\n";
    } else {
        echo "Table 'site_visits' EXISTS.\n";
    }

    // Check row count
    $count = $conn->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    echo "Current Row Count: " . $count . "\n";
    
    // Check if recent tracking works
    echo "\nTest Insert:\n";
    $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute(['127.0.0.1', '/test-check', 'diagnostic', 'Antigravity-Agent']);
    echo "Inserted test row.\n";
    
    $countNew = $conn->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    echo "New Row Count: " . $countNew . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
