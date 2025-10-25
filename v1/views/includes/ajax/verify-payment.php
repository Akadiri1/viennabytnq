<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =================================================================
// CRITICAL DEBUG CHECK: Check if $conn (PDO object) exists
// =================================================================
if (!isset($conn) || !($conn instanceof PDO)) {
    // DO NOT LEAVE THIS LIVE. Use only for debugging.
    // A live site should show a generic error page, not a technical error.
    header('Content-Type: text/plain');
    http_response_code(500);
    die("CRITICAL ERROR: Database connection (\$conn) is not defined or is not a PDO object. Check your 'require' statement and connection file.");
}
// =================================================================


// =================================================================
// 1. PAYMENT VERIFICATION LOGIC
// =================================================================

$reference = $_GET['reference'] ?? null;
if (!$reference) {
    // Paystack reference is missing
    header("Location: /checkout?error=invalid_reference");
    exit;
}

// IMPORTANT: Add a secret token for secure owner-side invoice viewing
// A good token is long and randomly generated. Replace this with a unique, unguessable value.
define('OWNER_VIEW_SECRET_TOKEN', 'k7D!aP@9zXyR5n$2BwFjLq#tVc1gH6mE_REPLACE_ME'); 

// Check if the payment was already processed in this session
if (isset($_SESSION['last_order_ref']) && $_SESSION['last_order_ref'] === $reference) {
    // Redirect to the success page to avoid re-processing
    header("Location: /order-success?ref=" . $reference);
    exit;
}

// IMPORTANT: Replace with your actual Paystack SECRET Key
$paystackSecretKey = 'sk_live_af1769aa9bb7110e01d76a98297715a7ac2978e';

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer " . $paystackSecretKey,
        "cache-control: no-cache"
    ],
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    error_log("cURL Verification Error for reference $reference: " . $err);
    header("Location: /checkout?error=verification_failed_curl");
    exit;
}

$result = json_decode($response);

// Variables to hold data for post-transaction actions (email, notification)
$emailContext = null;
$orderNumber = $reference; // Assuming reference is the order_number

// =================================================================
// 2. DATABASE UPDATE & WHATSAPP MESSAGE PREPARATION
// =================================================================

