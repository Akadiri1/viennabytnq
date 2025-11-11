<?php
// =================================================================
// 0. CRITICAL DEBUG SETUP & INITIALIZATION
// =================================================================

// 1. Safe Session Start: MUST be the first thing executed before any output
if (session_status() === PHP_SESSION_NONE) {
    // If sessions aren't running, start them securely
    if (!headers_sent()) {
        session_start();
    }
}

// 2. Turn on all error reporting for debug environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. Initialize critical variables defensively
// $conn MUST be defined by the including script for DB logic to run.
if (!isset($conn)) {
    $conn = null;
}
$siteSettings = [];
$dbSettingsFound = false;
$result = null;
$emailContext = null;
$orderNumber = null;
$reference = $_GET['reference'] ?? null;
$paystackSecretKey = 'sk_test_cf26f818cf4db08aaf9dd4552deff563464a2c3b';
define('OWNER_VIEW_SECRET_TOKEN', 'k7D!aP@9zXyR5n$2BwFjLq#tVc1gH6mE'); 

// --- CRITICAL FIX 1: Define the Absolute Base URL ---
$websiteOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');
// For local testing, ensure 'viennabytnq.com' matches your development domain if $_SERVER['HTTP_HOST'] is not set correctly.
// ----------------------------------------------------

