<?php
// PAGE SETUP & SECURITY - Ensure your DB connection and session start are here.
// require 'config/db_connection.php';
// require 'functions.php';
// session_start();

if (!isset($_GET['ref']) || !isset($_SESSION['last_order_ref']) || $_GET['ref'] !== $_SESSION['last_order_ref']) {
    header("Location: /shop");
    exit;
}

$orderRef = $_SESSION['last_order_ref'];

// =================================================================
// WHATSAPP NOTIFICATION FUNCTIONS (USING ULTRAMSG)
// =================================================================

/**
 * Main function to format and send an entire order notification to WhatsApp.
 * MODIFIED: Now takes an $orderDetails array for accurate financial totals.
 */
function sendOrderNotificationToWhatsApp(array $orderItems, array $config, array $orderDetails, array $buyerDetails = []) {
    if (empty($orderItems) || empty($config['recipient_numbers']) || empty($orderDetails)) {
        error_log("WhatsApp Error: Missing items, recipients, or order details.");
        return;
    }

    $orderNumber = $orderDetails['order_number'];

    // --- Build main message (This is done once) ---
    $message = "🎉 *New Order Received!* 🎉\n";
    $message .= "_Order #: " . htmlspecialchars($orderNumber) . "_\n\n";
    $message .= "-----------------------------------\n\n";

    $itemCounter = 1;
    
    // Build the text for all items first
    $itemsText = "";
    foreach ($orderItems as $item) {
        $itemsText .= "*Item " . $itemCounter++ . ": " . htmlspecialchars($item['product_name']) . "*\n";
        $itemsText .= "- _Quantity:_ " . $item['quantity'] . "\n";
        $itemsText .= "- _Price:_ ₦" . number_format($item['price_per_unit'], 2) . " each\n";

        if (!empty($item['color_name'])) $itemsText .= "- _Color:_ " . htmlspecialchars($item['color_name']) . "\n";
        if (!empty($item['custom_color_name'])) $itemsText .= "- _Color (Custom):_ " . htmlspecialchars($item['custom_color_name']) . "\n";
        if (!empty($item['size_name'])) $itemsText .= "- _Size:_ " . htmlspecialchars($item['size_name']) . "\n";
        if (!empty($item['custom_size_details'])) { /* You can add more detailed formatting here if needed */ }
        $itemsText .= "\n";
    }
    $message .= $itemsText;

    // --- MODIFIED: Build the final financial summary from $orderDetails ---
    $message .= "-----------------------------------\n";
    $message .= "Subtotal: ₦" . number_format($orderDetails['subtotal'], 2) . "\n";
    $message .= "Shipping: ₦" . number_format($orderDetails['shipping_fee'], 2) . "\n";

    if (!empty($orderDetails['discount_amount']) && $orderDetails['discount_amount'] > 0) {
        $message .= "Discount: -₦" . number_format($orderDetails['discount_amount'], 2) . "\n";
    }
    
    $message .= "*Grand Total: ₦" . number_format($orderDetails['grand_total'], 2) . "*\n\n";
    // --- END MODIFIED ---

    // --- MODIFIED: Add Buyer Information including Phone Number ---
    if (!empty($buyerDetails)) {
        $message .= "👤 *Buyer Information:*\n";
        if (!empty($buyerDetails['name'])) {
            $message .= "- Name: " . htmlspecialchars($buyerDetails['name']) . "\n";
        }
        if (!empty($buyerDetails['phone'])) {
            $message .= "- Phone: " . htmlspecialchars($buyerDetails['phone']) . "\n";
        }
        if (!empty($buyerDetails['address'])) {
            $message .= "- Address: " . htmlspecialchars($buyerDetails['address']) . "\n";
        }
        $message .= "\n";
    }
    // --- END MODIFIED ---

    $viewOrderUrl = rtrim($config['website_url'], '/') . '/order-view?order_number=' . urlencode($orderNumber) . '&token=' . urlencode(OWNER_VIEW_SECRET_TOKEN);
    $message .= "*View Full Details:*\n" . $viewOrderUrl;

    // Loop through each recipient and send the full notification
    foreach ($config['recipient_numbers'] as $recipient) {
        // 1. Send all images to the current recipient
        foreach ($orderItems as $item) {
            $imageUrl = rtrim($config['website_url'], '/') . '/' . ltrim($config['images_path'], '/') . $item['product_image'];
            $caption = htmlspecialchars($item['product_name']);
            sendWhatsAppImage($config, $recipient, $imageUrl, $caption);
        }

        // 2. Send the final, complete text message to the current recipient
        sendWhatsAppMessage($config, $recipient, $message);
    }
}

/**
 * Sends a text-only message to a specific recipient.
 */
