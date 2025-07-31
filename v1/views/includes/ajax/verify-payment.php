<?php
// You can leave error reporting on for now during development,
// but you should turn it off or log to a file on a live server.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$reference = $_GET['reference'] ?? null;
if (!$reference) {
    header("Location: /checkout?error=invalid_reference");
    exit;
}

// IMPORTANT: Replace with your Paystack SECRET Key
$paystackSecretKey = 'sk_test_2bde24977af50f9194874aededbee9236eed8947';

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
    // This will catch cURL errors if they happen again in the future.
    error_log("cURL Verification Error: " . $err);
    header("Location: /checkout?error=verification_failed_curl");
    exit;
}

$result = json_decode($response);

// A much safer check to prevent errors if the response is not what we expect
if (isset($result->status) && $result->status == true && isset($result->data->status) && $result->data->status == 'success') {
    // Payment was successful
    $conn->beginTransaction();
    try {
        // Update order status to 'paid' and set the payment reference
        $orderStmt = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_reference = ? WHERE order_number = ? AND order_status = 'pending'");
        $orderStmt->execute([$reference, $reference]);

        // Only clear the cart if the order was actually updated.
        // This prevents clearing the cart if a user refreshes the success page.
        if ($orderStmt->rowCount() > 0) {
            $cartToken = $_COOKIE['cart_token'] ?? null;
            if ($cartToken) {
                $clearCartStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_token = ?");
                $clearCartStmt->execute([$cartToken]);
            }
            // (Optional) Deduct stock here if you chose that strategy.
        }
        
        $conn->commit();
        
        // Set a session variable to grant one-time access to the success page
        $_SESSION['last_order_ref'] = $reference;
        header("Location: /order-success?ref=" . $reference);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Payment Verification DB Error: " . $e->getMessage());
        header("Location: /checkout?error=db_error");
        exit;
    }
} else {
    // This will happen if Paystack returns a "failed" status or an error message.
    header("Location: /checkout?error=payment_failed_paystack");
    exit;
}