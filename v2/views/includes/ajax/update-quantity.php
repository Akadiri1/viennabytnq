<?php
header('Content-Type: application/json');

// It's good practice to include your DB connection at the top.
// require_once 'includes/db_connection.php';

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
    // 1. Get the cart item's details, including its product_id and price_variant_id.
    $itemSql = "SELECT product_id, price_variant_id FROM cart_items WHERE id = ? AND cart_token = ?";
    $itemStmt = $conn->prepare($itemSql);
    $itemStmt->execute([$cartItemId, $cartToken]);
    $cartItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found in your cart.']);
        exit;
    }

    // 2. Determine the correct unit price based on the item's saved details.
    $unitPrice = null;

    if (!empty($cartItem['price_variant_id'])) {
        // Priority 1: This item HAS a price variant, so we MUST use its price.
        $priceStmt = $conn->prepare("SELECT price FROM product_price_variants WHERE id = ?");
        $priceStmt->execute([$cartItem['price_variant_id']]);
        $priceResult = $priceStmt->fetchColumn();
        if ($priceResult !== false) {
            $unitPrice = $priceResult;
        }
    }
    
    // Fallback: If no variant price was found (or the item had no variant), get the base price.
    if ($unitPrice === null) {
        $priceStmt = $conn->prepare("SELECT price FROM panel_products WHERE id = ?");
        $priceStmt->execute([$cartItem['product_id']]);
        $priceResult = $priceStmt->fetchColumn();
        if ($priceResult !== false) {
            $unitPrice = $priceResult;
        }
    }

    // Final check: If we still couldn't find a price, the product might have been deleted.
    if ($unitPrice === null) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Could not find the original product price. It may have been removed.']);
        exit;
    }

    // 3. Calculate the new total price using the correctly identified unit price.
    $newTotalPrice = $unitPrice * $newQuantity;

    // 4. Update the quantity and the new total price in the cart_items table.
    $updateSql = "UPDATE cart_items SET quantity = ?, total_price = ? WHERE id = ? AND cart_token = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$newQuantity, $newTotalPrice, $cartItemId, $cartToken]);

    // Check if the update was successful. It's better to treat 0 rows affected as success too,
    // in case the user clicked update without changing the quantity.
    echo json_encode(['status' => 'success', 'message' => 'Cart updated.']);

} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    error_log("Update Quantity Error: " . $errorMessage);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.', 'details' => $errorMessage]);
}
?>