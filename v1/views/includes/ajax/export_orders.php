<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Security Check: Only Admin/Owner
if (!isset($_SESSION['admin_logged_in'])) {
    die("Access Denied");
}

// Database Connection provided by index.php -> router.php
// require_once __DIR__ . '/../../../../config/model.php'; 
if (!isset($conn)) {
    // Fallback if accessed directly (though router should handle it)
    die("Database connection missing.");
}

// Filename
$filename = "All_Transaction_History_" . date('Y-m-d') . ".csv";

// Headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output Stream
$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, ['Order ID', 'Order Number', 'Customer Name', 'Email', 'Subtotal', 'Shipping', 'Total', 'Status', 'Date', 'Payment Ref']);

// Fetch All Orders
$sql = "SELECT o.id, o.order_number, c.full_name, c.email, o.subtotal, o.shipping_fee, o.grand_total, o.order_status, o.created_at, o.payment_reference 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        ORDER BY o.created_at DESC";
$stmt = $conn->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['order_number'],
        $row['full_name'],
        $row['email'],
        $row['subtotal'],
        $row['shipping_fee'],
        $row['grand_total'],
        strtoupper($row['order_status']),
        date('M j, Y h:i A', strtotime($row['created_at'])),
        $row['payment_reference']
    ]);
}

fclose($output);
exit;
