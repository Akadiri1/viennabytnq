<?php
header('Content-Type: application/json');

// Get the unique cart identifier from the user's cookie
$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) {
    // If there's no token, the cart is empty. Send an empty response.
    echo json_encode(['items' => [], 'subtotal' => 0, 'totalItemCount' => 0]);
    exit;
}

try {
    // This SQL query joins the cart items with the products table to get product name and image.
    $sql = "SELECT 
                ci.id, 
                ci.quantity, 
                ci.total_price, 
                (ci.total_price / ci.quantity) AS unit_price,
                ci.color_name, 
                ci.custom_color_name, 
                ci.size_name, 
                ci.custom_size_details,
                pp.name AS product_name,
                pp.image_one AS product_image
            FROM cart_items ci
            JOIN panel_products pp ON ci.product_id = pp.id
            WHERE ci.cart_token = ?
            ORDER BY ci.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$cartToken]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals on the server
    $subtotal = 0;
    $totalItemCount = 0;
    foreach ($items as $item) {
        $subtotal += $item['total_price'];
        $totalItemCount += $item['quantity'];
    }

    // Send the complete cart data back to the JavaScript
    echo json_encode([
        'status' => 'success',
        'items' => $items,
        'subtotal' => $subtotal,
        'totalItemCount' => $totalItemCount
    ]);

} catch (PDOException $e) {
    error_log("Get Cart Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve cart data.']);
}