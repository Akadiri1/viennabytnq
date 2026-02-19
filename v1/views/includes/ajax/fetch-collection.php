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
    $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
    $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

    // Build Query with variant aggregation
    $select = "SELECT p.*, 
        (SELECT MIN(price) FROM product_price_variants WHERE product_id = p.id AND price > 0) as min_variant_price,
        (SELECT MAX(price) FROM product_price_variants WHERE product_id = p.id) as max_variant_price,
        (SELECT COUNT(*) FROM product_price_variants WHERE product_id = p.id) as variant_count,
        COALESCE(
            (SELECT MIN(price) FROM product_price_variants WHERE product_id = p.id AND price > 0),
            NULLIF(p.price, 0)
        ) as effective_price";

    $from = " FROM panel_products p";
    $where = " WHERE LOWER(p.visibility) = 'show'";
    $params = [];
    
    if ($collectionId !== 'all' && filter_var($collectionId, FILTER_VALIDATE_INT)) {
        $where .= " AND p.collection_id = ?";
        $params[] = (int)$collectionId;
    }
    
    if ($search !== '') {
        $where .= " AND p.name LIKE ?";
        $params[] = "%$search%";
    }

    // Price range filter — filter by variant price if variants exist, else base price
    if ($minPrice !== null || $maxPrice !== null) {
        $priceConditions = [];
        
        if ($minPrice !== null && $maxPrice !== null) {
            // Product matches if:
            // 1) It has variants and at least one variant price is within range, OR
            // 2) It has no variants and its base price is within range
            $where .= " AND (
                (EXISTS (SELECT 1 FROM product_price_variants ppv WHERE ppv.product_id = p.id AND ppv.price >= ? AND ppv.price <= ?))
                OR
                (NOT EXISTS (SELECT 1 FROM product_price_variants ppv2 WHERE ppv2.product_id = p.id) AND p.price >= ? AND p.price <= ?)
            )";
            $params[] = $minPrice;
            $params[] = $maxPrice;
            $params[] = $minPrice;
            $params[] = $maxPrice;
        } elseif ($minPrice !== null) {
            $where .= " AND (
                (EXISTS (SELECT 1 FROM product_price_variants ppv WHERE ppv.product_id = p.id AND ppv.price >= ?))
                OR
                (NOT EXISTS (SELECT 1 FROM product_price_variants ppv2 WHERE ppv2.product_id = p.id) AND p.price >= ?)
            )";
            $params[] = $minPrice;
            $params[] = $minPrice;
        } elseif ($maxPrice !== null) {
            $where .= " AND (
                (EXISTS (SELECT 1 FROM product_price_variants ppv WHERE ppv.product_id = p.id AND ppv.price <= ?))
                OR
                (NOT EXISTS (SELECT 1 FROM product_price_variants ppv2 WHERE ppv2.product_id = p.id) AND p.price <= ?)
            )";
            $params[] = $maxPrice;
            $params[] = $maxPrice;
        }
    }

    // Sort — use effective_price (variant-aware) for price sorting
    $order = " ORDER BY p.id DESC";
    if ($sort === 'price-asc') $order = " ORDER BY effective_price ASC, p.id DESC";
    if ($sort === 'price-desc') $order = " ORDER BY effective_price DESC, p.id DESC";

    // === COUNT QUERY (for total_count) ===
    $countSql = "SELECT COUNT(*) as total" . $from . $where;
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // === MAIN QUERY ===
    $sql = $select . $from . $where . $order . " LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no products are found, return empty with count
    if (empty($products)) {
        echo json_encode(['products' => [], 'total_count' => $totalCount]);
        exit;
    }

    // Get all product IDs from the results for our IN() clauses
    $productIds = array_column($products, 'id');
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
    foreach ($colorResults as $row) {
        $allColors[$row['product_id']][] = $row['hex_code'];
    }
    
    // === QUERY 3: Fetch all sizes for ALL products in ONE query ===
    $sizeSql = "SELECT ps.product_id, s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id IN ($placeholders)";
    $sizeStmt = $conn->prepare($sizeSql);
    $sizeStmt->execute($productIds);
    $sizeResults = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sizeResults as $row) {
        $allSizes[$row['product_id']][] = $row['name'];
    }
    
    // === QUERY 4: Fetch all variants for ALL products in ONE query ===
    $variantSql = "SELECT product_id, variant_name, price, stock_quantity FROM product_price_variants WHERE product_id IN ($placeholders) ORDER BY price ASC";
    $variantStmt = $conn->prepare($variantSql);
    $variantStmt->execute($productIds);
    $variantResults = $variantStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($variantResults as $row) {
        $allVariants[$row['product_id']][] = $row;
    }

    // --- ASSEMBLE THE FINAL RESPONSE ---
    $response_data = [];
    foreach ($products as $product) {
        $productId = $product['id'];
        $variants = $allVariants[$productId] ?? [];
        
        // Determine the display price (lowest variant or base price)
        if (!empty($variants)) {
            $variantPrices = array_column($variants, 'price');
            $minVPrice = min($variantPrices);
            $displayPrice = ($minVPrice > 0) ? $minVPrice : $product['price'];
        } else {
            $displayPrice = $product['price'];
        }

        // Determine effective stock
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
            'stock_quantity' => $effectiveStock,
            'colors' => $allColors[$productId] ?? [],
            'sizes' => $allSizes[$productId] ?? [],
            'price_variants' => $variants,
            'display_price' => $displayPrice
        ];
    }

    // --- OUTPUT THE FINAL JSON (new format with total_count) ---
    echo json_encode([
        'products' => $response_data,
        'total_count' => $totalCount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred.']);
}
?>