<?php
// --- THE FIX: START THE SESSION TO ACCESS $_SESSION['user_id'] ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// --- END FIX ---

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Cart identifier is missing.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// 1. Sanitize all inputs from the frontend
$productId = filter_var($input['productId'] ?? 0, FILTER_VALIDATE_INT);
$quantity = filter_var($input['quantity'] ?? 0, FILTER_VALIDATE_INT);
$colorId = isset($input['colorId']) ? filter_var($input['colorId'], FILTER_VALIDATE_INT) : null;
$priceVariantId = isset($input['priceVariantId']) ? filter_var($input['priceVariantId'], FILTER_VALIDATE_INT) : null;
$sizeId = isset($input['sizeId']) ? filter_var($input['sizeId'], FILTER_VALIDATE_INT) : null;
$customColor = isset($input['customColor']) ? htmlspecialchars(trim($input['customColor']), ENT_QUOTES, 'UTF-8') : null;
$customSizeDetails = (isset($input['customSizeDetails']) && !empty($input['customSizeDetails'])) ? json_encode($input['customSizeDetails']) : null;

if (!$productId || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product data provided.']);
    exit;
}

try {
    // Your validation logic is excellent and remains unchanged.
    // ...

    // 3. --- PRICE CALCULATION LOGIC ---
    $unitPrice = null;
    if ($priceVariantId) {
        $priceStmt = $conn->prepare("SELECT price FROM product_price_variants WHERE id = ? AND product_id = ?");
        $priceStmt->execute([$priceVariantId, $productId]);
        $priceResult = $priceStmt->fetchColumn();
        if ($priceResult !== false) $unitPrice = $priceResult;
    }
    if ($unitPrice === null) {
        $productStmt = $conn->prepare("SELECT price FROM panel_products WHERE id = ? AND visibility = 'show'");
        $productStmt->execute([$productId]);
        $productPrice = $productStmt->fetchColumn();
        if ($productPrice === false) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            exit;
        }
        $unitPrice = $productPrice;
    }

    // 4. --- LOOKUP NAMES FROM IDs ---
    $colorName = null;
    $sizeName = null;
    if ($colorId) {
        $stmt = $conn->prepare("SELECT name FROM colors WHERE id = ?");
        $stmt->execute([$colorId]);
        $colorName = $stmt->fetchColumn();
    }
    if ($priceVariantId) {
        $stmt = $conn->prepare("SELECT variant_name FROM product_price_variants WHERE id = ?");
        $stmt->execute([$priceVariantId]);
        $sizeName = $stmt->fetchColumn();
    } elseif ($sizeId) {
        $stmt = $conn->prepare("SELECT name FROM sizes WHERE id = ?");
        $stmt->execute([$sizeId]);
        $sizeName = $stmt->fetchColumn();
    }
    
    // Because session_start() was called, this now works correctly!
    $userId = $_SESSION['user_id'] ?? null;

    // 5. --- CHECK FOR EXISTING ITEM, UPDATE, OR INSERT ---
    $checkSql = "SELECT id, quantity FROM cart_items WHERE
                     cart_token = :cart_token AND
                     product_id = :product_id AND
                     (color_name <=> :color_name) AND
                     (custom_color_name <=> :custom_color_name) AND
                     (size_name <=> :size_name) AND
                     (custom_size_details <=> :custom_size_details)
                 LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([':cart_token' => $cartToken, ':product_id' => $productId, ':color_name' => $colorName, ':custom_color_name' => $customColor, ':size_name' => $sizeName, ':custom_size_details' => $customSizeDetails]);
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;
        $newTotalPrice = $unitPrice * $newQuantity;
        // Also update the user_id in case a guest adds to cart then logs in
        $updateSql = "UPDATE cart_items SET quantity = :quantity, total_price = :total_price, user_id = :user_id WHERE id = :id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([':quantity' => $newQuantity, ':total_price' => $newTotalPrice, ':user_id' => $userId, ':id' => $existingItem['id']]);
        $message = "Cart quantity updated!";
    } else {
        $totalPrice = $unitPrice * $quantity;
        
        $insertSql = "INSERT INTO cart_items 
                      (cart_token, user_id, product_id, quantity, total_price, color_name, custom_color_name, size_name, custom_size_details, price_variant_id) 
                      VALUES (:cart_token, :user_id, :product_id, :quantity, :total_price, :color_name, :custom_color_name, :size_name, :custom_size_details, :price_variant_id)";
        
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([
            ':cart_token' => $cartToken, 
            ':user_id' => $userId, 
            ':product_id' => $productId, 
            ':quantity' => $quantity, 
            ':total_price' => $totalPrice, 
            ':color_name' => $colorName, 
            ':custom_color_name' => $customColor, 
            ':size_name' => $sizeName, 
            ':custom_size_details' => $customSizeDetails,
            ':price_variant_id' => $priceVariantId
        ]);

        $message = "Item added to cart!";
    }
    echo json_encode(['status' => 'success', 'message' => $message]);
} catch (PDOException $e) {
    error_log('Cart Add/Update Error: ' . $e->getMessage());
    http_response_code(500);
    // *** FIX 2: TEMPORARILY OUTPUT THE ACTUAL PDO ERROR FOR DEBUGGING ***
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>