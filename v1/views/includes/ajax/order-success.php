<?php
// =================================================================
// WHATSAPP NOTIFICATION FUNCTIONS (USING ULTRAMSG)
// =================================================================

/**
 * [FINAL VERSION] Main function to format and send an entire order notification to WhatsApp.
 * It now includes a secure link for the owner to view full details.
 */
function sendOrderNotificationToWhatsApp(array $orderItems, array $config, array $buyerDetails = []) {
    if (empty($orderItems)) {
        error_log("WhatsApp Error: Attempted to send notification with no order items.");
        return;
    }

    $orderNumber = $orderItems[0]['order_number'];

    // --- Build main message ---
    $message = "🎉 *New Order Received!* 🎉\n";
    $message .= "_Order #: " . htmlspecialchars($orderNumber) . "_\n\n";
    $message .= "-----------------------------------\n\n";

    $itemCounter = 1;
    $totalValue = 0;

    foreach ($orderItems as $item) {
        $imageUrl = rtrim($config['website_url'], '/') . '/' . ltrim($config['images_path'], '/') . $item['product_image'];
        $caption = htmlspecialchars($item['product_name']);
        
        // Send image
        $imageResponse = sendWhatsAppImage($config, $imageUrl, $caption);
        error_log("WhatsApp Image API Response: " . $imageResponse);

        $message .= "*Item " . $itemCounter++ . ": " . htmlspecialchars($item['product_name']) . "*\n";
        $message .= "- _Quantity:_ " . $item['quantity'] . "\n";
        $message .= "- _Price:_ ₦" . number_format($item['price_per_unit'], 2) . " each\n";

        if (!empty($item['color_name'])) {
            $message .= "- _Color:_ " . htmlspecialchars($item['color_name']) . "\n";
        }
        if (!empty($item['custom_color_name'])) {
            $message .= "- _Color (Custom):_ " . htmlspecialchars($item['custom_color_name']) . "\n";
        }
        if (!empty($item['size_name'])) {
            $message .= "- _Size:_ " . htmlspecialchars($item['size_name']) . "\n";
        }

        // ✅ Format custom size JSON
        if (!empty($item['custom_size_details'])) {
            $details = json_decode($item['custom_size_details'], true);
            if (is_array($details)) {
                $message .= "- _Size (Custom):_\n";
                foreach ($details as $key => $value) {
                    if (!empty($value)) {
                        $prettyKey = ucfirst(str_replace('_', ' ', $key));
                        $message .= "   • {$prettyKey}: {$value}\n";
                    }
                }
            } else {
                $message .= "- _Size (Custom):_ " . htmlspecialchars($item['custom_size_details']) . "\n";
            }
        }

        $message .= "\n";
        $totalValue += $item['quantity'] * $item['price_per_unit'];
    }

    $message .= "-----------------------------------\n";
    $message .= "*Total Order Value: ₦" . number_format($totalValue, 2) . "*\n\n";

    // ✅ Add buyer details if provided
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

    // ✅ Secure order view link
    $viewOrderUrl = rtrim($config['website_url'], '/') . 
                    '/order-view?order_number=' . urlencode($orderNumber) . 
                    '&token=' . urlencode(OWNER_VIEW_SECRET_TOKEN);

    $message .= "*View Full Details:*\n" . $viewOrderUrl;

    // Send text message
    $textResponse = sendWhatsAppMessage($config, $message);
    error_log("WhatsApp Text API Response: " . $textResponse);
}

/**
 * Sends a text-only message and returns the API response for debugging.
 */
