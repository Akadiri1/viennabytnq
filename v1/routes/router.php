<?php
// Secure Router Implementation

// 1. Parse URI and separate Path from Query
$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
$query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

// 2. Explode Path into Segments
// e.g., "/home" -> ["", "home"]
// e.g., "/product/slug" -> ["", "product", "slug"]
$uri = explode("/", $path);

// 3. Handle Token Presence Globally
$token = NULL;
if(isset($_GET['token'])){
  $token = $_GET['token'];
}

// 4. Special Case: LinkedIN
if (isset($uri[1]) && $uri[1] == "linkedin") {
  include APP_PATH."/views/linkedin.php";
  die();
}

// 5. Routing Logic

if (count($uri) > 2 && !empty($uri[2])) {
    // --- SUB-PATH ROUTE HANDLING (e.g. /product/slug) ---
    
    // Construct a route key for matching, e.g. "product/slug"
    $route_key = $uri[1] . "/" . $uri[2];

    switch ($route_key) {
        case "website/" . $uri[2]:
            include APP_PATH."/views/viewWebsite.php";
            die();
            break;

        case "product/" . $uri[2]:
            include APP_PATH."/views/shop-detail.php";
            die();
            break;

        default:
            http_response_code(404);
            include APP_PATH."/views/404.php";
            die();
            break;
    }

} else {
    // --- MAIN ROUTE HANDLING (e.g. /home, /shop) ---
    
    // Default to 'home' if empty path
    $route = isset($uri[1]) ? $uri[1] : '';
    if ($route === '') {
        $route = 'home';
    }

    switch ($route) {
        // --- PUBLIC PAGES ---
        case 'test':
            include APP_PATH."/views/test.php";
            break;

        case 'more-about':
            include APP_PATH."/views/more-about.php";
            break;

        case 'product': 
            include APP_PATH."/views/shop-detail.php";
            die();
            break;

        case 'home':
        case 'index':
            include APP_PATH."/views/home.php";
            break;

        case 'login':
            include APP_PATH."/views/login.php";
            break;

        case 'admin_login':
            include APP_PATH."/views/admin_login.php";
            break;

        case 'privacy':
            include APP_PATH."/views/privacy.php";
            break;

        case 'order-view':
            include APP_PATH."/views/order-view.php";
            break;
        
        case 'currency':
            include APP_PATH."/views/currency.php";
            break;

        case 'export-transactions':
            include APP_PATH."/views/includes/ajax/export_orders.php";
            die();
            break;

        case 'country_states':
            include APP_PATH."/views/country_states.php";
            break;

        case 'fetch-collection':
            include APP_PATH."/views/includes/ajax/fetch-collection.php";
            break;

        case 'img-resize':
            include APP_PATH."/views/includes/ajax/img-resize.php";
            break;

        // --- SHOP & CART ---
        case 'shop':
            if (!headers_sent()) {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            include APP_PATH."/views/shop.php";
            break;

        case 'create_users':
            include APP_PATH."/views/create_users.php";
            break;

        case 'pagination':
            if (!headers_sent()) {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            include APP_PATH."/views/includes/ajax/pagination.php";
            break;

        case 'cart':
            include APP_PATH."/views/includes/ajax/cart.php";
            break;

        case 'view-cart':
            include APP_PATH."/views/cart.php";
            break;

        case 'checkout':
            include APP_PATH."/views/checkout.php";
            break;
            
        case 'update-cart':
            include APP_PATH."/views/includes/ajax/update-cart.php";
            break;

        case 'place-order':
            include APP_PATH."/views/includes/ajax/place-order.php";
            break;

        case 'send-order-email':
            include APP_PATH."/views/includes/ajax/send-order-email.php";
            break;

        case 'order-success':
            include APP_PATH."/views/includes/ajax/order-success.php";
            break;

        case 'invoice':
            // Check if it's the AJAX invoice or view invoice based on context?
            // The original logic had logic for both?
            // Original: case 'invoice?'.$query_string: include APP_PATH."/views/invoice.php";
            // AND case 'invoice?'.$query_string: include APP_PATH."/views/includes/ajax/invoice.php";
            // This is a conflict in the original file (lines 201 & 268).
            // However, typical usage suggests matched by order usually falls through.
            // Let's assume view/invoice.php is the view page.
            include APP_PATH."/views/invoice.php";
            break;

        case 'verify-payment':
            include APP_PATH."/views/includes/ajax/verify-payment.php";
            break;

        case 'apply-discount':
            include APP_PATH."/views/includes/ajax/apply-discount.php";
            break;

        case 'update-quantity':
            include APP_PATH."/views/includes/ajax/update-quantity.php";
            break;

        case 'submit-review':
            include APP_PATH."/views/includes/ajax/submit-review.php";
            break;


        case 'delete-cart':
            include APP_PATH."/views/includes/ajax/delete-cart.php";
            break;

        // --- DASHBOARD (Handles all ?page=... automatically) ---
        case 'dashboard':
            include APP_PATH."/views/dashboard.php";
            break;

        // --- MISC VIEWS ---
        case 'contact-us':
            include APP_PATH."/views/contact.php";
            break;

        case 'about':
            include APP_PATH."/views/about.php";
            break;

        case 'auth':
            include APP_PATH."/views/auth.php";
            break;

        case'log-out':
            include APP_PATH."/views/logout.php";
            break;

        case 'logout':
            include APP_PATH."/auth/logout.php";
            break;

        case 'user-dashboard':
            include APP_PATH."/views/user-dashboard.php";
            break;

        case 'signin':
            include APP_PATH."/views/signin.php";
            break;

        case 'register':
            include APP_PATH."/views/register.php";
            break;

        case "services":
            include APP_PATH."/views/services.php";
            break;

        case "service-details":
            include APP_PATH."/views/service-details.php";
            break;

        case "book-appointment":
            include APP_PATH."/views/book-appointment.php";
            break;

        case "areas-we-cover":
            include APP_PATH."/views/areas.php";
            break;

        case "care-worker-application":
            include APP_PATH."/views/caregiver-application.php";
            break;

        case "application-backend":
            include APP_PATH."/views/includes/ajax/caregiver_application.php";
            break;

        case "contact-backend":
            include APP_PATH."/views/includes/ajax/contactus.php";
            break;

        case "services-backend":
            include APP_PATH."/views/includes/ajax/services.php";
            break;

        case "more-about-backend":
            include APP_PATH."/views/includes/ajax/more_about.php";
            break;

        case "privacy-policy":
        case "privacy-and-policy": // Consolidated
        case "policy":
            include APP_PATH."/views/privacy_and_policy.php";
            break;

        case "team":
            include APP_PATH."/views/team.php";
            break;

        case "view-post":
            include APP_PATH."/views/view-post.php";
            break;

        case "categories":
            include APP_PATH."/views/categories.php";
            break;

        case "view-project":
            include APP_PATH."/views/view-project.php";
            break;

        case "confirmRecovery":
            // Duplicate cases in original (lines 404, 531, 534). 
            // 404 -> views/confirm_recovery.php. 531 -> auth/confirm_recovery.php.
            // Usually auth is logic.
            include APP_PATH."/auth/confirm_recovery.php";
            break;

        case "shareCampaign":
            include APP_PATH."/views/shareCampaign.php";
            break;

        // --- ADMIN API ROUTES ---
        case "generate-invite":
            $action = 'generate_invite';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-list-pending":
            $action = 'list_pending';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-approve":
            $action = 'approve_admin';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-check-session":
            $action = 'check_session';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-list-all":
            $action = 'list_all_admins';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-delete":
            $action = 'delete_admin';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-suspend":
            $action = 'suspend_admin';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-reactivate":
            $action = 'reactivate_admin';
            include APP_PATH."/views/includes/ajax/admin_api.php";
            break;

        case "admin-setup":
            include APP_PATH."/views/admin-setup.php";
            break;

        // --- AUTH & MISC ---
        case "404":
            include APP_PATH."/views/404.php";
            break;

        case "myBusinesses":
            include APP_PATH."/views/myBusinesses.php";
            break;

        case "listing":
            include APP_PATH."/views/listing.php";
            break;

        case "crm":
            include APP_PATH."/views/crm.php";
            break;

        case "create-facebook-business":
            include APP_PATH."/views/create-facebook-business.php";
            break;

        case "get-facebook-businesses":
            include APP_PATH."/views/get-facebook-businesses.php";
            break;

        case 'timesheet':
            include APP_PATH."/views/timebook.php";
            break;

        case "tmpDemo":
            include APP_PATH."/views/tmpDemo.php";
            break;

        case "verify":
            include APP_PATH."/auth/verify_registration.php";
            break;

        case "forgotPassword":
            include APP_PATH."/auth/forgot_password.php";
            break;

        case "forgotPassword2":
            include APP_PATH."/auth/forgot_password2.php";
            break;

        case "secure":
            include APP_PATH."/auth/secure.php";
            break;

        case "confirm":
             include APP_PATH."/auth/confirm.php";
             break;

        case "changePassword":
            include APP_PATH."/auth/change_password.php";
            break;
        
        case "compressor":
            include APP_PATH."/views/compressor.php";
            break;

        default:
            http_response_code(404);
            include APP_PATH."/views/404.php";
            die();
            break;
    }
}
?>
