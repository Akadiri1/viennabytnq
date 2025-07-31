<?php
$reference = $_GET['reference'] ?? null;
if (!$reference) {
    header("Location: /checkout?error=invalid_reference");
    exit;
}

// IMPORTANT: Replace with your Paystack SECRET Key
$paystackSecretKey = 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxx';

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer " . $paystackSecretKey,
        "cache-control: no-cache"
    ],
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    // cURL error
    header("Location: /checkout?error=verification_failed");
    exit;
}

$result = json_decode($response);

if ($result->status && $result->data->status == 'success') {
    // Payment was successful
    $conn->beginTransaction();
    try {
        // Update order status to 'paid'
        $orderStmt = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_reference = ? WHERE order_number = ?");
        $orderStmt->execute([$reference, $reference]);

        // Clear the user's cart
        $cartToken = $_COOKIE['cart_token'] ?? null;
        if ($cartToken) {
            $clearCartStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_token = ?");
            $clearCartStmt->execute([$cartToken]);
        }

        $conn->commit();
        
        // Redirect to a success page
        header("Location: /order-success?ref=" . $reference);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        // Log the error but redirect user to a safe page
        error_log("Payment Verification DB Error: " . $e->getMessage());
        header("Location: /checkout?error=db_error");
        exit;
    }
} else {
    // Payment was not successful
    header("Location: /checkout?error=payment_failed");
    exit;
}