<?php
// =================================================================
// WHATSAPP NOTIFICATION FUNCTIONS
// It's good practice to keep these in a separate 'require_once' file,
// but for a single-file solution, they are placed here at the top.
// =================================================================

/**
 * Main function to format and send an entire order notification to WhatsApp.
 * @param array $orderItems An array of items fetched from your database.
 * @param array $config     Configuration array with all necessary credentials and paths.
 */
function sendOrderNotificationToWhatsApp(array $orderItems, array $config) {
    if (empty($orderItems)) {
        return; // Exit if there are no items to process
    }

    // Use the human-readable order_number for the message, which is more user-friendly.
    $orderNumber = $orderItems[0]['order_number'];

    // --- Build the main text message ---
    $message = "🎉 *New Order Received!* 🎉\n";
    $message .= "_Order #: " . htmlspecialchars($orderNumber) . "_\n\n";
    $message .= "-----------------------------------\n\n";

    $itemCounter = 1;
    $totalValue = 0;

    foreach ($orderItems as $item) {
        // --- Prepare and send the image for this item FIRST ---
        $imageUrl = rtrim($config['website_url'], '/') . '/' . ltrim($config['images_path'], '/') . $item['product_image'];
        $caption = htmlspecialchars($item['product_name']);
        
        // This function will send one image message per item
        sendWhatsAppImage($config, $imageUrl, $caption);

        // --- Append item details to the main text message ---
        $message .= "*Item " . $itemCounter++ . ": " . htmlspecialchars($item['product_name']) . "*\n";
        $message .= "- _Quantity:_ " . $item['quantity'] . "\n";
        $message .= "- _Price:_ $" . number_format($item['price_per_unit'], 2) . " each\n";

        if (!empty($item['color_name'])) $message .= "- _Color:_ " . htmlspecialchars($item['color_name']) . "\n";
        if (!empty($item['custom_color_name'])) $message .= "- _Color (Custom):_ " . htmlspecialchars($item['custom_color_name']) . "\n";
        if (!empty($item['size_name'])) $message .= "- _Size:_ " . htmlspecialchars($item['size_name']) . "\n";
        if (!empty($item['custom_size_details'])) $message .= "- _Size (Custom):_ " . htmlspecialchars($item['custom_size_details']) . "\n";
        
        $message .= "\n"; // Add a space between items

        $totalValue += $item['quantity'] * $item['price_per_unit'];
    }

    $message .= "-----------------------------------\n";
    $message .= "*Total Order Value: $" . number_format($totalValue, 2) . "*";

    // --- Send the final consolidated text message with all details ---
    sendWhatsAppMessage($config, $message);
}

/**
 * Sends a text-only message using the Twilio API.
 */
function sendWhatsAppMessage($config, $text) {
    $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . $config['twilio_sid'] . "/Messages.json";
    $data = [
        'To' => $config['recipient_number'],
        'From' => $config['twilio_number'],
        'Body' => $text
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['twilio_sid'] . ":" . $config['twilio_token']);
    curl_exec($ch);
    // In a real app, you might check curl_getinfo($ch, CURLINFO_HTTP_CODE) and the response body for errors.
    curl_close($ch);
}

/**
 * Sends an image message using the Twilio API.
 */
function sendWhatsAppImage($config, $imageUrl, $caption = '') {
    $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . $config['twilio_sid'] . "/Messages.json";
    $data = [
        'To' => $config['recipient_number'],
        'From' => $config['twilio_number'],
        'MediaUrl' => $imageUrl,
        'Body' => $caption // In the API, the 'Body' serves as the caption for media messages.
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['twilio_sid'] . ":" . $config['twilio_token']);
    curl_exec($ch);
    curl_close($ch);
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

// --- 1. CONFIGURE YOUR DETAILS HERE ---
// IMPORTANT: Fill in your actual data in this section.
$whatsappConfig = [
    'recipient_number' => 'whatsapp:+15558675309', // The website owner's WhatsApp number (international format)
    'website_url'      => 'https://viennabytnq.com',          // Your full website URL (e.g., https://www.example.com)
    'images_path'      => '/uploads/',                        // The public server path to your product images
    'twilio_sid'       => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',  // Your Twilio Account SID
    'twilio_token'     => 'your_auth_token_xxxxxxxxxxxxxx',   // Your Twilio Auth Token
    'twilio_number'    => 'whatsapp:+14155238886'           // Your Twilio WhatsApp-enabled Number
];

// We use a try-catch block so that if the notification fails,
// it does not break the success page for the customer.
try {
    // --- 2. GET THE ORDER ID FROM THE PAYMENT REFERENCE ---
    $orderIdStmt = $conn->prepare("SELECT id FROM orders WHERE payment_reference = ?");
    $orderIdStmt->execute([$orderRef]);
    $orderIdResult = $orderIdStmt->fetch(PDO::FETCH_ASSOC);

    if ($orderIdResult) {
        $orderId = $orderIdResult['id'];

        // --- 3. FETCH ALL ORDER ITEMS FOR THE NOTIFICATION ---
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

        // --- 4. SEND THE NOTIFICATION ---
        if (!empty($orderItemsForNotification)) {
            sendOrderNotificationToWhatsApp($orderItemsForNotification, $whatsappConfig);
        }
    }
} catch (Exception $e) {
    // If something goes wrong, the customer page will still load.
    // For debugging, you can log this error to a file.
    // error_log('WhatsApp Notification Failed: ' . $e->getMessage());
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