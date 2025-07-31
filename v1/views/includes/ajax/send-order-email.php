<?php
// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This line is crucial. It loads the PHPMailer library.
  require APP_PATH.'/phpm/PHPMailerAutoload.php';

// --- CUSTOMER EMAIL TEMPLATE ---
function generateCustomerEmailBody($order, $orderItems, $shippingDetails, $siteName) {
    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $optionsSummary = '';
        if (!empty($item['color_name'])) $optionsSummary .= $item['color_name'];
        elseif (!empty($item['custom_color_name'])) $optionsSummary .= 'Custom: ' . $item['custom_color_name'];
        
        if (!empty($item['size_name'])) $optionsSummary .= ' / ' . $item['size_name'];
        elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') $optionsSummary .= ' / Custom Size';

        $itemsHtml .= '
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 16px;">
                    <div style="display: flex; align-items: center;">
                        <img src="'.htmlspecialchars($item['product_image']).'" style="width: 64px; height: 80px; object-fit: cover; margin-right: 16px; border-radius: 4px;">
                        <div>
                            <p style="font-weight: 600; color: #111827; margin: 0;">'.htmlspecialchars($item['product_name']).'</p>
                            <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">'.htmlspecialchars($optionsSummary).'</p>
                        </div>
                    </div>
                </td>
                <td style="padding: 16px; text-align: right; color: #374151;">'.htmlspecialchars($item['quantity']).' x ₦'.number_format($item['unit_price'], 2).'</td>
                <td style="padding: 16px; text-align: right; font-weight: 600; color: #111827;">₦'.number_format($item['total_price'], 2).'</td>
            </tr>';
    }

    $customerName = htmlspecialchars($shippingDetails['fullName'] ?? 'Customer');

    // This is the full HTML structure of the email
    return '
    <!DOCTYPE html><html><body style="margin: 0; padding: 0; font-family: sans-serif; background-color: #f3f4f6;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6;"><tr><td align="center" style="padding: 40px 0;">
    <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <tr><td align="center" style="padding: 40px; border-bottom: 1px solid #e5e7eb;">
            <h1 style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;">Thank You For Your Order!</h1>
            <p style="color: #6b7280; margin-top: 8px;">Order #'.htmlspecialchars($order['order_number']).'</p>
        </td></tr>
        <tr><td style="padding: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Hi '.explode(' ', $customerName)[0].',</h2>
            <p style="color: #374151; line-height: 1.5;">Your order has been confirmed and will be shipped shortly. Here is a summary of your purchase:</p>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 32px;">'.$itemsHtml.'</table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 32px;"><tr><td align="right">
                <table border="0" cellspacing="0" cellpadding="0" style="width: 280px;">
                    <tr><td style="padding: 8px 0; color: #374151;">Subtotal:</td><td style="padding: 8px 0; text-align: right; color: #111827;">₦'.number_format($order['subtotal'], 2).'</td></tr>
                    <tr><td style="padding: 8px 0; color: #374151;">Shipping:</td><td style="padding: 8px 0; text-align: right; color: #111827;">₦'.number_format($order['shipping_fee'], 2).'</td></tr>
                    '.($order['discount_amount'] > 0 ? '<tr><td style="padding: 8px 0; color: #374151;">Discount:</td><td style="padding: 8px 0; text-align: right; color: #ef4444;">-₦'.number_format($order['discount_amount'], 2).'</td></tr>' : '').'
                    <tr style="border-top: 2px solid #e5e7eb;"><td style="padding: 16px 0 0 0; font-weight: 700; color: #111827;">Grand Total:</td><td style="padding: 16px 0 0 0; text-align: right; font-weight: 700; color: #111827;">₦'.number_format($order['grand_total'], 2).'</td></tr>
                </table>
            </td></tr></table>
            <p style="margin-top: 32px; color: #374151; line-height: 1.5;">Thank you for shopping with '.$siteName.'!</p>
        </td></tr>
        <tr><td align="center" style="padding: 24px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb;">© '.date("Y").' '.$siteName.'. All rights reserved.</td></tr>
    </table></td></tr></table></body></html>';
}


