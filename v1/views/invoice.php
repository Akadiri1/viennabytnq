<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: /admin_login"); 
    exit;
}

if (!isset($_GET['order_number'])) {
    die("Invalid Order Number");
}

$orderNumber = $_GET['order_number'];

// Handle direct download
// Handle direct download - JUST RENDER for JS to pick up
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    // Legacy support: redirect to mode=download_pdf or just let it flow
    // Ideally we don't force download headers anymore
}

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
    die('<h1>Order Not Found</h1>');
}

// Fetch order items
$itemsStmt = $conn->prepare("
    SELECT oi.*, pp.name as product_name, pp.image_one
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
    <title>Invoice #<?= htmlspecialchars($order['order_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            .page-break { page-break-inside: avoid; }
        }
        body { font-family: 'Inter', sans-serif; color: #1f2937; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-4xl mx-auto bg-white p-12 shadow-sm rounded-xl print:shadow-none print:p-0 print:max-w-none">
        <!-- Header -->
        <div class="flex justify-between items-start mb-12">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 tracking-tight">INVOICE</h1>
                <p class="text-gray-500 mt-2">#<?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-gray-900 mb-1">Vienna by TNQ</div>
                <div class="text-sm text-gray-500">
                    <p>admin@viennabytnq.com</p>
                    <p>Lagos, Nigeria</p>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-12 mb-12 border-b border-gray-100 pb-12">
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Billed To</h3>
                <div class="text-gray-900 font-medium"><?= htmlspecialchars($shippingDetails['fullName']) ?></div>
                <div class="text-gray-600 mt-1 text-sm leading-6">
                    <?= htmlspecialchars($shippingDetails['address']) ?><br>
                    <?php if (!empty($shippingDetails['addressLine2'])): ?>
                        <?= htmlspecialchars($shippingDetails['addressLine2']) ?><br>
                    <?php endif; ?>
                    <?= htmlspecialchars($shippingDetails['city']) ?>, <?= htmlspecialchars($shippingDetails['state']) ?><br>
                    <?= htmlspecialchars($shippingDetails['country']) ?>
                </div>
                <div class="text-gray-600 mt-2 text-sm"><?= htmlspecialchars($order['email']) ?></div>
                <div class="text-gray-600 text-sm"><?= htmlspecialchars($shippingDetails['phoneNumber']) ?></div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Payment Date</h3>
                    <p class="text-gray-900 font-medium"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Payment Ref</h3>
                    <p class="text-gray-900 font-medium truncate" title="<?= htmlspecialchars($order['payment_reference']) ?>"><?= htmlspecialchars($order['payment_reference']) ?></p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Payment Method</h3>
                    <p class="text-gray-900 font-medium">Online Payment</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-12">
            <thead>
                <tr class="text-left border-b border-gray-200">
                    <th class="pb-4 font-semibold text-gray-900 w-1/2">Item Description</th>
                    <th class="pb-4 font-semibold text-gray-900 text-center">Qty</th>
                    <th class="pb-4 font-semibold text-gray-900 text-right">Price</th>
                    <th class="pb-4 font-semibold text-gray-900 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach($orderItems as $item): ?>
                <tr class="border-b border-gray-100 last:border-0 page-break">
                    <td class="py-4 flex items-center gap-4">
                        <?php if (!empty($item['image_one'])): 
                              $imgUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($item['image_one'], '/');
                        ?>
                            <img src="<?= $imgUrl ?>" alt="" class="w-12 h-12 object-cover rounded border bg-gray-50 flex-shrink-0">
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
                            <div class="text-gray-500 mt-1 space-y-0.5">
                            <?php if(!empty($item['color_name'])) echo '<div>Color: '.htmlspecialchars($item['color_name']).'</div>'; ?>
                            <?php if(!empty($item['size_name'])) echo '<div>Size: '.htmlspecialchars($item['size_name']).'</div>'; ?>
                             <?php 
                                if(!empty($item['custom_size_details'])) {
                                    $details = json_decode($item['custom_size_details'], true);
                                    if(is_array($details)) {
                                        echo '<div class="mt-1 text-xs bg-gray-50 p-1 rounded inline-block">';
                                        foreach($details as $k => $v) {
                                            if(!empty($v)) echo ucfirst(str_replace('_', ' ', $k)) . ': ' . $v . ', ';
                                        }
                                        echo '</div>';
                                    }
                                }
                            ?>
                        </div>
                    </td>
                    <td class="py-4 text-center text-gray-600"><?= $item['quantity'] ?></td>
                    <td class="py-4 text-right text-gray-600">₦<?= number_format($item['price_per_unit'], 2) ?></td>
                    <td class="py-4 text-right text-gray-900 font-medium">₦<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end border-t border-gray-200 pt-8">
            <div class="w-64 space-y-3">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>₦<?= number_format($order['order_total_amount'], 2) // Assuming this is subtotal ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Shipping</span>
                    <span>₦<?= number_format($order['shipping_fee'], 2) ?></span>
                </div>
                <div class="flex justify-between text-xl font-bold text-gray-900 pt-4 border-t border-gray-100">
                    <span>Total</span>
                    <span>₦<?= number_format($order['grand_total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-16 text-center text-sm text-gray-500 print:mt-8">
            <p>Thank you for your business!</p>
            <p class="mt-1">For questions, contact us at help@viennabytnq.com</p>
        </div>

        <!-- Print Button -->
    <!-- Action Buttons -->
    <div class="fixed bottom-8 right-8 no-print flex gap-4">
        <button onclick="generatePDF()" class="bg-indigo-600 text-white px-6 py-3 rounded-full shadow-lg hover:bg-indigo-700 transition-colors font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Download PDF
        </button>
        <button onclick="window.print()" class="bg-gray-800 text-white px-6 py-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
            Print
        </button>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.querySelector('.max-w-4xl');
            const clone = element.cloneNode(true);
            const buttons = clone.querySelector('.no-print');
            if(buttons) buttons.remove();

            const opt = {
                margin:       [10, 10, 10, 10], 
                filename:     'Invoice-<?= htmlspecialchars($order['order_number']) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                // Check if this was an auto-download
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('mode') === 'download_pdf') {
                    // Optional: Close window after download (some browsers block this though)
                    // window.close(); 
                }
            });
        }

        // Auto-Download Trigger
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('mode') === 'download_pdf') {
                generatePDF();
            }
        });
    </script>
</body>
</html>
