<?php
// Set headers FIRST. No output should come before this.
header('Content-Type: application/json');
ini_set('display_errors', 0); // Production servers should not display errors in the JSON response.
error_reporting(E_ALL);

// --- 2. MAIN LOGIC WITH ERROR HANDLING ---
try {
    // Validate the input collection ID
    $collectionId = $_GET['id'] ?? null;
    if (!$collectionId) {
        http_response_code(400); 
        echo json_encode(['error' => 'Missing collection ID.']);
        exit;
    }
    
    // Pagination Params
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Filter Params
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

    // Build Query
    $where = "WHERE LOWER(visibility) = 'show'";
    $params = [];
    
    if ($collectionId !== 'all' && filter_var($collectionId, FILTER_VALIDATE_INT)) {
        $where .= " AND collection_id = ?";
        $params[] = (int)$collectionId;
    }
    
    if ($search !== '') {
        $where .= " AND name LIKE ?";
        $params[] = "%$search%";
    }

    $order = "ORDER BY id DESC";
    if ($sort === 'price-asc') $order = "ORDER BY price ASC";
    if ($sort === 'price-desc') $order = "ORDER BY price DESC";

    // === QUERY 1: Get initial list of products ===
    // Use raw query for flexibility with search/sort
    $sql = "SELECT * FROM panel_products $where $order LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no products are found, return an empty array immediately. This is valid JSON.
    if (empty($products)) {
        echo json_encode([]);
        exit;
    }

    // Get all product IDs from the results for our IN() clauses
    $productIds = array_column($products, 'id');
    
    // Create a string of placeholders for the IN clause (?, ?, ?, ...)
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    // Prepare arrays to hold all details, organized by product_id
    $allColors = [];
    $allSizes = [];
    $allVariants = [];

    // === QUERY 2: Fetch all colors for ALL products in ONE query ===
    $colorSql = "SELECT pc.product_id, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id IN ($placeholders)";
    $colorStmt = $conn->prepare($colorSql);
    $colorStmt->execute($productIds);
    $colorResults = $colorStmt->fetchAll(PDO::FETCH_ASSOC);
    // Re-organize the data for easy lookup
    foreach ($colorResults as $row) {
        $allColors[$row['product_id']][] = $row['hex_code'];
    }
    
    // === QUERY 3: Fetch all sizes for ALL products in ONE query ===
    $sizeSql = "SELECT ps.product_id, s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id IN ($placeholders)";
    $sizeStmt = $conn->prepare($sizeSql);
    $sizeStmt->execute($productIds);
    $sizeResults = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);
    // Re-organize the data
    foreach ($sizeResults as $row) {
        $allSizes[$row['product_id']][] = $row['name'];
    }
    
    // === QUERY 4: Fetch all variants for ALL products in ONE query ===
    $variantSql = "SELECT product_id, variant_name, price, stock_quantity FROM product_price_variants WHERE product_id IN ($placeholders) ORDER BY price ASC";
    $variantStmt = $conn->prepare($variantSql);
    $variantStmt->execute($productIds);
    $variantResults = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    // Re-organize the data
    foreach ($variantResults as $row) {
        $allVariants[$row['product_id']][] = $row;
    }

    // --- 4. ASSEMBLE THE FINAL RESPONSE ---
    // Now, loop through the products and add the pre-fetched details. This is very fast.
    $response_data = [];
    foreach ($products as $product) {
        $productId = $product['id'];
        $variants = $allVariants[$productId] ?? [];
        
        // Determine the display price (lowest variant or base price)
        $displayPrice = !empty($variants) ? $variants[0]['price'] : $product['price'];

        // Determine effective stock
        // If variants exist, stock is the sum of all variant stocks. Otherwise, use main product stock.
        if (!empty($variants)) {
            $effectiveStock = 0;
            foreach ($variants as $v) {
                $effectiveStock += (int)$v['stock_quantity'];
            }
        } else {
            $effectiveStock = (int)$product['stock_quantity'];
        }

        // Build the final structure for this one product
        $response_data[] = [
            'id' => $productId,
            'name' => $product['name'],
            'image_one' => $product['image_one'],
            'image_two' => $product['image_two'],
            'price' => $product['price'],
            'stock_quantity' => $effectiveStock, // Use calculated stock
            'colors' => $allColors[$productId] ?? [], // Use pre-fetched data
            'sizes' => $allSizes[$productId] ?? [], // Use pre-fetched data
            'price_variants' => $variants, // Use pre-fetched data
            'display_price' => $displayPrice
        ];
    }

    // --- 5. OUTPUT THE FINAL JSON ---
    echo json_encode($response_data);

} catch (PDOException $e) {
    // Catch database-specific errors
    http_response_code(500); // Internal Server Error
    // In production, you should log this error instead of echoing it
    // error_log("Database Error: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
} catch (Exception $e) {
    // Catch any other general errors
    http_response_code(500);
    // error_log("General Error: " . $e->getMessage());
    echo json_encode(['error' => 'An unexpected error occurred.']);
}
?>