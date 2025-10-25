<?php
// PHP SETUP & SECURITY
session_start();
// Make sure to include your database connection file.
// This file MUST define your $conn (PDO) object
// IMPORTANT: UNCOMMENT THIS LINE if your connection file is named db_connection.php
// require 'config/db_connection.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =================================================================
// TEMPORARY DEBUG CHECK: Check if $conn exists before proceeding
// If this echoes, your database connection file is missing or failing.
if (!isset($conn) || !($conn instanceof PDO)) {
    // DO NOT LEAVE THIS LIVE. Use only for debugging.
    header('Content-Type: text/plain');
    die("CRITICAL ERROR: Database connection (\$conn) is not defined or is not a PDO object. Check your 'require' statement and connection file.");
}
// =================================================================


// =================================================================
// 1. PAYMENT VERIFICATION LOGIC
// =================================================================

$reference = $_GET['reference'] ?? null;
if (!$reference) {
    header("Location: /checkout?error=invalid_reference");
    exit;
}

// IMPORTANT: Add a secret token for secure owner-side invoice viewing
define('OWNER_VIEW_SECRET_TOKEN', 'k7D!aP@9zXyR5n$2BwFjLq#tVc1gH6mE');
// A good token is long and randomly generated. Replace this with a unique value.

// Check if the payment was already processed
if (isset($_SESSION['last_order_ref']) && $_SESSION['last_order_ref'] === $reference) {
    // Redirect to the success page to avoid re-processing
    header("Location: /order-success?ref=" . $reference);
    exit;
}

// IMPORTANT: Replace with your Paystack SECRET Key
$paystackSecretKey = 'sk_test_2bde24977af50f9194874aededbee9236eed8947';

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
    error_log("cURL Verification Error: " . $err);
    header("Location: /checkout?error=verification_failed_curl");
    exit;
}

$result = json_decode($response);

$emailContext = null;

// =================================================================
// 2. DATABASE UPDATE & WHATSAPP MESSAGE PREPARATION
// =================================================================

