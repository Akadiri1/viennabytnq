<?php
require_once __DIR__.'/../includes/config.php';

$searchTerm = $_GET['term'] ?? '';

if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT id, name FROM panel_products WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->execute(["%$searchTerm%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($products);
}
?>
