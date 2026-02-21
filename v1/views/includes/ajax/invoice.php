<?php
// require_once 'config/db.php'; 

// --- FETCH ORDER DATA FROM DATABASE ---
if (!isset($_GET['order_number'])) {
    die("No order specified.");
}
$orderNumber = $_GET['order_number'];

// Fetch the main order details and customer email
$orderStmt = $conn->prepare(
    "SELECT o.*, c.email as customer_email
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     WHERE o.order_number = ?"
);
$orderStmt->execute([$orderNumber]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Invoice not found.");
}

// Decode the shipping_address JSON to get customer details
$shippingDetails = json_decode($order['shipping_address'], true);

// --- MODIFIED SECTION: Extract all contact details including phone number ---
$customerName = $shippingDetails['fullName'] ?? 'N/A'; 
$customerPhone = $shippingDetails['phoneNumber'] ?? ''; // <-- Added phone number extraction
$fullAddress = ($shippingDetails['address'] ?? '') . ', ' . ($shippingDetails['city'] ?? '') . ', ' . ($shippingDetails['state'] ?? '') . ' ' . ($shippingDetails['zip'] ?? '') . ', ' . ($shippingDetails['country'] ?? '');
$customerEmail = $order['customer_email'];
// --- END MODIFIED SECTION ---

// Fetch order items and JOIN with panel_products to get the name and image_one.
$itemsStmt = $conn->prepare(
    "SELECT oi.*, pp.name as product_name, pp.image_one as product_image
     FROM order_items oi
     JOIN panel_products pp ON oi.product_id = pp.id
     WHERE oi.order_id = ?"
);
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Process items to create a clean options summary for display
foreach ($orderItems as &$item) { // Use reference '&' to modify the array directly
    $options = [];
    if (!empty($item['color_name'])) {
        $options[] = $item['color_name'];
    } elseif (!empty($item['custom_color_name'])) {
        $options[] = "Custom: " . $item['custom_color_name'];
    }

    if (!empty($item['size_name'])) {
        $options[] = $item['size_name'];
    } elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') {
        $options[] = "Custom Size";
    }
    // Add the generated summary string back into the item array
    $item['options_summary'] = implode(' / ', $options);
}
unset($item); // Unset the reference after the loop

// Assuming these variables are defined elsewhere for your header/footer
$logo_directory = $logo_directory ?? 'images/logo.png';
$site_name = $site_name ?? 'Your Site Name';
$site_address = $site_address ?? '123 Fashion Ave, Lagos, Nigeria';
$site_email = $site_email ?? 'contact@yoursite.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($order['order_number']) ?> - <?=$site_name?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Lato', sans-serif; background-color: #F3F4F6; }
        .invoice-container { max-width: 800px; margin: 2rem auto; background-color: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1); }
        .invoice-header h1 { font-family: ['Playfair Display', serif; }
        @media print {
            body { background-color: white; }
            .no-print { display: none; }
            .invoice-container { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

    <div class="no-print p-8 md:p-12">
        <div class="max-w-4xl mx-auto flex justify-between items-center px-4">
            <a href="/shop" class="text-blue-600 hover:text-blue-800 font-medium">← Continue Shopping</a>
            <button id="download-btn" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                Download PDF
            </button>
        </div>
    </div>
    
    <div id="invoice-content" class="invoice-container p-8 md:p-12">
        <header class="invoice-header flex justify-between items-start border-b pb-8">
            <div>
                <img src="<?= htmlspecialchars($logo_directory) ?>" alt="<?=$site_name?> Logo" class="h-12">
                <div class="mt-4 text-sm text-gray-600">
                    <p><?= htmlspecialchars($site_address) ?></p>
                    <p><?= htmlspecialchars($site_email) ?></p>
                </div>
            </div>
            <div class="text-right">
                <h1 class="text-3xl font-bold text-gray-800">INVOICE ID</h1>
                <p class="text-gray-500 mt-2">#<?= htmlspecialchars($order['order_number']) ?></p>
            </div>
        </header>

        <section class="grid md:grid-cols-2 gap-8 mt-8">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">Billed To</h2>
                <p class="font-semibold text-gray-800 mt-2"><?= htmlspecialchars($customerName) ?></p>
                <p class="text-gray-600"><?= nl2br(htmlspecialchars(trim($fullAddress, ', '))) ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($customerEmail) ?></p>
                
                <!-- ADDED: Display phone number if it exists -->
                <?php if (!empty($customerPhone)): ?>
                <p class="text-gray-600"><?= htmlspecialchars($customerPhone) ?></p>
                <?php endif; ?>
                <!-- END ADDED -->

            </div>
            <div class="text-right">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">Details</h2>
                <p class="mt-2"><span class="font-semibold text-gray-700">Order Date:</span> <?= date("F j, Y", strtotime($order['created_at'])) ?></p>
                <p><span class="font-semibold text-gray-700">Order Status:</span> <span class="bg-green-100 text-green-800 font-medium py-1 px-2 rounded-full text-xs"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span></p>
            </div>
        </section>

        <section class="mt-10">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4 text-sm font-semibold uppercase text-gray-600">Product</th>
                        <th class="p-4 text-sm font-semibold uppercase text-gray-600 text-center">Qty</th>
                        <th class="p-4 text-sm font-semibold uppercase text-gray-600 text-right">Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orderItems as $item): ?>
                    <tr class="border-b">
                        <td class="p-4">
                            <div class="flex items-center">
                                <?php if (!empty($item['product_image'])): ?>
                                <img src="<?= htmlspecialchars($item['product_image']) ?>" class="w-12 h-16 object-cover mr-4 rounded-md shadow-sm" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                <?php endif; ?>
                                <div>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['product_name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($item['options_summary']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-center text-gray-600"><?= $item['quantity'] ?></td>
                        <td class="p-4 text-right text-gray-600">₦<?= number_format($item['price_per_unit'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-8 flex justify-end">
            <div class="w-full max-w-xs">
                <div class="flex justify-between py-2"><span class="text-gray-600">Subtotal</span><span class="text-gray-800">₦<?= number_format($order['subtotal'], 2) ?></span></div>
                <div class="flex justify-between py-2"><span class="text-gray-600">Shipping</span><span class="text-gray-800">₦<?= number_format($order['shipping_fee'], 2) ?></span></div>
                <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <div class="flex justify-between py-2"><span class="text-gray-600">Discount</span><span class="text-red-500">-₦<?= number_format($order['discount_amount'], 2) ?></span></div>
                <?php endif; ?>
                <div class="border-t-2 mt-2 pt-2 flex justify-between">
                    <span class="font-bold text-gray-900 text-lg">Grand Total</span>
                    <span class="font-bold text-gray-900 text-lg">₦<?= number_format($order['grand_total'], 2) ?></span>
                </div>
            </div>
        </section>

        <footer class="mt-12 border-t pt-6 text-center text-sm text-gray-500">
            <p>Thank you for your business!</p>
            <p>If you have any questions, please contact us at your support email.</p>
        </footer>
    </div>

<script>
document.getElementById('download-btn').addEventListener('click', () => {
    const invoiceContent = document.getElementById('invoice-content');
    const orderNumber = '<?= htmlspecialchars($order['order_number']) ?>';
    
    const options = { margin: 0.5, filename: `Invoice-${orderNumber}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' } };
    
    html2pdf().from(invoiceContent).set(options).save();
});
</script>
</body>
</html>