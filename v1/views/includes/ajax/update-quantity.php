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
$cartItemId = filter_var($input['cartItemId'] ?? 0, FILTER_VALIDATE_INT);
$newQuantity = filter_var($input['quantity'] ?? 0, FILTER_VALIDATE_INT);

if (!$cartItemId || $newQuantity < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

try {
    // First, get the single unit price from the product table.
    // This is a security measure to prevent price tampering.
    $priceSql = "SELECT pp.price FROM cart_items ci JOIN panel_products pp ON ci.product_id = pp.id WHERE ci.id = ? AND ci.cart_token = ?";
    $priceStmt = $conn->prepare($priceSql);
    $priceStmt->execute([$cartItemId, $cartToken]);
    $product = $priceStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found in your cart.']);
        exit;
    }

    $unitPrice = $product['price'];
    $newTotalPrice = $unitPrice * $newQuantity;

    // Now, update the quantity and the total price in the cart_items table.
    $updateSql = "UPDATE cart_items SET quantity = ?, total_price = ? WHERE id = ? AND cart_token = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$newQuantity, $newTotalPrice, $cartItemId, $cartToken]);

    if ($updateStmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Cart updated.']);
    } else {
        // This case might happen if the item was already removed in another tab.
        echo json_encode(['status' => 'error', 'message' => 'Could not update item.']);
    }

} catch (PDOException $e) {
    error_log("Update Quantity Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}