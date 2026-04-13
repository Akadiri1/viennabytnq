<?php
$cookie_name = 'cart_token';
$thirty_days = time() + (86400 * 30);
if (!isset($_COOKIE[$cookie_name])) {
    $token = bin2hex(random_bytes(32)); 
    setcookie($cookie_name, $token, [ 'expires' => $thirty_days, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax' ]);
    $cartToken = $token;
} else {
    $cartToken = $_COOKIE[$cookie_name];
}
header('P3P: CP="CAO PSA OUR"');
// --- END OF CART TOKEN LOGIC ---

ob_start();

session_start();
// die("Critical Maintenance in progress");
#Define App Path

define("D_PATH", dirname(dirname(__FILE__)));

// --- VERSION SWITCHING ---
// Handle /switch/v1 and /switch/v2 routes
$request_path = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('#^/switch/(v[12])$#', $request_path, $ver_match)) {
    $version = $ver_match[1];
    setcookie('app_version', $version, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    // Redirect to home after switching
    header('Location: /home');
    exit;
}

// Determine active version from cookie (default: v2)
$app_version = isset($_COOKIE['app_version']) && $_COOKIE['app_version'] === 'v1' ? 'v1' : 'v2';
define("APP_VERSION", $app_version);
define("APP_PATH", D_PATH."/".$app_version);

#load config
include D_PATH."/.env/config.php";
#load database
require APP_PATH."/models/model.php";

// --- VISITOR TRACKING ---
// Track visits if not an admin and not an AJAX request
if (!isset($_SESSION['admin_logged_in'])) {
    try {
        // --- Improved IP Detection for Production (Cloudflare & Proxies) ---
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        // If coming through Cloudflare, trust CF-Connecting-IP
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } 

        $visit_url = $_SERVER['REQUEST_URI'] ?? '/';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $currentHost = $_SERVER['HTTP_HOST'] ?? 'Unknown';
        
        // --- 0. Filters (AJAX, Bots, API Data, Subdomain, and Session Lock) ---
        $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $is_api = preg_match('#^/(ajax|currency|cart-update|fetch-cart|process-order|submit-review)#i', $visit_url);
        $is_scanner = preg_match('#(wp-admin|wp-login|wp-includes|\.git|\.env|setup-config|xmlrpc|phpmyadmin|\.well-known|admin/controller|sites/default|composer\.json)#i', $visit_url);
        
        // Block non-human URL requests (robots.txt, sitemap, etc.)
        $is_bot_url = preg_match('#^/(robots\.txt|sitemap\.xml|favicon\.ico|\.well-known)#i', $visit_url);

        // Exclude admin/internal pages — these are not customer visits
        $is_admin_page = preg_match('#^/(dashboard|admin_login|admin-setup|manage|seed_admin|debug)#i', $visit_url);

        // Only track the main domain — exclude subdomains like mail.viennabytnq.com
        $allowed_hosts = ['viennabytnq.com', 'www.viennabytnq.com', 'localhost', '127.0.0.1', 'viennabytnq.local'];
        $is_subdomain = !in_array($currentHost, $allowed_hosts);

        // Strict IP blocklist for known common crawler/bot/cloud ranges
        $known_bot_ips = [
            // Google
            '66.249.', '66.102.', '64.233.', '72.14.', '209.85.', '216.239.', 
            // Bing
            '157.55.', '40.77.', '13.66.', '207.46.', '104.47.', '157.54.', '157.56.', '157.58.', '157.59.', '157.60.', '208.84.',
            // Yandex (Russia)
            '5.255.', '77.88.', '87.250.', '93.158.', '95.108.', '141.8.', '213.180.', '130.193.', '178.154.', '176.103.', '90.156.', '85.26.',
            // Apple
            '17.58.', '17.111.', '17.121.', '17.52.',
            // PetalBot & Others
            '114.119.', '119.13.', '220.181.', '123.186.', '37.200.', '149.143.',
            // Facebook / Meta crawlers (data centers — not real users)
            '31.13.', '66.220.', '173.252.', '69.63.', '69.171.', '74.119.76.',
            // DigitalOcean (bot hosting)
            '138.197.', '142.93.', '45.55.', '104.131.', '159.89.', '167.99.', '165.227.', '134.209.', '64.225.',
            // AWS common bot ranges
            '107.22.', '54.236.', '54.173.', '54.162.',
            // Russian bot/scanner ranges
            '85.143.', '45.138.', '45.147.',
            // Known monitoring/scanner services
            '205.169.',
        ];
        $is_bot_ip = false;
        foreach ($known_bot_ips as $bot_ip) {
            if (strpos($ip_address, $bot_ip) === 0) {
                $is_bot_ip = true;
                break;
            }
        }

        // Regex to catch common bots, spiders, crawlers, and AI scrapers
        $is_bot_user_agent = preg_match('/(bot|spider|crawl|slurp|facebookexternalhit|facebook|whatsapp|petalbot|datanyze|yandex|bingbot|applebot|googlebot|curl|python-requests|headless|wget|go-http-client|ips-agent|postman|insomnia|httpclient|lighthouse|pagespeed|semrush|ahrefs|mj12bot|dotbot|screaming.frog|bytespider|gptbot|claudebot|ccbot|amazonbot|chatgpt|barkrowler|dataforseo|zoominfobot|censys|masscan|zgrab)/i', $user_agent);
        
        $is_bot = $is_bot_ip || $is_bot_user_agent;

        if (trim($user_agent) === 'Mozilla/5.0' || empty(trim($user_agent))) {
            $is_bot = true; // Generic scraper footprint
        }

        // Track only if it passes ALL filters
        if (!$is_ajax && (!$is_api || $visit_url === '/') && !$is_bot && !$is_scanner && !$is_bot_url && !$is_admin_page && !$is_subdomain && !isset($_SESSION['visit_tracked'])) {

            // Simple optimization: Don't track static assets if they accidentally route here
            if (!preg_match('/\.(jpg|jpeg|png|gif|css|js|ico|svg|woff|woff2)$/i', $visit_url)) {

                // --- Duplicate visit protection ---
                // Use IP + user_agent fingerprint so different people on same WiFi are counted
                // but the same person refreshing multiple times is not
                try {
                    $visitor_fingerprint = md5($ip_address . '|' . $user_agent);
                    $stmtDup = $conn->prepare("SELECT id FROM site_visits WHERE ip_address = ? AND user_agent = ? AND DATE(created_at) = CURDATE() LIMIT 1");
                    $stmtDup->execute([$ip_address, $user_agent]);
                    if ($stmtDup->fetch()) {
                        $_SESSION['visit_tracked'] = true;
                        goto end_tracking; // Skip — already recorded today
                    }
                } catch (Exception $e) { /* Column may not exist yet, continue */ }

                // Set session lock so we don't track them again on next page click
                $_SESSION['visit_tracked'] = true;
            
            // --- 1. Geolocation Logic ---
            $country = null;
            $region = null;
            $city = null;
            $country_code = null;

            // Helper function for production-ready geolocation
            if (!function_exists('fetchGeoData')) {
                function fetchGeoData($ip) {
                    $url = "http://ip-api.com/json/{$ip}";
                    $data = null;

                    // Try cURL first (more robust)
                    if (function_exists('curl_init')) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        if ($response) $data = json_decode($response, true);
                    }

                    // Fallback to file_get_contents
                    if (!$data && ini_get('allow_url_fopen')) {
                        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                        $response = @file_get_contents($url, false, $ctx);
                        if ($response) $data = json_decode($response, true);
                    }

                    return ($data && isset($data['status']) && $data['status'] === 'success') ? $data : null;
                }
            }
            
            try {
                // Check if we already know this IP's location
                $stmtLoc = $conn->prepare("SELECT * FROM site_visits WHERE ip_address = ? AND country IS NOT NULL LIMIT 1");
                $stmtLoc->execute([$ip_address]);
                $existing = $stmtLoc->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $country = $existing['country'] ?? null;
                    $region = $existing['region'] ?? null;
                    $city = $existing['city'] ?? null;
                    $country_code = $existing['country_code'] ?? null;
                } else {
                    // Fetch new
                    if ($ip_address === '127.0.0.1' || $ip_address === '::1') {
                         $country = 'Localhost';
                         $region = 'Private Network';
                         $city = 'Local';
                         $country_code = 'LOC';
                    } else {
                        $data = fetchGeoData($ip_address);
                        if ($data) {
                            $country = $data['country'] ?? null;
                            $region = $data['regionName'] ?? null;
                            $city = $data['city'] ?? null;
                            $country_code = $data['countryCode'] ?? null;
                        }
                    }
                }
            } catch (Exception $e) { /* Ignore Geo errors */ }

            // --- 2. Device & OS Parsing (FIXED ORDER) ---
            $device_type = 'Desktop'; // Default
            $os_name = 'Unknown';

            // Detect Device — check specific patterns first, then fall back to Desktop
            if (preg_match('/iphone/i', $user_agent)) {
                $device_type = 'Mobile';
            } elseif (preg_match('/ipad/i', $user_agent)) {
                $device_type = 'Tablet';
            } elseif (preg_match('/(tablet|playbook|kindle|silk)|(android(?!.*(mobi|phone)))/i', $user_agent)) {
                $device_type = 'Tablet';
            } elseif (preg_match('/(mobi|phone|ipod|android|iemobile|up\.browser|up\.link|mmp|symbian|smartphone|midp|wap)/i', $user_agent)) {
                $device_type = 'Mobile';
            }
            // Instagram/Facebook in-app browsers on phones
            if (preg_match('/instagram|FBAV|FBAN/i', $user_agent) && !preg_match('/ipad|tablet/i', $user_agent)) {
                if (preg_match('/iphone|android.*mobi|android.*mobile/i', $user_agent)) {
                    $device_type = 'Mobile';
                }
            }

            // Detect OS — IMPORTANT: check specific (iOS, Android) BEFORE generic (Mac OS, Linux)
            // because iOS UAs contain "like Mac OS X" and Android UAs contain "Linux"
            if (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
                $os_name = 'iOS';
            } elseif (preg_match('/android/i', $user_agent)) {
                $os_name = 'Android';
            } elseif (preg_match('/windows|win32/i', $user_agent)) {
                $os_name = 'Windows';
            } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
                $os_name = 'Mac OS';
            } elseif (preg_match('/linux/i', $user_agent)) {
                $os_name = 'Linux';
            } elseif (preg_match('/cros/i', $user_agent)) {
                $os_name = 'Chrome OS';
            }

            // --- 3. Save Visit ---
            try {
                $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region, city, device_type, os_name, country_code, host) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region, $city, $device_type, $os_name, $country_code, $currentHost]);
            } catch (PDOException $e) {
                // Fallback if column missing
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region, city, device_type, os_name, host) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region, $city, $device_type, $os_name, $currentHost]);
                    } catch(PDOException $e2) {
                        try {
                            $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region, host) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region, $currentHost]);
                        } catch(Exception $e3) {
                             // Final legacy fallback
                             $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region) VALUES (?, ?, ?, ?, ?, ?)");
                             $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region]);
                        }
                    }
                }
            }
        } // Closes if (!preg_match static asset)
    } // Closes if (filters pass)

    end_tracking: // Jump target for duplicate protection skip

} catch (Exception $e) {
    // Silently fail to avoid disrupting the user experience
}
}

