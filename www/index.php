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
CONST APP_PATH = D_PATH."/v1";
#load config
include D_PATH."/.env/config.php";
#load database
require APP_PATH."/models/model.php";

// --- VISITOR TRACKING ---
// Track visits if not an admin and not an AJAX request (optional refinement)
if (!isset($_SESSION['admin_logged_in'])) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $visit_url = $_SERVER['REQUEST_URI'];
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Simple optimization: Don't track static assets if they accidentally route here
        // Simple optimization: Don't track static assets if they accidentally route here
        if (!preg_match('/\.(jpg|jpeg|png|gif|css|js|ico|svg|woff|woff2)$/i', $visit_url)) {
            
            // --- 1. Geolocation Logic ---
            $country = null;
            $region = null;
            $city = null;
            $country_code = null; // New
            
            try {
                // Check if we already know this IP's location
                $stmtLoc = $conn->prepare("SELECT country, region, city, country_code FROM site_visits WHERE ip_address = ? AND country IS NOT NULL LIMIT 1");
                $stmtLoc->execute([$ip_address]);
                $existing = $stmtLoc->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $country = $existing['country'];
                    $region = $existing['region'];
                    $city = $existing['city'] ?? null;
                    $country_code = $existing['country_code'] ?? null;
                } else {
                    // Fetch new (with timeout to prevent hanging)
                    if ($ip_address === '127.0.0.1' || $ip_address === '::1') {
                         $country = 'Localhost';
                         $region = 'Private Network';
                         $city = 'Local';
                         $country_code = 'LOC';
                    } else {
                        $ctx = stream_context_create(['http' => ['timeout' => 2]]); 
                        $json = @file_get_contents("http://ip-api.com/json/{$ip_address}", false, $ctx);
                        if ($json) {
                            $data = json_decode($json, true);
                            if (($data['status'] ?? '') === 'success') {
                                $country = $data['country'] ?? null;
                                $region = $data['regionName'] ?? null;
                                $city = $data['city'] ?? null;
                                $country_code = $data['countryCode'] ?? null;
                            }
                        }
                    }
                }
            } catch (Exception $e) { /* Ignore Geo errors */ }

            // --- 2. Device & OS Parsing ---
            $device_type = 'Desktop'; // Default
            $os_name = 'Unknown';

            // Detect Device
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
                $device_type = 'Tablet';
            } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
                $device_type = 'Mobile';
            }

            // Detect OS
            if (preg_match('/windows|win32/i', $user_agent)) {
                $os_name = 'Windows';
            } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
                $os_name = 'Mac OS';
            } elseif (preg_match('/linux/i', $user_agent)) {
                $os_name = 'Linux';
            } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
                $os_name = 'iOS';
            } elseif (preg_match('/android/i', $user_agent)) {
                $os_name = 'Android';
            }

            // --- 3. Save Visit ---
            // Note: DB schema updated in dashboard.php to include new columns
            try {
                $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region, city, device_type, os_name, country_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region, $city, $device_type, $os_name, $country_code]);
            } catch (PDOException $e) {
                // Fallback if column missing (e.g. race condition before migration run)
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                     // Try old inset
                     try {
                        $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region, city, device_type, os_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region, $city, $device_type, $os_name]);
                     } catch(PDOException $e2) {
                         // Really old fallback
                        $stmt = $conn->prepare("INSERT INTO site_visits (ip_address, visit_url, referrer, user_agent, country, region) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$ip_address, $visit_url, $referrer, $user_agent, $country, $region]);
                     }
                }
            }
        }
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
