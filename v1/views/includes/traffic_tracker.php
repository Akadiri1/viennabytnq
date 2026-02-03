<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Track Visit
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $url = $_SERVER['REQUEST_URI'] ?? '/';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, page_url, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $url, $agent]);

} catch (PDOException $e) {
    // Silently fail if tracking errors, don't break site
    error_log("Tracking Error: " . $e->getMessage());
}
?>