function sendWhatsAppMessage(array $config, string $text) {
    $endpoint = "https://api.ultramsg.com/" . $config['ultramsg_instance_id'] . "/messages/chat";
    $data = [
        'token' => $config['ultramsg_token'],
        'to' => $config['recipient_number'],
        'body' => $text,
        'priority' => 10
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return "cURL Error: " . $error;
    return $response;
}

/**
 * Sends an image message and returns the API response for debugging.
 */
function sendWhatsAppImage(array $config, string $imageUrl, string $caption = '') {
    $endpoint = "https://api.ultramsg.com/" . $config['ultramsg_instance_id'] . "/messages/image";
    $data = [
        'token' => $config['ultramsg_token'],
        'to' => $config['recipient_number'],
        'image' => $imageUrl,
        'caption' => $caption,
        'priority' => 5
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return "cURL Error: " . $error;
    return $response;
}

// =================================================================
// PAGE LOGIC STARTS HERE
// =================================================================

// This page should only be accessible right after a successful payment.
if (!isset($_GET['ref']) || !isset($_SESSION['last_order_ref']) || $_GET['ref'] !== $_SESSION['last_order_ref']) {
    header("Location: /shop");
    exit;
}

$orderRef = $_SESSION['last_order_ref'];

// =================================================================
// ==> START: WHATSAPP NOTIFICATION TRIGGER
// =================================================================

// --- 1. DEFINE A SECRET TOKEN FOR THE OWNER'S VIEW LINK ---
// IMPORTANT: Keep this secret. This is your key to the order-view.php page.
define('OWNER_VIEW_SECRET_TOKEN', ' Vienna-Secret-Key-For-Viewing-Orders-789123');

// --- 2. CONFIGURE YOUR WHATSAPP DETAILS HERE ---
$whatsappConfig = [
    // CRITICAL: Your number must be in international format.
    // I have used +234 for Nigeria as an example. Change it if you are in a different country.
    'recipient_number'    => '+2349010035033',
    
    'website_url'         => 'https://viennabytnq.com',
    'images_path'         => '/uploads/',
    
    // YOUR REAL CREDENTIALS FROM ULTRAMSG
    'ultramsg_instance_id' => 'instance137057',
    'ultramsg_token'       => 'nj5z4gaollvjn0y1'
];

// We use a try-catch block so that if the notification fails,
// it does not break the success page for the customer.
try {
    // This assumes $conn is your PDO database connection object.
    $orderIdStmt = $conn->prepare("SELECT id FROM orders WHERE payment_reference = ?");
    $orderIdStmt->execute([$orderRef]);
    $orderIdResult = $orderIdStmt->fetch(PDO::FETCH_ASSOC);

    if ($orderIdResult) {
        $orderId = $orderIdResult['id'];
        $itemsStmt = $conn->prepare("
            SELECT
                o.order_number,
                oi.quantity,
                oi.price_per_unit,
                oi.color_name,
                oi.custom_color_name,
                oi.size_name,
                oi.custom_size_details,
                pp.name AS product_name,
                pp.image_one AS product_image
            FROM order_items AS oi
            JOIN panel_products AS pp ON oi.product_id = pp.id
            JOIN orders AS o ON oi.order_id = o.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $orderItemsForNotification = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($orderItemsForNotification)) {
            sendOrderNotificationToWhatsApp($orderItemsForNotification, $whatsappConfig);
        } else {
             error_log("WhatsApp Notice: Order found, but no items associated with order ID: " . $orderId);
        }
    } else {
        error_log("WhatsApp Error: Could not find order with payment reference: " . $orderRef);
    }
} catch (Exception $e) {
    error_log('WhatsApp Notification Script Failed: ' . $e->getMessage());
}
// =================================================================
// ==> END: WHATSAPP NOTIFICATION TRIGGER
// =================================================================


// --- Continue with your original code for the customer-facing page ---
$stmt = $conn->prepare(
    "SELECT o.order_number, o.shipping_address, c.email
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     WHERE o.payment_reference = ? AND o.order_status = 'paid'"
);
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    // Redirect if the order can't be found (e.g., payment failed)
    header("Location: /shop");
    exit;
}

$shippingDetails = json_decode($order['shipping_address'], true);
$customerName = $shippingDetails['fullName'] ?? 'Valued Customer';
$customerEmail = $order['email'];

// Unset the session variable to prevent re-triggering on refresh.
unset($_SESSION['last_order_ref']);

// Fetch breadcrumb image from DB
$productBreadcrumb = selectContent($conn, "product_breadcrumb", ['visibility' => 'show']);
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
      tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', 'brand-red': '#EF4444', }, fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], } } } };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-brand-bg font-sans text-brand-text">
<!-- MAIN SUCCESS CONTENT -->
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

<!-- FOOTER -->
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