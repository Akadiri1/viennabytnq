# Vienna by TNQ - AI Coding Agent Guide

## Project Overview
**Vienna by TNQ** is a PHP-based e-commerce and content management system with multi-country shipping support, admin dashboard, and payment integration (Flutterwave/Paystack).

**Architecture**: Traditional MVC with a procedural/include-based routing system. Entry point: `www/index.php` defines `APP_PATH` constant (`v1/`) and bootstraps the application.

---

## Critical Architecture Patterns

### 1. Request Flow & Routing
- **Entry**: `www/index.php` starts sessions, sets up cart tokens, loads configuration
- **Main Router**: `v1/routes/router.php` parses `REQUEST_URI`, splits into segments
- **AJAX Router**: `v1/ajax/ajax_router/router.php` handles API calls via switch on `$uri[1]` (add, read, put, delete, upload2server, etc.)
- **Key concept**: Routes are determined by URI path segments, then matched via switch statements to include files

### 2. Database Connection
- **Model file**: `v1/models/model.php` establishes global `$conn` (PDO object)
- **Credentials**: Use environment variables from `$ENV/config.php` (DB_USER, DB_PASSWORD, DB_NAME)
- **Default DB**: `viennabytnq` using PDO MySQL with ERRMODE_EXCEPTION
- **Pattern**: Pass `$conn` as parameter or assume it's globally available in included files

### 3. Controller Functions
- `v1/controllers/controller.php` (1400+ lines) contains procedural utility functions: `decodeDate()`, `insert()`, `columnSummation()`, `ForumInfo()`
- `v1/auth/auth_controller/controller.php` contains auth-specific functions: `usersLogin()`, `doChangePassword()`, `doesPhoneNumberExist()`
- **No classes**: Functions operate directly on database, use PDO prepared statements with `:named` or `?` placeholders

### 4. Session & Authentication
- Cart token stored in secure HTTP-only cookie (`cart_token`)
- User login sets `$_SESSION['id']`, `$_SESSION['username']`
- Admin login sets `$_SESSION['admin_id']`, `$_SESSION['admin_logged_in']`
- Admin invite system uses tokens with expiration (see `admin_invites` table)

### 5. Key Database Tables (Inferred)
- **users**: hash_id, email, hash (password), phone_number, firstname, lastname, usname, verification, level
- **admin_users**: id, username, password_hash, status, created_at
- **admin_invites**: token, expires_at
- **panel_products**: id, name, price, image_one, stock_quantity, visibility, collection_id
- **product_colors**: product_id, color_id
- **product_price_variants**: id, product_id, variant_name, price
- **cart_items**: cart_token, user_id, product_id, quantity, total_price, color_name, size_name, added_at
- **orders**: order_number, customer_id, payment_reference, order_status, subtotal, shipping_fee, grand_total, created_at
- **shipping_fees**: location_name, fee, country_code, currency, is_active
- **site_visits**: ip_address, visit_url, referrer, user_agent, host (visitor tracking)
- **product_reviews**: product_id, rating, review_text, is_approved, created_at

---

## Common Developer Tasks

### Adding a New Page Route
1. Create view file in `v1/views/yourpage.php`
2. Add case in `v1/routes/router.php` switch statement
3. Example:
   ```php
   case 'yourpage':
       include APP_PATH."/views/yourpage.php";
       break;
   ```

### Adding AJAX Endpoint
1. Create handler in `v1/ajax/yourhandler.php`
2. Add case in `v1/ajax/ajax_router/router.php`:
   ```php
   case "yourhandler":
       include APP_PATH."/ajax/yourhandler.php";
       exit();
       break;
   ```
3. Access via `/yourhandler` in AJAX calls

### Database Query Pattern
```php
// Prepared statement with named placeholders
$stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $user_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Or with positional placeholders
$stmt = $conn->prepare("SELECT * FROM panel_products WHERE id = ? AND visibility = ?");
$stmt->execute([$product_id, 'show']);
$product = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Payment Integration Points
- Flutterwave/Paystack webhooks: `v1/views/includes/ajax/verify-payment.php`
- Order creation after payment: Updates `orders` table with payment_reference and order_status
- Cart clearing: After successful payment, deletes cart_items with matching cart_token

### Admin Dashboard
- Access: `/dashboard` (requires `$_SESSION['admin_logged_in']`)
- Page routing: `v1/views/dashboard.php` uses `$_GET['page']` parameter (products, customers, reviews, colors, sizes)
- Check `if ($page === 'add_product')` pattern for implementing new sections

---

## Security & Validation Patterns

### Bot & Crawler Detection
- IP blocklist for known crawlers (Google, Bing, Yandex, Apple, etc.)
- User-agent regex detection
- Applied in `www/index.php` visitor tracking to filter noise

### CORS & Request Validation
- P3P header set for IE compatibility
- Cloudflare IP detection: Checks `HTTP_CF_CONNECTING_IP` if behind Cloudflare
- Admin checks: Verify `$_SESSION['admin_logged_in']` before sensitive operations

### File Upload Security
- Upload endpoints: `v1/ajax/upload2server.php`, `multiple2server.php`, `change2server.php`
- Image compression available via `v1/views/compressor.php` using Intervention Image library

---

## Environment & Configuration

- **Config file**: `.env/config.php` (contains APP_DOMAIN, DB credentials)
- **Environment variables**: DB_USER, DB_PASSWORD, DB_NAME (fallback to hardcoded defaults)
- **Production mode**: PRODUCTION_MODE env var (false by default)

---

## Common File Locations Reference

| Purpose | Location |
|---------|----------|
| Main entry | `www/index.php` |
| Router | `v1/routes/router.php` |
| AJAX router | `v1/ajax/ajax_router/router.php` |
| DB connection | `v1/models/model.php` |
| Utility functions | `v1/controllers/controller.php` |
| Auth functions | `v1/auth/auth_controller/controller.php` |
| Payment handling | `v1/views/includes/ajax/verify-payment.php` |
| Admin dashboard | `v1/views/dashboard.php` |
| Shop pages | `v1/views/shop-detail.php`, `checkout.php` |
| Mailer library | `v1/phpm/` (PHPMailer) |

---

## Important Notes for AI Agents

1. **Global $conn availability**: Most view/controller files assume `$conn` is already connected. It's initialized in `v1/models/model.php` and included early in `www/index.php`.

2. **No error.log by default**: Check `error_log()` calls in code; errors are often logged via standard PDO exception handling.

3. **Currency handling**: Multi-country shipping uses `country_code` and `currency` columns in `shipping_fees`. USD, NGN, GBP, EUR, CAD, AUD supported.

4. **include vs require**: This codebase uses `include` for views/routers; uses `require` for model/config. Include gracefully handles missing files; require is strict.

5. **Sessions and cookies**: Session data persists across page loads. Cart token is cookie-based for non-authenticated users; authenticated users also tracked by `user_id`.

6. **Disable problematic routers**: Some routers are commented out in `www/index.php` (payment_router, auth_router, admin_router). Only active routes are AJAX router and main router.

7. **Date formatting**: `decodeDate()` function converts `YYYY-MM-DD` to readable format; used throughout for display.