// --- ADMIN EMAIL TEMPLATE ---
function generateAdminEmailBody($order, $orderItems, $shippingDetails, $siteName, $customerEmail) {
    $itemsHtml = '';
    // This loop is identical to the customer version
    foreach ($orderItems as $item) {
        $optionsSummary = '';
        if (!empty($item['color_name'])) $optionsSummary .= $item['color_name'];
        elseif (!empty($item['custom_color_name'])) $optionsSummary .= 'Custom: ' . $item['custom_color_name'];
        
        if (!empty($item['size_name'])) $optionsSummary .= ' / ' . $item['size_name'];
        elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') {
            $optionsSummary .= ' / Custom Size';
            // Optionally, decode and display custom measurements for the admin
            $customDetails = json_decode($item['custom_size_details'], true);
            $detailsString = '';
            if($customDetails) {
                 foreach($customDetails as $key => $value) {
                     $detailsString .= ucwords(str_replace('_', ' ', $key)) . ': ' . htmlspecialchars($value) . '; ';
                 }
                 $optionsSummary .= ' ('.$detailsString.')';
            }
        }

        $itemsHtml .= '
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 16px;">
                    <p style="font-weight: 600; color: #111827; margin: 0;">'.htmlspecialchars($item['product_name']).'</p>
                    <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">'.htmlspecialchars($optionsSummary).'</p>
                </td>
                <td style="padding: 16px; text-align: center; color: #374151;">'.htmlspecialchars($item['quantity']).'</td>
                <td style="padding: 16px; text-align: right; font-weight: 600; color: #111827;">₦'.number_format($item['total_price'], 2).'</td>
            </tr>';
    }

    // --- Format the shipping address for easy reading ---
    $fullAddress = ($shippingDetails['fullName'] ?? '') . '<br>' . 
                   ($shippingDetails['address'] ?? '') . '<br>' . 
                   ($shippingDetails['city'] ?? '') . ', ' . ($shippingDetails['state'] ?? '') . ' ' . ($shippingDetails['zip'] ?? '') . '<br>' . 
                   ($shippingDetails['country'] ?? '');
    
    return '
    <!DOCTYPE html><html><body style="margin: 0; padding: 0; font-family: sans-serif; background-color: #f3f4f6;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6;"><tr><td align="center" style="padding: 40px 0;">
    <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <tr><td align="center" style="padding: 40px; border-bottom: 1px solid #e5e7eb;">
            <h1 style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;">New Order Notification</h1>
            <p style="color: #6b7280; margin-top: 8px;">Order #'.htmlspecialchars($order['order_number']).'</p>
        </td></tr>
        <tr><td style="padding: 40px;">
            <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 24px 0;">Customer Details</h2>
            <p style="color: #374151; line-height: 1.6;"><strong>Name:</strong> '.htmlspecialchars($shippingDetails['fullName']).'</p>
            <p style="color: #374151; line-height: 1.6;"><strong>Email:</strong> '.htmlspecialchars($customerEmail).'</p>
            <p style="color: #374151; line-height: 1.6;"><strong>Shipping Address:</strong><br>'.$fullAddress.'</p>

            <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin: 32px 0 16px 0;">Order Items</h2>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <thead><tr style="border-bottom: 2px solid #d1d5db;"><th style="padding: 0 16px 16px 16px; text-align: left; color: #6b7280; font-size: 12px; text-transform: uppercase;">Product</th><th style="padding: 0 16px 16px 16px; text-align: center; color: #6b7280; font-size: 12px; text-transform: uppercase;">Qty</th><th style="padding: 0 16px 16px 16px; text-align: right; color: #6b7280; font-size: 12px; text-transform: uppercase;">Total</th></tr></thead>
                <tbody>'.$itemsHtml.'</tbody>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 32px;"><tr><td align="right">
                <table border="0" cellspacing="0" cellpadding="0" style="width: 280px;">
                    <tr><td style="padding: 8px 0; color: #374151;">Subtotal:</td><td style="padding: 8px 0; text-align: right; color: #111827;">₦'.number_format($order['subtotal'], 2).'</td></tr>
                    <tr><td style="padding: 8px 0; color: #374151;">Shipping:</td><td style="padding: 8px 0; text-align: right; color: #111827;">₦'.number_format($order['shipping_fee'], 2).'</td></tr>
                    '.($order['discount_amount'] > 0 ? '<tr><td style="padding: 8px 0; color: #374151;">Discount:</td><td style="padding: 8px 0; text-align: right; color: #ef4444;">-₦'.number_format($order['discount_amount'], 2).'</td></tr>' : '').'
                    <tr style="border-top: 2px solid #e5e7eb;"><td style="padding: 16px 0 0 0; font-weight: 700; color: #111827;">Grand Total:</td><td style="padding: 16px 0 0 0; text-align: right; font-weight: 700; color: #111827;">₦'.number_format($order['grand_total'], 2).'</td></tr>
                </table>
            </td></tr></table>
        </td></tr>
        <tr><td align="center" style="padding: 24px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb;">This is an automated notification from '.$siteName.'.</td></tr>
    </table></td></tr></table></body></html>';
}


// --- CORE EMAIL SENDING FUNCTION ---
function sendOrderEmail($recipientEmail, $recipientName, $subject, $htmlBody, $fromEmail, $fromName) {
    $mail = new PHPMailer(true);

    try {
        // Server settings - IMPORTANT: Replace with your SMTP details
        $mail->isSMTP();
        $mail->Host       = 'eight.qservers.net'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@tuttomondocare.com'; // Your SMTP username
        $mail->Password   = 'Abiola@2021'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo($fromEmail, $fromName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = 'Please view this email in an HTML-compatible client.';

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error. Don't show it to the customer.
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}