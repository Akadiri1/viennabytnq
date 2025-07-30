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
    echo json_encode(['status' => 'error', 'message' => 'Cart identifier is missing. Please refresh the page.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$productId = filter_var($input['productId'] ?? 0, FILTER_VALIDATE_INT);
$quantity = filter_var($input['quantity'] ?? 0, FILTER_VALIDATE_INT);
$colorId = isset($input['colorId']) ? filter_var($input['colorId'], FILTER_VALIDATE_INT) : null;
$sizeId = isset($input['sizeId']) ? filter_var($input['sizeId'], FILTER_VALIDATE_INT) : null;
$customColor = isset($input['customColor']) ? htmlspecialchars(trim($input['customColor']), ENT_QUOTES, 'UTF-8') : null;
$customSizeJson = isset($input['customSizeDetails']) ? json_encode($input['customSizeDetails']) : null;

if (!$productId || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product data provided.']);
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
$totalPrice = $price * $quantity;

// --- NEW LOGIC: Convert IDs to Names ---
$colorName = null;
$sizeName = null;

// If a standard color ID was provided, fetch its name from the 'colors' table.
if ($colorId) {
    $colorStmt = $conn->prepare("SELECT name FROM colors WHERE id = ?");
    $colorStmt->execute([$colorId]);
    $colorResult = $colorStmt->fetch(PDO::FETCH_ASSOC);
    if ($colorResult) {
        $colorName = $colorResult['name'];
    }
}

// If a standard size ID was provided, fetch its name from the 'sizes' table.
if ($sizeId) {
    $sizeStmt = $conn->prepare("SELECT name FROM sizes WHERE id = ?");
    $sizeStmt->execute([$sizeId]);
    $sizeResult = $sizeStmt->fetch(PDO::FETCH_ASSOC);
    if ($sizeResult) {
        $sizeName = $sizeResult['name'];
    }
}
// --- END OF NEW LOGIC ---

$userId = $_SESSION['user_id'] ?? null;

try {
    // **FIX 1: Updated the SQL to use the new `_name` columns.**
    $sql = "INSERT INTO cart_items 
                (cart_token, user_id, product_id, quantity, color_name, custom_color_name, size_name, custom_size_details, total_price)
            VALUES
                (:cart_token, :user_id, :product_id, :quantity, :color_name, :custom_color_name, :size_name, :custom_size_details, :total_price)";

    $stmt = $conn->prepare($sql);

    // **FIX 2: Execute with the new name variables instead of the ID variables.**
    $stmt->execute([
        ':cart_token' => $cartToken,
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':quantity' => $quantity,
        ':color_name' => $colorName, // Use the fetched standard color name
        ':custom_color_name' => $customColor,
        ':size_name' => $sizeName, // Use the fetched standard size name
        ':custom_size_details' => $customSizeJson,
        ':total_price' => $totalPrice
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Item added to cart!']);

} catch (PDOException $e) {
    error_log('Cart Add Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Could not add item to cart.']);
}