// Check for successful payment from Paystack
if (isset($result->status) && $result->status == true && isset($result->data->status) && $result->data->status == 'success') {

    $conn->beginTransaction();
    try {
        // Update order status and set payment reference
        $orderStmt = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_reference = ? WHERE order_number = ? AND order_status = 'pending'");
        $orderStmt->execute([$reference, $reference]);

        // Fetch the updated order and customer details
        $orderFetchStmt = $conn->prepare("
            SELECT o.*, c.email FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.payment_reference = ? AND o.order_status = 'paid'
        ");
        $orderFetchStmt->execute([$reference]);
        $order = $orderFetchStmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $shippingDetails = json_decode($order['shipping_address'], true);

            // Build the WhatsApp message string
            $whatsappMessage = "🎉 New Paid Order! ✅\n\n" .
                "Order #: " . htmlspecialchars($order['order_number']) . "\n" .
                "Status: Paid\n" .
                "Customer: " . htmlspecialchars($shippingDetails['fullName'] ?? 'N/A') . "\n" .
                "Phone: " . htmlspecialchars($shippingDetails['phoneNumber'] ?? 'N/A') . "\n" .
                "Address: " . htmlspecialchars($shippingDetails['address'] ?? 'N/A') . "\n" .
                "Total: ₦" . number_format($order['grand_total'], 2) . "\n\n";

            // Add the invoice link for YOUR reference
            $websiteUrl = 'https://viennabytnq.com'; // Or your live domain, e.g., 'https://yourwebsite.com'
            $invoiceLink = $websiteUrl . '/invoice?order_number=' . urlencode($order['order_number']) . '&token=' . OWNER_VIEW_SECRET_TOKEN;
            $whatsappMessage .= "🔗 *View Invoice:* " . $invoiceLink;

            // Store the message and a flag in the session
            $_SESSION['whatsapp_notification_message'] = $whatsappMessage;
            $_SESSION['last_order_ref'] = $reference;

            $orderItemsStmt = $conn->prepare("
                SELECT oi.*, pp.name AS product_name, pp.image_one AS product_image
                FROM order_items oi
                JOIN panel_products pp ON oi.product_id = pp.id
                WHERE oi.order_id = ?
            ");
            $orderItemsStmt->execute([$order['id']]);
            $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $emailContext = [
                'order' => $order,
                'shipping' => $shippingDetails,
                'items' => $orderItems,
                'invoice_link' => $invoiceLink,
            ];
        }

        // Clear the cart if the order was updated successfully
        if ($orderStmt->rowCount() > 0) {
            $cartToken = $_COOKIE['cart_token'] ?? null;
            if ($cartToken) {
                $clearCartStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_token = ?");
                $clearCartStmt->execute([$cartToken]);
            }
        }

        $conn->commit();

        // Decrement stock for each product in the order
        $updateStmt = $conn->prepare("UPDATE panel_products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $cart = json_decode($order['cart'], true);
        foreach ($cart as $item) {
            $updateStmt->execute([$item['quantity'], $item['product_id']]);

            // Check if stock reached zero and disable product
            $checkStmt = $conn->prepare("SELECT stock_quantity FROM panel_products WHERE id = ?");
            $checkStmt->execute([$item['product_id']]);
            $stock = $checkStmt->fetchColumn();

            if ($stock <= 0) {
                $disableStmt = $conn->prepare("UPDATE panel_products SET visibility = 'hide' WHERE id = ?");
                $disableStmt->execute([$item['product_id']]);
            }
        }

        // =================================================================
        // 3. LOAD SITE SETTINGS FOR EMAIL (RELYING on selectContent ONLY)
        // =================================================================
        
        // Define default values in case settings are missing
        $site_email = '';
        $site_email_2 = '';
        $site_email_from = '';
        $site_email_password = '';
        $site_email_smtp_host = '';
        $site_email_smtp_secure_type = '';
        $site_email_smtp_port = '';
        $site_name = 'VIENNA BY TNQ'; // Default site name

        try {
            $settings = null;
            
            // Check if the user's custom function is available to prevent a fatal error.
            if (function_exists('selectContent')) {
                // --- Using the user's suggested function call ---
                $websiteInfo = selectContent($conn, "read_website_info", ['visibility' => 'show']);
                
                // Normalize the result: take the first element if it's an array of results, 
                // otherwise assume it's the single result row itself.
                if (is_array($websiteInfo)) {
                    if (isset($websiteInfo[0]) && is_array($websiteInfo[0])) {
                        // It's an array of results, use the first one
                        $settings = $websiteInfo[0];
                    } elseif (!empty($websiteInfo) && !isset($websiteInfo[0])) {
                        // It's likely a single associative row (e.g., if selectContent optimized the result)
                        $settings = $websiteInfo;
                    }
                }

                if (!$settings) {
                    error_log("CRITICAL: selectContent() returned no usable site settings. Emails will fail or use defaults.");
                }
            } else {
                 error_log("CRITICAL: selectContent() function is missing. Site settings were not loaded.");
            }

            if ($settings) {
                // Assign database values to the variables the email function needs
                $site_email                  = $settings['input_email'] ?? '';
                $site_email_2                = $settings['input_email_2'] ?? '';
                $site_email_from             = $settings['input_email_from'] ?? '';
                $site_email_password         = $settings['input_email_password'] ?? ''; // This will be 'glplukrjtkmtrlcx'
                $site_email_smtp_host        = $settings['input_email_smtp_host'] ?? '';
                $site_email_smtp_secure_type = $settings['input_email_smtp_secure_type'] ?? '';
                $site_email_smtp_port        = $settings['input_email_smtp_port'] ?? '';
                $site_name                   = $settings['input_name'] ?? 'VIENNA BY TNQ';
            }
        } catch (Exception $e) {
            // This catches database/query errors and logs them
            error_log("Failed to query site settings: " . $e->getMessage());
        }

        // =================================================================
        // 4. SEND ORDER EMAILS (Your existing email code starts here)
        // =================================================================

        if (!empty($emailContext)) {
            // Note: Make sure APP_PATH is defined elsewhere, or replace with the actual path
            require_once APP_PATH . '/phpm/PHPMailerAutoload.php';

            if (!function_exists('initOrderMailer')) {
                function initOrderMailer() {
                    // Now this function will have the correct values loaded from the DB
                    global $site_email, $site_name, $site_email_from, $site_email_password, $site_email_smtp_host, $site_email_smtp_secure_type, $site_email_smtp_port;

                    $mail = new PHPMailer;
                    $mail->isSMTP();
                    $mail->SMTPDebug = 2; // Keep this on to check logs if it fails again
                    $mail->Debugoutput = function ($message) {
                        error_log('PHPMailer: ' . $message);
                    };
                    $mail->SMTPAuth = true;
                    $mail->CharSet = 'UTF-8';

                    // Use the loaded settings
                    $fromAddress = trim($site_email ?: $site_email_from ?: '');
                    if (empty($fromAddress)) {
                        $fromAddress = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');
                    }
                    $fromName = trim($site_name ?? '') ?: 'Vienna by TNQ';

                    $mail->setFrom($fromAddress, $fromName);
                    $mail->addReplyTo($fromAddress, $fromName);
                    
                    // Use the loaded settings
                    $host = trim($site_email_smtp_host ?? '');
                    $mail->Host = $host !== '' ? $host : 'smtp.gmail.com'; // Default to gmail if empty

                    // Use the loaded settings
                    $username = trim($site_email_from ?? '') ?: trim($site_email ?? '') ?: $fromAddress;
                    $mail->Username = $username;

                    // Use the loaded settings
                    $password = trim((string) ($site_email_password ?? ''));
                    $mail->Password = $password; // This should now be 'glplukrjtkmtrlcx'

                    // Use the loaded settings
                    $secureType = strtolower(trim($site_email_smtp_secure_type ?? ''));
                    if ($secureType === 'ssl' || $secureType === 'tls') {
                        $mail->SMTPSecure = $secureType;
                    } else {
                        $mail->SMTPSecure = 'tls'; // Default to tls
                    }

                    // Use the loaded settings
                    $port = (int) trim((string) ($site_email_smtp_port ?? ''));
                    if ($port <= 0) {
                        // Default port based on secure type
                        $port = $mail->SMTPSecure === 'ssl' ? 465 : 587;
                    }
                    $mail->Port = $port;

                    $mail->isHTML(true);
                    $mail->AltBody = 'This email requires an HTML-compatible email client to display correctly.';

                    return $mail;
                }
            }

            if (!function_exists('buildOrderEmailBody')) {
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
                        if (!empty($item['color_name'])) {
                            $options[] = 'Color: ' . htmlspecialchars($item['color_name'], ENT_QUOTES, 'UTF-8');
                        }
                        if (!empty($item['custom_color_name'])) {
                            $options[] = 'Custom Color: ' . htmlspecialchars($item['custom_color_name'], ENT_QUOTES, 'UTF-8');
                        }
                        if (!empty($item['size_name'])) {
                            $options[] = 'Size: ' . htmlspecialchars($item['size_name'], ENT_QUOTES, 'UTF-8');
                        }
                        if (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') {
                            $options[] = 'Custom Size';
                        }
                        $optionsText = !empty($options) ? implode(' | ', $options) : '—';

                        $itemsRows .= '<tr style="border-bottom:1px solid #e5e7eb;">'
                            . '<td style="padding:8px 0;">' . $productName . '<br><span style="color:#6b7280;font-size:12px;">' . $optionsText . '</span></td>'
                            . '<td style="padding:8px 0;text-align:center;">' . $quantity . '</td>'
                            . '<td style="padding:8px 0;text-align:right;">' . $currencySymbol . number_format($unitPrice, 2) . '</td>'
                            . '<td style="padding:8px 0;text-align:right;">' . $currencySymbol . number_format($lineTotal, 2) . '</td>
                            . '</tr>';
                    }

                    if ($itemsRows === '') {
                        $itemsRows = '<tr><td colspan="4" style="padding:12px 0;text-align:center;">No order items found.</td></tr>';
                    }

                    $summaryRows = '<tr>'
                        . '<td style="padding:4px 0;">Subtotal:</td>'
                        . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['subtotal'] ?? 0), 2) . '</td>'
                        . '</tr>';

                    if (!empty($order['discount_amount'])) {
                        $summaryRows .= '<tr>'
                            . '<td style="padding:4px 0;">Discount:</td>'
                            . '<td style="padding:4px 0;text-align:right;">- ' . $currencySymbol . number_format((float) $order['discount_amount'], 2) . '</td>
                            . '</tr>';
                    }

                    $summaryRows .= '<tr>'
                        . '<td style="padding:4px 0;">Shipping:</td>'
                        . '<td style="padding:4px 0;text-align:right;">' . $currencySymbol . number_format((float) ($order['shipping_fee'] ?? 0), 2) . '</td>
                        . '</tr>';

                    $summaryRows .= '<tr>'
                        . '<td style="padding:8px 0;font-weight:bold;">Grand Total:</td>'
                        . '<td style="padding:8px 0;text-align:right;font-weight:bold;">' . $currencySymbol . number_format((float) ($order['grand_total'] ?? 0), 2) . '</td>
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
                                <p style="margin:4px 0;"><strong>Order Date:</strong> ' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</p>
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
                            <table style="width:100%;margin-top:16px;border-collapse:collapse;">
                                ' . $summaryRows . '
                            </table>
                            ' . $invoiceButton . '
                            <p style="margin-top:24px;">If you have any questions, reply to this email and our team will be happy to help.</p>
                        </div>
                    ';
                }
            }

            $orderData = $emailContext['order'];
            $shipping = $emailContext['shipping'] ?? [];
            $orderItems = $emailContext['items'] ?? [];
            $invoiceLink = $emailContext['invoice_link'] ?? null;
            $websiteOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'viennabytnq.com');

            // --- Send Customer Email ---
            if (!empty($orderData['email'])) {
                $customerMail = initOrderMailer();
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
            }

            // --- Send Admin/Client Email ---
            // $site_email is now loaded from your database
            if (!empty($site_email)) {
                $clientMail = initOrderMailer();
                $clientMail->addAddress($site_email, $site_name ?? 'Store Admin');
                
                // $site_email_2 is also loaded from your database
                if (!empty($site_email_2)) {
                    $clientMail->addCC($site_email_2, $site_name ?? 'Store Admin');
                }
                
                $clientMail->Subject = 'New Paid Order - ' . ($orderData['order_number'] ?? '');
                $clientMail->Body = buildOrderEmailBody(
                    $orderData,
                    $shipping,
                    $orderItems,
                    'New paid order received',
                    'A new customer order has been paid on ' . $websiteOrigin . '. Review the order details below.',
                    $invoiceLink
                );

                if (!$clientMail->send()) {
                    error_log('Order email to client failed: ' . $clientMail->ErrorInfo);
                }
            }
        } // End if (!empty($emailContext))

        // Redirect to the success page to trigger the notification
        header("Location: /order-success?ref=" . $reference);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        // CATCHING AND LOGGING ANY DATABASE OR TRANSACTION ERRORS
        error_log("Payment Verification DB/Transaction Error: " . $e->getMessage());
        header("Location: /checkout?error=db_transaction_error");
        exit;
    }

} else {
    // Payment failed from Paystack
    header("Location: /checkout?error=payment_failed_paystack");
    exit;
}
