<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Track Visit
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $url = $_SERVER['REQUEST_URI'] ?? '/';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $host = $_SERVER['HTTP_HOST'] ?? 'Unknown';

    // Strict IP blocklist for known common crawler ranges (Googlebot, Bingbot, etc.)
    $known_bot_ips = [
        '66.249.', '66.102.', '64.233.', // Googlebot
        '157.55.', '40.77.', '13.66.', '207.46.', // Bingbot
        '17.58.',  // Applebot
        '114.119.' // PetalBot
    ];
    $is_bot_ip = false;
    foreach ($known_bot_ips as $bot_ip) {
        if (strpos($ip, $bot_ip) === 0) {
            $is_bot_ip = true;
            break;
        }
    }

    $is_bot_user_agent = preg_match('/(bot|spider|crawl|slurp|facebookexternalhit|whatsapp|petalbot|datanyze|yandex|bingbot|applebot|googlebot|curl|python-requests|headless|wget|go-http-client|ips-agent|postman|insomnia|httpclient|lighthouse|pagespeed)/i', $agent);
    
    if (!$is_bot_ip && !$is_bot_user_agent && trim($agent) !== 'Mozilla/5.0') {
        $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, page_url, user_agent, host) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $url, $agent, $host]);
    }

} catch (PDOException $e) {
    // Silently fail if tracking errors, don't break site
    error_log("Tracking Error: " . $e->getMessage());
}
?>