#load Controllers(functions)
require APP_PATH."/controllers/controller.php";

#load auth Controllers(functions)
require APP_PATH."/auth/auth_controller/controller.php";
#load routes
// require APP_PATH."/routes/router.php";

$websiteInfo = selectContent($conn, "read_website_info", ['visibility' => 'show']);
$socialLinks = selectContent($conn, "social_links", ['visibility' => 'show']);
$homeProduct = selectContent($conn, "home_product", ['visibility' => 'show']);
// $officeHours = selectContent($conn, "panel_office_hours", ['visibility' => 'show']);
// $websiteStyle = selectContent($conn, "website_status", ['visibility' => 'show']);

$_SESSION['color'] = "green";
// $_SESSION['debug'] = true;
//
$site_name = $websiteInfo[0]['input_name'];
$site_email = $websiteInfo[0]['input_email'];
$site_email_2 = $websiteInfo[0]['input_email_2'];
$site_email_from = $websiteInfo[0]['input_email_from'];
$site_email_smtp_host = $websiteInfo[0]['input_email_smtp_host'];
$site_email_smtp_secure_type = $websiteInfo[0]['input_email_smtp_secure_type'];
$site_email_smtp_port = $websiteInfo[0]['input_email_smtp_port'];
$site_email_password = $websiteInfo[0]['input_email_password'];
$site_phone = $websiteInfo[0]['input_phone_number'];
$site_phone_1 = $websiteInfo[0]['input_phone_number_1'];
$site_address = $websiteInfo[0]['input_address'];
$fbLink = $websiteInfo[0]['input_facebook'];
$igLink = $websiteInfo[0]['input_instagram'];
$linkedinLink = $websiteInfo[0]['input_linkedin'];
$twitterLink = $websiteInfo[0]['input_twitter'];
$description = $websiteInfo[0]['text_description'];
$logo_directory = $websiteInfo[0]['image_1'];
$domain = $_SERVER['HTTP_HOST'];

// die(var_dump($domain));
//
// if($websiteStyle[0]['status'] === "live"){
// if (count($websiteStyle) > 0 && $websiteStyle[0]['color'] !="") {
//   $style_color = $websiteStyle[0]['color'];
// }else{
//   // die(count($websiteStyle[0]['color']));
//   // unset($style_color);
//     }
// }
//
//
// if($websiteStyle[0]['status'] === "demo"){
// if (isset($_SESSION['color'])) {
//   $style_color = $_SESSION['color'];
// }
// }
//
//
// if($websiteStyle[0]['status'] === "demo"){
// if (isset($_SESSION['image_select'])) {
//   $logo_directory = $_SESSION['image_select'];
// }
// }


$fbid = "2213158278782711";




#load routes
include APP_PATH."/ajax/ajax_router/router.php";
// include APP_PATH."/routes/ajax_router.php";
// include APP_PATH."/payment/payment_router/router.php";
// include APP_PATH."/auth/auth_router/router.php";
// include APP_PATH."/routes/admin_router.php";
include APP_PATH."/routes/router.php";


#load auth Controllers(functions)
// require APP_PATH."/auth_controller/controller.php";

 ?>