function sendWhatsAppMessage(array $config, string $recipient, string $text) {
    $endpoint = "https://api.ultramsg.com/" . $config['ultramsg_instance_id'] . "/messages/chat";
    $data = ['token' => $config['ultramsg_token'], 'to' => $recipient, 'body' => $text, 'priority' => 10];
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $endpoint, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Sends an image message to a specific recipient.
 */
function sendWhatsAppImage(array $config, string $recipient, string $imageUrl, string $caption = '') {
    $endpoint = "https://api.ultramsg.com/" . $config['ultramsg_instance_id'] . "/messages/image";
    $data = ['token' => $config['ultramsg_token'], 'to' => $recipient, 'image' => $imageUrl, 'caption' => $caption, 'priority' => 5];
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $endpoint, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
    curl_exec($ch);
    curl_close($ch);
}

// =================================================================
// WHATSAPP NOTIFICATION TRIGGER LOGIC
// =================================================================

define('OWNER_VIEW_SECRET_TOKEN', 'Vienna-Secret-Key-For-Viewing-Orders-789123');

$whatsappConfig = [
    'recipient_numbers' => ['+2349010035033'],
    'website_url'         => 'https://viennabytnq.com',
    'images_path'         => '/uploads/',
    'ultramsg_instance_id' => 'instance137057',
    'ultramsg_token'       => 'nj5z4gaollvjn0y1'
];

try {
    // MODIFIED: Fetch the entire order record to get totals and shipping address
    $orderStmt = $conn->prepare("SELECT * FROM orders WHERE payment_reference = ?");
    $orderStmt->execute([$orderRef]);
    $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if ($orderResult) {
        $orderId = $orderResult['id'];
        
        // Prepare the necessary data arrays
        $buyerShippingDetails = json_decode($orderResult['shipping_address'], true);
        $buyerInfoForWhatsApp = [
            'name'    => $buyerShippingDetails['fullName'] ?? 'N/A',
            'phone'   => $buyerShippingDetails['phoneNumber'] ?? 'N/A',
            'address' => $buyerShippingDetails['address'] ?? 'N/A'
        ];

        $orderDetailsForWhatsApp = [
            'order_number'    => $orderResult['order_number'],
            'subtotal'        => $orderResult['subtotal'],
            'shipping_fee'    => $orderResult['shipping_fee'],
            'discount_amount' => $orderResult['discount_amount'],
            'grand_total'     => $orderResult['grand_total'],
        ];

        // Fetch all items for the order
        $itemsStmt = $conn->prepare("
            SELECT oi.*, pp.name AS product_name, pp.image_one AS product_image
            FROM order_items AS oi
            JOIN panel_products AS pp ON oi.product_id = pp.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $orderItemsForNotification = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Call the function with all required pieces of information
        if (!empty($orderItemsForNotification)) {
            sendOrderNotificationToWhatsApp($orderItemsForNotification, $whatsappConfig, $orderDetailsForWhatsApp, $buyerInfoForWhatsApp);
        }
    }
} catch (Exception $e) {
    error_log('WhatsApp Notification Script Failed: ' . $e->getMessage());
}

// =================================================================
// CUSTOMER-FACING PAGE LOGIC
// =================================================================

// This fetch is now only for the customer-facing part of the page
$stmt = $conn->prepare(
    "SELECT o.order_number, o.shipping_address, c.email
     FROM orders o JOIN customers c ON o.customer_id = c.id
     WHERE o.payment_reference = ? AND o.order_status = 'paid'"
);
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: /shop");
    exit;
}

$shippingDetails = json_decode($order['shipping_address'], true);
$customerName = $shippingDetails['fullName'] ?? 'Valued Customer';
$customerEmail = $order['email'];

unset($_SESSION['last_order_ref']);

$logo_directory = $logo_directory ?? 'path/to/your/logo.png';
$site_name = $site_name ?? 'Your Site Name';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <title><?=$site_name?> | Order Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', }, fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<main class="bg-brand-bg">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 md:p-12 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <i data-feather="check" class="w-8 h-8 text-green-600"></i>
            </div>
            <h1 class="mt-6 text-3xl md:text-4xl font-serif font-semibold text-brand-text">Thank you for your order!</h1>
            <p class="mt-4 text-brand-gray">
                Hi <?= htmlspecialchars(explode(' ', $customerName)[0]) ?>, your order has been confirmed. A confirmation email with the full details has been sent to <span class="font-medium text-brand-text"><?= htmlspecialchars($customerEmail) ?></span>.
            </p>
            <div class="mt-8 bg-brand-bg border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-brand-gray">Your Order Number is:</p>
                <p class="text-xl font-bold font-mono tracking-wider text-brand-text mt-1"><?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                <a href="/shop" class="w-full sm:w-auto bg-brand-text text-white py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-800 transition-colors">
                    Continue Shopping
                </a>
                <a href="/invoice?order_number=<?= urlencode($order['order_number']) ?>" target="_blank" class="w-full sm:w-auto bg-transparent text-brand-text border border-gray-300 py-3 px-6 rounded-md text-sm font-semibold hover:bg-gray-100 transition-colors">
                    Download Invoice
                </a>
            </div>
        </div>
    </div>
</main>
<footer class="bg-white border-t border-gray-200">
    <div class="p-6 text-center">
        <p class="text-xs text-brand-gray">© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p>
    </div>
</footer>
<script>
    feather.replace();
</script>
</body>
</html>