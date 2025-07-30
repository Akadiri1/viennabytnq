<?php
// We will always respond with JSON
header('Content-Type: application/json');

// --- 1. VALIDATE REQUEST & GET CART TOKEN ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get the unique cart identifier from the user's cookie
$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Cart identifier is missing. Please refresh the page.']);
    exit;
}

// --- 2. GET & VALIDATE INPUT DATA ---
$input = json_decode(file_get_contents('php://input'), true);

$productId = filter_var($input['productId'] ?? 0, FILTER_VALIDATE_INT);
$quantity = filter_var($input['quantity'] ?? 0, FILTER_VALIDATE_INT);
$colorId = isset($input['colorId']) ? filter_var($input['colorId'], FILTER_VALIDATE_INT) : null;
$sizeId = isset($input['sizeId']) ? filter_var($input['sizeId'], FILTER_VALIDATE_INT) : null;
$customColor = isset($input['customColor']) ? htmlspecialchars(trim($input['customColor']), ENT_QUOTES, 'UTF-8') : null;
$customSizeJson = isset($input['customSizeDetails']) ? json_encode($input['customSizeDetails']) : null;

// Basic validation check
if (!$productId || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product data provided.']);
    exit;
}

// --- 3. GET PRODUCT PRICE (from DB to prevent tampering) ---
$productStmt = $conn->prepare("SELECT price FROM panel_products WHERE id = ? AND visibility = 'show'");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit;
}
$price = $product['price'];

// --- 4. INSERT DATA INTO THE DATABASE ---
$userId = $_SESSION['user_id'] ?? null; // For future use with logged-in users

try {
    $sql = "INSERT INTO cart_items 
                (cart_token, user_id, product_id, quantity, color_id, custom_color_name, size_id, custom_size_details, total_price)
            VALUES
                (:cart_token, :user_id, :product_id, :quantity, :color_id, :custom_color_name, :size_id, :custom_size_details, :price)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':cart_token' => $cartToken,
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':quantity' => $quantity,
        ':color_id' => $colorId,
        ':custom_color_name' => $customColor,
        ':size_id' => $sizeId,
        ':custom_size_details' => $customSizeJson,
        ':price' => $price
    ]);
    
    // You could add logic here to merge cart items if the same configuration is added again.
    // For now, we add a new line item each time for simplicity.

    echo json_encode(['status' => 'success', 'message' => 'Item added to cart!']);

} catch (PDOException $e) {
    // Log the real error for debugging, but don't show it to the user
    error_log('Cart Add Error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Could not add item to cart.']);
}