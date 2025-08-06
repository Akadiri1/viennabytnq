<?php

define('OWNER_VIEW_SECRET_TOKEN', 'Vienna-Secret-Key-For-Viewing-Orders-789123');


function formatCustomSizeDetails($jsonString) {
    if (empty($jsonString)) {
        return '';
    }

    $details = json_decode($jsonString, true);

    if (!is_array($details) || json_last_error() !== JSON_ERROR_NONE) {
        return htmlspecialchars($jsonString);
    }

    $formattedParts = '';
    foreach ($details as $key => $value) {
        if (!empty($value)) {
            $prettyKey = htmlspecialchars(ucfirst(str_replace('_', ' ', $key)));
            $prettyValue = htmlspecialchars($value);
            $formattedParts .= "{$prettyKey}: {$prettyValue}<br>";
        }
    }
    return $formattedParts;
}

// Security check
if (!isset($_GET['order_number']) || !isset($_GET['token']) || $_GET['token'] !== OWNER_VIEW_SECRET_TOKEN) {
    http_response_code(403); 
    die('<h1>Access Denied</h1><p>You do not have permission to view this page.</p>');
}

$orderNumber = $_GET['order_number'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, c.full_name, c.email
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.order_number = ?
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<h1>Order Not Found</h1><p>The specified order could not be found.</p>');
}

// Fetch order items
$itemsStmt = $conn->prepare("
    SELECT oi.*, pp.name as product_name
    FROM order_items oi
    JOIN panel_products pp ON oi.product_id = pp.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Decode shipping address
$shippingDetails = json_decode($order['shipping_address'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | <?= htmlspecialchars($order['order_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280' } } } };
    </script>
    <style>
        .detail-card { background-color: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .detail-card h2 { font-size: 1.25rem; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 16px; }
        .detail-grid { display: grid; grid-template-columns: 150px 1fr; gap: 8px; }
        .detail-grid dt { font-weight: 500; color: #4b5563; }
    </style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold mb-8">Order Details</h1>
    
    <div class="detail-card">
        <h2>Order Summary</h2>
        <dl class="detail-grid">
            <dt>Order Number:</dt>
            <dd><?= htmlspecialchars($order['order_number']) ?></dd>
            
            <dt>Order Date:</dt>
            <dd><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></dd>
            
            <dt>Order Status:</dt>
            <dd class="font-semibold uppercase text-green-600"><?= htmlspecialchars($order['order_status']) ?></dd>
            
            <dt>Payment Reference:</dt>
            <dd><?= htmlspecialchars($order['payment_reference']) ?></dd>

            <dt>Total Amount:</dt>
            <dd class="font-bold text-lg">₦<?= number_format($order['grand_total'], 2) ?></dd>
        </dl>
    </div>

    <div class="detail-card">
        <h2>Customer Information</h2>
        <dl class="detail-grid">
            <dt>Full Name:</dt>
            <dd><?= htmlspecialchars($order['full_name']) ?></dd>
            
            <dt>Email Address:</dt>
            <dd><?= htmlspecialchars($order['email']) ?></dd>
            
            <dt>Phone Number:</dt>
            <dd><?= htmlspecialchars($shippingDetails['phoneNumber']) ?></dd>
        </dl>
    </div>

    <div class="detail-card">
        <h2>Shipping Address</h2>
        <address class="not-italic">
            <?= htmlspecialchars($shippingDetails['fullName']) ?><br>
            <?= htmlspecialchars($shippingDetails['address']) ?><br>
            <?php if (!empty($shippingDetails['addressLine2'])): ?>
                <?= htmlspecialchars($shippingDetails['addressLine2']) ?><br>
            <?php endif; ?>
            <?= htmlspecialchars($shippingDetails['city']) ?>, <?= htmlspecialchars($shippingDetails['state']) ?> <?= htmlspecialchars($shippingDetails['zip']) ?><br>
            <?= htmlspecialchars($shippingDetails['country']) ?>
        </address>
    </div>

    <div class="detail-card">
        <h2>Items Ordered</h2>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-3 font-semibold">Product</th>
                    <th class="p-3 font-semibold">Details</th>
                    <th class="p-3 font-semibold text-center">Quantity</th>
                    <th class="p-3 font-semibold text-right">Unit Price</th>
                    <th class="p-3 font-semibold text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orderItems as $item): ?>
                <tr class="border-b">
                    <td class="p-3 font-medium"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="p-3 text-sm text-gray-600">
                        <?php if(!empty($item['color_name'])) echo 'Color: '.htmlspecialchars($item['color_name']).'<br>'; ?>
                        <?php if(!empty($item['custom_color_name'])) echo 'Custom Color: '.htmlspecialchars($item['custom_color_name']).'<br>'; ?>
                        <?php if(!empty($item['size_name'])) echo 'Size: '.htmlspecialchars($item['size_name']).'<br>'; ?>
                        <?php 
                            if(!empty($item['custom_size_details'])) {
                                echo '<div class="mt-2 p-2 bg-gray-100 rounded border">
                                        <strong>Custom Size:</strong><br>' 
                                        . formatCustomSizeDetails($item['custom_size_details']) . 
                                     '</div>';
                            }
                        ?>
                    </td>
                    <td class="p-3 text-center"><?= $item['quantity'] ?></td>
                    <td class="p-3 text-right">₦<?= number_format($item['price_per_unit'], 2) ?></td>
                    <td class="p-3 text-right font-semibold">₦<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