// Start HTML output wrapper for clear debugging
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Verification Debug Log</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; background-color: #f4f7f9; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        h1 { color: #1e40af; border-bottom: 2px solid #eff2f6; padding-bottom: 10px; margin-top: 0; }
        h2 { color: #333; margin-top: 25px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        h4 { color: #555; margin-top: 15px; }
        pre { background: #eef; border: 1px solid #ddd; padding: 15px; border-radius: 6px; overflow-x: auto; color: #333; }
        .status-ok { color: green; font-weight: bold; }
        .status-warn { color: orange; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .critical-block { border: 2px dashed #f97316; padding: 15px; margin: 20px 0; background: #fff7ed; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">';
echo '<h1>Paystack Verification Script Debug Report</h1>';
echo '<p>Script Execution Time: ' . date('Y-m-d H:i:s') . '</p>';
echo '<p>PHP Errors Display: <span class="status-ok">ON</span> (E_ALL)</p>';
echo '<p>Website Base URL: <code>' . htmlspecialchars($websiteOrigin) . '</code></p>'; // Report the base URL

// Check if DB is connected
if ($conn === null) {
    echo '<p class="status-error">CRITICAL WARNING: $conn (Database connection) is undefined. All DB operations will be skipped.</p>';
} else {
    echo '<p class="status-ok">Database connection ($conn) detected and ready for use.</p>';
}

// =================================================================
// 1. DYNAMICALLY LOAD SITE SETTINGS FROM DATABASE (or use fallback)
// =================================================================

$columnMap = [
    'input_name' => 'site_name',
    'input_email' => 'site_email',
    'input_email_2' => 'site_email_2',
    'input_email_from' => 'site_email_from',
    'input_email_smtp_host' => 'site_email_smtp_host',
    'input_email_smtp_secure_type' => 'site_email_smtp_secure_type',
    'input_email_smtp_port' => 'site_email_smtp_port',
    'input_email_password' => 'site_email_password',
];

if ($conn !== null) {
    echo '<h2>1. Database Settings Fetch Attempt</h2>';
    try {
        $settingsStmt = $conn->prepare("
            SELECT 
                input_name, 
                input_email, 
                input_email_2,
                input_email_from,
                input_email_smtp_host,
                input_email_smtp_secure_type,
                input_email_smtp_port,
                input_email_password
            FROM read_website_info 
            WHERE id = 1
        ");
        $settingsStmt->execute();
        $dbSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

        if ($dbSettings) {
            echo '<p class="status-ok">SUCCESS: Email settings loaded from database.</p>';
            foreach ($columnMap as $dbCol => $settingKey) {
                $siteSettings[$settingKey] = $dbSettings[$dbCol];
            }
            $dbSettingsFound = true;
        } else {
             echo '<p class="status-warn">NOTICE: Database query ran, but no settings record found (WHERE id=1). Falling back to hardcoded defaults.</p>';
        }

    } catch (Exception $e) {
        echo '<p class="status-error">DB ERROR: Could not fetch site settings!</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        error_log("Site settings fetch error: " . $e->getMessage());
    }
}

// Fallback to the hardcoded default settings 
if (!$dbSettingsFound) {
    echo '<p class="status-warn">NOTICE: Using hardcoded default email settings for testing/fallback.</p>';
    $siteSettings = [
        'site_email' => 'info@tuttomondocare.com', 
        'site_email_2' => 'info@tuttomondocare.com',
        'site_email_from' => 'info@tuttomondocare.com',
        'site_email_password' => 'Abiola@2021', 
        'site_email_smtp_host' => 'eight.qservers.net', 
        'site_email_smtp_secure_type' => 'ssl', 
        'site_email_smtp_port' => 465, 
        'site_name' => 'VIENNA BY TNQ',
    ];
}

echo '<h4>Site Settings in Use:</h4><pre>' . print_r($siteSettings, true) . '</pre>';


// --- PHPMailer & APP_PATH CHECK ---
if (!defined('APP_PATH')) {
    // Defines the root path, assuming 'phpm' is in the parent directory
    define('APP_PATH', __DIR__ . '/..'); 
}

$mailerPath = APP_PATH . '/phpm/PHPMailerAutoload.php';
echo '<h2>2. PHPMailer Dependency Check</h2>';
echo '<p>Attempting to load PHPMailer from: <code>' . htmlspecialchars($mailerPath) . '</code></p>';

if (!file_exists($mailerPath)) {
    // This uses die() because a missing required file will cause a fatal error anyway.
    die('<p class="status-error">FATAL ERROR: PHPMailer file not found.</p><p>Path checked: <code>' . $mailerPath . '</code>. Please ensure the file path is correct.</p></div></body></html>');
}
require_once $mailerPath;
echo '<p class="status-ok">PHPMailer loaded successfully.</p>';


// =================================================================
// 3. PAYMENT VERIFICATION LOGIC
// =================================================================

echo '<h2>3. Paystack Verification</h2>';

if (!$reference) {
    // Paystack reference is missing
    echo '<p class="status-error">ERROR: Invalid Reference. No Paystack reference found in the URL parameter.</p>';
    // header("Location: /checkout?error=invalid_reference");
    exit;
}
echo '<p>Processing Paystack Reference: <strong style="color:#059669;">' . htmlspecialchars($reference) . '</strong></p>';
$orderNumber = $reference; 

// Check if the payment was already processed in this session
if (isset($_SESSION['last_order_ref']) && $_SESSION['last_order_ref'] === $reference) {
    echo '<p class="status-warn">NOTICE: Already Processed. This reference was already handled in this session. Skipping verification to prevent duplicate actions.</p>';
    // header("Location: /order-success?ref=" . $reference);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer " . $paystackSecretKey,
        "cache-control: no-cache"
    ],
    // DANGEROUS: These should be removed in a PROD environment with a valid SSL certificate.
    CURLOPT_SSL_VERIFYPEER => false, 
    CURLOPT_SSL_VERIFYHOST => false,
));
$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo '<div class="critical-block">';
echo '<h4>cURL Verification Result</h4>';
echo '<p class="status-warn">WARNING: CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST are disabled for debugging. Re-enable in production!</p>';
echo '<p>HTTP Status Code: <strong>' . $httpCode . '</strong></p>';

if ($err) {
    echo '<p class="status-error">cURL FATAL ERROR</p><pre>' . htmlspecialchars($err) . '</pre>';
    error_log("cURL Verification Error for reference $reference: " . $err);
    // header("Location: /checkout?error=verification_failed_curl");
    exit;
}

$result = json_decode($response, true);
echo '<h4>Paystack JSON Response</h4>';
echo '<pre>' . print_r($result, true) . '</pre>';
echo '</div>'; // end critical-block

// =================================================================
// 4. DATABASE UPDATE & WHATSAPP MESSAGE PREPARATION
// =================================================================

// Check for successful payment from Paystack
if (isset($result['status']) && $result['status'] === true && isset($result['data']['status']) && $result['data']['status'] === 'success') {
    echo '<h2 style="color:green;">4. TRANSACTION SUCCESS - Processing Order</h2>';

    if ($conn === null) {
        echo '<p class="status-error">DATABASE OPERATIONS SKIPPED: $conn is null. Cannot update order status.</p>';
        // If DB is skipped, we still need placeholder data for email testing
        $order = [
            'id' => 1, 'order_number' => $reference, 'customer_id' => 1, 'order_status' => 'paid',
            'subtotal' => 120000.00, 'shipping_fee' => 5000.00, 'grand_total' => 125000.00,
            'payment_reference' => $reference, 'created_at' => date('Y-m-d H:i:s'), 'email' => 'akadiriokiki@gmail.com',
            'shipping_address' => json_encode([
                'fullName' => 'Test Customer', 'phoneNumber' => '08012345678', 'address' => '123 Test Street',
                'city' => 'Lagos', 'state' => 'Lagos', 'postalCode' => '100001', 'country' => 'Nigeria'
            ])
        ];
        
        // --- 4.3 ENHANCED PLACEHOLDER FOR ORDER ITEMS (Including Image and Custom Sizes) ---
        $orderItems = [
            [
                'product_name' => 'Luxurious Gold Dress', 
                'main_image_url' => 'images/FUJIFILM-0354.jpg', // Placeholder now uses a relative path to test the fix
                'quantity' => 1, 
                'price_per_unit' => 100000.00,
                'color_name' => 'Gold',
                'size_name' => 'Custom',
                'custom_size_details' => '{"Bust": "34in", "Waist": "28in", "Length": "60in", "Shoulder": "15in"}', // JSON string
            ],
            [
                'product_name' => 'Bespoke Suit', 
                'main_image_url' => 'images/suit_placeholder.jpg', 
                'quantity' => 2, 
                'price_per_unit' => 15000.00,
                'color_name' => 'Navy Blue',
                'size_name' => 'Medium',
                'custom_size_details' => '', // Empty custom size
            ],
        ];
        echo '<p class="status-warn">Using placeholder data for order details and items since DB connection is missing.</p>';

    } else {
        // Database connection logic starts here (assuming $conn is defined and valid)
        $conn->beginTransaction();
        try {
            
            // --- 4.1 Update order status and set payment reference ---
            $orderStmt = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_reference = ? WHERE order_number = ? AND order_status = 'pending'");
            $orderStmt->execute([$reference, $orderNumber]);
            echo '<p>Order status update attempted. Rows affected: ' . $orderStmt->rowCount() . '</p>';

            // --- 4.2 Fetch the updated order and customer details ---
            $orderFetchStmt = $conn->prepare("
                SELECT 
                    o.*, 
                    c.email,
                    c.id AS customer_id_fk 
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                WHERE o.order_number = ? AND o.order_status = 'paid'
            ");
            $orderFetchStmt->execute([$orderNumber]);
            $order = $orderFetchStmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                echo '<p class="status-ok">Order details successfully retrieved for processing.</p>';
                $shippingDetails = json_decode($order['shipping_address'], true);
                
                // --- 4.3 FETCH ORDER ITEMS (CRITICAL: JOIN TO PANEL_PRODUCTS FOR NAME/IMAGE) ---
                // We join to panel_products (pp) using the product_id to retrieve the current name and image.
                $itemsFetchStmt = $conn->prepare("
                    SELECT 
                        oi.quantity, 
                        oi.price_per_unit, 
                        oi.color_name, 
                        oi.size_name,
                        oi.custom_color_name,
                        oi.custom_size_details, 
                        pp.name AS product_name,         -- Fetched from panel_products
                        pp.image_one AS main_image_url   -- Fetched from panel_products
                    FROM order_items oi
                    JOIN panel_products pp ON oi.product_id = pp.id
                    WHERE oi.order_id = ? 
                ");
                $itemsFetchStmt->execute([$order['id']]);
                $orderItems = $itemsFetchStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($orderItems)) {
                    echo '<p class="status-warn">WARNING: No order items found for this order ID. Email will use placeholder items if necessary.</p>';
                    // Fallback item list if DB is empty (recommended to prevent blank emails)
                    $orderItems = [['product_name' => 'Item Error: Contact Support', 'quantity' => 1, 'price_per_unit' => 0.00, 'main_image_url' => '']];
                }
                
                // Set shipping details for context
                $shippingDetails = json_decode($order['shipping_address'], true);
                
                // --- 4.4 Build the WhatsApp message (for Admin notification) ---
                $whatsappMessage = "🎉 New Paid Order! ✅\n\n" .
                    "Order #: " . htmlspecialchars($order['order_number']) . "\n" .
                    "Status: Paid\n" .
                    "Customer: " . htmlspecialchars($shippingDetails['fullName'] ?? 'N/A') . "\n" .
                    "Phone: " . htmlspecialchars($shippingDetails['phoneNumber'] ?? 'N/A') . "\n" .
                    "Total: ₦" . number_format((float)($order['grand_total'] ?? 0), 2) . "\n\n";

                $invoiceLink = $websiteOrigin . '/invoice?order_number=' . urlencode($order['order_number']) . '&token=' . OWNER_VIEW_SECRET_TOKEN;
                $whatsappMessage .= "🔗 *View Invoice:* " . $invoiceLink;

                // Storing for use on the success page after redirect
                $_SESSION['whatsapp_notification_message'] = $whatsappMessage;
                $_SESSION['last_order_ref'] = $reference;

                echo '<p class="status-warn">Stock decrement and Cart Clear logic are placeholders. They should be implemented here.</p>';
                
                // Final context for email
                $emailContext = [
                    'order' => $order,
                    'shipping' => $shippingDetails,
                    'items' => $orderItems,
                    'invoice_link' => $invoiceLink,
                ];

            } else {
                echo '<p class="status-error">ERROR: Order not found in database after successful payment confirmation.</p>';
            }

            $conn->commit();
            echo '<p class="status-ok">Database Transaction committed successfully.</p>';

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            // CATCHING AND PRINTING ANY DATABASE OR TRANSACTION ERRORS
            echo '<p class="status-error">DATABASE TRANSACTION FAILED!</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            error_log("Payment Verification DB/Transaction Error for ref $reference: " . $e->getMessage());
            // header("Location: /checkout?error=db_transaction_error");
            exit;
        }
    } // End of DB operations block
    
    // If DB operations were skipped, use placeholder data for email context
    if ($conn === null) {
        $emailContext = [
            'order' => $order,
            'shipping' => json_decode($order['shipping_address'], true),
            'items' => $orderItems,
            'invoice_link' => $websiteOrigin . '/invoice?order_number=' . $reference . '&token=' . OWNER_VIEW_SECRET_TOKEN,
        ];
    }


    // =================================================================
    // 5. EMAIL HELPER FUNCTIONS (FIXED PHPMailer & ENHANCED BODY)
    // =================================================================
    
    /**
     * Initializes and configures a PHPMailer object with site settings.
     * @param array $settings Site settings loaded from the database.
     * @return PHPMailer Configured mailer instance.
     */
    function initOrderMailer(array $settings): PHPMailer {
        $mail = new PHPMailer(true); // Enable exceptions
        $mail->isSMTP();
        // --- CRITICAL DEBUG SETTING ---
        $mail->SMTPDebug = 2; // Set to 2 for debugging (SMTP conversation details)
        $mail->SMTPAuth = true;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->AltBody = 'This email requires an HTML-compatible email client to display correctly.';

        // Configure From Address
        $fromAddress = trim($settings['site_email_from'] ?: $settings['site_email'] ?: '');
        if (empty($fromAddress)) {
            $fromAddress = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');
        }
        $fromName = trim($settings['site_name'] ?? '') ?: 'Store';

        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        
        // Configure Host, Username, Password
        $host = trim($settings['site_email_smtp_host'] ?? '');
        $mail->Host = $host !== '' ? $host : 'smtp.gmail.com'; // Fallback to a common host

        $username = trim($settings['site_email_from'] ?? '') ?: trim($settings['site_email'] ?? '') ?: $fromAddress;
        $mail->Username = $username;

        $password = trim((string) ($settings['site_email_password'] ?? ''));
        $mail->Password = $password;

        // Configure SMTPSecure & Port
        $secureType = strtolower(trim($settings['site_email_smtp_secure_type'] ?? ''));
        
        // --- FIX FOR PHPMailer CONSTANT ERROR (DO NOT TOUCH) ---
        if ($secureType === 'ssl') {
            $mail->SMTPSecure = 'ssl'; // Use string 'ssl' instead of constant
        } elseif ($secureType === 'tls') {
            $mail->SMTPSecure = 'tls'; // Use string 'tls' instead of constant
        } else {
            $mail->SMTPSecure = ''; 
        }

        $port = (int) trim((string) ($settings['site_email_smtp_port'] ?? ''));
        if ($port <= 0) {
            $port = ($mail->SMTPSecure === 'ssl') ? 465 : 587; 
        }
        $mail->Port = $port;
        // ----------------------------------------

        return $mail;
    }

    /**
     * Generates the HTML body for the order confirmation email.
     * NOW ACCEPTS $baseUrl TO FIX IMAGE PATHS.
     */
    function buildOrderEmailBody(array $order, array $shipping, array $items, string $heading, string $intro, string $baseUrl, ?string $invoiceLink = null) {
        $currencySymbol = '₦';
        $orderNumber = htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $paymentReference = htmlspecialchars($order['payment_reference'] ?? '', ENT_QUOTES, 'UTF-8');
        $customerName = htmlspecialchars($shipping['fullName'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
        $customerEmail = htmlspecialchars($order['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $customerPhone = htmlspecialchars($shipping['phoneNumber'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $createdAt = !empty($order['created_at']) ? date('F j, Y g:i A', strtotime($order['created_at'])) : date('F j, Y g:i A');

        $addressParts = array_filter([
            $shipping['address'] ?? '',
            $shipping['city'] ?? '',
            $shipping['state'] ?? '',
            $shipping['postalCode'] ?? '',
            $shipping['country'] ?? '',
        ]);
        $addressHtml = htmlspecialchars(implode(', ', $addressParts), ENT_QUOTES, 'UTF-8');

        $itemsRows = '';
        foreach ($items as $item) {
            // NOTE: 'product_name' and 'main_image_url' are fetched using the JOIN
            $productName = htmlspecialchars($item['product_name'] ?? 'Product', ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['price_per_unit'] ?? 0);
            $lineTotal = $unitPrice * max($quantity, 0);
            $imageUrl = htmlspecialchars($item['main_image_url'] ?? '', ENT_QUOTES, 'UTF-8');
            
            // --- CRITICAL FIX 2: Construct Absolute Image URL ---
            // If the image URL is not already absolute (doesn't start with http/https), prepend the base URL.
            if (!empty($imageUrl) && strpos($imageUrl, 'http') === false) {
                // Remove trailing slash from base URL and leading slash from image path to prevent double slashes
                $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imageUrl, '/');
            }
            // --------------------------------------------------

            // Default placeholder image if URL is missing or after the fix
            if (empty($imageUrl)) {
                $imageUrl = 'https://placehold.co/60x60/f3f4f6/9ca3af?text=N%2FA';
            }

            // Standard Options (Color/Size)
            $options = [];
            if (!empty($item['color_name'])) { $options[] = 'Color: ' . htmlspecialchars($item['color_name'], ENT_QUOTES, 'UTF-8'); }
            if (!empty($item['size_name'])) { $options[] = 'Size: ' . htmlspecialchars($item['size_name'], ENT_QUOTES, 'UTF-8'); }
            $optionsText = !empty($options) ? implode(' | ', $options) : '';
            
            // --- Custom Size Details Logic ---
            $customDetailsHtml = '';
            $customDetails = json_decode($item['custom_size_details'] ?? '', true);
            
            if (!empty($customDetails) && is_array($customDetails)) {
                $customDetailsHtml = '<div style="margin-top: 8px; border-top: 1px dashed #e5e7eb; padding-top: 4px;">';
                $customDetailsHtml .= '<span style="font-weight: bold; color: #10b981; font-size: 11px; display: block; margin-bottom: 2px;">CUSTOM MEASUREMENTS:</span>';
                
                foreach ($customDetails as $key => $value) {
                    $customDetailsHtml .= '<span style="font-size: 12px; color: #4b5563; display: block;">'
                        . htmlspecialchars($key) . ': ' . htmlspecialchars($value)
                        . '</span>';
                }
                $customDetailsHtml .= '</div>';
            }

            // Build the row
            $itemsRows .= '<tr style="border-bottom:1px solid #e5e7eb;">'
                . '<td style="padding:12px 0; display: flex; align-items: center;">'
                    . '<img src="' . $imageUrl . '" alt="' . $productName . '" width="60" height="60" style="width:60px; height:60px; border-radius:4px; margin-right:12px; object-fit: cover;">'
                    . '<div>'
                        . '<strong style="display:block; font-size:14px;">' . $productName . '</strong>'
                        . '<span style="color:#6b7280;font-size:12px;">' . $optionsText . '</span>'
                        . $customDetailsHtml // Inject Custom Size details here
                    . '</div>'
                . '</td>'
                . '<td style="padding:12px 0;text-align:center; vertical-align: top;">' . $quantity . '</td>'
                . '<td style="padding:12px 0;text-align:right; vertical-align: top;">' . $currencySymbol . number_format($unitPrice, 2) . '</td>'
                . '<td style="padding:12px 0;text-align:right;font-weight:bold; vertical-align: top;">' . $currencySymbol . number_format($lineTotal, 2) . '</td>'
                . '</tr>';
        }

        if ($itemsRows === '') { $itemsRows = '<tr><td colspan="4" style="padding:12px 0;text-align:center; color:red;">No order items found. Please check the database.</td></tr>'; }

        $summaryRows = '<tr>'
            . '<td colspan="2" style="padding:4px 0;"></td>' // Extra empty column for alignment
            . '<td style="padding:4px 0;">Subtotal:</td>'
            . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['subtotal'] ?? 0), 2) . '</td>'
            . '</tr>';

        if (!empty($order['discount_amount'])) {
            $summaryRows .= '<tr>'
                . '<td colspan="2" style="padding:4px 0;"></td>'
                . '<td style="padding:4px 0;">Discount:</td>'
                . '<td style="padding:4px 0;text-align:right;">- ' . $currencySymbol . number_format((float) $order['discount_amount'], 2) . '</td>'
                . '</tr>';
        }

        $summaryRows .= '<tr>'
            . '<td colspan="2" style="padding:4px 0;"></td>'
            . '<td style="padding:4px 0;">Shipping:</td>'
            . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['shipping_fee'] ?? 0), 2) . '</td>'
            . '</tr>';

        $summaryRows .= '<tr>'
            . '<td colspan="2" style="padding:8px 0;"></td>'
            . '<td style="padding:8px 0;font-weight:bold;border-top:2px solid #374151;">Grand Total:</td>'
            . '<td style="padding:8px 0;text-align:right;font-weight:bold;font-size:18px;border-top:2px solid #374151;">' . $currencySymbol . number_format((float) ($order['grand_total'] ?? 0), 2) . '</td>'
            . '</tr>';

        $invoiceButton = '';
        if (!empty($invoiceLink)) {
            $safeLink = htmlspecialchars($invoiceLink, ENT_QUOTES, 'UTF-8');
            $invoiceButton = '<p style="margin-top:20px;">'
                . '<a href="' . $safeLink . '" style="display:inline-block;padding:10px 16px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:4px;">View Invoice</a>'
                . '</p>';
        }

        return '
            <div style="font-family:Inter,Arial,sans-serif;font-size:14px;color:#111827;line-height:1.6; max-width: 600px; margin: 0 auto;">
                <h2 style="font-size:20px;margin-bottom:8px; color: #1e40af;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h2>
                <p style="margin:0 0 16px;">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>
                
                <h3 style="font-size:16px;margin-bottom:8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">Order Details</h3>
                <div style="background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:20px;">
                    <p style="margin:0;"><strong>Order Number:</strong> ' . $orderNumber . '</p>
                    <p style="margin:4px 0;"><strong>Order Date:</strong> ' . $createdAt . '</p>
                    <p style="margin:4px 0;"><strong>Payment Reference:</strong> ' . $paymentReference . '</p>
                    <p style="margin:4px 0;"><strong>Customer:</strong> ' . $customerName . '</p>
                    <p style="margin:4px 0;"><strong>Email:</strong> ' . $customerEmail . '</p>
                    <p style="margin:4px 0;"><strong>Phone:</strong> ' . $customerPhone . '</p>
                    <p style="margin:4px 0;"><strong>Shipping Address:</strong><br>' . $addressHtml . '</p>
                </div>

                <h3 style="font-size:16px;margin-bottom:8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">Items Ordered</h3>
                <table style="width:100%;border-collapse:collapse; table-layout: fixed;">
                    <thead>
                        <tr style="text-align:left;border-bottom:2px solid #111827;">
                            <th style="padding:8px 0; width: 50%;">Product</th>
                            <th style="padding:8px 0;text-align:center; width: 10%;">Qty</th>
                            <th style="padding:8px 0;text-align:right; width: 20%;">Unit Price</th>
                            <th style="padding:8px 0;text-align:right; width: 20%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>' . $itemsRows . '</tbody>
                </table>
                <table style="width:100%;margin-top:16px;border-collapse:collapse;text-align:right; table-layout: fixed;">
                    ' . $summaryRows . '
                </table>
                ' . $invoiceButton . '
                // <p style="margin-top:24px;">If you have any questions, reply to this email and our team will be happy to help.</p>
            </div>
        ';
    }


    // =================================================================
    // 6. SEND ORDER EMAILS
    // =================================================================

    if (!empty($emailContext)) {
        
        $orderData = $emailContext['order'];
        $shipping = $emailContext['shipping'] ?? [];
        $orderItems = $emailContext['items'] ?? [];
        $invoiceLink = $emailContext['invoice_link'] ?? null;
        
        echo '<h2>5. PHPMailer SMTP Debug Output (See Detailed Log Below)</h2>';
        echo '<pre>'; // Start a monolithic pre block for SMTP output

        // --- Send Customer Email ---
        if (!empty($orderData['email'])) {
            echo "\n--- Attempting to send CUSTOMER EMAIL to: " . $orderData['email'] . " ---\n";
            try {
                $customerMail = initOrderMailer($siteSettings);
                $customerMail->SMTPDebug = 2; 
                
                $customerMail->addAddress($orderData['email'], $shipping['fullName'] ?? '');
                $customerMail->Subject = 'Your Order Confirmation - ' . ($orderData['order_number'] ?? 'N/A');
                
                // --- CRITICAL FIX 3: Pass $websiteOrigin to the body builder ---
                $customerMail->Body = buildOrderEmailBody(
                    $orderData,
                    $shipping,
                    $orderItems,
                    'Thank you for your purchase!',
                    'Your order has been received and payment confirmed. We are preparing it for delivery.',
                    $websiteOrigin // New Argument
                );

                if (!$customerMail->send()) {
                    echo "\nCUSTOMER EMAIL FAILED! Error: " . $customerMail->ErrorInfo . "\n";
                    error_log('Order email to customer failed: ' . $customerMail->ErrorInfo);
                } else {
                    echo "\nCUSTOMER EMAIL SENT SUCCESSFULLY.\n";
                }
            } catch (Exception $e) {
                echo "\nCustomer Email Exception: " . $e->getMessage() . "\n";
                error_log('Customer Email Error: ' . $e->getMessage());
            }
        }

        // --- Send Admin/Client Email ---
        if (!empty($siteSettings['site_email'])) {
            echo "\n--- Attempting to send ADMIN EMAIL to: " . $siteSettings['site_email'] . " ---\n";
            try {
                $clientMail = initOrderMailer($siteSettings);
                $clientMail->SMTPDebug = 2; 

                $clientMail->addAddress($siteSettings['site_email'], $siteSettings['site_name'] ?? 'Store Admin');
                
                if (!empty($siteSettings['site_email_2'])) {
                    $clientMail->addCC($siteSettings['site_email_2'], $siteSettings['site_name'] ?? 'Store Admin');
                }
                
                $clientMail->Subject = 'New Paid Order - ' . ($orderData['order_number'] ?? 'N/A');
                
                // --- CRITICAL FIX 3: Pass $websiteOrigin to the body builder ---
                $clientMail->Body = buildOrderEmailBody(
                    $orderData,
                    $shipping,
                    $orderItems,
                    'New Paid Order Received',
                    'A new customer order has been paid on ' . $websiteOrigin . '. Review the order details below.',
                    $websiteOrigin, // New Argument
                    $invoiceLink // Admin email gets the special invoice link
                );

                if (!$clientMail->send()) {
                    echo "\nADMIN EMAIL FAILED! Error: " . $clientMail->ErrorInfo . "\n";
                    error_log('Order email to client failed: ' . $clientMail->ErrorInfo);
                } else {
                    echo "\nADMIN EMAIL SENT SUCCESSFULLY.\n";
                }
            } catch (Exception $e) {
                echo "\nAdmin Email Exception: " . $e->getMessage() . "\n";
                error_log('Client Email Error: ' . $e->getMessage());
            }
        }

        echo '</pre>'; // End PHPMailer output block
    }

    echo '<h2 style="color:blue;">6. SCRIPT ENDED AFTER SUCCESSFUL PROCESSING</h2>';
    // If testing locally, you can uncomment this to force a redirect:
    header("Location: /order-success?ref=" . $reference);
    exit;

} else {
    // Payment failed from Paystack
    $errorDetails = $result['data']['gateway_response'] ?? $result['data']['message'] ?? 'Unknown Paystack failure';
    echo '<h2 style="color:red;">4. PAYSTACK VERIFICATION FAILED</h2><p>Gateway Response: <span class="status-error">' . htmlspecialchars($errorDetails) . '</span></p>';
    error_log("Paystack verification failed for reference $reference. Response: $errorDetails");
    
    // header("Location: /checkout?error=payment_failed_paystack");
    exit;
}

echo '</div></body></html>';
?>
