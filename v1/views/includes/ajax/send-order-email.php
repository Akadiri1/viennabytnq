<?php

/**
 * Main function to format and send an entire order notification to WhatsApp.
 *
 * @param array $orderItems   An array of items fetched from your database.
 * @param array $config       An array of configuration values (Twilio credentials, URLs, etc.).
 * @param array $orderDetails An array with order-level details like 'order_number', 'shipping_fee', 'grand_total'.
 * @param array $buyerDetails An array containing buyer info like 'name' and 'phone'.
 */
function sendOrderNotificationToWhatsApp(array $orderItems, array $config, array $orderDetails, array $buyerDetails = []) {
    // Exit if there is nothing to process
    if (empty($orderItems) || empty($orderDetails)) {
        error_log("WhatsApp Error: Missing order items or order details.");
        return;
    }

    $orderNumber = $orderDetails['order_number'] ?? 'N/A';

    // --- Build the main text message ---
    $message = "🎉 *New Order Received!* 🎉\n";
    $message .= "_Order #: " . htmlspecialchars($orderNumber) . "_\n\n";
    $message .= "-----------------------------------\n\n";

    $itemCounter = 1;

    foreach ($orderItems as $item) {
        // --- Send the image for this item FIRST ---
        $imageUrl = rtrim($config['website_url'], '/') . '/' . ltrim($config['images_path'], '/') . $item['product_image'];
        $caption = htmlspecialchars($item['product_name']);
        
        sendWhatsAppImage(
            $config['twilio_sid'], 
            $config['twilio_token'], 
            $config['twilio_number'], 
            $config['recipient_number'], 
            $imageUrl, 
            $caption
        );

        // --- Append item details to the main text message ---
        $message .= "*Item " . $itemCounter++ . ": " . htmlspecialchars($item['product_name']) . "*\n";
        $message .= "- _Quantity:_ " . $item['quantity'] . "\n";
        $message .= "- _Price:_ $" . number_format($item['price_per_unit'], 2) . " each\n";

        if (!empty($item['color_name'])) $message .= "- _Color:_ " . htmlspecialchars($item['color_name']) . "\n";
        if (!empty($item['custom_color_name'])) $message .= "- _Color (Custom):_ " . htmlspecialchars($item['custom_color_name']) . "\n";
        if (!empty($item['size_name'])) $message .= "- _Size:_ " . htmlspecialchars($item['size_name']) . "\n";
        if (!empty($item['custom_size_details'])) $message .= "- _Size (Custom):_ " . htmlspecialchars($item['custom_size_details']) . "\n";
        
        $message .= "\n"; // Space between items
    }

    // --- MODIFIED: Build the final financial summary ---
    $message .= "-----------------------------------\n";
    $message .= "Subtotal: $" . number_format($orderDetails['subtotal'], 2) . "\n";
    $message .= "Shipping: $" . number_format($orderDetails['shipping_fee'], 2) . "\n";

    if (!empty($orderDetails['discount_amount']) && $orderDetails['discount_amount'] > 0) {
        $message .= "Discount: -$" . number_format($orderDetails['discount_amount'], 2) . "\n";
    }

    $message .= "*Grand Total: $" . number_format($orderDetails['grand_total'], 2) . "*\n\n";
    // --- END MODIFIED ---

    // --- Include buyer details if they are provided ---
    if (!empty($buyerDetails)) {
        $message .= "👤 *Buyer Information:*\n";
        if (!empty($buyerDetails['name'])) {
            $message .= "- Name: " . htmlspecialchars($buyerDetails['name']) . "\n";
        }
        // This includes the customer's phone number
        if (!empty($buyerDetails['phone'])) {
            $message .= "- Phone: " . htmlspecialchars($buyerDetails['phone']) . "\n";
        }
        $message .= "\n"; // Add a final space after the buyer info block
    }

    // --- Send the final consolidated text message ---
    sendWhatsAppMessage(
        $config['twilio_sid'], 
        $config['twilio_token'], 
        $config['twilio_number'], 
        $config['recipient_number'], 
        $message
    );
}

/**
 * Sends a text-only message using the Twilio API.
 */
function sendWhatsAppMessage($accountSid, $authToken, $twilioNumber, $recipientNumber, $text) {
    $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . $accountSid . "/Messages.json";
    $data = [
        'To' => $recipientNumber,
        'From' => $twilioNumber,
        'Body' => $text
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ":" . $authToken);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

/**
 * Sends an image message using the Twilio API.
 */
function sendWhatsAppImage($accountSid, $authToken, $twilioNumber, $recipientNumber, $imageUrl, $caption = '') {
    $endpoint = "https://api.twilio.com/2010-04-01/Accounts/" . $accountSid . "/Messages.json";
    $data = [
        'To' => $recipientNumber,
        'From' => $twilioNumber,
        'MediaUrl' => $imageUrl,
        'Body' => $caption // The 'Body' becomes the caption for an image
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ":" . $authToken);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}