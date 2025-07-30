<?php
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

$productId = filter_var($input['productId'] ?? 0, FILTER_VALIDATE_INT);
$quantityToAdd = filter_var($input['quantity'] ?? 0, FILTER_VALIDATE_INT); // Renamed for clarity
$colorId = isset($input['colorId']) ? filter_var($input['colorId'], FILTER_VALIDATE_INT) : null;
$sizeId = isset($input['sizeId']) ? filter_var($input['sizeId'], FILTER_VALIDATE_INT) : null;
$customColor = isset($input['customColor']) ? htmlspecialchars(trim($input['customColor']), ENT_QUOTES, 'UTF-8') : null;
$customSizeDetails = (isset($input['customSizeDetails']) && !empty($input['customSizeDetails'])) ? json_encode($input['customSizeDetails']) : null;

if (!$productId || $quantityToAdd < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product data provided.']);
    exit;
}

if ((!$colorId && !$customColor) || (!$sizeId && !$customSizeDetails)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Color and size options are required.']);
    exit;
}

$productStmt = $conn->prepare("SELECT price FROM panel_products WHERE id = ? AND visibility = 'show'");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit;
}
$price = $product['price'];
$priceForAddedItems = $price * $quantityToAdd; // Price of only the new quantity

$colorName = null;
$sizeName = null;

if ($colorId) {
    $colorStmt = $conn->prepare("SELECT name FROM colors WHERE id = ?");
    $colorStmt->execute([$colorId]);
    $colorResult = $colorStmt->fetch(PDO::FETCH_ASSOC);
    if ($colorResult) $colorName = $colorResult['name'];
}

if ($sizeId) {
    $sizeStmt = $conn->prepare("SELECT name FROM sizes WHERE id = ?");
    $sizeStmt->execute([$sizeId]);
    $sizeResult = $sizeStmt->fetch(PDO::FETCH_ASSOC);
    if ($sizeResult) $sizeName = $sizeResult['name'];
}

$userId = $_SESSION['user_id'] ?? null;

try {
    // --- NEW LOGIC: CHECK FOR EXISTING ITEM ---
    // The <=> (NULL-safe equals) operator is crucial here. It correctly compares values that might be NULL.
    $checkSql = "SELECT id, quantity, total_price FROM cart_items WHERE
                    cart_token = :cart_token AND
                    product_id = :product_id AND
                    color_name <=> :color_name AND
                    custom_color_name <=> :custom_color_name AND
                    size_name <=> :size_name AND
                    custom_size_details <=> :custom_size_details
                LIMIT 1";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([
        ':cart_token' => $cartToken,
        ':product_id' => $productId,
        ':color_name' => $colorName,
        ':custom_color_name' => $customColor,
        ':size_name' => $sizeName,
        ':custom_size_details' => $customSizeDetails
    ]);
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // -- ITEM EXISTS: UPDATE a RATHER THAN INSERT --
        $newQuantity = $existingItem['quantity'] + $quantityToAdd;
        $newTotalPrice = $existingItem['total_price'] + $priceForAddedItems;

        $updateSql = "UPDATE cart_items SET quantity = :quantity, total_price = :total_price WHERE id = :id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([
            ':quantity' => $newQuantity,
            ':total_price' => $newTotalPrice,
            ':id' => $existingItem['id']
        ]);
        $message = "Cart quantity updated!";

    } else {
        // -- ITEM DOES NOT EXIST: INSERT a NEW ROW (original logic) --
        $insertSql = "INSERT INTO cart_items (cart_token, user_id, product_id, quantity, color_name, custom_color_name, size_name, custom_size_details, total_price) VALUES (:cart_token, :user_id, :product_id, :quantity, :color_name, :custom_color_name, :size_name, :custom_size_details, :total_price)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([
            ':cart_token' => $cartToken, 
            ':user_id' => $userId, 
            ':product_id' => $productId, 
            ':quantity' => $quantityToAdd, 
            ':color_name' => $colorName, 
            ':custom_color_name' => $customColor, 
            ':size_name' => $sizeName, 
            ':custom_size_details' => $customSizeDetails, 
            ':total_price' => $priceForAddedItems
        ]);
        $message = "Item added to cart!";
    }

    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (PDOException $e) {
    error_log('Cart Add/Update Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}