<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['discountCode'] ?? '';
$subtotal = $input['subtotal'] ?? 0;

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a code.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = TRUE AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
$stmt->execute([$code]);
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

if ($discount) {
    $discountAmount = 0;
    if ($discount['discount_type'] == 'percentage') {
        $discountAmount = ($subtotal * $discount['discount_value']) / 100;
    } else {
        $discountAmount = $discount['discount_value'];
    }
    echo json_encode(['status' => 'success', 'discountAmount' => $discountAmount]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired discount code.']);
}