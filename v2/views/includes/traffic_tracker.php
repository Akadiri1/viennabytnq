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

    // Strict IP blocklist for known common crawler ranges
    $known_bot_ips = [
        // Google
        '66.249.', '66.102.', '64.233.', '72.14.', '209.85.', '216.239.', '34.', '35.', 
        // Bing
        '157.55.', '40.77.', '13.66.', '207.46.', '104.47.', '157.54.', '157.56.', '157.58.', '157.59.', '157.60.', '208.84.',
        // Yandex (Russia)
        '5.255.', '77.88.', '87.250.', '93.158.', '95.108.', '141.8.', '213.180.', '130.193.', '178.154.', '176.103.', '90.156.', '85.26.',
        // Apple
        '17.58.', '17.111.', '17.121.', '17.52.',
        // PetalBot & Others
        '114.119.', '119.13.', '220.181.', '123.186.', '37.200.', '149.143.' // Added specific IPs user reported
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
