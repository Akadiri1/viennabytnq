<?php
require_once __DIR__ . '/../../../../config/model.php';

echo "Migrating site_visits table...\n";

try {
    // Add country column
    $conn->exec("ALTER TABLE site_visits ADD COLUMN country VARCHAR(100) DEFAULT NULL");
    echo "Added 'country' column.\n";
} catch (Exception $e) { /* Ignore if exists */ }

try {
    // Add region column
    $conn->exec("ALTER TABLE site_visits ADD COLUMN region VARCHAR(100) DEFAULT NULL");
    echo "Added 'region' column.\n";
} catch (Exception $e) { /* Ignore if exists */ }

echo "Migration complete.\n";
?>
