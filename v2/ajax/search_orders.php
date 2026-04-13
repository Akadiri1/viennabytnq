<?php
require_once __DIR__.'/../includes/config.php';

$searchTerm = $_GET['term'] ?? '';

if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT order_number, full_name FROM orders o JOIN customers c ON o.customer_id = c.id WHERE order_number LIKE ? OR full_name LIKE ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($orders);
}
?>
