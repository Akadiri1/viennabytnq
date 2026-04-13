<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// Check if JSON body (newsletter) or FormData (contact form)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$type = $input['type'] ?? 'contact';

// Get site email settings from database
$emailTo = $site_email ?? '';
$emailFrom = $site_email_from ?? $emailTo;
$smtpHost = $site_email_smtp_host ?? '';
$smtpPort = $site_email_smtp_port ?? 587;
$smtpSecure = $site_email_smtp_secure_type ?? 'tls';
$smtpPassword = $site_email_password ?? '';
$siteName = $site_name ?? 'Website';

// === NEWSLETTER SUBSCRIPTION ===
if ($type === 'newsletter') {
    $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Store in database
    try {
        // Create table if not exists
        $conn->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $conn->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Subscribed successfully!']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'You are already subscribed.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not subscribe. Please try again.']);
    }
    exit;
}

// === CONTACT FORM ===
$name = trim($input['name'] ?? '');
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$subject = trim($input['subject'] ?? 'Contact Form Message');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

// Store in database
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);
} catch (Exception $e) {
    // Log but don't fail - email might still work
}

// Send email notification
$emailSent = false;
if ($emailTo) {
    $emailBody = "New contact form submission:\n\n";
    $emailBody .= "Name: {$name}\n";
    $emailBody .= "Email: {$email}\n";
    $emailBody .= "Subject: {$subject}\n\n";
    $emailBody .= "Message:\n{$message}\n";

    $headers = "From: {$siteName} <{$emailFrom}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $emailSent = @mail($emailTo, "[{$siteName}] {$subject}", $emailBody, $headers);
}

echo json_encode([
    'status' => 'success',
    'message' => 'Thank you! Your message has been sent successfully.'
]);
