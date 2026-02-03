<?php
    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM product_price_variants LIKE 'stock_quantity'");
    if (!$stmt->fetch()) {
        $conn->exec("ALTER TABLE product_price_variants ADD COLUMN stock_quantity INT DEFAULT 0 AFTER price");
        echo "Column 'stock_quantity' added to 'product_price_variants'.\n";
    } else {
        echo "Column 'stock_quantity' already exists.\n";
    }
?>