// Check for successful payment from Paystack
if (isset($result->status) && $result->status == true && isset($result->data->status) && $result->data->status == 'success') {

    $conn->beginTransaction();
    try {
        // --- 2.1 Update order status and set payment reference ---
        // Use order_number (which is $reference) to find the original order
        // and ensure we only update 'pending' orders to prevent double processing.
        $orderStmt = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_reference = ?, payment_gateway_response = ? WHERE order_number = ? AND order_status = 'pending'");
        $orderStmt->execute([$reference, $response, $orderNumber]);
        
        $orderUpdated = $orderStmt->rowCount() > 0;

        // --- 2.2 Fetch the updated order and customer details ---
        // Fetch using the order_number, now that the update has (hopefully) happened.
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
            $shippingDetails = json_decode($order['shipping_address'], true);

            // --- 2.3 Decrement stock for each product in the order (Inside transaction) ---
            // This is safer inside the transaction to ensure atomicity.
            $cart = json_decode($order['cart'], true) ?? []; // Decode the original cart data
            
            // Prepare stock update statements
            $updateStockStmt = $conn->prepare("UPDATE panel_products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
            $checkStockStmt = $conn->prepare("SELECT stock_quantity FROM panel_products WHERE id = ?");
            $disableProductStmt = $conn->prepare("UPDATE panel_products SET visibility = 'hide' WHERE id = ?");

            foreach ($cart as $item) {
                $productId = $item['product_id'];
                $quantity = (int) $item['quantity'];

                // Decrement stock
                $updateStockStmt->execute([$quantity, $productId, $quantity]);

                // Check and disable product if stock hits zero
                $checkStockStmt->execute([$productId]);
                $stock = $checkStockStmt->fetchColumn();

                if ($stock !== false && (int)$stock <= 0) {
                    $disableProductStmt->execute([$productId]);
                }
            }

            // --- 2.4 Build the WhatsApp message (for Admin notification) ---
            $whatsappMessage = "🎉 New Paid Order! ✅\n\n" .
                "Order #: " . htmlspecialchars($order['order_number']) . "\n" .
                "Status: Paid\n" .
                "Customer: " . htmlspecialchars($shippingDetails['fullName'] ?? 'N/A') . "\n" .
                "Phone: " . htmlspecialchars($shippingDetails['phoneNumber'] ?? 'N/A') . "\n" .
                "Address: " . htmlspecialchars($shippingDetails['address'] ?? 'N/A') . "\n" .
                "Total: ₦" . number_format($order['grand_total'], 2) . "\n\n";

            // Add the invoice link for YOUR reference
            $websiteUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');
            $invoiceLink = $websiteUrl . '/invoice?order_number=' . urlencode($order['order_number']) . '&token=' . OWNER_VIEW_SECRET_TOKEN;
            $whatsappMessage .= "🔗 *View Invoice:* " . $invoiceLink;

            // Store the message and a flag in the session for redirect handling
            $_SESSION['whatsapp_notification_message'] = $whatsappMessage;
            $_SESSION['last_order_ref'] = $reference;

            // --- 2.5 Fetch order items for email context ---
            $orderItemsStmt = $conn->prepare("
                SELECT oi.*, pp.name AS product_name, pp.image_one AS product_image
                FROM order_items oi
                JOIN panel_products pp ON oi.product_id = pp.id
                WHERE oi.order_id = ?
            ");
            $orderItemsStmt->execute([$order['id']]);
            $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Final context for email
            $emailContext = [
                'order' => $order,
                'shipping' => $shippingDetails,
                'items' => $orderItems,
                'invoice_link' => $invoiceLink,
            ];

            // --- 2.6 Clear the cart if the order was a new update ---
            if ($orderUpdated) {
                $cartToken = $_COOKIE['cart_token'] ?? null;
                if ($cartToken) {
                    // Check if the cart token belongs to the customer
                    $checkCartStmt = $conn->prepare("SELECT 1 FROM cart_items WHERE cart_token = ? LIMIT 1");
                    $checkCartStmt->execute([$cartToken]);
                    if ($checkCartStmt->rowCount() > 0) {
                         $clearCartStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_token = ?");
                         $clearCartStmt->execute([$cartToken]);
                    }
                }
            }
        }
        
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        // CATCHING AND LOGGING ANY DATABASE OR TRANSACTION ERRORS
        error_log("Payment Verification DB/Transaction Error for ref $reference: " . $e->getMessage());
        header("Location: /checkout?error=db_transaction_error");
        exit;
    }

    // =================================================================
    // 3. LOAD SITE SETTINGS FOR EMAIL
    // =================================================================

    // Define default values in case settings are missing
    $siteSettings = [
        'site_email' => '',
        'site_email_2' => '',
        'site_email_from' => '',
        'site_email_password' => '',
        'site_email_smtp_host' => '',
        'site_email_smtp_secure_type' => '',
        'site_email_smtp_port' => '',
        'site_name' => 'VIENNA BY TNQ'
    ];

    try {
        // Check if the user's custom function is available to prevent a fatal error.
        if (function_exists('selectContent')) {
            // --- Using the user's suggested function call ---
            $websiteInfo = selectContent($conn, "read_website_info", ['visibility' => 'show']);
            
            // Normalize the result
            $settings = null;
            if (is_array($websiteInfo)) {
                $settings = $websiteInfo[0] ?? (isset($websiteInfo['input_name']) ? $websiteInfo : null);
            }

            if ($settings) {
                // Assign database values to the associative array
                $siteSettings['site_email'] = $settings['input_email'] ?? $siteSettings['site_email'];
                $siteSettings['site_email_2'] = $settings['input_email_2'] ?? $siteSettings['site_email_2'];
                $siteSettings['site_email_from'] = $settings['input_email_from'] ?? $siteSettings['site_email_from'];
                $siteSettings['site_email_password'] = $settings['input_email_password'] ?? $siteSettings['site_email_password'];
                $siteSettings['site_email_smtp_host'] = $settings['input_email_smtp_host'] ?? $siteSettings['site_email_smtp_host'];
                $siteSettings['site_email_smtp_secure_type'] = $settings['input_email_smtp_secure_type'] ?? $siteSettings['site_email_smtp_secure_type'];
                $siteSettings['site_email_smtp_port'] = $settings['input_email_smtp_port'] ?? $siteSettings['site_email_smtp_port'];
                $siteSettings['site_name'] = $settings['input_name'] ?? $siteSettings['site_name'];
            } else {
                 error_log("CRITICAL: selectContent() returned no usable site settings. Emails will fail or use defaults.");
            }
        } else {
            error_log("CRITICAL: selectContent() function is missing. Site settings were not loaded.");
        }
    } catch (Exception $e) {
        // This catches database/query errors and logs them
        error_log("Failed to query site settings: " . $e->getMessage());
    }

    // =================================================================
    // 4. EMAIL HELPER FUNCTIONS
    // =================================================================
    
    // Ensure PHPMailer is available
    require_once APP_PATH . '/phpm/PHPMailerAutoload.php';

    /**
     * Initializes and configures a PHPMailer object with site settings.
     * @param array $settings Site settings loaded from the database.
     * @return PHPMailer Configured mailer instance.
     */
    function initOrderMailer(array $settings): PHPMailer {
        $mail = new PHPMailer(true); // Enable exceptions
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // Set to 2 for debugging, 0 for production
        $mail->SMTPAuth = true;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->AltBody = 'This email requires an HTML-compatible email client to display correctly.';

        // Configure From Address
        $fromAddress = trim($settings['site_email'] ?: $settings['site_email_from'] ?: '');
        if (empty($fromAddress)) {
            $fromAddress = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');
        }
        $fromName = trim($settings['site_name'] ?? '') ?: 'Store';

        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        
        // Configure Host, Username, Password
        $host = trim($settings['site_email_smtp_host'] ?? '');
        $mail->Host = $host !== '' ? $host : 'smtp.gmail.com'; // Default to gmail if empty

        $username = trim($settings['site_email_from'] ?? '') ?: trim($settings['site_email'] ?? '') ?: $fromAddress;
        $mail->Username = $username;

        $password = trim((string) ($settings['site_email_password'] ?? ''));
        $mail->Password = $password;

        // Configure SMTPSecure & Port
        $secureType = strtolower(trim($settings['site_email_smtp_secure_type'] ?? ''));
        if ($secureType === 'ssl' || $secureType === 'tls') {
            $mail->SMTPSecure = $secureType;
        } else {
            $mail->SMTPSecure = 'tls'; // Default
        }

        $port = (int) trim((string) ($settings['site_email_smtp_port'] ?? ''));
        if ($port <= 0) {
            $port = $mail->SMTPSecure === 'ssl' ? 465 : 587; // Default port based on secure type
        }
        $mail->Port = $port;

        return $mail;
    }

    /**
     * Generates the HTML body for the order confirmation email. (Refactored to be self-contained)
     */
    function buildOrderEmailBody(array $order, array $shipping, array $items, string $heading, string $intro, ?string $invoiceLink = null) {
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
            $productName = htmlspecialchars($item['product_name'] ?? 'Product', ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['price_per_unit'] ?? 0);
            $lineTotal = $unitPrice * max($quantity, 0);

            $options = [];
            if (!empty($item['color_name'])) { $options[] = 'Color: ' . htmlspecialchars($item['color_name'], ENT_QUOTES, 'UTF-8'); }
            if (!empty($item['custom_color_name'])) { $options[] = 'Custom Color: ' . htmlspecialchars($item['custom_color_name'], ENT_QUOTES, 'UTF-8'); }
            if (!empty($item['size_name'])) { $options[] = 'Size: ' . htmlspecialchars($item['size_name'], ENT_QUOTES, 'UTF-8'); }
            if (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') { $options[] = 'Custom Size'; }
            $optionsText = !empty($options) ? implode(' | ', $options) : '—';

            $itemsRows .= '<tr style="border-bottom:1px solid #e5e7eb;">'
                . '<td style="padding:8px 0;">' . $productName . '<br><span style="color:#6b7280;font-size:12px;">' . $optionsText . '</span></td>'
                . '<td style="padding:8px 0;text-align:center;">' . $quantity . '</td>'
                . '<td style="padding:8px 0;text-align:right;">' . $currencySymbol . number_format($unitPrice, 2) . '</td>'
                . '<td style="padding:8px 0;text-align:right;">' . $currencySymbol . number_format($lineTotal, 2) . '</td>'
                . '</tr>';
        }

        if ($itemsRows === '') { $itemsRows = '<tr><td colspan="4" style="padding:12px 0;text-align:center;">No order items found.</td></tr>'; }

        $summaryRows = '<tr>'
            . '<td style="padding:4px 0;">Subtotal:</td>'
            . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['subtotal'] ?? 0), 2) . '</td>'
            . '</tr>';

        if (!empty($order['discount_amount'])) {
            $summaryRows .= '<tr>'
                . '<td style="padding:4px 0;">Discount:</td>'
                . '<td style="padding:4px 0;text-align:right;">- ' . $currencySymbol . number_format((float) $order['discount_amount'], 2) . '</td>'
                . '</tr>';
        }

        $summaryRows .= '<tr>'
            . '<td style="padding:4px 0;">Shipping:</td>'
            . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['shipping_fee'] ?? 0), 2) . '</td>'
            . '</tr>';

        $summaryRows .= '<tr>'
            . '<td style="padding:8px 0;font-weight:bold;">Grand Total:</td>'
            . '<td style="padding:8px 0;text-align:right;font-weight:bold;">' . $currencySymbol . number_format((float) ($order['grand_total'] ?? 0), 2) . '</td>'
            . '</tr>';

        $invoiceButton = '';
        if (!empty($invoiceLink)) {
            $safeLink = htmlspecialchars($invoiceLink, ENT_QUOTES, 'UTF-8');
            $invoiceButton = '<p style="margin-top:20px;">'
                . '<a href="' . $safeLink . '" style="display:inline-block;padding:10px 16px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:4px;">View Invoice</a>'
                . '</p>';
        }

        return '
            <div style="font-family:Inter,Arial,sans-serif;font-size:14px;color:#111827;line-height:1.6;">
                <h2 style="font-size:20px;margin-bottom:8px;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h2>
                <p style="margin:0 0 16px;">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>
                <div style="background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:20px;">
                    <p style="margin:0;"><strong>Order Number:</strong> ' . $orderNumber . '</p>
                    <p style="margin:4px 0;"><strong>Order Date:</strong> ' . $createdAt . '</p>
                    <p style="margin:4px 0;"><strong>Payment Reference:</strong> ' . $paymentReference . '</p>
                    <p style="margin:4px 0;"><strong>Customer:</strong> ' . $customerName . '</p>
                    <p style="margin:4px 0;"><strong>Email:</strong> ' . $customerEmail . '</p>
                    <p style="margin:4px 0;"><strong>Phone:</strong> ' . $customerPhone . '</p>
                    <p style="margin:4px 0;"><strong>Shipping Address:</strong><br>' . $addressHtml . '</p>
                </div>
                <h3 style="font-size:16px;margin-bottom:8px;">Items Ordered</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #d1d5db;">
                            <th style="padding:8px 0;">Item</th>
                            <th style="padding:8px 0;text-align:center;">Qty</th>
                            <th style="padding:8px 0;text-align:right;">Unit Price</th>
                            <th style="padding:8px 0;text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>' . $itemsRows . '</tbody>
                </table>
                <table style="width:100%;margin-top:16px;border-collapse:collapse;text-align:right;">
                    ' . $summaryRows . '
                </table>
                ' . $invoiceButton . '
                <p style="margin-top:24px;">If you have any questions, reply to this email and our team will be happy to help.</p>
            </div>
        ';
    }


    // =================================================================
    // 5. SEND ORDER EMAILS
    // =================================================================

    if (!empty($emailContext)) {
        
        $orderData = $emailContext['order'];
        $shipping = $emailContext['shipping'] ?? [];
        $orderItems = $emailContext['items'] ?? [];
        $invoiceLink = $emailContext['invoice_link'] ?? null;
        $websiteOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');

        // --- Send Customer Email ---
        if (!empty($orderData['email'])) {
            try {
                $customerMail = initOrderMailer($siteSettings);
                $customerMail->addAddress($orderData['email'], $shipping['fullName'] ?? '');
                $customerMail->Subject = 'Your Order Confirmation - ' . ($orderData['order_number'] ?? '');
                $customerMail->Body = buildOrderEmailBody(
                    $orderData,
                    $shipping,
                    $orderItems,
                    'Thank you for your purchase!',
                    'Your order has been received and payment confirmed. We are preparing it for delivery.'
                );

                if (!$customerMail->send()) {
                    error_log('Order email to customer failed: ' . $customerMail->ErrorInfo);
                }
            } catch (Exception $e) {
                error_log('Customer Email Error: ' . $e->getMessage());
            }
        }

        // --- Send Admin/Client Email ---
        if (!empty($siteSettings['site_email'])) {
            try {
                $clientMail = initOrderMailer($siteSettings);
                $clientMail->addAddress($siteSettings['site_email'], $siteSettings['site_name'] ?? 'Store Admin');
                
                if (!empty($siteSettings['site_email_2'])) {
                    $clientMail->addCC($siteSettings['site_email_2'], $siteSettings['site_name'] ?? 'Store Admin');
                }
                
                $clientMail->Subject = 'New Paid Order - ' . ($orderData['order_number'] ?? '');
                $clientMail->Body = buildOrderEmailBody(
                    $orderData,
                    $shipping,
                    $orderItems,
                    'New paid order received',
                    'A new customer order has been paid on ' . $websiteOrigin . '. Review the order details below.',
                    $invoiceLink // Admin email gets the special invoice link
                );

                if (!$clientMail->send()) {
                    error_log('Order email to client failed: ' . $clientMail->ErrorInfo);
                }
            } catch (Exception $e) {
                error_log('Client Email Error: ' . $e->getMessage());
            }
        }
    }

    // Redirect to the success page to trigger the notification
    header("Location: /order-success?ref=" . $reference);
    exit;

} else {
    // Payment failed from Paystack
    // Log the failure details
    $errorDetails = $result->data->gateway_response ?? $result->data->message ?? 'Unknown Paystack failure';
    error_log("Paystack verification failed for reference $reference. Response: $errorDetails");
    
    // Redirect with a generic error
    header("Location: /checkout?error=payment_failed_paystack");
    exit;
}