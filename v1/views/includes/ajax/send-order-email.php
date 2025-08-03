<?php

/**
 * Main function to format and send an entire order notification to WhatsApp.
 *
 * @param array  $orderItems        An array of items fetched from your database.
 * @param string $config['recipient_number'] The website owner's number (e.g., 'whatsapp:+911234567890').
 * @param string $config['website_url']      The base URL of your site (e.g., 'https://yourwebsite.com').
 * @param string $config['images_path']      The public path to images (e.g., '/images/products/').
 * @param string $config['twilio_sid']       Your Twilio Account SID.
 * @param string $config['twilio_token']     Your Twilio Auth Token.
 * @param string $config['twilio_number']    Your Twilio WhatsApp-enabled number.
 */
function sendOrderNotificationToWhatsApp(array $orderItems, array $config) {
    // Exit if there is nothing to process
    if (empty($orderItems)) {
        return;
    }

    $orderId = $orderItems[0]['order_id'];

    // --- Build the main text message ---
    $message = "🎉 *New Order Received!* 🎉\n";
    $message .= "_Order ID: " . htmlspecialchars($orderId) . "_\n\n";
    $message .= "-----------------------------------\n\n";

    $itemCounter = 1;
    $totalValue = 0;

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

        $totalValue += $item['quantity'] * $item['price_per_unit'];
    }

    $message .= "-----------------------------------\n";
    $message .= "*Total Order Value: $" . number_format($totalValue, 2) . "*";

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
    
    // For debugging: curl_setopt($ch, CURLOPT_VERBOSE, true);
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


// =================================================================
// HOW TO USE IT
// =================================================================

/*
// 1. First, fetch your order data from the database into an array.
//    (This database query part goes in your main application logic)
$orderItemsFromDB = [
    [
        'order_id' => 12345,
        'product_name' => 'Modern Armchair',
        'product_image' => 'armchair.jpg',
        'quantity' => 1,
        'price_per_unit' => 250.00,
        'color_name' => 'Charcoal Gray',
        'custom_color_name' => '',
        'size_name' => 'Standard',
        'custom_size_details' => ''
    ],
    [
        'order_id' => 12345,
        'product_name' => 'Hand-Woven Rug',
        'product_image' => 'rug.jpg',
        'quantity' => 1,
        'price_per_unit' => 120.00,
        'color_name' => '',
        'custom_color_name' => '#A0522D',
        'size_name' => '',
        'custom_size_details' => '200cm x 300cm'
    ]
];

// 2. Set up your configuration array.
$whatsappConfig = [
    'recipient_number' => 'whatsapp:+15558675309', // The website owner's number
    'website_url'      => 'https://yourwebsite.com',
    'images_path'      => '/images/products/',
    'twilio_sid'       => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // Your Twilio SID
    'twilio_token'     => 'your_auth_token_xxxxxxxxxxxxxx',  // Your Twilio Auth Token
    'twilio_number'    => 'whatsapp:+14155238886'          // Your Twilio Number
];

// 3. Call the function. That's it!
sendOrderNotificationToWhatsApp($orderItemsFromDB, $whatsappConfig);

echo "Notification sent!";
*/