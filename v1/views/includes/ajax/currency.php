<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$newCurrency = $input['currency'] ?? null;

if ($newCurrency === 'USD' || $newCurrency === 'NGN') {
    $_SESSION['currency'] = $newCurrency;
    echo json_encode(['status' => 'success', 'message' => 'Currency set to ' . $newCurrency]);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid currency.']);
}
?>