<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... handle error ... */ exit; }

$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) { /* ... handle error ... */ exit; }

$input = json_decode(file_get_contents('php://input'), true);

// --- 1. VALIDATE & SANITIZE CUSTOMER DATA ---
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$fullName = htmlspecialchars(trim($input['fullName'] ?? ''), ENT_QUOTES, 'UTF-8');
// ... add validation for all other address fields ...
if (!$email || !$fullName) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}
$shippingAddress = json_encode([
    'fullName' => $fullName, 'address' => $input['address'], 'city' => $input['city'],
    'state' => $input['state'], 'zip' => $input['zip'], 'country' => $input['country']
]);

// --- 2. GET CART CONTENTS ---
$cartSql = "SELECT ci.*, pp.price FROM cart_items ci JOIN panel_products pp ON ci.product_id = pp.id WHERE ci.cart_token = ?";
$cartStmt = $conn->prepare($cartSql);
$cartStmt->execute([$cartToken]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Your cart is empty.']);
    exit;
}

// --- 3. CALCULATE TOTALS ---
$subtotal = array_sum(array_column($cartItems, 'total_price'));
$shippingFee = 0;
$discountAmount = 0;

// Fetch shipping fee
if (!empty($input['shippingId'])) {
    $shippingStmt = $conn->prepare("SELECT fee FROM shipping_fees WHERE id = ? AND is_active = TRUE");
    $shippingStmt->execute([$input['shippingId']]);
    $shippingFee = $shippingStmt->fetchColumn() ?: 0;
}

// Validate and apply discount
if (!empty($input['discountCode'])) {
    $discountStmt = $conn->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = TRUE AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $discountStmt->execute([$input['discountCode']]);
    $discount = $discountStmt->fetch(PDO::FETCH_ASSOC);
    if ($discount) {
        if ($discount['discount_type'] == 'percentage') {
            $discountAmount = ($subtotal * $discount['discount_value']) / 100;
        } else {
            $discountAmount = $discount['discount_value'];
        }
    }
}

$grandTotal = ($subtotal - $discountAmount) + $shippingFee;

// --- 4. CREATE ORDER IN DATABASE ---
$conn->beginTransaction();
try {
    // Find or create customer
    $customerStmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $customerStmt->execute([$email]);
    $customerId = $customerStmt->fetchColumn();
    if (!$customerId) {
        $insertCustomerStmt = $conn->prepare("INSERT INTO customers (email, full_name) VALUES (?, ?)");
        $insertCustomerStmt->execute([$email, $fullName]);
        $customerId = $conn->lastInsertId();
    }

    // Create a unique order number
    $orderNumber = 'VN-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Insert into orders table
    $orderSql = "INSERT INTO orders (customer_id, order_number, subtotal, shipping_fee, discount_amount, grand_total, shipping_address, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$customerId, $orderNumber, $subtotal, $shippingFee, $discountAmount, $grandTotal, $shippingAddress]);
    $orderId = $conn->lastInsertId();

    // Insert into order_items table
    $orderItemsSql = "INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, color_name, custom_color_name, size_name, custom_size_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $orderItemsStmt = $conn->prepare($orderItemsSql);
    foreach ($cartItems as $item) {
        $unitPrice = $item['total_price'] / $item['quantity'];
        $orderItemsStmt->execute([$orderId, $item['product_id'], $item['quantity'], $unitPrice, $item['color_name'], $item['custom_color_name'], $item['size_name'], $item['custom_size_details']]);
    }

    $conn->commit();

    // --- 5. RETURN DATA FOR PAYSTACK ---
    echo json_encode([
        'status' => 'success',
        'email' => $email,
        'amountKobo' => $grandTotal * 100, // Paystack requires amount in Kobo
        'reference' => $orderNumber
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Place Order Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not place order.']);
}