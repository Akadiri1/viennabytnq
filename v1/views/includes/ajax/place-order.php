<?php
header('Content-Type: application/json');
// Function to send a standardized error response and exit
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Invalid request method.', 405);
}

$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) {
    send_json_error('Your session has expired or is invalid. Please refresh the page.');
}

$input = json_decode(file_get_contents('php://input'), true);

// --- 1. VALIDATE & SANITIZE CUSTOMER DATA ---
// MODIFIED: This section is updated to handle the nested shippingAddress object and the new phone number.

// Check for the presence of the main data structures
if (!isset($input['email']) || !isset($input['shippingAddress']) || !is_array($input['shippingAddress'])) {
    send_json_error('Incomplete form data. Please refresh and try again.');
}

$email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);

// Extract data from the nested shippingAddress object
$shippingData = $input['shippingAddress'];
$fullName = htmlspecialchars(trim($shippingData['fullName'] ?? ''), ENT_QUOTES, 'UTF-8');
$phoneNumber = htmlspecialchars(trim($shippingData['phoneNumber'] ?? ''), ENT_QUOTES, 'UTF-8'); // ADDED: Get the phone number
$address = htmlspecialchars(trim($shippingData['address'] ?? ''), ENT_QUOTES, 'UTF-8');
$city = htmlspecialchars(trim($shippingData['city'] ?? ''), ENT_QUOTES, 'UTF-8');
$state = htmlspecialchars(trim($shippingData['state'] ?? ''), ENT_QUOTES, 'UTF-8');
$zip = htmlspecialchars(trim($shippingData['zip'] ?? ''), ENT_QUOTES, 'UTF-8');
$country = htmlspecialchars(trim($shippingData['country'] ?? ''), ENT_QUOTES, 'UTF-8');

// Validate all required fields
if (!$email || empty($fullName) || empty($phoneNumber) || empty($address) || empty($city) || empty($state)) {
    send_json_error('Please fill in all required fields, including your phone number.');
}

// MODIFIED: Create the JSON string including the phone number
$shippingAddress = json_encode([
    'fullName' => $fullName,
    'phoneNumber' => $phoneNumber, // ADDED
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'country' => $country
]);

// --- 2. GET CART CONTENTS (No changes needed here) ---
$cartSql = "SELECT ci.*, pp.price FROM cart_items ci JOIN panel_products pp ON ci.product_id = pp.id WHERE ci.cart_token = ?";
$cartStmt = $conn->prepare($cartSql);
$cartStmt->execute([$cartToken]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    send_json_error('Your cart is empty.');
}

// --- 3. CALCULATE TOTALS (No changes needed here) ---
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

// --- 4. CREATE ORDER IN DATABASE (No changes needed here, it uses the modified $shippingAddress variable) ---
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
    // The $shippingAddress variable below now contains the JSON with the phone number
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

    // --- 5. RETURN DATA FOR PAYSTACK (No changes needed here) ---
    echo json_encode([
        'status' => 'success',
        'email' => $email,
        'amountKobo' => round($grandTotal * 100), // Use round() for safety with floats
        'reference' => $orderNumber
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Place Order Error: " . $e->getMessage());
    send_json_error('Could not place your order due to a server error.', 500);
}