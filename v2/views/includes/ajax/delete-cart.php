<?php
header('Content-Type: application/json');

// --- 1. VALIDATE REQUEST & GET CART TOKEN ---
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

// --- 2. GET & VALIDATE ITEM ID ---
$input = json_decode(file_get_contents('php://input'), true);
$cartItemId = filter_var($input['cartItemId'] ?? 0, FILTER_VALIDATE_INT);

if (!$cartItemId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID.']);
    exit;
}

// --- 3. DELETE ITEM FROM DATABASE ---
try {
    // We include `cart_token = ?` in the WHERE clause as a security measure.
    // This ensures a user can only delete items from their own cart.
    $sql = "DELETE FROM cart_items WHERE id = ? AND cart_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$cartItemId, $cartToken]);

    if ($stmt->rowCount() > 0) {
        // If a row was deleted, it was successful.
        echo json_encode(['status' => 'success', 'message' => 'Item removed from cart.']);
    } else {
        // If no rows were affected, it means the item wasn't found or didn't belong to this user.
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found in your cart.']);
    }

} catch (PDOException $e) {
    error_log("Delete Cart Item Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}