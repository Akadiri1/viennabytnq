<?php
// =================================================================================================
// INITIALIZATION & SECURITY (Preserved)
// =================================================================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin authentication
$isLegacyAdmin = isset($_SESSION['admin_logged_in']);
$isRoleAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin']);

if (!$isLegacyAdmin && !$isRoleAdmin) {
    header('Location: /admin_login');
    exit();
}

// Normalize Identity
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? 0;
$currentUserRole = $_SESSION['role'] ?? ($isLegacyAdmin ? 'super_admin' : 'user'); 

// --- SECURITY HEARTBEAT: Check Status Immediately ---
try {
    if ($isLegacyAdmin) {
        $stmtStatus = $conn->prepare("SELECT status FROM admin_users WHERE id = ?");
    } else {
        $stmtStatus = $conn->prepare("SELECT status FROM users WHERE id = ?");
    }
    $stmtStatus->execute([$currentUserId]);
    $realTimeStatus = $stmtStatus->fetchColumn();

    if ($realTimeStatus === 'suspended') {
        session_destroy();
        header("Location: /admin_login?error=suspended"); // or /login
        exit();
    }
} catch (Exception $e) { /* Ignore check errors to avoid lockout on DB fail */ }
// ----------------------------------------------------

// =================================================================================================
// IMAGE UPLOAD CONFIGURATION
// =================================================================================================
define('UPLOAD_DIR_SERVER', realpath(dirname(__DIR__) . '/../www/uploads/') . '/');
define('UPLOAD_PATH_WEB', 'uploads/');

// =================================================================================================
// POST ACTION HANDLER (Logic Unchanged)
// =================================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for Max Post Size Exceeded (Empty POST but Content-Length > 0)
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $maxPost = ini_get('post_max_size');
        $_SESSION['error_message'] = "File upload exceeds server limit ($maxPost). Please try smaller images.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Note: Database connection $conn is assumed to be available globally from your config file
    // Note: Database connection $conn is assumed to be available globally from your config file
    
    // --- USER MANAGEMENT (SECURED) ---
    if ($_POST['action'] === 'toggle_suspend') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $currentStatus = $_POST['current_status'];
        
        // Security Check: Get Target Role
        $stmtCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheck->execute([$userId]);
        $targetRole = $stmtCheck->fetchColumn();
        
        // Block modification of Super Admin
        if ($targetRole === 'super_admin' || $userId == 1) { 
             $_SESSION['error_message'] = "Action denied: Cannot suspend Super Admin.";
        } elseif ($userId == $currentUserId && !$isLegacyAdmin) {
             $_SESSION['error_message'] = "Action denied: Cannot suspend yourself.";
        } else {
             $newStatus = ($currentStatus === 'suspended') ? 'active' : 'suspended';
             $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
             $stmt->execute([$newStatus, $userId]);
             $_SESSION['success_message'] = "User status updated.";
        }
        header("Location: ?page=users");
        exit();
    }

    if ($_POST['action'] === 'delete_user') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $stmtCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheck->execute([$userId]);
        $targetRole = $stmtCheck->fetchColumn();
        
        if ($targetRole === 'super_admin' || $userId == 1 || ($userId == $currentUserId && !$isLegacyAdmin)) {
             $_SESSION['error_message'] = "Action denied: Cannot remove Super Admin or yourself.";
        } else {
            $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $_SESSION['success_message'] = "User deleted.";
        }
        header("Location: ?page=users");
        exit();
    }
    
    if ($_POST['action'] === 'update_role') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $newRole = $_POST['role'];
        
        // Prevent changing own role or Super Admin's role
        $stmtCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheck->execute([$userId]);
        $targetRole = $stmtCheck->fetchColumn();
        
        if ($userId == $currentUserId && !$isLegacyAdmin) {
             $_SESSION['error_message'] = "Cannot change your own role.";
        } elseif ($targetRole === 'super_admin' || $userId == 1) {
             $_SESSION['error_message'] = "Cannot demote Super Admin.";
        } else {
            $conn->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);
            $_SESSION['success_message'] = "User role updated.";
        }
        header("Location: ?page=users");
        exit();
    }
    if ($_POST['action'] === 'delete_gallery_image') {
        $imgId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
        if ($imgId) {
            $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->execute([$imgId]);
            $path = $stmt->fetchColumn();
            if ($path && file_exists(UPLOAD_DIR_SERVER . basename($path))) {
                unlink(UPLOAD_DIR_SERVER . basename($path));
            }
            $conn->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $table = $_POST['table'] ?? '';
        $allowed_tables = ['panel_products', 'colors', 'sizes', 'product_images', 'collections', 'product_price_variants', 'orders', 'customers'];
        if ($id && in_array($table, $allowed_tables)) {
            if ($table === 'panel_products') {
                $stmt = $conn->prepare("SELECT image_one, image_two FROM panel_products WHERE id = ?");
                $stmt->execute([$id]);
                $images = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($images) {
                    if ($images['image_one'] && file_exists(UPLOAD_DIR_SERVER . basename($images['image_one']))) {
                        unlink(UPLOAD_DIR_SERVER . basename($images['image_one']));
                    }
                    if ($images['image_two'] && file_exists(UPLOAD_DIR_SERVER . basename($images['image_two']))) {
                        unlink(UPLOAD_DIR_SERVER . basename($images['image_two']));
                    }
                }
                $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                $stmt->execute([$id]);
                $gallery_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($gallery_images as $img_path) {
                    if (file_exists(UPLOAD_DIR_SERVER . basename($img_path))) {
                        unlink(UPLOAD_DIR_SERVER . basename($img_path));
                    }
                }
                $conn->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM product_price_variants WHERE product_id = ?")->execute([$id]);
            }
            if ($table === 'orders') {
                $conn->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
            }
            $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Item deleted successfully.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($_POST['action'] === 'update_variant_stock') {
        $vid = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
        $vStock = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
        $vPrice = filter_input(INPUT_POST, 'variant_price', FILTER_VALIDATE_FLOAT);
        $isAjax = isset($_POST['ajax']);
        
        $updates = [];
        $params = [];

        if ($vStock !== null && $vStock !== false && $vStock >= 0) {
            $updates[] = "stock_quantity = ?";
            $params[] = $vStock;
        }
        if ($vPrice !== false) {
            $updates[] = "price = ?";
            $params[] = $vPrice;
        }

        if ($vid && !empty($updates)) {
            $params[] = $vid;
            $sql = "UPDATE product_price_variants SET " . implode(', ', $updates) . " WHERE id = ?";
            $conn->prepare($sql)->execute($params);
            
            $msg = "Updated successfully.";
            $_SESSION['success_message'] = $msg;
            if ($isAjax) { echo json_encode(['success' => true, 'msg' => $msg]); exit; }
        } else {
            if ($isAjax) { echo json_encode(['success' => false, 'msg' => 'Invalid input']); exit; }
        }
        header('Location: /dashboard?page=manage_products');
        exit;
    }

    if ($_POST['action'] === 'quick_add_variant') {
        $pid = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $vName = trim($_POST['variant_name']);
        $vPrice = !empty($_POST['variant_price']) ? $_POST['variant_price'] : 0;
        $vStock = filter_input(INPUT_POST, 'variant_stock', FILTER_VALIDATE_INT);
        $isAjax = isset($_POST['ajax']);

        if ($pid && $vStock > 0) {
            $conn->prepare("INSERT INTO product_price_variants (product_id, variant_name, price, stock_quantity) VALUES (?, ?, ?, ?)")
                    ->execute([$pid, $vName, $vPrice, $vStock]);
            $newId = $conn->lastInsertId();
            
            $msg = "Variant added successfully.";
            $_SESSION['success_message'] = $msg;
            if ($isAjax) { 
                echo json_encode([
                    'success' => true, 
                    'msg' => $msg, 
                    'variant' => ['id' => $newId, 'name' => $vName, 'qty' => $vStock, 'price' => $vPrice]
                ]); 
                exit; 
            }
        } else {
            if ($isAjax) { echo json_encode(['success' => false, 'msg' => 'Stock must be greater than 0']); exit; }
        }
        header('Location: /dashboard?page=manage_products');
        exit;
    }

    if ($_POST['action'] === 'add_product' || $_POST['action'] === 'edit_product') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']));
        $product_text = htmlspecialchars(trim($_POST['product_text']));
        $price = !empty($_POST['price']) ? filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) : 0.00;
        $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        $visibility = $_POST['visibility'] === 'show' ? 'show' : 'hide';
        $collection_id = filter_input(INPUT_POST, 'collection_id', FILTER_VALIDATE_INT) ?: null;

        $uploadSingleImage = function ($fileKey, $currentImageKey) {
            $imagePath = $_POST[$currentImageKey] ?? '';
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                if (!is_dir(UPLOAD_DIR_SERVER)) mkdir(UPLOAD_DIR_SERVER, 0775, true);
                $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES[$fileKey]["name"]);
                if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], UPLOAD_DIR_SERVER . $filename)) {
                    $imagePath = UPLOAD_PATH_WEB . $filename;
                }
            }
            return $imagePath;
        };

        $image_one = $uploadSingleImage('image_one', 'current_image_one');
        $image_two = $uploadSingleImage('image_two', 'current_image_two');

        if ($_POST['action'] === 'add_product') {
            $sql = "INSERT INTO panel_products (name, product_text, price, stock_quantity, visibility, image_one, image_two, collection_id, date_created, time_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";
            $conn->prepare($sql)->execute([$name, $product_text, $price, $stock_quantity, $visibility, $image_one, $image_two, $collection_id]);
            $productId = $conn->lastInsertId();
        } else {
            $sql = "UPDATE panel_products SET name=?, product_text=?, price=?, stock_quantity=?, visibility=?, image_one=?, image_two=?, collection_id=? WHERE id=?";
            $conn->prepare($sql)->execute([$name, $product_text, $price, $stock_quantity, $visibility, $image_one, $image_two, $collection_id, $id]);
            $productId = $id;

            // Clear existing variants on edit to replace with new selection
            $conn->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$productId]);
            // We are using Variants now instead of simple Sizes table, so clear variants
            $conn->prepare("DELETE FROM product_price_variants WHERE product_id = ?")->execute([$productId]);
            // Also clear sizes just in case, to avoid shop-detail.php confusion
            $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$productId]);
            // Note: We don't delete existing gallery images immediately unless requested, 
            // but for simplicity in this 'add/edit' flow, we might just append new ones. 
            // The user didn't explicitly ask for gallery MANAGEMENT (delete individual), just "add multiple images".
        }

        // --- Handle Colors (Dynamic Creation/Selection) ---
        if (isset($_POST['color_names']) && is_array($_POST['color_names'])) {
            $stmtCheckColor = $conn->prepare("SELECT id FROM colors WHERE name = ? LIMIT 1");
            $stmtInsertColor = $conn->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)");
            $stmtLinkColor = $conn->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");

            foreach ($_POST['color_names'] as $idx => $cName) {
                $cName = trim($cName);
                if (empty($cName)) continue;
                
                $cHex = $_POST['color_hexes'][$idx] ?? '#000000';
                
                // Check if color exists (by name) to reuse ID (keeps DB clean)
                $stmtCheckColor->execute([$cName]);
                $existing = $stmtCheckColor->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    $colorId = $existing['id'];
                    // Optional: Update hex if it changed? For now, we assume existing name implies existing color.
                } else {
                    $stmtInsertColor->execute([$cName, $cHex]);
                    $colorId = $conn->lastInsertId();
                }
                
                // Link to product
                $stmtLinkColor->execute([$productId, $colorId]);
            }
        }

        if (isset($_POST['variants_name']) && is_array($_POST['variants_name'])) {
            $stmtVariant = $conn->prepare("INSERT INTO product_price_variants (product_id, variant_name, price, stock_quantity) VALUES (?, ?, ?, ?)");
            foreach ($_POST['variants_name'] as $index => $vName) {
                $vName = trim($vName);
                $vPrice = isset($_POST['variants_price'][$index]) ? trim($_POST['variants_price'][$index]) : '';
                $vStock = isset($_POST['variants_stock'][$index]) ? trim($_POST['variants_stock'][$index]) : '';
                // Save variant if it has at least a price or stock (name is optional)
                if ($vPrice !== '' || $vStock !== '') {
                    $vPrice = !empty($vPrice) ? $vPrice : ($price ?: 0.00);
                    $vStock = !empty($vStock) ? $vStock : 0;
                    $stmtVariant->execute([$productId, $vName, $vPrice, $vStock]);
                }
            }
        }




        // --- Handle Gallery Images ---
        if (isset($_FILES['gallery_images'])) {
            $fileCount = count($_FILES['gallery_images']['name']);
            $stmtGallery = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . $i . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['gallery_images']['name'][$i]);
                    if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], UPLOAD_DIR_SERVER . $fileName)) {
                        $stmtGallery->execute([$productId, UPLOAD_PATH_WEB . $fileName]);
                    }
                }
            }
        }

        $_SESSION['success_message'] = "Product " . ($_POST['action'] === 'add_product' ? 'added' : 'updated') . " successfully!";
        header('Location: /dashboard?page=manage_products');
        exit;
    }

    if ($_POST['action'] === 'add_color' || $_POST['action'] === 'edit_color') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']));
        $hex_code = htmlspecialchars(trim($_POST['hex_code']));
        if ($_POST['action'] === 'add_color') {
            $conn->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)")->execute([$name, $hex_code]);
            $_SESSION['success_message'] = "Color added.";
        } else {
            $conn->prepare("UPDATE colors SET name = ?, hex_code = ? WHERE id = ?")->execute([$name, $hex_code, $id]);
            $_SESSION['success_message'] = "Color updated.";
        }
        header('Location: /dashboard?page=colors');
        exit;
    }

    if ($_POST['action'] === 'add_size' || $_POST['action'] === 'edit_size') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']));
        if ($_POST['action'] === 'add_size') {
            $conn->prepare("INSERT INTO sizes (name) VALUES (?)")->execute([$name]);
            $_SESSION['success_message'] = "Size added.";
        } else {
            $conn->prepare("UPDATE sizes SET name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success_message'] = "Size updated.";
        }
        header('Location: /dashboard?page=sizes');
        exit;
    }

    if ($_POST['action'] === 'add_collection' || $_POST['action'] === 'edit_collection') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']));
        if ($_POST['action'] === 'add_collection') {
            $conn->prepare("INSERT INTO collections (name) VALUES (?)")->execute([$name]);
            $_SESSION['success_message'] = "Collection added.";
        } else {
            $conn->prepare("UPDATE collections SET name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success_message'] = "Collection updated.";
        }
        header('Location: /dashboard?page=collections');
        exit;
    }

    if ($_POST['action'] === 'logout') {
        session_destroy();
        header('Location: /admin_login');
        exit;
    }
}

// =================================================================================================
// GET DATA FETCHER (Preserved)
// =================================================================================================
$page = $_GET['page'] ?? 'dashboard';

if ($page === 'dashboard') {
    // Safe Revenue Fetch
    try {
        $stmt = $conn->query("SELECT SUM(grand_total) FROM orders WHERE order_status = 'paid'");
        $totalRevenue = $stmt ? ($stmt->fetchColumn() ?? 0) : 0;
    } catch (Exception $e) { $totalRevenue = 0; }

    // Safe New Customers Fetch (Signups & Guests)
    try {
        // Query customers table instead of users to capture guest checkouts too
        $stmt = $conn->query("SELECT COUNT(id) FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $newCustomers = $stmt ? ($stmt->fetchColumn() ?? 0) : 0;
    } catch (Exception $e) { $newCustomers = 0; }

    // Safe Total Orders Fetch
    try {
        $stmt = $conn->query("SELECT COUNT(id) FROM orders");
        $totalOrders = $stmt ? ($stmt->fetchColumn() ?? 0) : 0;
    } catch (Exception $e) { $totalOrders = 0; }

    // Safe Total Visits Fetch (Unique Visitors)
    try {
        $stmt = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM site_visits");
        $totalVisits = $stmt ? ($stmt->fetchColumn() ?? 0) : 0;
    } catch (Exception $e) { 
        // Table likely doesn't exist, try to create it
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS site_visits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45),
                visit_url VARCHAR(255),
                referrer VARCHAR(255),
                user_agent VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                country VARCHAR(100) DEFAULT NULL,
                region VARCHAR(100) DEFAULT NULL
            )");
            $totalVisits = 0; 
        } catch (Exception $ex) {
            // Try adding columns if table exists but cols don't (Migration)
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN country VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN region VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN city VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN device_type VARCHAR(50) DEFAULT NULL"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN os_name VARCHAR(50) DEFAULT NULL"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE site_visits ADD COLUMN country_code VARCHAR(5) DEFAULT NULL"); } catch(Exception $e){}
            
            // --- USERS TABLE MIGRATION ---
            try { $conn->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'"); } catch(Exception $e){}
            try { $conn->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'"); } catch(Exception $e){}
            
            $totalVisits = 0; 
        }
    }

    // --- ENSURE USERS SCHEMA (Explicit Migration) ---
    try { $conn->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'"); } catch(Exception $e){}

    // --- VISITOR ANALYTICS DATA ---
    $analyticsData = [
        'unique_visits_chart' => [],
        'device_stats' => [],
        'os_stats' => [],
        'top_cities' => [],
        'top_countries' => [],
        'total_visitors_change' => 0
    ];

    try {
        // 1. Unique Visits Chart (Last 7 Days)
        $chartStmt = $conn->query("
            SELECT DATE(created_at) as date, COUNT(DISTINCT ip_address) as visits 
            FROM site_visits 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        if ($chartStmt) {
            $rawChartData = $chartStmt->fetchAll(PDO::FETCH_KEY_PAIR); // Date => Count
            // Fill missing dates with 0
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $analyticsData['unique_visits_chart'][$d] = $rawChartData[$d] ?? 0;
            }
        }

        // 2. Device Stats
        $deviceStmt = $conn->query("SELECT device_type, COUNT(*) as count FROM site_visits WHERE device_type IS NOT NULL GROUP BY device_type");
        if ($deviceStmt) $analyticsData['device_stats'] = $deviceStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. OS Stats
        $osStmt = $conn->query("SELECT os_name, COUNT(*) as count FROM site_visits WHERE os_name IS NOT NULL GROUP BY os_name");
        if ($osStmt) $analyticsData['os_stats'] = $osStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Top Cities
        $cityStmt = $conn->query("SELECT city, country, COUNT(DISTINCT ip_address) as visitors FROM site_visits WHERE city IS NOT NULL AND city != '' GROUP BY city, country ORDER BY visitors DESC LIMIT 5");
        if ($cityStmt) $analyticsData['top_cities'] = $cityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Top Countries (Enhanced) - Fetch country_code too
        $countryStmt = $conn->query("SELECT country, country_code, region, COUNT(DISTINCT ip_address) as visitors FROM site_visits WHERE country IS NOT NULL GROUP BY country ORDER BY visitors DESC LIMIT 200");
        if ($countryStmt) $analyticsData['top_countries'] = $countryStmt->fetchAll(PDO::FETCH_ASSOC);

        // Total Visitors Percentage Change (Today vs Yesterday)
        $visitsToday = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM site_visits WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $visitsYesterday = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM site_visits WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
        
        if ($visitsYesterday > 0) {
            $analyticsData['total_visitors_change'] = round((($visitsToday - $visitsYesterday) / $visitsYesterday) * 100, 2);
        } else {
            $analyticsData['total_visitors_change'] = $visitsToday > 0 ? 100 : 0;
        }

    } catch (Exception $e) { /* Ignore */ }

    // Legacy variable for compatibility
    $locationStats = $analyticsData['top_countries'];
    
    // Recent Orders Logic with Search
    $dashboardSearch = $_GET['dashboard_search'] ?? '';
    $recentWhere = "";
    $recentParams = [];
    if($dashboardSearch) {
        $recentWhere = "WHERE (o.order_number LIKE ? OR c.full_name LIKE ?)";
        $recentParams = ["%$dashboardSearch%", "%$dashboardSearch%"];
    }
    
    $recentOrders = $conn->prepare("SELECT o.id, o.order_number, o.subtotal, o.shipping_fee, o.discount_amount, o.discount_code, o.grand_total, o.shipping_address, o.order_status, o.created_at, c.full_name, c.email 
                                 FROM orders o JOIN customers c ON o.customer_id = c.id 
                                 $recentWhere
                                 ORDER BY o.created_at DESC LIMIT 8");
    $recentOrders->execute($recentParams);
    $recentOrders = $recentOrders->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for recent orders
    $recentOrderIds = array_column($recentOrders, 'id');
    $recentOrderDetailsMap = [];
    if (!empty($recentOrderIds)) {
        $inQuery = implode(',', array_fill(0, count($recentOrderIds), '?'));
        $stmtItems = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image_one 
            FROM order_items oi 
            LEFT JOIN panel_products p ON oi.product_id = p.id 
            WHERE oi.order_id IN ($inQuery)
        ");
        $stmtItems->execute($recentOrderIds);
        while ($row = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
            $recentOrderDetailsMap[$row['order_id']][] = $row;
        }
    }

    // Prepare chart data (Logic provided in original)
    // --- REAL ANALYTICS DATA ---
    $ordersByCountry = [];
    $monthlyRevenue = [];
    
    // Fetch all paid/completed orders (Case Insensitive)
    $stmt = $conn->query("SELECT shipping_address, grand_total, created_at FROM orders WHERE LOWER(order_status) IN ('paid', 'completed') ORDER BY created_at ASC");
    $analyticsOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($analyticsOrders as $ord) {
        // Aggregate by Country
        $addr = json_decode($ord['shipping_address'] ?? '{}', true);
        $country = $addr['country'] ?? 'Unknown';
        $country = trim($country) === '' ? 'Unknown' : $country;
        $ordersByCountry[$country] = ($ordersByCountry[$country] ?? 0) + 1;

        // Aggregate by Month
        $month = date('M Y', strtotime($ord['created_at']));
        // Remove currency symbols (₦) and commas, keep only digits and dots
        $cleanAmount = preg_replace('/[^0-9.]/', '', $ord['grand_total']);
        $amount = (float)$cleanAmount;
        $monthlyRevenue[$month] = ($monthlyRevenue[$month] ?? 0) + $amount;
    }

    // Prepare Country Data (Sort by count descending)
    arsort($ordersByCountry);
    // Limit to top 5 for cleaner chart
    $ordersByCountry = array_slice($ordersByCountry, 0, 5, true);
    $countryLabels = json_encode(array_keys($ordersByCountry));
    $countryCounts = json_encode(array_values($ordersByCountry));

    // Prepare Month Data
    $monthLabels = json_encode(array_keys($monthlyRevenue));
    $monthData = json_encode(array_values($monthlyRevenue));

    // Fallbacks for empty data to prevent JS errors
    if (empty($analyticsOrders)) {
        $countryLabels = json_encode(['No Data']);
        $countryCounts = json_encode([0]);
        $monthLabels = json_encode(['No Data']);
        $monthData = json_encode([0]);
    }
}

if ($page === 'users') {
    try {
        $sql = "
            SELECT id, username, status, created_at, 'legacy' as source, role FROM admin_users
            UNION ALL
            SELECT id, full_name as username, status, created_at, 'role' as source, role FROM users 
            WHERE role IN ('admin', 'super_admin')
            ORDER BY created_at DESC
        ";
        $stmt = $conn->query($sql);
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $allUsers = [];
    }
}

if ($page === 'manage_products') {
    $searchTerm = $_GET['search'] ?? '';
    $itemsPerPage = 10;
    $currentPage = max(1, intval($_GET['p'] ?? 1));
    $offset = ($currentPage - 1) * $itemsPerPage;
    $whereSQL = "";
    $params = [];
    if($searchTerm) {
        $whereSQL = "WHERE name LIKE ?";
        $params = ["%$searchTerm%"];
    }

    // Count for pagination
    $countStmt = $conn->prepare("SELECT COUNT(id) FROM panel_products $whereSQL");
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $itemsPerPage);

    // Fetch filtered products
    // Fetch filtered products with Variant Aggregates
    $stmt = $conn->prepare("
        SELECT *,
        (SELECT SUM(stock_quantity) FROM product_price_variants WHERE product_id = panel_products.id) as total_variant_stock,
        (SELECT GROUP_CONCAT(CONCAT(variant_name, ':', stock_quantity, ':', id, ':', price) SEPARATOR ', ') FROM product_price_variants WHERE product_id = panel_products.id) as variant_stock_details,
        (SELECT MIN(price) FROM product_price_variants WHERE product_id = panel_products.id) as min_variant_price,
        (SELECT MAX(price) FROM product_price_variants WHERE product_id = panel_products.id) as max_variant_price
        FROM panel_products $whereSQL ORDER BY id DESC LIMIT $itemsPerPage OFFSET $offset
    ");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'orders') {
    $searchTerm = $_GET['search'] ?? '';
    $itemsPerPage = 10;
    $currentPage = max(1, intval($_GET['p'] ?? 1));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    $whereSQL = "";
    $params = [];
    if($searchTerm) {
        $whereSQL = "WHERE o.order_number LIKE ? OR c.full_name LIKE ?";
        $params = ["%$searchTerm%", "%$searchTerm%"];
    }

    $countStmt = $conn->prepare("SELECT COUNT(o.id) FROM orders o JOIN customers c ON o.customer_id = c.id $whereSQL");
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $totalPages = ceil($totalOrders / $itemsPerPage);

    $sql = "SELECT o.*, c.full_name, c.email FROM orders o JOIN customers c ON o.customer_id = c.id $whereSQL ORDER BY o.created_at DESC LIMIT $itemsPerPage OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for these orders to show in details
    $orderIds = array_column($orders, 'id');
    $orderDetailsMap = [];
    if (!empty($orderIds)) {
        $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
        $stmtItems = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image_one 
            FROM order_items oi 
            LEFT JOIN panel_products p ON oi.product_id = p.id 
            WHERE oi.order_id IN ($inQuery)
        ");
        $stmtItems->execute($orderIds);
        while ($row = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
            $orderDetailsMap[$row['order_id']][] = $row;
        }
    }
}

if ($page === 'clients') {
    $searchTerm = $_GET['search'] ?? '';
    $itemsPerPage = 10;
    $currentPage = max(1, intval($_GET['p'] ?? 1));
    $offset = ($currentPage - 1) * $itemsPerPage;

    $whereSQL = "";
    $params = [];
    if($searchTerm) {
        $whereSQL = "WHERE c.full_name LIKE ? OR c.email LIKE ?";
        $params = ["%$searchTerm%", "%$searchTerm%"];
    }

    $countStmt = $conn->prepare("SELECT COUNT(id) FROM customers c $whereSQL");
    $countStmt->execute($params);
    $totalCustomers = $countStmt->fetchColumn();
    $totalPages = ceil($totalCustomers / $itemsPerPage);

    // Fetch customers with their latest order shipping info for phone/location
    $sql = "
        SELECT c.*, 
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) as order_count,
        (SELECT shipping_address FROM orders o WHERE o.customer_id = c.id ORDER BY created_at DESC LIMIT 1) as latest_address_json
        FROM customers c 
        $whereSQL
        ORDER BY c.id DESC 
        LIMIT $itemsPerPage OFFSET $offset
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'colors') {
    $colors = $conn->query("SELECT * FROM colors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $colorToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM colors WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $colorToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($page === 'sizes') {
    $sizes = $conn->query("SELECT * FROM sizes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sizeToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM sizes WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $sizeToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($page === 'collections') {
    $collections = $conn->query("SELECT * FROM collections ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $collectionToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM collections WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $collectionToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($page === 'shipping_fees') {
    // Handle shipping fees actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shipping_action'])) {
        if ($_POST['shipping_action'] === 'add' || $_POST['shipping_action'] === 'update') {
            $location_name = trim($_POST['location_name'] ?? '');
            $country_code = trim($_POST['country_code'] ?? 'NG');
            $fee = floatval($_POST['fee'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $edit_id = intval($_POST['edit_id'] ?? 0);

            if (!empty($location_name) && $fee >= 0) {
                if ($edit_id > 0) {
                    $stmt = $conn->prepare("UPDATE shipping_fees SET location_name = ?, country_code = ?, fee = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$location_name, $country_code, $fee, $is_active, $edit_id]);
                    $_SESSION['success_message'] = "Shipping fee updated successfully.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO shipping_fees (location_name, country_code, fee, is_active) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$location_name, $country_code, $fee, $is_active]);
                    $_SESSION['success_message'] = "Shipping fee added successfully.";
                }
            }
        } elseif ($_POST['shipping_action'] === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            if ($delete_id > 0) {
                $conn->prepare("DELETE FROM shipping_fees WHERE id = ?")->execute([$delete_id]);
                $_SESSION['success_message'] = "Shipping fee deleted successfully.";
            }
        } elseif ($_POST['shipping_action'] === 'delete_all') {
            $conn->exec("DELETE FROM shipping_fees");
            $_SESSION['success_message'] = "All shipping fees deleted successfully.";
        }
        header('Location: /dashboard?page=shipping_fees');
        exit;
    }

    // Fetch shipping fees
    $shippingFees = $conn->query("SELECT * FROM shipping_fees ORDER BY country_code ASC, location_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $shippingFeeToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM shipping_fees WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $shippingFeeToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ========================
// DISCOUNT CODES MANAGEMENT
// ========================
if ($page === 'discount_codes') {
    // Handle discount code actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discount_action'])) {
        if ($_POST['discount_action'] === 'add' || $_POST['discount_action'] === 'update') {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discount_type = $_POST['discount_type'] ?? 'percentage';
            $discount_value = floatval($_POST['discount_value'] ?? 0);
            $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
            $max_uses = intval($_POST['max_uses'] ?? 0);
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $edit_id = intval($_POST['edit_id'] ?? 0);

            if (!empty($code) && $discount_value > 0) {
                if ($edit_id > 0) {
                    $stmt = $conn->prepare("UPDATE discounts SET code = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_uses = ?, expiry_date = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_uses, $expiry_date, $is_active, $edit_id]);
                    $_SESSION['success_message'] = "Discount code updated successfully.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO discounts (code, discount_type, discount_value, min_order_amount, max_uses, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_uses, $expiry_date, $is_active]);
                    $_SESSION['success_message'] = "Discount code added successfully.";
                }
            }
        } elseif ($_POST['discount_action'] === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            if ($delete_id > 0) {
                $conn->prepare("DELETE FROM discounts WHERE id = ?")->execute([$delete_id]);
                $_SESSION['success_message'] = "Discount code deleted successfully.";
            }
        } elseif ($_POST['discount_action'] === 'delete_all') {
            $conn->exec("DELETE FROM discounts");
            $_SESSION['success_message'] = "All discount codes deleted successfully.";
        }
        header('Location: /dashboard?page=discount_codes');
        exit;
    }

    // Fetch discount codes
    $discountCodes = $conn->query("SELECT * FROM discounts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $discountToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $discountToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($page === 'reviews') {
    // Handle Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
        $action = $_POST['review_action'];
        $review_id = intval($_POST['review_id'] ?? 0);

        if ($review_id > 0) {
            if ($action === 'approve') {
                $conn->prepare("UPDATE product_reviews SET is_approved = 1 WHERE id = ?")->execute([$review_id]);
                $_SESSION['success_message'] = "Review approved successfully.";
            } elseif ($action === 'unapprove') {
                $conn->prepare("UPDATE product_reviews SET is_approved = 0 WHERE id = ?")->execute([$review_id]);
                $_SESSION['success_message'] = "Review unapproved successfully.";
            } elseif ($action === 'delete') {
                $conn->prepare("DELETE FROM product_reviews WHERE id = ?")->execute([$review_id]);
                $_SESSION['success_message'] = "Review deleted successfully.";
            }
        }
        header('Location: /dashboard?page=reviews');
        exit;
    }

    // Fetch Reviews
    $reviews = $conn->query("
        SELECT r.*, p.name as product_name, p.image_one 
        FROM product_reviews r 
        JOIN panel_products p ON r.product_id = p.id 
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'add_product') {
    $colors = $conn->query("SELECT * FROM colors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sizes = $conn->query("SELECT * FROM sizes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $collections = $conn->query("SELECT * FROM collections ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $productToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM panel_products WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $productToEdit = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch associated colors with details
        $stmt = $conn->prepare("SELECT c.name, c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $productToEdit['colors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch price variants
        $stmt = $conn->prepare("SELECT * FROM product_price_variants WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$_GET['edit_id']]);
        $productToEdit['variants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch gallery images
        $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $productToEdit['gallery'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

function getStatusBadge($status) {
    $status = strtolower($status);
    $base = "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ";
    if (in_array($status, ['paid', 'show', 'completed'])) return "<span class='{$base} bg-emerald-100 text-emerald-700'>$status</span>";
    if (in_array($status, ['pending', 'processing'])) return "<span class='{$base} bg-amber-100 text-amber-700'>$status</span>";
    return "<span class='{$base} bg-rose-100 text-rose-700'>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>" />
    <title>Admin Dashboard | <?= $site_name ?? 'VIENNA' ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- JSVectorMap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .glass-sidebar { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }
        .nav-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .nav-link.active { background: rgba(255,255,255,0.05); border-left-color: #6366f1; color: white !important; }
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 1.25rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stats-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        input:focus, select:focus, textarea:focus { border-color: #6366f1 !important; ring: 2px #6366f120; }
        /* Hide scrollbar while keeping scroll functionality */
        .overflow-y-auto, .glass-sidebar { scrollbar-width: none; -ms-overflow-style: none; }
        .overflow-y-auto::-webkit-scrollbar, .glass-sidebar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body x-data="{ sidebarOpen: false }">

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/60 z-40 md:hidden backdrop-blur-sm"></div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
                <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="glass-sidebar w-64 text-slate-400 fixed inset-y-0 left-0 z-50 transform md:sticky md:top-0 md:translate-x-0 md:h-screen md:overflow-y-auto transition duration-300 ease-in-out">
            <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <?php if (!empty($logo_directory)): ?>
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden border-2 border-slate-700 shadow-sm">
                            <img src="<?= htmlspecialchars($logo_directory) ?>" alt="Logo" class="w-8 h-8 object-contain">
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden border-2 border-slate-700 shadow-sm">
                            <i class="fa-solid fa-gem text-indigo-600 text-lg"></i>
                        </div>
                    <?php endif; ?>
                    <span class="text-lg font-extrabold text-white tracking-tight uppercase"><?= $site_name ?? 'VIENNA' ?></span>
                </div>
                <!-- Mobile Close Button -->
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <nav class="mt-6 px-3 space-y-1">
                <p class="text-[10px] uppercase font-bold text-slate-500 px-3 mb-2 tracking-widest">General</p>
                <a href="/" target="_blank" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white group">
                    <i class="fa-solid fa-globe w-5 text-indigo-400 group-hover:text-white transition"></i><span class="ml-3 font-medium">View Website</span>
                </a>
                <a href="?page=dashboard" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line w-5"></i><span class="ml-3 font-medium">Dashboard</span>
                </a>
                <a href="?page=clients" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'clients' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users w-5"></i><span class="ml-3 font-medium">Clients</span>
                </a>
                <a href="?page=reviews" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'reviews' ? 'active' : '' ?>">
                    <i class="fa-solid fa-star w-5"></i><span class="ml-3 font-medium">Reviews</span>
                </a>

                <p class="text-[10px] uppercase font-bold text-slate-500 px-3 mt-8 mb-2 tracking-widest">Inventory</p>
                <div x-data="{ open: <?= in_array($page, ['add_product', 'manage_products']) ? 'true' : 'false' ?> }">
                    <button @click="open = !open" class="w-full nav-link flex items-center justify-between px-4 py-3 rounded-xl hover:text-white">
                        <div class="flex items-center"><i class="fa-solid fa-box-open w-5"></i><span class="ml-3 font-medium">Products</span></div>
                        <i class="fa-solid fa-chevron-down text-[10px] transition" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" class="ml-4 mt-1 space-y-1 border-l border-slate-800">
                        <a href="?page=add_product" @click="sidebarOpen = false" class="block py-2 px-8 text-sm hover:text-white <?= $page == 'add_product' ? 'text-indigo-400' : '' ?>">Add New</a>
                        <a href="?page=manage_products" @click="sidebarOpen = false" class="block py-2 px-8 text-sm hover:text-white <?= $page == 'manage_products' ? 'text-indigo-400' : '' ?>">Manage Catalog</a>
                    </div>
                </div>

                <a href="?page=collections" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'collections' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group w-5"></i><span class="ml-3 font-medium">Collections</span>
                </a>
                
                <p class="text-[10px] uppercase font-bold text-slate-500 px-3 mt-8 mb-2 tracking-widest">Settings</p>
                <a href="?page=shipping_fees" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'shipping_fees' ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck w-5"></i><span class="ml-3 font-medium">Shipping Fees</span>
                </a>
                <a href="?page=discount_codes" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'discount_codes' ? 'active' : '' ?>">
                    <i class="fa-solid fa-percent w-5"></i><span class="ml-3 font-medium">Discount Codes</span>
                </a>
                <!-- <a href="?page=colors" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'colors' ? 'active' : '' ?>">
                    <i class="fa-solid fa-palette w-5"></i><span class="ml-3 font-medium">Colors</span>
                </a>
                <a href="?page=sizes" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'sizes' ? 'active' : '' ?>">
                    <i class="fa-solid fa-ruler-combined w-5"></i><span class="ml-3 font-medium">Sizes</span>
                </a> -->
                <a href="?page=users" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'users' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear w-5"></i><span class="ml-3 font-medium">Admin Users</span>
                </a>

                <div class="pt-10">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="w-full flex items-center px-4 py-3 text-rose-400 hover:bg-rose-500/10 rounded-xl transition">
                            <i class="fa-solid fa-power-off w-5"></i><span class="ml-3 font-bold">Logout</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Header -->
            <header class="h-20 bg-white border-b flex items-center justify-between px-6 sticky top-0 z-30">
                <div class="flex items-center">
                    <button @click="sidebarOpen = true" class="md:hidden mr-4 text-slate-600"><i class="fa-solid fa-bars-staggered text-xl"></i></button>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight capitalize"><?= str_replace('_', ' ', $page) ?></h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:block text-right mr-2">
                        <p class="text-xs font-bold text-slate-900">Welcome! <?= htmlspecialchars($_SESSION['admin_username'] ?? 'System Admin') ?></p>
                        <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase"><?= date('D, M j, Y') ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-100 border flex items-center justify-center text-slate-400">
                        <i class="fa-solid fa-user-shield text-lg"></i>
                    </div>
                </div>
            </header>

            <main class="p-6 md:p-8 flex-1">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center shadow-sm animate-pulse">
                        <i class="fa-solid fa-circle-check mr-3"></i> 
                        <span class="font-semibold"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center shadow-sm">
                        <i class="fa-solid fa-circle-exclamation mr-3"></i> 
                        <span class="font-semibold"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                    </div>
                <?php endif; ?>

                <?php switch ($page):
                    case 'dashboard': ?>
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="glass-card p-6 flex items-center">
                                <div class="stats-icon bg-indigo-50 text-indigo-600"><i class="fa-solid fa-naira-sign text-xl"></i></div>
                                <div class="ml-5">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Revenue</p>
                                    <h3 class="text-2xl font-black text-slate-800">₦<?= number_format($totalRevenue) ?></h3>
                                </div>
                            </div>
                            <div class="glass-card p-6 flex items-center">
                                <div class="stats-icon bg-blue-50 text-blue-600"><i class="fa-solid fa-bag-shopping text-xl"></i></div>
                                <div class="ml-5">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Orders</p>
                                    <h3 class="text-2xl font-black text-slate-800"><?= number_format($totalOrders) ?></h3>
                                </div>
                            </div>
                            <div class="glass-card p-6 flex items-center">
                                <div class="stats-icon bg-emerald-50 text-emerald-600"><i class="fa-solid fa-users text-xl"></i></div>
                                <div class="ml-5">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">New Clients</p>
                                    <h3 class="text-2xl font-black text-slate-800"><?= number_format($newCustomers) ?></h3>
                                </div>
                            </div>
                            <div class="glass-card p-6 flex items-center">
                                <div class="stats-icon bg-amber-50 text-amber-600"><i class="fa-solid fa-eye text-xl"></i></div>
                                <div class="ml-5">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Visits</p>
                                    <h3 class="text-2xl font-black text-slate-800"><?= number_format($totalVisits) ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-10">
                            <div class="glass-card p-6">
                                <div class="flex justify-between items-center mb-6">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-800">Sales Growth</h4>
                                        <?php 
                                        $revenueValues = array_values($monthlyRevenue);
                                        if (count($revenueValues) >= 2) {
                                            $lastMonth = end($revenueValues);
                                            $prevMonth = prev($revenueValues);
                                            if ($prevMonth > 0) {
                                                $growth = (($lastMonth - $prevMonth) / $prevMonth) * 100;
                                                $isPositive = $growth >= 0;
                                                $color = $isPositive ? 'emerald' : 'rose';
                                                $icon = $isPositive ? 'arrow-trend-up' : 'arrow-trend-down';
                                                echo "<span class='text-[10px] font-bold text-{$color}-600 bg-{$color}-50 px-2 py-0.5 rounded flex items-center'><i class='fa-solid fa-{$icon} mr-1'></i>" . number_format(abs($growth), 1) . "%</span>";
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="flex items-center gap-2 text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100">
                                        <span class="relative flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                                        </span>
                                        Live Data
                                    </span>
                                </div>
                                <?php if (!empty($analyticsOrders)): ?>
                                    <div class="h-72"><canvas id="revenueChart"></canvas></div>
                                <?php else: ?>
                                    <div class="h-72 flex flex-col items-center justify-center text-slate-300 border-2 border-dashed border-slate-100 rounded-xl">
                                        <i class="fa-solid fa-chart-line text-4xl mb-3"></i>
                                        <p class="font-bold text-sm">No Sales Data Yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="glass-card p-6">
                                <h4 class="font-bold text-slate-800 mb-6">Sales by Region</h4>
                                <?php if (!empty($analyticsOrders)): ?>
                                    <div class="h-72"><canvas id="countryChart"></canvas></div>
                                <?php else: ?>
                                    <div class="h-72 flex flex-col items-center justify-center text-slate-300 border-2 border-dashed border-slate-100 rounded-xl">
                                        <i class="fa-solid fa-globe text-4xl mb-3"></i>
                                        <p class="font-bold text-sm">No Regional Data Yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Visitor Locations (Removed - Replaced by Traffic Analytics Section) -->
                            <!-- Unique Visitors (Moved) -->
                            <!-- Removed, merged into new layout below -->
                        </div>

                        <!-- Traffic Analytics Layout -->
                        <div class="mt-10 mb-10 space-y-6">
                             <!-- Header & Time Range -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="text-xl font-bold text-slate-800">Traffic Analytics</h4>
                                <div class="bg-white rounded-lg p-1 flex items-center border shadow-sm">
                                    <button class="px-3 py-1 text-xs font-bold bg-slate-800 text-white rounded shadow-sm">Today</button>
                                    <button class="px-3 py-1 text-xs font-bold text-slate-500 hover:text-slate-700">1 Week</button>
                                    <button class="px-3 py-1 text-xs font-bold text-slate-500 hover:text-slate-700">1 Month</button>
                                    <button class="px-3 py-1 text-xs font-bold text-slate-500 hover:text-slate-700">1 Year</button>
                                    <button class="px-3 py-1 text-xs font-bold text-slate-500 hover:text-slate-700">All Time</button>
                                </div>
                            </div>

                            <!-- Row 1: Line Chart (Wide) | Stats Col (Narrow) -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Unique VisitorsChart (Span 2) -->
                                <div class="glass-card p-6 lg:col-span-2">
                                    <div class="flex justify-between items-center mb-6">
                                        <h4 class="font-bold text-slate-800">Unique Visitors</h4>
                                        <div class="text-xs font-bold text-slate-400">Past 7 Days</div>
                                    </div>
                                    <div class="h-80"><canvas id="uniqueVisitsChart"></canvas></div>
                                </div>
                                
                                <!-- Right Column -->
                                <div class="space-y-6">
                                    <!-- Total Stats -->
                                    <div class="glass-card p-6 bg-slate-600 text-white relative overflow-hidden">
                                        <div class="relative z-10">
                                            <p class="text-xs font-bold text-slate-100 uppercase tracking-widest">Total Visitors</p>
                                            <h3 class="text-5xl font-black mt-3 text-white"><?= number_format($totalVisits) ?></h3>
                                            <div class="mt-4 flex items-center text-xs font-bold">
                                                <span class="bg-white/20 px-2 py-1 rounded text-white flex items-center gap-1">
                                                    <i class="fa-solid fa-arrow-trend-up"></i> <?= abs($analyticsData['total_visitors_change']) ?>%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Device Stats -->
                                    <div class="glass-card p-6">
                                        <h4 class="font-bold text-slate-800 mb-2 text-sm">Pageview by Device</h4>
                                        <div class="h-40"><canvas id="deviceChart"></canvas></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: OS & Cities -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- OS Stats -->
                                <div class="glass-card p-6">
                                    <h4 class="font-bold text-slate-800 mb-4">Operating System</h4>
                                    <div class="h-56 flex items-center justify-center"><canvas id="osChart"></canvas></div>
                                </div>
                                <!-- Top Cities -->
                                <div class="glass-card p-6">
                                    <h4 class="font-bold text-slate-800 mb-4">Top Cities</h4>
                                    <div class="space-y-4 max-h-56 overflow-y-auto pr-2 custom-scrollbar">
                                        <?php if (!empty($analyticsData['top_cities'])): ?>
                                            <?php foreach ($analyticsData['top_cities'] as $city): 
                                                $percentage = $totalVisits > 0 ? round(($city['visitors'] / $totalVisits) * 100, 1) : 0;
                                            ?>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                        <span class="text-slate-700 truncate max-w-[150px]" title="<?= $city['city'] ?>"><?= $city['city'] ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-slate-800"><?= $city['visitors'] ?></span>
                                                        <span class="text-[10px] text-emerald-500 bg-emerald-50 px-1 py-0.5 rounded"><?= $percentage ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-400 text-center py-4">No city data available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3: Map & Country List -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Map (Span 2) -->
                                <div class="glass-card p-6 lg:col-span-2">
                                     <div id="world-map" style="width: 100%; height: 350px;"></div>
                                </div>
                                <!-- Country List -->
                                <div class="glass-card p-6">
                                     <h4 class="font-bold text-slate-800 mb-4">Countries</h4>
                                     <div class="space-y-4 max-h-80 overflow-y-auto custom-scrollbar">
                                        <?php if (!empty($analyticsData['top_countries'])): ?>
                                            <?php foreach ($analyticsData['top_countries'] as $country): 
                                                $percentage = $totalVisits > 0 ? round(($country['visitors'] / $totalVisits) * 100, 1) : 0;
                                            ?>
                                            <div class="flex items-center gap-3 border-b border-dashed border-slate-100 pb-3 last:border-0 last:pb-0">
                                                <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between text-xs font-bold mb-1">
                                                        <span class="text-slate-700"><?= $country['country'] ?></span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="block text-sm font-bold text-slate-800"><?= $country['visitors'] ?></span>
                                                    <span class="text-[10px] text-emerald-500 font-bold"><?= $percentage ?>%</span>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="py-8 text-center text-slate-400 italic">No country data available yet.</div>
                                        <?php endif; ?>
                                     </div>
                                </div>
                            </div>

                        <!-- Orders Table -->
                        <div class="glass-card mt-10 overflow-hidden" x-data="{ showModal: false, selectedOrder: null, 
                            viewOrder(order, items) { 
                                this.selectedOrder = order; 
                                this.selectedOrder.items = items || [];
                                // Parse JSON address if string
                                try { 
                                    if(typeof this.selectedOrder.shipping_address === 'string') {
                                        this.selectedOrder.shipping_address = JSON.parse(this.selectedOrder.shipping_address);
                                    }
                                } catch(e) { console.error(e); }
                                this.showModal = true; 
                            } 
                        }">
                            <div class="px-6 py-5 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Recent Transactions</h4>
                                <div class="flex items-center gap-3">
                                    <!-- Export Button -->
                                    <a href="/export-transactions" target="_blank" class="text-xs font-bold text-slate-500 hover:text-slate-800 shrink-0 flex items-center gap-1 bg-slate-100 px-2 py-1 rounded">
                                        <i class="fa-solid fa-file-csv text-emerald-600"></i> Export CSV
                                    </a>
                                    <form method="GET" action="" class="relative">
                                        <input type="hidden" name="page" value="dashboard">
                                        <input type="text" name="dashboard_search" placeholder="Search recent..." value="<?= htmlspecialchars($_GET['dashboard_search'] ?? '') ?>" class="live-search pl-9 pr-4 py-1.5 bg-slate-50 border rounded-lg text-xs outline-none w-48 transition focus:w-64">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                                    </form>
                                    <a href="?page=orders" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 shrink-0">View All <i class="fa-solid fa-arrow-right ml-1"></i></a>
                                </div>
                            </div>
                            <div id="search-results-container" class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Ref</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Client</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($recentOrders as $order): 
                                            $orderJson = htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8');
                                            $itemsJson = htmlspecialchars(json_encode($recentOrderDetailsMap[$order['id']] ?? []), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr class="hover:bg-slate-50/50 transition duration-150">
                                            <td class="px-6 py-4 text-xs font-bold text-indigo-600 font-mono">#<?= $order['order_number'] ?></td>
                                            <td class="px-6 py-4 text-sm font-semibold text-slate-700"><?= $order['full_name'] ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-400 font-medium"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td class="px-6 py-4 text-sm font-black text-slate-800">₦<?= number_format($order['grand_total']) ?></td>
                                            <td class="px-6 py-4"><?= getStatusBadge($order['order_status']) ?></td>
                                            <td class="px-6 py-4 text-right space-x-2">
                                                <button @click="viewOrder(<?= $orderJson ?>, <?= $itemsJson ?>)" class="text-slate-400 hover:text-indigo-600" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                                <a href="/invoice?order_number=<?= $order['order_number'] ?>&mode=download_pdf" target="_blank" class="text-slate-400 hover:text-emerald-600" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></a>
                                                <form method="POST" onsubmit="return confirm('Delete order #<?= $order['order_number'] ?>? This CANNOT be undone.')" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="table" value="orders">
                                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600" title="Delete Order"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Order Details Modal -->
                            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                                <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative z-10 animate-fadeInUp">
                                    <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-20">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order Details</p>
                                            <h3 class="text-xl font-bold text-slate-800">#<span x-text="selectedOrder?.order_number"></span></h3>
                                        </div>
                                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                                    </div>
                                    <div class="p-6 space-y-8">
                                        <!-- Customer Info -->
                                        <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                            <div>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Client</p>
                                                <p class="font-bold text-slate-700" x-text="selectedOrder?.full_name"></p>
                                                <p class="text-sm text-slate-500" x-text="selectedOrder?.email"></p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Shipping Details</p>
                                                <div class="text-sm text-slate-600 font-medium">
                                                    <p x-text="selectedOrder?.shipping_address?.address"></p>
                                                    <p><span x-text="selectedOrder?.shipping_address?.city"></span>, <span x-text="selectedOrder?.shipping_address?.state"></span></p>
                                                    <p x-text="selectedOrder?.shipping_address?.country"></p>
                                                    <p x-text="selectedOrder?.shipping_address?.phoneNumber" class="mt-1 text-xs text-slate-400"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Items List -->
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Purchased Items</p>
                                            <div class="space-y-3">
                                                <template x-for="item in selectedOrder?.items" :key="item.id">
                                                    <div class="flex items-center p-3 border rounded-xl hover:bg-slate-50">
                                                        <div class="w-12 h-12 bg-slate-100 rounded-lg flex-shrink-0 bg-cover bg-center" :style="`background-image: url('/${item.image_one || ''}')`"></div>
                                                        <div class="ml-4 flex-1">
                                                            <p class="font-bold text-slate-700 text-sm" x-text="item.product_name || 'Unknown Product'"></p>
                                                            <div class="flex gap-2 text-xs text-slate-400 mt-0.5">
                                                                <span x-show="item.color_name" x-text="`Color: ${item.color_name}`"></span>
                                                                <span x-show="item.size_name" x-text="`Size: ${item.size_name}`"></span>
                                                                <span x-text="`Qty: ${item.quantity}`"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-black text-slate-800 text-sm" x-text="'₦' + new Intl.NumberFormat().format(item.price_per_unit * item.quantity)"></p>
                                                            <p class="text-[10px] text-slate-400" x-text="'₦' + new Intl.NumberFormat().format(item.price_per_unit) + ' / unit'"></p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Summary -->
                                        <div class="flex flex-col items-end pt-4 border-t">
                                            <div class="w-full sm:w-1/2 space-y-2">
                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Subtotal</span>
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.order_total_amount || 0)"></span>
                                                </div>
                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Shipping</span>
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.shipping_fee || 0)"></span>
                                                </div>
                                                <div class="flex justify-between text-lg font-black text-slate-800 pt-2 border-t">
                                                    <span>Total</span>
                                                    <span x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.grand_total || 0)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 bg-slate-50 border-t rounded-b-2xl flex justify-between items-center">
                                       <span class="px-3 py-1 rounded-full text-xs font-bold uppercase" :class="{
                                            'bg-emerald-100 text-emerald-700': ['paid', 'completed'].includes(selectedOrder?.order_status?.toLowerCase()),
                                            'bg-amber-100 text-amber-700': ['pending', 'processing'].includes(selectedOrder?.order_status?.toLowerCase()),
                                            'bg-rose-100 text-rose-700': ['failed', 'cancelled'].includes(selectedOrder?.order_status?.toLowerCase())
                                       }" x-text="selectedOrder?.order_status"></span>
                                       
                                        <a :href="`/invoice?order_number=${selectedOrder?.order_number}&download=true`" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-slate-800 transition">Download Invoice</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'users': ?>
                        <div class="max-w-7xl mx-auto">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-8">
                                <div>
                                    <h3 class="text-2xl font-bold text-slate-800">User Management</h3>
                                    <p class="text-sm text-slate-500 font-medium mt-1">Manage user access, roles, and status.</p>
                                </div>
                            </div>
                            
                            <!-- Invite New Admin Card -->
                            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
                                <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="fa-solid fa-paper-plane"></i> Invite New Administrator
                                </h4>
                                <div class="flex flex-col md:flex-row gap-4">
                                    <div class="flex-1 relative">
                                        <input type="text" id="main-invite-link-output" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" readonly placeholder="The invitation link will appear here...">
                                        <button id="main-copy-invite-btn" class="hidden absolute right-3 top-1/2 -translate-y-1/2 bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg text-xs font-black hover:bg-indigo-100 transition">COPY</button>
                                    </div>
                                    <button id="main-generate-invite-btn" class="bg-slate-900 text-white px-8 py-3 rounded-xl text-sm font-black shadow-lg shadow-slate-200 hover:bg-slate-800 transition flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Invitation
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-3 font-medium">Invitation links are unique and expire after 24 hours. They can only be used to create one account.</p>
                            </div>

                            <div class="glass-card overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="bg-slate-50 border-b border-slate-100">
                                            <tr>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Source</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="main-admin-table-body" class="divide-y divide-slate-50">
                                            <?php if (!empty($allUsers)): ?>
                                                <?php foreach ($allUsers as $user): 
                                                    $isLegacyType = ($user['source'] === 'legacy');
                                                    $isSelf = ($user['id'] == $currentUserId && $isLegacyAdmin == $isLegacyType);
                                                ?>
                                                <tr class="hover:bg-slate-50 transition">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-[10px] font-black uppercase">
                                                                <?= substr($user['username'], 0, 2) ?>
                                                            </div>
                                                            <div>
                                                                <p class="text-xs font-bold text-slate-700 leading-tight"><?= htmlspecialchars($user['username']) ?></p>
                                                                <p class="text-[10px] text-slate-400">Joined <?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="text-[10px] font-medium text-slate-500 italic"><?= $isLegacyType ? 'Legacy DB' : 'Role-Based' ?></span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider <?= strpos($user['role'], 'super') !== false ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' ?>">
                                                            <?= htmlspecialchars($user['role'] ?? 'admin') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <?php if ($user['status'] === 'active'): ?>
                                                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                            </span>
                                                        <?php elseif ($user['status'] === 'pending'): ?>
                                                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <?php if ($isSelf): ?>
                                                            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest bg-indigo-50 px-2 py-1 rounded">YOU</span>
                                                        <?php else: ?>
                                                            <div class="flex justify-end gap-2">
                                                                <?php if ($user['status'] === 'pending'): ?>
                                                                    <button onclick="approveUser(<?= $user['id'] ?>, '<?= $user['source'] ?>')" class="text-emerald-500 hover:text-emerald-700 text-[10px] font-black uppercase">Approve</button>
                                                                <?php endif; ?>
                                                                <button onclick="changeStatus(<?= $user['id'] ?>, '<?= $user['source'] ?>', '<?= $user['status'] === 'suspended' ? 'reactivate' : 'suspend' ?>')" class="<?= $user['status'] === 'suspended' ? 'text-emerald-500' : 'text-amber-500' ?> hover:opacity-75 transition" title="<?= $user['status'] === 'suspended' ? 'Reactivate' : 'Suspend' ?>">
                                                                    <i class="fa-solid <?= $user['status'] === 'suspended' ? 'fa-check' : 'fa-ban' ?>"></i>
                                                                </button>
                                                                <button onclick="deleteUser(<?= $user['id'] ?>, '<?= $user['source'] ?>')" class="text-rose-400 hover:text-rose-600 transition" title="Delete">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-12">
                                                        <div class="flex flex-col items-center">
                                                            <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                                                <i class="fa-solid fa-users text-slate-300 text-xl"></i>
                                                            </div>
                                                            <p class="text-slate-400 italic text-sm">No administrators found.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'add_product': ?>
                        <div class="max-w-7xl mx-auto">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-8">
                                <h3 class="text-2xl font-bold text-slate-800"><?= $productToEdit ? 'Edit Product' : 'Add New Product' ?></h3>
                                <a href="?page=manage_products" class="text-xs font-bold text-slate-400 hover:text-slate-600">Cancel & Go Back</a>
                            </div>

                            <form id="addProductForm" action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-12 gap-6">
                                <input type="hidden" name="action" value="<?= $productToEdit ? 'edit_product' : 'add_product' ?>">
                                <?php if ($productToEdit): ?><input type="hidden" name="id" value="<?= $productToEdit['id'] ?>"><?php endif; ?>

                                <!-- LEFT COLUMN (content) -->
                                <div class="col-span-12 lg:col-span-8 space-y-6">
                                    
                                    <!-- 1. Core Details -->
                                    <div class="glass-card p-6">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Product Name</label>
                                                <input type="text" name="name" value="<?= htmlspecialchars($productToEdit['name'] ?? '') ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-indigo-400 transition font-bold text-slate-700 placeholder:font-normal" placeholder="e.g., Luxury Silk Dress" required>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Description</label>
                                                <textarea name="product_text" rows="3" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:border-indigo-400 transition" placeholder="Tell customers about this product..."><?= htmlspecialchars($productToEdit['product_text'] ?? '') ?></textarea>
                                            </div>
                                            <!-- Price & Stock are now managed through Variants -->
                                            <!-- <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Base Price (₦)</label>
                                                    <input type="number" name="price" value="<?= $productToEdit['price'] ?? '' ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none font-bold text-slate-700" placeholder="0.00">
                                                </div>
                                            </div> -->
                                            <input type="hidden" name="price" value="<?= $productToEdit['price'] ?? '0' ?>">
                                            <input type="hidden" name="stock_quantity" value="<?= $productToEdit['stock_quantity'] ?? '0' ?>">
                                        </div>
                                    </div>

                                    <!-- 2. Visuals -->
                                    <div class="glass-card p-6">
                                        <h4 class="font-bold text-slate-800 mb-4 text-sm">Media & Gallery</h4>
                                        <div class="grid grid-cols-4 gap-4">
                                            <!-- Main -->
                                            <div class="col-span-1 relative group cursor-pointer" onclick="document.getElementById('image_one').click()">
                                                <input type="hidden" name="current_image_one" value="<?= $productToEdit['image_one'] ?? '' ?>">
                                                <div class="aspect-[3/4] bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center hover:border-indigo-400 transition overflow-hidden relative">
                                                    <?php if (!empty($productToEdit['image_one'])): ?>
                                                        <img src="/<?= $productToEdit['image_one'] ?>" class="w-full h-full object-cover absolute inset-0">
                                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                            <span class="text-white text-[10px] font-bold uppercase">Change</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-camera text-slate-300 text-xl group-hover:text-indigo-400"></i>
                                                        <span class="text-[10px] font-bold text-slate-400 mt-2 uppercase">Main</span>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" name="image_one" id="image_one" class="hidden" onchange="previewImage(this)">
                                            </div>
                                            <!-- Secondary -->
                                            <div class="col-span-1 relative group cursor-pointer" onclick="document.getElementById('image_two').click()">
                                                <input type="hidden" name="current_image_two" value="<?= $productToEdit['image_two'] ?? '' ?>">
                                                <div class="aspect-[3/4] bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center hover:border-indigo-400 transition overflow-hidden relative">
                                                    <?php if (!empty($productToEdit['image_two'])): ?>
                                                        <img src="/<?= $productToEdit['image_two'] ?>" class="w-full h-full object-cover absolute inset-0">
                                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                                            <span class="text-white text-[10px] font-bold uppercase">Change</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-image text-slate-300 text-xl group-hover:text-indigo-400"></i>
                                                        <span class="text-[10px] font-bold text-slate-400 mt-2 uppercase">Hover</span>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" name="image_two" id="image_two" class="hidden" onchange="previewImage(this)">
                                            </div>
                                            <!-- Gallery Upload -->
                                            <div class="col-span-2 relative group cursor-pointer" onclick="document.getElementById('gallery_images').click()">
                                                <div class="h-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center hover:border-indigo-400 transition min-h-[120px]">
                                                    <i class="fa-solid fa-images text-slate-300 text-2xl group-hover:text-indigo-400 mb-1"></i>
                                                    <span class="text-xs font-bold text-slate-500">Add Gallery Images</span>
                                                    <span class="text-[9px] text-slate-400 mt-1">Select multiple files</span>
                                                </div>
                                                <input type="file" name="gallery_images[]" id="gallery_images" class="hidden" multiple onchange="handleGallerySelect(this)">
                                            </div>
                                        </div>

                                        <!-- PREVIEW CONTAINER FOR NEW UPLOADS -->
                                        <div id="gallery-preview-container" class="mt-4 grid grid-cols-4 sm:grid-cols-6 gap-3 empty:hidden"></div>
                                        
                                        <!-- Existing Gallery Images -->
                                        <?php if (!empty($productToEdit['gallery'])): ?>
                                        <div class="mt-4">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Existing Gallery</p>
                                            <div class="flex gap-3 overflow-x-auto pb-2">
                                                <?php foreach ($productToEdit['gallery'] as $gImg): ?>
                                                <div class="relative w-20 h-20 flex-shrink-0 group">
                                                    <img src="/<?= $gImg['image_path'] ?>" class="w-full h-full object-cover rounded-lg border border-slate-200">
                                                    <button type="button" onclick="deleteGalleryImage(<?= $gImg['id'] ?>, this)" class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-md hover:scale-110 transition"><i class="fa-solid fa-xmark text-xs"></i></button>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <script>
                                            function previewImage(input) {
                                                if (input.files && input.files[0]) {
                                                    var reader = new FileReader();
                                                    reader.onload = function(e) {
                                                        const container = input.previousElementSibling;
                                                        // Replace content with image preview
                                                        container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover absolute inset-0"><div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"><span class="text-white text-[10px] font-bold uppercase">Change</span></div>`;
                                                    }
                                                    reader.readAsDataURL(input.files[0]);
                                                }
                                            }
                                            
                                            // --- GALLERY PREVIEW LOGIC ---
                                            let galleryFiles = new DataTransfer();

                                            function handleGallerySelect(input) {
                                                const files = input.files;
                                                const container = document.getElementById('gallery-preview-container');
                                                
                                                if (files.length > 0) {
                                                    // Add new files to our DataTransfer object
                                                    for (let i = 0; i < files.length; i++) {
                                                        galleryFiles.items.add(files[i]);
                                                    }
                                                    
                                                    // Sync input with accumulated files
                                                    input.files = galleryFiles.files;
                                                    
                                                    // Re-render previews
                                                    renderGalleryPreviews();
                                                }
                                            }

                                            function renderGalleryPreviews() {
                                                const container = document.getElementById('gallery-preview-container');
                                                container.innerHTML = ''; // Clear current display
                                                
                                                const files = galleryFiles.files;
                                                
                                                for (let i = 0; i < files.length; i++) {
                                                    const file = files[i];
                                                    const reader = new FileReader();
                                                    
                                                    reader.onload = function(e) {
                                                        const div = document.createElement('div');
                                                        div.className = 'relative aspect-square group';
                                                        div.innerHTML = `
                                                            <img src="${e.target.result}" class="w-full h-full object-cover rounded-lg border border-slate-200">
                                                            <button type="button" onclick="removeGalleryPreview(${i})" class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-md hover:scale-110 transition z-10">
                                                                <i class="fa-solid fa-xmark text-xs"></i>
                                                            </button>
                                                        `;
                                                        container.appendChild(div);
                                                    }
                                                    reader.readAsDataURL(file);
                                                }
                                            }

                                            function removeGalleryPreview(index) {
                                                // Create a new DataTransfer to filter out the deleted item
                                                const newDataTransfer = new DataTransfer();
                                                const currentFiles = galleryFiles.files;
                                                
                                                for (let i = 0; i < currentFiles.length; i++) {
                                                    if (i !== index) {
                                                        newDataTransfer.items.add(currentFiles[i]);
                                                    }
                                                }
                                                
                                                // Update global object and input
                                                galleryFiles = newDataTransfer;
                                                document.getElementById('gallery_images').files = galleryFiles.files;
                                                
                                                // Re-render
                                                renderGalleryPreviews();
                                            }

                                            function deleteGalleryImage(id, btn) {
                                                if(!confirm('Delete this image?')) return;
                                                const formData = new FormData();
                                                formData.append('action', 'delete_gallery_image');
                                                formData.append('image_id', id);
                                                
                                                // Use current URL to ensure we post to the correct controller
                                                fetch(window.location.href, {
                                                    method: 'POST',
                                                    body: formData
                                                })
                                                .then(res => res.json())
                                                .then(data => {
                                                    if(data.status === 'success') {
                                                        btn.parentElement.remove();
                                                    } else {
                                                        alert('Failed to delete image');
                                                    }
                                                })
                                                .catch(err => console.error('Error:', err));
                                            }
                                        </script>
                                    </div>

                                    <!-- 3. Product Variants (Redesigned) -->
                                    <div class="glass-card p-6">
                                        <h4 class="font-bold text-slate-800 mb-6 text-sm">Product Variants</h4>
                                        <div class="grid grid-cols-1 gap-8">
                                            
                                            <!-- COL 1: PRODUCT COLORS -->
                                            <div>
                                                <div class="flex justify-between items-center mb-4">
                                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Product Colors</label>
                                                    <button type="button" onclick="addColorRow()" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1">
                                                        <i class="fa-solid fa-plus"></i> Add Color
                                                    </button>
                                                </div>
                                                
                                                <div id="colors-container" class="space-y-3">
                                                    <?php 
                                                    // Logic to display existing Colors
                                                    // Assuming productToEdit['selected_colors'] contains IDs.
                                                    // We render inputs for Name/Hex.
                                                    // If we don't have detailed color data loaded, we default to empty rows or basic re-population.
                                                    $currentColors = []; // In a real scenario, we'd fetch details.
                                                    // Fallback check to see if we have data to populate
                                                    if (!empty($productToEdit['colors'])) {
                                                        $currentColors = $productToEdit['colors']; 
                                                    } else {
                                                        $currentColors = [['name' => '', 'hex_code' => '#000000']];
                                                    }

                                                    foreach ($currentColors as $color): 
                                                    ?>
                                                    <div class="flex items-center gap-2 group">
                                                        <input type="hidden" name="color_hexes[]" value="<?= htmlspecialchars($color['hex_code'] ?? '#000000') ?>">
                                                        <input type="text" name="color_names[]" value="<?= htmlspecialchars($color['name'] ?? '') ?>" class="flex-1 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Color Name (e.g. Black)">
                                                        <button type="button" onclick="this.parentElement.remove()" class="text-slate-300 hover:text-rose-500 p-2 transition"><i class="fa-solid fa-xmark"></i></button>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <script>
                                                    function addColorRow() {
                                                        const div = document.createElement('div');
                                                        div.className = 'flex items-center gap-2 group';
                                                        div.innerHTML = `
                                                            <input type="hidden" name="color_hexes[]" value="#000000">
                                                            <input type="text" name="color_names[]" class="flex-1 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Color Name">
                                                            <button type="button" onclick="this.parentElement.remove()" class="text-slate-300 hover:text-rose-500 p-2 transition"><i class="fa-solid fa-xmark"></i></button>
                                                        `;
                                                        document.getElementById('colors-container').appendChild(div);
                                                    }
                                                </script>
                                            </div>

                                            <!-- COL 2: VARIANTS (SIZE / OPTION) -->
                                            <div>
                                                <div class="flex justify-between items-center mb-4">
                                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Variants (Size / Price / Stock)</label>
                                                    <button type="button" onclick="addVariantRow()" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1">
                                                        <i class="fa-solid fa-plus"></i> Add Variant
                                                    </button>
                                                </div>
                                                
                                                <div id="variants-container" class="space-y-3">
                                                    <?php 
                                                    $existingVariants = $productToEdit['variants'] ?? [];
                                                    // Don't default to an empty row — variants are optional
                                                    foreach ($existingVariants as $variant): 
                                                    ?>
                                                    <div class="flex items-center gap-3 group">
                                                        <div class="flex-1 min-w-[25%]">
                                                            <input type="text" name="variants_name[]" value="<?= htmlspecialchars($variant['variant_name']) ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Size (e.g. 10)">
                                                        </div>
                                                        <div class="w-36">
                                                            <input type="number" name="variants_price[]" value="<?= htmlspecialchars($variant['price']) ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Price">
                                                        </div>
                                                        <div class="w-28">
                                                            <input type="number" name="variants_stock[]" value="<?= htmlspecialchars($variant['stock_quantity'] ?? '') ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Stock" min="1">
                                                        </div>
                                                        <button type="button" onclick="this.parentElement.remove()" class="text-slate-300 hover:text-rose-500 p-2 transition"><i class="fa-solid fa-xmark"></i></button>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <style>
                                                    @keyframes shake {
                                                        0%, 100% { transform: translateX(0); }
                                                        10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
                                                        20%, 40%, 60%, 80% { transform: translateX(4px); }
                                                    }
                                                    .animate-shake {
                                                        animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
                                                    }
                                                </style>
                                                <script>
                                                    function addVariantRow() {
                                                        const div = document.createElement('div');
                                                        div.className = 'flex items-center gap-3 group';
                                                        div.innerHTML = `
                                                            <div class="flex-1 min-w-[25%]">
                                                                <input type="text" name="variants_name[]" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Size (e.g. 10)">
                                                            </div>
                                                            <div class="w-36">
                                                                <input type="number" name="variants_price[]" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Price">
                                                            </div>
                                                            <div class="w-28">
                                                                <input type="number" name="variants_stock[]" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium outline-none focus:border-indigo-400 placeholder:font-normal placeholder:text-slate-400 transition" placeholder="Stock" min="1">
                                                            </div>
                                                            <button type="button" onclick="this.parentElement.remove()" class="text-slate-300 hover:text-rose-500 p-2 transition"><i class="fa-solid fa-xmark"></i></button>
                                                        `;
                                                        document.getElementById('variants-container').appendChild(div);
                                                    }

                                                    // Validation Logic
                                                    document.addEventListener('DOMContentLoaded', () => {
                                                        const form = document.getElementById('addProductForm');
                                                        if(form) {
                                                            form.addEventListener('submit', function(e) {
                                                                let isValid = true;
                                                                let firstError = null;

                                                                // Select all variant rows
                                                                const variantNames = document.querySelectorAll('input[name="variants_name[]"]');
                                                                const variantPrices = document.querySelectorAll('input[name="variants_price[]"]');
                                                                const variantStocks = document.querySelectorAll('input[name="variants_stock[]"]');

                                                                // Variants are OPTIONAL — only validate rows where the user started filling in data
                                                                if (variantNames.length > 0) {
                                                                    const markError = (input, msg) => {
                                                                        isValid = false;
                                                                        input.classList.add('border-rose-500', 'animate-shake');
                                                                        if(!firstError) firstError = input;
                                                                        let errorEl = input.parentNode.querySelector('.variant-error-msg');
                                                                        if (!errorEl) {
                                                                            errorEl = document.createElement('p');
                                                                            errorEl.className = 'variant-error-msg text-[10px] text-rose-500 font-bold mt-1 animate-pulse';
                                                                            input.parentNode.appendChild(errorEl);
                                                                        }
                                                                        errorEl.innerText = msg;
                                                                        setTimeout(() => input.classList.remove('animate-shake'), 400);
                                                                        input.addEventListener('input', function() {
                                                                            this.classList.remove('border-rose-500');
                                                                            const m = this.parentNode.querySelector('.variant-error-msg');
                                                                            if(m) m.remove();
                                                                        }, {once: true});
                                                                    };

                                                                    variantNames.forEach((nameInput, i) => {
                                                                        const name = nameInput.value.trim();
                                                                        const price = variantPrices[i]?.value.trim();
                                                                        const stock = variantStocks[i]?.value.trim();
                                                                        // Skip fully empty rows (user left a blank row)
                                                                        if (!name && !price && !stock) return;
                                                                        // Row has data — only price and stock are required, name is optional
                                                                        if (!price) markError(variantPrices[i], 'Required');
                                                                        if (!stock || parseInt(stock) < 1) markError(variantStocks[i], !stock ? 'Required' : 'Must be > 0');
                                                                    });
                                                                }

                                                                // Validate Collection
                                                                const collectionSelect = document.querySelector('select[name="collection_id"]');
                                                                if(collectionSelect && !collectionSelect.value) {
                                                                    isValid = false;
                                                                    collectionSelect.classList.add('border-rose-500', 'animate-shake');
                                                                    if(!firstError) firstError = collectionSelect;
                                                                    
                                                                    // Error Message
                                                                    let errorMsg = collectionSelect.parentNode.querySelector('.collection-error-msg');
                                                                    if (!errorMsg) {
                                                                        errorMsg = document.createElement('p');
                                                                        errorMsg.className = 'collection-error-msg text-[10px] text-rose-500 font-bold mt-1 animate-pulse';
                                                                        errorMsg.innerText = 'Please select a collection';
                                                                        collectionSelect.parentNode.appendChild(errorMsg);
                                                                    }

                                                                    setTimeout(() => { collectionSelect.classList.remove('animate-shake'); }, 400);

                                                                    collectionSelect.addEventListener('change', function() {
                                                                        this.classList.remove('border-rose-500');
                                                                        const msg = this.parentNode.querySelector('.collection-error-msg');
                                                                        if(msg) msg.remove();
                                                                    }, {once: true});
                                                                }

                                                                if (!isValid) {
                                                                    e.preventDefault();
                                                                    if(firstError) firstError.focus();
                                                                } else {
                                                                    // Show uploading state
                                                                    const submitBtn = document.getElementById('productSubmitBtn');
                                                                    const submitText = document.getElementById('submitBtnText');
                                                                    if (submitBtn && submitText) {
                                                                        submitBtn.disabled = true;
                                                                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                                                                        submitBtn.classList.remove('hover:bg-indigo-700', 'active:scale-[0.98]');
                                                                        const isEditing = form.querySelector('input[name="action"]')?.value === 'edit_product';
                                                                        submitText.innerHTML = isEditing
                                                                            ? '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving Changes...'
                                                                            : '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Uploading Product...';
                                                                    }
                                                                }
                                                            });
                                                        }
                                                    });
                                                </script>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN (Settings) -->
                                <div class="col-span-12 lg:col-span-4 space-y-6">
                                    <div class="glass-card p-6 sticky top-6">
                                        <h4 class="font-bold text-slate-800 mb-4 text-sm">Publishing Settings</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Status</label>
                                                <select name="visibility" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none text-sm font-bold text-slate-700">
                                                    <option value="show" <?= ($productToEdit['visibility'] ?? 'show') == 'show' ? 'selected' : '' ?>>Publicly Visible</option>
                                                    <option value="hide" <?= ($productToEdit['visibility'] ?? '') == 'hide' ? 'selected' : '' ?>>Hidden Draft</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Collection</label>
                                                <select name="collection_id" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none text-sm text-slate-700" required>
                                                    <option value="">No Collection</option>
                                                    <?php foreach ($collections as $collection): ?>
                                                        <option value="<?= $collection['id'] ?>" <?= ($productToEdit['collection_id'] ?? null) == $collection['id'] ? 'selected' : '' ?>><?= $collection['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="pt-4 border-t border-slate-100">
                                                <button type="submit" id="productSubmitBtn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-100 transition active:scale-[0.98] flex items-center justify-center gap-2">
                                                    <span id="submitBtnText"><?= $productToEdit ? 'Save Changes' : 'Publish Product' ?></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php break; ?>

                    <?php case 'manage_products': ?>
                        <div class="glass-card overflow-hidden">
                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Product Catalog <span class="text-slate-400 ml-1 font-medium">(<?= $totalProducts ?>)</span></h4>
                                <div class="flex items-center space-x-3">
                                    <form method="GET" class="relative">
                                        <input type="hidden" name="page" value="manage_products">
                                        <input type="text" name="search" placeholder="Search product..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="live-search pl-10 pr-4 py-2 bg-slate-50 border rounded-xl text-sm outline-none w-full sm:w-64">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                    </form>
                                    <a href="?page=add_product" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md shadow-indigo-100 hover:bg-indigo-700 transition">Add New</a>
                                </div>
                            </div>
                            <div id="search-results-container" x-data="{ 
                                stockModalOpen: false, 
                                priceModalOpen: false,
                                currentStockProduct: '', 
                                currentStockProductId: null, 
                                currentStockVariants: [], 
                                currentPriceVariants: [], 
                                showAddVariant: true,
                                newVariant: { name: '', price: '', stock: '' },
                                addError: '',
                                addSuccess: '',
                                async addVariant() {
                                    this.addError = '';
                                    this.addSuccess = '';
                                    if(this.newVariant.stock <= 0) { 
                                        this.addError = 'Stock must be greater than 0'; 
                                        return; 
                                    }
                                    let fd = new FormData();
                                    fd.append('action', 'quick_add_variant');
                                    fd.append('ajax', '1');
                                    fd.append('product_id', this.currentStockProductId);
                                    fd.append('variant_name', this.newVariant.name);
                                    fd.append('variant_price', this.newVariant.price);
                                    fd.append('variant_stock', this.newVariant.stock);
                                    
                                    await fetch('', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => {
                                            if(d.success) {
                                                this.currentStockVariants.push({ name: d.variant.name, qty: parseInt(d.variant.qty), id: d.variant.id });
                                                this.newVariant = { name: '', price: '', stock: '' };
                                                this.addSuccess = 'Variant added!';
                                                setTimeout(() => this.addSuccess = '', 3000);
                                            } else {
                                                this.addError = d.msg || 'Error adding variant';
                                            }
                                        });
                                },
                                openPriceModal(prodName, prodId, variantDetails) {
                                    this.currentStockProduct = prodName;
                                    this.currentStockProductId = prodId;
                                    // Initialize for Price Modal
                                    if (!variantDetails) {
                                        this.currentPriceVariants = [];
                                    } else {
                                        this.currentPriceVariants = variantDetails.split(', ').filter(s => s).map(s => {
                                            let parts = s.split(':');
                                            return { 
                                                name: parts[0].trim(), 
                                                id: parseInt(parts[2] || 0),
                                                price: parseFloat(parts[3] || 0),
                                                success: false 
                                            };
                                        });
                                    }
                                    this.priceModalOpen = true;
                                },
                                async updatePrice(currVar) {
                                    let fd = new FormData();
                                    fd.append('action', 'update_variant_stock');
                                    fd.append('ajax', '1');
                                    fd.append('variant_id', currVar.id);
                                    fd.append('variant_price', currVar.price);
                                    
                                    await fetch('', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => {
                                            if(d.success) {
                                                currVar.success = true;
                                                setTimeout(() => currVar.success = false, 2000);
                                            } else {
                                                alert(d.msg || 'Error updating');
                                            }
                                        });
                                },
                                async updateVariant(currVar) {
                                    if(currVar.qty <= 0) { alert('Stock must be > 0'); return; }
                                    let fd = new FormData();
                                    fd.append('action', 'update_variant_stock');
                                    fd.append('ajax', '1');
                                    fd.append('variant_id', currVar.id);
                                    fd.append('stock_quantity', currVar.qty);
                                    fd.append('variant_price', currVar.price);
                                    
                                    await fetch('', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => {
                                            if(d.success) {
                                                currVar.success = true;
                                                setTimeout(() => currVar.success = false, 2000);
                                            } else {
                                                alert(d.msg || 'Error updating');
                                            }
                                        });
                                }
                            }">
                                <!-- Stock Modal -->
                                <div x-show="stockModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
                                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="stockModalOpen = false"></div>
                                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform transition-all">
                                        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                            <div>
                                                <h3 class="font-bold text-slate-800 text-lg" x-text="currentStockProduct"></h3>
                                                <p class="text-xs text-slate-400 font-medium">Manage Stock & Variants</p>
                                            </div>
                                            <button @click="stockModalOpen = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-100 rounded-full">
                                                <i class="fa-solid fa-xmark text-xl"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Main Content -->
                                        <div class="p-6">
                                            <!-- Existing Variants List -->
                                            <div class="space-y-3 mb-8 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
                                                <template x-for="currVar in currentStockVariants" :key="currVar.id">
                                                    <div class="grid grid-cols-12 gap-3 items-end p-3 rounded-xl bg-white border border-slate-100 shadow-sm hover:border-indigo-100 transition group">
                                                        <div class="col-span-8">
                                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Variant Name</div>
                                                            <div class="font-bold text-slate-700 text-sm truncate" x-text="currVar.name"></div>
                                                        </div>
                                                        <div class="col-span-3">
                                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Stock</div>
                                                            <input type="number" x-model="currVar.qty" 
                                                                class="w-full text-sm font-bold text-center p-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition" 
                                                                min="1">
                                                        </div>
                                                        <div class="col-span-1 relative">
                                                            <div class="text-[10px] font-bold text-transparent uppercase tracking-widest mb-0.5">.</div>
                                                            <button type="button" @click="updateVariant(currVar)" class="w-full h-[38px] flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg transition" title="Save">
                                                                <i class="fa-solid fa-check"></i>
                                                            </button>
                                                            <div x-show="currVar.success" x-transition class="absolute -top-6 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm whitespace-nowrap">
                                                                Saved!
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div x-show="currentStockVariants.length === 0" class="text-center py-8 border-2 border-dashed border-slate-100 rounded-xl">
                                                    <p class="text-sm text-slate-400 font-medium">No variants found.</p>
                                                </div>
                                            </div>

                                            <!-- Add New Variant Section -->
                                            <div class="bg-slate-50 p-5 rounded-2xl border border-dashed border-slate-200">
                                                <h4 class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                                    <i class="fa-solid fa-plus-circle"></i> Add New Variant
                                                </h4>
                                                <form @submit.prevent="addVariant" class="grid grid-cols-12 gap-3 items-end">
                                                    <div class="col-span-5">
                                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Name / Size</label>
                                                        <input type="text" x-model="newVariant.name" placeholder="e.g. XL" class="w-full text-sm p-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition" required>
                                                    </div>
                                                    <div class="col-span-3">
                                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Price</label>
                                                        <input type="number" x-model="newVariant.price" placeholder="Opt." class="w-full text-sm p-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                                                    </div>
                                                    <div class="col-span-3">
                                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Stock</label>
                                                        <input type="number" x-model="newVariant.stock" placeholder="Qty" class="w-full text-sm p-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition font-bold" min="1" required>
                                                        <p x-show="addError" x-text="addError" class="text-[10px] text-rose-500 font-bold mt-1"></p>
                                                    </div>
                                                    <div class="col-span-1 relative">
                                                        <button type="submit" class="w-full h-[42px] flex items-center justify-center bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                        <div x-show="addSuccess" x-transition class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                                                            Added!
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <!-- Price Modal -->
                                    <div x-show="priceModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
                                        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="priceModalOpen = false"></div>
                                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform transition-all">
                                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                                <div>
                                                    <h3 class="font-bold text-slate-800 text-lg" x-text="currentStockProduct"></h3>
                                                    <p class="text-xs text-slate-400 font-medium">Manage Pricing</p>
                                                </div>
                                                <button @click="priceModalOpen = false" class="text-slate-400 hover:text-slate-600 transition p-2 hover:bg-slate-100 rounded-full">
                                                    <i class="fa-solid fa-xmark text-xl"></i>
                                                </button>
                                            </div>
                                            <div class="p-6">
                                                <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                                                    <template x-for="currVar in currentPriceVariants" :key="currVar.id">
                                                        <div class="grid grid-cols-12 gap-3 items-end p-3 rounded-xl bg-white border border-slate-100 shadow-sm hover:border-indigo-100 transition group">
                                                            <div class="col-span-7">
                                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Variant Name</div>
                                                                <div class="font-bold text-slate-700 text-sm truncate" x-text="currVar.name"></div>
                                                            </div>
                                                            <div class="col-span-4">
                                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Price</div>
                                                                <input type="number" x-model="currVar.price" 
                                                                    class="w-full text-sm font-bold text-center p-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition">
                                                            </div>
                                                            <div class="col-span-1 relative">
                                                                <div class="text-[10px] font-bold text-transparent uppercase tracking-widest mb-0.5">.</div>
                                                                <button type="button" @click="updatePrice(currVar)" class="w-full h-[38px] flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg transition" title="Save">
                                                                    <i class="fa-solid fa-check"></i>
                                                                </button>
                                                                <div x-show="currVar.success" x-transition class="absolute -top-6 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm whitespace-nowrap">
                                                                    Saved!
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <div x-show="currentPriceVariants.length === 0" class="text-center py-8 border-2 border-dashed border-slate-100 rounded-xl">
                                                        <p class="text-sm text-slate-400 font-medium">No variants found.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                            <th class="px-8 py-4">Item Preview</th>
                                            <th class="px-8 py-4">Product Name</th>
                                            <th class="px-8 py-4">Pricing</th>
                                            <th class="px-8 py-4">Stock</th>
                                            <th class="px-8 py-4">Status</th>
                                            <th class="px-8 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($products as $product): ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-8 py-4">
                                                <div class="w-12 h-12 rounded-xl overflow-hidden border bg-white flex items-center justify-center">
                                                    <?php if ($product['image_one']): ?>
                                                        <img src="/<?= $product['image_one'] ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-image text-slate-200"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-8 py-4 font-bold text-slate-700 text-sm"><?= $product['name'] ?></td>
                                            <td class="px-8 py-4 cursor-pointer hover:bg-indigo-50/50 transition group" @click="openPriceModal('<?= addslashes($product['name']) ?>', <?= $product['id'] ?>, '<?= addslashes($product['variant_stock_details']) ?>')">
                                                <div class="flex items-center gap-2">
                                                    <div class="font-black text-slate-800 text-sm group-hover:text-indigo-600 transition">
                                                        <?php 
                                                            // Always prefer variant prices when variants exist
                                                            if ($product['min_variant_price'] > 0) {
                                                                if ($product['min_variant_price'] == $product['max_variant_price']) {
                                                                    $displayPrice = '₦' . number_format($product['min_variant_price']);
                                                                } else {
                                                                    $displayPrice = '₦' . number_format($product['min_variant_price']) . ' - ₦' . number_format($product['max_variant_price']);
                                                                }
                                                            } else {
                                                                $displayPrice = '₦' . number_format($product['price']);
                                                            }
                                                            echo $displayPrice;
                                                        ?>
                                                    </div>
                                                    <i class="fa-solid fa-pen-to-square text-xs text-indigo-300 opacity-0 group-hover:opacity-100 transition translate-y-0.5"></i>
                                                </div>
                                            </td>
                                            <td class="px-8 py-4 text-sm font-medium">
                                                <div class="flex flex-col items-start gap-1">
                                                     <span class="<?= $product['total_variant_stock'] < 5 ? 'text-rose-500 font-bold' : 'text-slate-500' ?>">
                                                        <?= (!empty($product['variant_stock_details'])) ? ($product['total_variant_stock'] ?? 0) : $product['stock_quantity'] ?> left
                                                    </span>
                                                    <?php if (!empty($product['variant_stock_details'])): ?>
                                                        <button 
                                                            @click="
                                                                currentStockProduct = '<?= addslashes($product['name']) ?>';
                                                                currentStockProductId = <?= $product['id'] ?>;
                                                                currentStockVariants = '<?= $product['variant_stock_details'] ?>'.split(', ').filter(s => s).map(s => {
                                                                    let parts = s.split(':');
                                                                    return { name: parts[0].trim(), qty: parseInt(parts[1] || 0), id: parseInt(parts[2] || 0) };
                                                                });
                                                                stockModalOpen = true;
                                                            "
                                                            class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700 bg-indigo-50 px-2 py-1 rounded-md transition"
                                                        >
                                                            View Breakdown
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-8 py-4"><?= getStatusBadge($product['visibility']) ?></td>
                                            <td class="px-8 py-4 text-right space-x-3">
                                                <a href="?page=add_product&edit_id=<?= $product['id'] ?>" class="text-indigo-500 hover:text-indigo-700"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <form action="" method="POST" class="inline" onsubmit="return confirm('Permanent delete this item?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="table" value="panel_products">
                                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="text-rose-400 hover:text-rose-600"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                            <div class="px-8 py-4 border-t bg-slate-50 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                                <div class="flex space-x-2">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?page=manage_products&p=<?= $currentPage - 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Prev</a>
                                    <?php endif; ?>
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=manage_products&p=<?= $currentPage + 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php break; ?>

                    <?php case 'shipping_fees': ?>
                        <script>
                            function shippingFeesData() {
                                return {
                                    editModal: false,
                                    editData: {},
                                    searchQuery: '',
                                    fees: <?= json_encode($shippingFees ?? []) ?>,
                                    get filteredFees() {
                                        if (!this.searchQuery.trim()) return this.fees;
                                        const q = this.searchQuery.toLowerCase();
                                        return this.fees.filter(f => f.location_name.toLowerCase().includes(q) || f.country_code.toLowerCase().includes(q));
                                    },
                                    init() {}
                                }
                            }
                        </script>
                        <div class="max-w-5xl mx-auto" x-data="shippingFeesData()" x-init="init()">
                            <!-- Header + Add Form Row -->
                            <div class="glass-card p-6 mb-6">
                                <div class="flex flex-col md:flex-row md:items-end gap-4">
                                    <div class="flex-shrink-0">
                                        <h3 class="text-xl font-bold text-slate-800">Shipping Fees</h3>
                                        <p class="text-xs text-slate-400">Add delivery locations and fees</p>
                                    </div>
                                    <form method="POST" class="flex-1 flex flex-wrap items-end gap-3">
                                        <input type="hidden" name="shipping_action" value="add">
                                        <div class="flex-1 min-w-[140px]">
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Location</label>
                                            <input type="text" name="location_name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400" placeholder="Lagos Mainland">
                                        </div>
                                        <div class="w-28">
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Country</label>
                                            <select name="country_code" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400">
                                                <option value="NG">NG</option>
                                                <option value="US">US</option>
                                                <option value="GB">GB</option>
                                                <option value="CA">CA</option>
                                                <option value="GH">GH</option>
                                            </select>
                                        </div>
                                        <div class="w-28">
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Fee (₦)</label>
                                            <input type="number" name="fee" step="0.01" min="0" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400" placeholder="2500">
                                        </div>
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-lg text-sm transition">
                                            <i class="fa-solid fa-plus mr-1"></i> Add
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Shipping Fees Table -->
                            <div class="glass-card overflow-hidden">
                                <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <h4 class="font-bold text-slate-800">All Fees <span class="text-slate-400 font-medium" x-text="'(' + filteredFees.length + ')'"></span></h4>
                                        <?php if (!empty($shippingFees)): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to DELETE ALL shipping fees? This cannot be undone.')">
                                                <input type="hidden" name="shipping_action" value="delete_all">
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-trash-can"></i> Delete All
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="searchQuery" placeholder="Search locations..." class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400 w-full sm:w-56">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                                <th class="px-6 py-3">Location</th>
                                                <th class="px-6 py-3">Country</th>
                                                <th class="px-6 py-3">Fee</th>
                                                <th class="px-6 py-3">Status</th>
                                                <th class="px-6 py-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="sf in filteredFees" :key="sf.id">
                                                <tr class="hover:bg-slate-50 transition">
                                                    <td class="px-6 py-3 font-medium text-slate-800" x-text="sf.location_name"></td>
                                                    <td class="px-6 py-3 text-slate-500" x-text="sf.country_code"></td>
                                                    <td class="px-6 py-3 font-bold text-slate-800" x-text="'₦' + parseFloat(sf.fee).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></td>
                                                    <td class="px-6 py-3">
                                                        <span x-show="sf.is_active == 1" class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-full">Active</span>
                                                        <span x-show="sf.is_active != 1" class="px-2 py-0.5 bg-slate-100 text-slate-400 text-[10px] font-bold rounded-full">Inactive</span>
                                                    </td>
                                                    <td class="px-6 py-3 text-right">
                                                        <button @click="editModal = true; editData = { id: sf.id, location_name: sf.location_name, country_code: sf.country_code, fee: sf.fee, is_active: sf.is_active == 1 }" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold mr-3">Edit</button>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this shipping fee?')">
                                                            <input type="hidden" name="shipping_action" value="delete">
                                                            <input type="hidden" name="delete_id" :value="sf.id">
                                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="filteredFees.length === 0">
                                                <td colspan="5" class="text-center py-8 text-slate-400 font-medium">
                                                    <span x-show="fees.length === 0">No shipping fees configured yet.</span>
                                                    <span x-show="fees.length > 0 && filteredFees.length === 0">No results found for "<span x-text="searchQuery"></span>"</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="editModal = false"></div>
                                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
                                    <div class="flex items-center justify-between mb-5">
                                        <h4 class="text-lg font-bold text-slate-800">Edit Shipping Fee</h4>
                                        <button @click="editModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="shipping_action" value="update">
                                        <input type="hidden" name="edit_id" x-bind:value="editData.id">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Location Name</label>
                                                <input type="text" name="location_name" x-bind:value="editData.location_name" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Country</label>
                                                    <select name="country_code" x-model="editData.country_code" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                        <option value="NG">Nigeria (NG)</option>
                                                        <option value="US">United States (US)</option>
                                                        <option value="GB">United Kingdom (GB)</option>
                                                        <option value="CA">Canada (CA)</option>
                                                        <option value="GH">Ghana (GH)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Fee (₦)</label>
                                                    <input type="number" name="fee" x-bind:value="editData.fee" step="0.01" min="0" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="is_active" id="edit_is_active" x-bind:checked="editData.is_active" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                                <label for="edit_is_active" class="text-sm font-medium text-slate-700">Active</label>
                                            </div>
                                        </div>
                                        <div class="flex gap-3 mt-6">
                                            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition">Update Fee</button>
                                            <button type="button" @click="editModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-4 rounded-xl text-sm transition">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'discount_codes': ?>
                        <script>
                            function discountCodesData() {
                                return {
                                    editModal: false,
                                    editData: {},
                                    searchQuery: '',
                                    codes: <?= json_encode($discountCodes ?? []) ?>,
                                    get filteredCodes() {
                                        if (!this.searchQuery.trim()) return this.codes;
                                        const q = this.searchQuery.toLowerCase();
                                        return this.codes.filter(c => c.code.toLowerCase().includes(q));
                                    },
                                    formatDate(dateStr) {
                                        if (!dateStr) return 'No expiry';
                                        return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                                    },
                                    isExpired(dateStr) {
                                        if (!dateStr) return false;
                                        return new Date(dateStr) < new Date();
                                    }
                                }
                            }
                        </script>
                        <div class="max-w-6xl mx-auto" x-data="discountCodesData()">
                            <!-- Header + Add Form Row -->
                            <div class="glass-card p-6 mb-6">
                                <div class="flex flex-col gap-4">
                                    <div class="flex-shrink-0">
                                        <h3 class="text-xl font-bold text-slate-800">Discount Codes</h3>
                                        <p class="text-xs text-slate-400">Create and manage promotional discount codes</p>
                                    </div>
                                    <form method="POST" class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
                                        <input type="hidden" name="discount_action" value="add">
                                        <div class="col-span-2 md:col-span-1">
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Code</label>
                                            <input type="text" name="code" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400 uppercase" placeholder="SAVE20">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Type</label>
                                            <select name="discount_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Fixed (₦)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Value</label>
                                            <input type="number" name="discount_value" step="0.01" min="0" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400" placeholder="20">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Min Order (₦)</label>
                                            <input type="number" name="min_order_amount" step="0.01" min="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Expiry Date</label>
                                            <input type="date" name="expiry_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400">
                                        </div>
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-lg text-sm transition h-[38px]">
                                            <i class="fa-solid fa-plus mr-1"></i> Add
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Discount Codes Table -->
                            <div class="glass-card overflow-hidden">
                                <div class="px-6 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <h4 class="font-bold text-slate-800">All Codes <span class="text-slate-400 font-medium" x-text="'(' + filteredCodes.length + ')'"></span></h4>
                                        <?php if (!empty($discountCodes)): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to DELETE ALL discount codes? This cannot be undone.')">
                                                <input type="hidden" name="discount_action" value="delete_all">
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-trash-can"></i> Delete All
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="searchQuery" placeholder="Search codes..." class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-400 w-full sm:w-56">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                                <th class="px-6 py-3">Code</th>
                                                <th class="px-6 py-3">Type</th>
                                                <th class="px-6 py-3">Value</th>
                                                <th class="px-6 py-3">Min Order</th>
                                                <th class="px-6 py-3">Expiry</th>
                                                <th class="px-6 py-3">Status</th>
                                                <th class="px-6 py-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="dc in filteredCodes" :key="dc.id">
                                                <tr class="hover:bg-slate-50 transition">
                                                    <td class="px-6 py-3 font-bold text-indigo-600" x-text="dc.code"></td>
                                                    <td class="px-6 py-3 text-slate-500 capitalize" x-text="dc.discount_type"></td>
                                                    <td class="px-6 py-3 font-bold text-slate-800">
                                                        <span x-text="dc.discount_type === 'percentage' ? dc.discount_value + '%' : '₦' + parseFloat(dc.discount_value).toLocaleString()"></span>
                                                    </td>
                                                    <td class="px-6 py-3 text-slate-500" x-text="dc.min_order_amount > 0 ? '₦' + parseFloat(dc.min_order_amount).toLocaleString() : 'None'"></td>
                                                    <td class="px-6 py-3">
                                                        <span x-text="formatDate(dc.expiry_date)" :class="isExpired(dc.expiry_date) ? 'text-rose-500' : 'text-slate-500'"></span>
                                                    </td>
                                                    <td class="px-6 py-3">
                                                        <span x-show="dc.is_active == 1 && !isExpired(dc.expiry_date)" class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-full">Active</span>
                                                        <span x-show="dc.is_active != 1" class="px-2 py-0.5 bg-slate-100 text-slate-400 text-[10px] font-bold rounded-full">Inactive</span>
                                                        <span x-show="dc.is_active == 1 && isExpired(dc.expiry_date)" class="px-2 py-0.5 bg-rose-50 text-rose-500 text-[10px] font-bold rounded-full">Expired</span>
                                                    </td>
                                                    <td class="px-6 py-3 text-right">
                                                        <button @click="editModal = true; editData = { id: dc.id, code: dc.code, discount_type: dc.discount_type, discount_value: dc.discount_value, min_order_amount: dc.min_order_amount, max_uses: dc.max_uses, expiry_date: dc.expiry_date, is_active: dc.is_active == 1 }" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold mr-3">Edit</button>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this discount code?')">
                                                            <input type="hidden" name="discount_action" value="delete">
                                                            <input type="hidden" name="delete_id" :value="dc.id">
                                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="filteredCodes.length === 0">
                                                <td colspan="7" class="text-center py-8 text-slate-400 font-medium">
                                                    <span x-show="codes.length === 0">No discount codes configured yet.</span>
                                                    <span x-show="codes.length > 0 && filteredCodes.length === 0">No results found for "<span x-text="searchQuery"></span>"</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="editModal = false"></div>
                                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
                                    <div class="flex items-center justify-between mb-5">
                                        <h4 class="text-lg font-bold text-slate-800">Edit Discount Code</h4>
                                        <button @click="editModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="discount_action" value="update">
                                        <input type="hidden" name="edit_id" x-bind:value="editData.id">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Code</label>
                                                <input type="text" name="code" x-bind:value="editData.code" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400 uppercase">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Type</label>
                                                    <select name="discount_type" x-model="editData.discount_type" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed (₦)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Value</label>
                                                    <input type="number" name="discount_value" x-bind:value="editData.discount_value" step="0.01" min="0" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Min Order (₦)</label>
                                                    <input type="number" name="min_order_amount" x-bind:value="editData.min_order_amount" step="0.01" min="0" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Expiry Date</label>
                                                    <input type="date" name="expiry_date" x-bind:value="editData.expiry_date" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-indigo-400">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="is_active" id="edit_discount_active" x-bind:checked="editData.is_active" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                                <label for="edit_discount_active" class="text-sm font-medium text-slate-700">Active</label>
                                            </div>
                                        </div>
                                        <div class="flex gap-3 mt-6">
                                            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition">Update Code</button>
                                            <button type="button" @click="editModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-4 rounded-xl text-sm transition">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'manage_admins': ?>
                         <div class="max-w-4xl mx-auto">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-8">
                                <div>
                                    <h3 class="text-2xl font-bold text-slate-800">Admin Management</h3>
                                    <p class="text-sm text-slate-400">Control system access and user roles</p>
                                </div>
                                <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-3">
                                    <i class="fa-solid fa-shield-halved text-indigo-600 text-xl"></i>
                                    <div class="text-right">
                                        <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest leading-none mb-1">Current User</p>
                                        <p class="text-sm font-bold text-indigo-900 leading-none"><?= htmlspecialchars($_SESSION['admin_username']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Invite Section -->
                            <div class="glass-card p-6 mb-8 border-l-4 border-l-indigo-500">
                                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-slate-800 mb-1">Invite New Administrator</h4>
                                        <p class="text-sm text-slate-500">Generate a secure, one-time link for new team members to set up their account.</p>
                                    </div>
                                    <div class="w-full md:w-auto flex flex-col items-end gap-2">
                                        <button id="generate-invite-link-btn" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 hover:shadow-lg transition flex items-center gap-2">
                                            <i class="fa-solid fa-link"></i> Generate Invite Link
                                        </button>
                                        <div id="invite-result-container" class="hidden flex items-center gap-2 bg-slate-100 p-1 rounded-lg border border-slate-200 w-full md:w-80">
                                            <input type="text" id="invite-link-field" class="bg-transparent border-none text-xs font-mono text-slate-600 w-full px-2 focus:ring-0" readonly>
                                            <button id="copy-link-btn" class="text-xs font-bold bg-white text-indigo-600 px-3 py-1.5 rounded-md shadow-sm hover:text-indigo-800">COPY</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admins List -->
                            <div class="glass-card overflow-hidden" x-data="{ activeTab: 'active' }">
                                <!-- Tabs -->
                                <div class="flex border-b border-slate-100 bg-slate-50/50">
                                    <button @click="activeTab = 'active'" :class="activeTab === 'active' ? 'text-indigo-600 border-indigo-600 bg-white' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-50'" class="flex-1 py-4 text-sm font-bold uppercase tracking-wide border-b-2 transition">Active Users</button>
                                    <button @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'text-amber-600 border-amber-600 bg-white' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-50'" class="flex-1 py-4 text-sm font-bold uppercase tracking-wide border-b-2 transition relative justify-center flex items-center gap-2">
                                        Pending Requests
                                        <span id="page-pending-badge" class="hidden w-2 h-2 bg-amber-500 rounded-full animate-ping"></span>
                                    </button>
                                    <!-- <button @click="activeTab = 'suspended'" :class="activeTab === 'suspended' ? 'text-rose-600 border-rose-600 bg-white' : 'text-slate-400 border-transparent hover:text-slate-600 hover:bg-slate-50'" class="flex-1 py-4 text-sm font-bold uppercase tracking-wide border-b-2 transition">Suspended</button> -->
                                </div>

                                <!-- Lists -->
                                <div class="p-0">
                                    <div x-show="activeTab === 'active'" id="page-list-active" class="divide-y divide-slate-50"></div>
                                    <div x-show="activeTab === 'pending'" id="page-list-pending" class="divide-y divide-slate-50"></div>
                                    <div x-show="activeTab === 'suspended'" id="page-list-suspended" class="divide-y divide-slate-50"></div>
                                </div>
                            </div>

                         </div>
                         <!-- Auto-init script for this page -->
                         <script>
                             document.addEventListener('DOMContentLoaded', () => {
                                 if(typeof fetchAllAdminsPage === 'function') fetchAllAdminsPage();
                             });
                         </script>
                    <?php break; ?>

                    <?php case 'orders': ?>
                        <div class="glass-card overflow-hidden" x-data="{ showModal: false, selectedOrder: null, 
                            viewOrder(order, items) { 
                                this.selectedOrder = order; 
                                this.selectedOrder.items = items || [];
                                // Parse JSON address if string
                                try { 
                                    if(typeof this.selectedOrder.shipping_address === 'string') {
                                        this.selectedOrder.shipping_address = JSON.parse(this.selectedOrder.shipping_address);
                                    }
                                } catch(e) { console.error(e); }
                                this.showModal = true; 
                            } 
                        }">
                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Order History <span class="text-slate-400 ml-1 font-medium">(<?= $totalOrders ?>)</span></h4>
                                <form method="GET" class="relative">
                                    <input type="hidden" name="page" value="orders">
                                    <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="live-search pl-10 pr-4 py-2 bg-slate-50 border rounded-xl text-sm outline-none w-full sm:w-64">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                </form>
                            </div>
                            <div id="search-results-container">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                            <th class="px-8 py-4">Ref</th>
                                            <th class="px-8 py-4">Client</th>
                                            <th class="px-8 py-4">Date</th>
                                            <th class="px-8 py-4">Total</th>
                                            <th class="px-8 py-4">Status</th>
                                            <th class="px-8 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($orders as $order): 
                                            $orderJson = htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8');
                                            $itemsJson = htmlspecialchars(json_encode($orderDetailsMap[$order['id']] ?? []), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-8 py-4 text-xs font-bold text-indigo-600 font-mono">#<?= $order['order_number'] ?></td>
                                            <td class="px-8 py-4 text-sm font-semibold text-slate-700"><?= htmlspecialchars($order['full_name']) ?></td>
                                            <td class="px-8 py-4 text-xs text-slate-400 font-medium"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td class="px-8 py-4 text-sm font-black text-slate-800">₦<?= number_format($order['grand_total']) ?></td>
                                            <td class="px-8 py-4"><?= getStatusBadge($order['order_status']) ?></td>
                                            <td class="px-8 py-4 text-right space-x-2">
                                            <td class="px-8 py-4 text-right space-x-2">
                                                <button @click="viewOrder(<?= $orderJson ?>, <?= $itemsJson ?>)" class="text-slate-400 hover:text-indigo-600" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                                <a href="/invoice?order_number=<?= $order['order_number'] ?>&mode=download_pdf" target="_blank" class="text-slate-400 hover:text-emerald-600" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></a>
                                                <form method="POST" onsubmit="return confirm('Delete order #<?= $order['order_number'] ?>? This CANNOT be undone.')" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="table" value="orders">
                                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600" title="Delete Order"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($orders)): ?>
                                            <tr><td colspan="6" class="text-center py-8 text-slate-400 font-medium">No orders found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Order Details Modal -->
                            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                                <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative z-10 animate-fadeInUp">
                                    <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-20">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order Details</p>
                                            <h3 class="text-xl font-bold text-slate-800">#<span x-text="selectedOrder?.order_number"></span></h3>
                                        </div>
                                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                                    </div>
                                    <div class="p-6 space-y-8">
                                        <!-- Customer Info -->
                                        <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                            <div>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Client</p>
                                                <p class="font-bold text-slate-700" x-text="selectedOrder?.full_name"></p>
                                                <p class="text-sm text-slate-500" x-text="selectedOrder?.email"></p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Shipping Details</p>
                                                <div class="text-sm text-slate-600 font-medium">
                                                    <p x-text="selectedOrder?.shipping_address?.address"></p>
                                                    <p><span x-text="selectedOrder?.shipping_address?.city"></span>, <span x-text="selectedOrder?.shipping_address?.state"></span></p>
                                                    <p x-text="selectedOrder?.shipping_address?.country"></p>
                                                    <p x-text="selectedOrder?.shipping_address?.phoneNumber" class="mt-1 text-xs text-slate-400"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Items List -->
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Purchased Items</p>
                                            <div class="space-y-3">
                                                <template x-for="item in selectedOrder?.items" :key="item.id">
                                                    <div class="flex items-center p-3 border rounded-xl hover:bg-slate-50">
                                                        <div class="w-12 h-12 bg-slate-100 rounded-lg flex-shrink-0 bg-cover bg-center" :style="`background-image: url('/${item.image_one || ''}')`"></div>
                                                        <div class="ml-4 flex-1">
                                                            <p class="font-bold text-slate-700 text-sm" x-text="item.product_name || 'Unknown Product'"></p>
                                                            <div class="flex gap-2 text-xs text-slate-400 mt-0.5">
                                                                <span x-show="item.color_name" x-text="`Color: ${item.color_name}`"></span>
                                                                <span x-show="item.size_name" x-text="`Size: ${item.size_name}`"></span>
                                                                <span x-text="`Qty: ${item.quantity}`"></span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-black text-slate-800 text-sm" x-text="'₦' + new Intl.NumberFormat().format(item.price_per_unit * item.quantity)"></p>
                                                            <p class="text-[10px] text-slate-400" x-text="'₦' + new Intl.NumberFormat().format(item.price_per_unit) + ' / unit'"></p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Summary -->
                                        <div class="flex flex-col items-end pt-4 border-t">
                                            <div class="w-full sm:w-1/2 space-y-2">
                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Subtotal</span>
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.subtotal || 0)"></span>
                                                </div>
                                                <div class="flex justify-between text-sm text-slate-500" x-show="selectedOrder?.shipping_fee > 0">
                                                    <span>Shipping</span>
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.shipping_fee || 0)"></span>
                                                </div>
                                                <div class="flex justify-between text-sm text-green-600" x-show="selectedOrder?.discount_amount > 0">
                                                    <span>Discount <span x-show="selectedOrder?.discount_code" class="text-xs bg-green-100 px-1.5 py-0.5 rounded font-bold" x-text="selectedOrder?.discount_code"></span></span>
                                                    <span class="font-bold" x-text="'-₦' + new Intl.NumberFormat().format(selectedOrder?.discount_amount || 0)"></span>
                                                </div>
                                                <div class="flex justify-between text-lg font-black text-slate-800 pt-2 border-t">
                                                    <span>Total</span>
                                                    <span x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.grand_total)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 bg-slate-50 border-t rounded-b-2xl flex justify-between items-center">
                                       <span class="px-3 py-1 rounded-full text-xs font-bold uppercase" :class="{
                                            'bg-emerald-100 text-emerald-700': ['paid', 'completed'].includes(selectedOrder?.order_status?.toLowerCase()),
                                            'bg-amber-100 text-amber-700': ['pending', 'processing'].includes(selectedOrder?.order_status?.toLowerCase()),
                                            'bg-rose-100 text-rose-700': ['failed', 'cancelled'].includes(selectedOrder?.order_status?.toLowerCase())
                                       }" x-text="selectedOrder?.order_status"></span>
                                       
                                       
                                        <a :href="`/invoice?order_number=${selectedOrder?.order_number}&download=true`" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-slate-800 transition">Download Invoice</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                             <?php if ($totalPages > 1): ?>
                            <div class="px-8 py-4 border-t bg-slate-50 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                                <div class="flex space-x-2">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?page=orders&p=<?= $currentPage - 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Prev</a>
                                    <?php endif; ?>
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=orders&p=<?= $currentPage + 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'clients': ?>
                        <div class="glass-card overflow-hidden">
                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Client Database <span class="text-slate-400 ml-1 font-medium">(<?= $totalCustomers ?>)</span></h4>
                                <form method="GET" class="relative">
                                    <input type="hidden" name="page" value="clients">
                                    <input type="text" name="search" placeholder="Search clients..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="live-search pl-10 pr-4 py-2 bg-slate-50 border rounded-xl text-sm outline-none w-full sm:w-64">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                </form>
                            </div>
                            <div id="search-results-container">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                                <th class="px-8 py-4">Client</th>
                                                <th class="px-8 py-4">Contact Info</th>
                                                <th class="px-8 py-4">Location</th>
                                                <th class="px-8 py-4">Orders</th>
                                                <th class="px-8 py-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                            <?php foreach ($customers as $cust): 
                                                $addr = json_decode($cust['latest_address_json'] ?? '{}', true);
                                                $phone = $addr['phoneNumber'] ?? 'N/A';
                                                $city = $addr['city'] ?? '';
                                                $country = $addr['country'] ?? '';
                                                $location = ($city && $country) ? "$city, $country" : 'Unknown';
                                            ?>
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="px-8 py-4">
                                                    <div class="flex items-center">
                                                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs mr-3">
                                                            <?= strtoupper(substr($cust['full_name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($cust['full_name']) ?></p>
                                                            <p class="text-xs text-slate-400">Joined: <?= date('M Y', strtotime($cust['created_at'] ?? 'now')) ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-4">
                                                    <div class="space-y-1">
                                                        <p class="text-xs font-medium text-slate-600"><i class="fa-regular fa-envelope w-4"></i> <?= htmlspecialchars($cust['email']) ?></p>
                                                        <p class="text-xs font-medium text-slate-600"><i class="fa-solid fa-phone w-4"></i> <?= htmlspecialchars($phone) ?></p>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-4 text-sm font-medium text-slate-600"><?= htmlspecialchars($location) ?></td>
                                                <td class="px-8 py-4">
                                                    <span class="px-2 py-1 bg-slate-100 rounded text-xs font-bold text-slate-600"><?= $cust['order_count'] ?> orders</span>
                                                </td>
                                                <td class="px-8 py-4 text-right">
                                                    <form method="POST" onsubmit="return confirm('Delete client <?= htmlspecialchars($cust['full_name'], ENT_QUOTES) ?>? Warning: This may delete all their orders too.')" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="customers">
                                                        <input type="hidden" name="id" value="<?= $cust['id'] ?>">
                                                        <button type="submit" class="text-rose-400 hover:text-rose-600 p-2"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($customers)): ?>
                                                <tr><td colspan="5" class="text-center py-8 text-slate-400 font-medium">No clients found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                <div class="px-8 py-4 border-t bg-slate-50 flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                                    <div class="flex space-x-2">
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?page=clients&p=<?= $currentPage - 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Prev</a>
                                        <?php endif; ?>
                                        <?php if ($currentPage < $totalPages): ?>
                                            <a href="?page=clients&p=<?= $currentPage + 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Next</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'reviews': ?>
                        <div class="glass-card overflow-hidden">
                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Customer Reviews <span class="text-slate-400 ml-1 font-medium">(<?= count($reviews ?? []) ?>)</span></h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                            <th class="px-8 py-4">Product</th>
                                            <th class="px-8 py-4">Reviewer</th>
                                            <th class="px-8 py-4">Rating</th>
                                            <th class="px-8 py-4">Review</th>
                                            <th class="px-8 py-4">Status</th>
                                            <th class="px-8 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php if (!empty($reviews)): ?>
                                            <?php foreach ($reviews as $review): ?>
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="px-8 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <img src="/<?= htmlspecialchars($review['image_one']) ?>" class="w-10 h-10 rounded-lg object-cover bg-slate-100">
                                                        <span class="text-sm font-bold text-slate-700 max-w-[150px] truncate" title="<?= htmlspecialchars($review['product_name']) ?>"><?= htmlspecialchars($review['product_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-4">
                                                    <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($review['reviewer_name']) ?></div>
                                                    <div class="text-xs text-slate-400"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                                </td>
                                                <td class="px-8 py-4">
                                                    <div class="flex text-amber-400 text-xs">
                                                        <?php for($i=1; $i<=5; $i++): ?>
                                                            <i class="fa-solid fa-star <?= $i <= $review['rating'] ? '' : 'text-slate-200' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-4">
                                                    <p class="text-sm text-slate-600 max-w-xs truncate" title="<?= htmlspecialchars($review['review_text']) ?>"><?= htmlspecialchars($review['review_text']) ?></p>
                                                </td>
                                                <td class="px-8 py-4">
                                                    <?php if($review['is_approved'] == 1): ?>
                                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold uppercase">Approved</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold uppercase">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-8 py-4 text-right space-x-2">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <?php if($review['is_approved'] == 1): ?>
                                                            <input type="hidden" name="review_action" value="unapprove">
                                                            <button type="submit" class="text-amber-500 hover:text-amber-700" title="Unapprove"><i class="fa-solid fa-ban"></i></button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="review_action" value="approve">
                                                            <button type="submit" class="text-emerald-500 hover:text-emerald-700" title="Approve"><i class="fa-solid fa-check"></i></button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this review?')">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <input type="hidden" name="review_action" value="delete">
                                                        <button type="submit" class="text-rose-400 hover:text-rose-600" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center py-8 text-slate-400 font-medium">No reviews found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php break; ?>

                    <?php case 'colors':
                    case 'sizes': 
                    case 'collections':
                        $isColors = $page === 'colors';
                        $isSizes = $page === 'sizes';
                        $title = $isColors ? 'Color' : ($isSizes ? 'Size' : 'Collection');
                        $items = $isColors ? $colors : ($isSizes ? $sizes : $collections);
                        $editItem = $isColors ? $colorToEdit : ($isSizes ? $sizeToEdit : $collectionToEdit);
                        $actionPrefix = $isColors ? 'color' : ($isSizes ? 'size' : 'collection');
                    ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="md:col-span-1">
                                <div class="glass-card p-6">
                                    <h4 class="font-bold text-slate-800 mb-6"><?= $editItem ? "Edit $title" : "Add New $title" ?></h4>
                                    <form action="?page=<?= $page ?>" method="POST">
                                        <input type="hidden" name="action" value="<?= $editItem ? "edit_{$actionPrefix}" : "add_{$actionPrefix}" ?>">
                                        <?php if ($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>
                                        
                                        <div class="mb-4">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Name</label>
                                            <input type="text" name="name" value="<?= htmlspecialchars($editItem['name'] ?? '') ?>" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none transition" required>
                                        </div>
                                        
                                        <?php if ($isColors): ?>
                                        <div class="mb-6">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Color Code</label>
                                            <div class="flex items-center space-x-2">
                                                <input type="color" name="hex_code" value="<?= $editItem['hex_code'] ?? '#000000' ?>" class="h-10 w-20 rounded cursor-pointer border-0 bg-transparent">
                                                <span class="text-xs text-slate-500">Pick a color</span>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition">
                                            <?= $editItem ? "Update $title" : "Add $title" ?>
                                        </button>
                                        <?php if ($editItem): ?>
                                            <a href="?page=<?= $page ?>" class="block text-center mt-4 text-xs font-bold text-slate-400 hover:text-slate-600">Cancel</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="glass-card overflow-hidden">
                                     <div class="px-6 py-4 border-b flex justify-between items-center">
                                        <h4 class="font-bold text-slate-800">Available <?= $title ?>s</h4>
                                    </div>
                                    <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                                        <?php foreach ($items as $item): ?>
                                            <div class="flex items-center justify-between p-3 border rounded-xl bg-slate-50">
                                                <div class="flex items-center space-x-3">
                                                    <?php if ($isColors): ?>
                                                        <div class="w-6 h-6 rounded-full border shadow-sm" style="background-color: <?= $item['hex_code'] ?>"></div>
                                                    <?php endif; ?>
                                                    <span class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($item['name']) ?></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <a href="?page=<?= $page ?>&edit_id=<?= $item['id'] ?>" class="text-indigo-500 hover:text-indigo-700 p-1"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    <form method="POST" onsubmit="return confirm('Delete this?')" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="<?= $page ?>">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        <button type="submit" class="text-rose-400 hover:text-rose-600 p-1"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if(empty($items)): ?>
                                            <p class="col-span-full text-center text-slate-400 text-sm py-4">No <?= strtolower($title) ?>s found.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php break; ?>
                <?php endswitch; ?>
            </main>
        </div>
    </div>

    <!-- Chart Implementations -->
    <?php if ($page == 'dashboard'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false } } } };
            
            // Revenue Line Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: <?= $monthLabels ?>,
                        datasets: [{
                            data: <?= $monthData ?>,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.05)',
                            borderWidth: 4,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: true,
                                backgroundColor: '#ffffff',
                                titleColor: '#1e293b',
                                bodyColor: '#475569',
                                titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: 'bold' },
                                bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                                padding: 12,
                                cornerRadius: 10,
                                displayColors: false,
                                borderColor: '#e2e8f0',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += '₦' + new Intl.NumberFormat().format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                display: true,
                                grid: {
                                    borderDash: [5, 5],
                                    color: '#f1f5f9',
                                    drawBorder: false,
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '₦' + new Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 1 }).format(value);
                                    },
                                    font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                                    color: '#94a3b8'
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                                    color: '#94a3b8'
                                }
                            }
                        }
                    }
                });
            }

            // Country Pie Chart
            const countryCtx = document.getElementById('countryChart');
            if (countryCtx) {
                new Chart(countryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= $countryLabels ?>,
                        datasets: [{
                            data: <?= $countryCounts ?>,
                            backgroundColor: ['#6366f1', '#38bdf8', '#fbbf24', '#f87171'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { weight: 'bold', size: 10 } } } } }
                });
            }

            // --- NEW ANALYTICS CHARTS ---

            // Unique Visits Chart
            const uniqueCtx = document.getElementById('uniqueVisitsChart');
            if (uniqueCtx) {
                new Chart(uniqueCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_keys($analyticsData['unique_visits_chart'])) ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($analyticsData['unique_visits_chart'])) ?>,
                            borderColor: '#10b981', // Emerald-500
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Device Chart
            const deviceCtx = document.getElementById('deviceChart');
            if (deviceCtx) {
                const deviceData = <?= json_encode($analyticsData['device_stats']) ?>;
                new Chart(deviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: deviceData.map(d => d.device_type),
                        datasets: [{
                            data: deviceData.map(d => d.count),
                            backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981'],
                            borderWidth: 0,
                            hoverOffset: 5
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true, font: {size: 10} } } } }
                });
            }

            // OS Chart
            const osCtx = document.getElementById('osChart');
            if (osCtx) {
                const osData = <?= json_encode($analyticsData['os_stats']) ?>;
                new Chart(osCtx, {
                    type: 'pie', // Pie for variety
                    data: {
                        labels: osData.map(d => d.os_name),
                        datasets: [{
                            data: osData.map(d => d.count),
                            backgroundColor: ['#3b82f6', '#8b5cf6', '#a855f7', '#6366f1', '#0ea5e9'],
                            borderWidth: 0,
                            hoverOffset: 5
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: {size: 10} } } } }
                });
            }
        });
    </script>
    <?php endif; ?>

    <!-- Map Libs -->
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/js/jsvectormap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>

    <script>
        // --- MAP IMPLEMENTATION ---
        document.addEventListener('DOMContentLoaded', () => {
             const mapEl = document.getElementById('world-map');
             if (mapEl) {
                 // Prepare Data
                 const dbData = <?= json_encode($analyticsData['top_countries']) ?>;
                 const mapData = {};
                 
                 // Fallback Mapping for existing data w/o country_code
                 const nameToCode = {
                    'Nigeria': 'NG', 'United States': 'US', 'United Kingdom': 'GB', 'Canada': 'CA', 'Ghana': 'GH', 
                    'Germany': 'DE', 'France': 'FR', 'Australia': 'AU', 'India': 'IN', 'China': 'CN', 'Brazil': 'BR',
                    'South Africa': 'ZA', 'Kenya': 'KE', 'Russia': 'RU', 'Japan': 'JP', 'Italy': 'IT', 'Spain': 'ES'
                 };

                 dbData.forEach(item => {
                     let code = item.country_code;
                     if (!code && item.country) {
                         code = nameToCode[item.country] || null;
                     }
                     if (code) {
                         mapData[code] = (mapData[code] || 0) + item.visitors;
                     }
                 });

                 new jsVectorMap({
                    selector: '#world-map',
                    map: 'world',
                    zoomButtons: true,
                    zoomOnScroll: false,
                    visualizeData: {
                        scale: ['#e0e7ff', '#4f46e5'], // Indigo-50 to Indigo-600
                        values: mapData
                    },
                    regionStyle: {
                        initial: { fill: '#f1f5f9' },
                        hover: { fill: '#6366f1' }
                    },
                    onRegionTooltipShow(event, tooltip, code) {
                        const count = mapData[code] || 0;
                        if (count > 0) {
                            tooltip.text(
                                `<h5 class="font-bold text-sm">${tooltip.text()}</h5>
                                 <p class="text-xs">Visitors: ${count}</p>`
                            , true); // true = enable HTML
                        }
                    }
                });
             }
        });
    </script>
    <script>
        // --- LIVE SEARCH IMPLEMENTATION ---
        document.addEventListener('DOMContentLoaded', () => {
            const searchInputs = document.querySelectorAll('.live-search');
            
            searchInputs.forEach(input => {
                let timeout = null;
                
                input.addEventListener('input', function() {
                    const form = this.closest('form');
                    const url = new URL(window.location.href);
                    const params = new URLSearchParams(new FormData(form));
                    
                    // Clear previous timeout (Debounce)
                    clearTimeout(timeout);
                    
                    // Set new timeout to execute search after 500ms
                    timeout = setTimeout(() => {
                        // Update URL params
                        for(const [key, value] of params) {
                            url.searchParams.set(key, value);
                        }
                        
                        // Update Browser History without reload
                        window.history.pushState({}, '', url);
                        
                        // Fetch new content
                        fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('search-results-container');
                            const currentContainer = document.getElementById('search-results-container');
                            
                            if (newContainer && currentContainer) {
                                currentContainer.innerHTML = newContainer.innerHTML;
                            }
                        })
                        .catch(err => console.error('Live Search Error:', err));
                        
                    }, 500); // 500ms delay
                });
                
                // Prevent Enter key from submitting (since we have live search)
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
    <!-- ADMIN MANAGEMENT SECTION (Appended) -->
    <div id="admin-management-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('admin-management-modal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-4" id="modal-title">Administrative Access</h3>
                    
                    <!-- Invite Section -->
                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 mb-6" id="invite-section">
                        <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-widest mb-2">Invite New Admin</h4>
                        <div class="flex gap-2">
                            <button id="generate-invite-btn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-700 transition">
                                <i class="fa-solid fa-link mr-2"></i>Generate Link
                            </button>
                            <div class="flex-1 relative">
                                <input type="text" id="invite-link-output" class="w-full border border-indigo-200 rounded-lg px-3 py-2 text-sm bg-white text-gray-600 outline-none focus:border-indigo-400" readonly placeholder="Link will appear here...">
                                <button id="copy-invite-btn" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-indigo-600 hover:text-indigo-800 text-xs font-bold bg-indigo-100 px-2 py-1 rounded">COPY</button>
                            </div>
                        </div>
                    </div>

                    <!-- Admins Lists -->
                    <div x-data="{ activeTab: 'active' }">
                        <div class="flex border-b border-gray-200 mb-4">
                            <button @click="activeTab = 'active'" :class="activeTab === 'active' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-2 px-4 border-b-2 font-medium text-sm transition">Active</button>
                            <button @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-2 px-4 border-b-2 font-medium text-sm transition relative">
                                Pending 
                                <span id="pending-badge" class="hidden absolute top-2 right-10 w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                            </button>
                            <button @click="activeTab = 'suspended'" :class="activeTab === 'suspended' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="flex-1 py-2 px-4 border-b-2 font-medium text-sm transition">Suspended</button>
                        </div>

                        <!-- User Lists Containers -->
                        <div class="h-64 overflow-y-auto space-y-1 custom-scrollbar pr-2">
                            <div x-show="activeTab === 'active'" id="list-active" class="space-y-2"></div>
                            <div x-show="activeTab === 'pending'" id="list-pending" class="space-y-2"></div>
                            <div x-show="activeTab === 'suspended'" id="list-suspended" class="space-y-2"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                    <button type="button" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto sm:text-sm" onclick="document.getElementById('admin-management-modal').classList.add('hidden')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = 0; // Explicitly declared

        // Auto-run if on management section
        document.addEventListener('DOMContentLoaded', () => {
            const listContainer = document.getElementById('list-active');
            const mainTable = document.getElementById('main-admin-table-body');
            
            if(listContainer || mainTable) {
                fetchAllAdminsPage();
            }

            // Initialize Invite logic for both Modal and Main Page
            setupInviteUI('generate-invite-btn', 'invite-link-output', 'copy-invite-btn');
            setupInviteUI('main-generate-invite-btn', 'main-invite-link-output', 'main-copy-invite-btn');
        });

        const setupInviteUI = (genId, outId, copyId) => {
            const btn = document.getElementById(genId);
            const input = document.getElementById(outId);
            const copy = document.getElementById(copyId);
            
            if(btn && input) {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Generating...';
                    try {
                        const res = await fetch('generate-invite', { method: 'POST' });
                        const data = await res.json();
                        if(data.success) {
                            input.value = data.link;
                            if(copy) copy.classList.remove('hidden');
                        } else { alert('Error: ' + data.message); }
                    } catch(e) { alert('Failed to generate link.'); }
                    btn.disabled = false;
                    btn.innerHTML = (genId.includes('main')) ? '<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Invitation' : '<i class="fa-solid fa-link mr-2"></i>Generate Link';
                });
            }
            
            if(copy && input) {
                copy.addEventListener('click', () => {
                    input.select();
                    document.execCommand('copy');
                    const originalText = copy.innerHTML;
                    copy.innerHTML = 'COPIED!';
                    setTimeout(() => copy.innerHTML = originalText, 2000);
                });
            }
        };

        async function fetchAllAdminsPage() {
            const listActive = document.getElementById('list-active');
            const listPending = document.getElementById('list-pending');
            const listSuspended = document.getElementById('list-suspended');
            const mainTableBody = document.getElementById('main-admin-table-body');
            
            // Show loading if any container exists
            if(listActive) listActive.innerHTML = listPending.innerHTML = listSuspended.innerHTML = '<p class="text-xs text-slate-400 italic text-center py-4 text-center">Loading...</p>';

            try {
                const res = await fetch('admin-list-all');
                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                const data = await res.json();
                
                if(data.success) {
                    currentUserId = data.current_user_id || 0;
                    const users = data.users || [];
                    const isLegacySession = data.is_legacy === true;
                    
                    // --- MODAL RENDERING ---
                    const renderUserModal = (u, type) => {
                        if (!u || !u.username) return ''; 
                        const isSelf = (u.id == currentUserId && u.source === (isLegacySession ? 'legacy' : 'role'));
                        let actions = '';
                        
                        if (!isSelf) {
                            if (type === 'active') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="changeStatus(${u.id}, '${u.source}', 'suspend')" class="text-amber-500 hover:text-amber-700 text-xs font-bold" title="Suspend"><i class="fa-solid fa-ban"></i></button>
                                        <button onclick="deleteUser(${u.id}, '${u.source}')" class="text-rose-400 hover:text-rose-600 text-xs font-bold" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>`;
                            } else if (type === 'pending') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="approveUser(${u.id}, '${u.source}')" class="text-emerald-500 hover:text-emerald-700 text-xs font-bold uppercase border border-emerald-200 bg-emerald-50 px-2 py-1 rounded">Approve</button>
                                        <button onclick="deleteUser(${u.id}, '${u.source}')" class="text-rose-400 hover:text-rose-600 text-xs font-bold px-2 py-1"><i class="fa-solid fa-xmark"></i></button>
                                    </div>`;
                            } else if (type === 'suspended') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="changeStatus(${u.id}, '${u.source}', 'reactivate')" class="text-emerald-500 hover:text-emerald-700 text-xs font-bold uppercase">Reactivate</button>
                                        <button onclick="deleteUser(${u.id}, '${u.source}')" class="text-rose-400 hover:text-rose-600 text-xs font-bold" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>`;
                            }
                        } else {
                            actions = `<div class="flex items-center"><span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded-md font-black tracking-tighter border border-indigo-100 uppercase">You</span></div>`;
                        }

                        const initials = (u.username || 'U').substring(0,2).toUpperCase();
                        const joinedDate = u.created_at ? new Date(u.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Unknown';

                        return `
                        <div class="flex justify-between items-center py-4 px-2 hover:bg-slate-50 transition border-b border-dashed border-slate-100 last:border-0 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-50 to-white border border-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-black shadow-sm">
                                    ${initials}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 leading-tight">${u.username}</p>
                                    <p class="text-[10px] text-slate-400 font-medium">Member since ${joinedDate}</p>
                                </div>
                            </div>
                            <div class="flex items-center">${actions}</div>
                        </div>`;
                    };

                    const active = users.filter(u => u.status === 'active');
                    const pending = users.filter(u => u.status === 'pending');
                    const suspended = users.filter(u => u.status === 'suspended');

                    if(listActive) {
                        listActive.innerHTML = active.length ? active.map(u => renderUserModal(u, 'active')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No active admins.</p>';
                        listPending.innerHTML = pending.length ? pending.map(u => renderUserModal(u, 'pending')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No pending requests.</p>';
                        listSuspended.innerHTML = suspended.length ? suspended.map(u => renderUserModal(u, 'suspended')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No suspended accounts.</p>';
                        const badge = document.getElementById('pending-badge');
                        if(badge) badge.classList.toggle('hidden', pending.length === 0);
                    }

                    // --- MAIN TABLE RENDERING ---
                    if(mainTableBody) {
                        mainTableBody.innerHTML = users.map(u => {
                            const isLegacyType = (u.source === 'legacy');
                            const isSelf = (u.id == currentUserId && isLegacySession == isLegacyType);
                            const joined = u.created_at ? new Date(u.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Unknown';
                            
                            let statusBadge = '';
                            if(u.status === 'active') statusBadge = '<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active</span>';
                            else if(u.status === 'pending') statusBadge = '<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending</span>';
                            else statusBadge = '<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended</span>';

                            let actions = '';
                            if(isSelf) {
                                actions = '<span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest bg-indigo-50 px-2 py-1 rounded">YOU</span>';
                            } else {
                                actions = `<div class="flex justify-end gap-2">`;
                                if(u.status === 'pending') actions += `<button onclick="approveUser(${u.id}, '${u.source}')" class="text-emerald-500 hover:text-emerald-700 text-[10px] font-black uppercase">Approve</button>`;
                                actions += `
                                    <button onclick="changeStatus(${u.id}, '${u.source}', '${u.status === 'suspended' ? 'reactivate' : 'suspend'}')" class="${u.status === 'suspended' ? 'text-emerald-500' : 'text-amber-500'} hover:opacity-75 transition">
                                        <i class="fa-solid ${u.status === 'suspended' ? 'fa-check' : 'fa-ban'}"></i>
                                    </button>
                                    <button onclick="deleteUser(${u.id}, '${u.source}')" class="text-rose-400 hover:text-rose-600 transition"><i class="fa-solid fa-trash"></i></button>
                                </div>`;
                            }

                            return `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-[10px] font-black uppercase">${(u.username||'U').substring(0,2)}</div>
                                        <div><p class="text-xs font-bold text-slate-700 leading-tight">${u.username}</p><p class="text-[10px] text-slate-400">Joined ${joined}</p></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-[10px] font-medium text-slate-500 italic">${isLegacyType ? 'Legacy DB' : 'Role-Based'}</span></td>
                                <td class="px-6 py-4"><span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider ${(u.role || '').includes('super') ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'}">${u.role || 'admin'}</span></td>
                                <td class="px-6 py-4">${statusBadge}</td>
                                <td class="px-6 py-4 text-right">${actions}</td>
                            </tr>`;
                        }).join('');
                    }

                }
            } catch(e) { console.error('Fetch Error:', e); }
        }

        async function approveUser(id, source) {
            if(!confirm('Approve this user?')) return;
            postAction('admin-approve', { id, source });
        }

        async function changeStatus(id, source, action) {
            const endpoint = (action === 'suspend') ? 'admin-suspend' : 'admin-reactivate';
            if(!confirm(`Are you sure you want to ${action} this user?`)) return;
            postAction(endpoint, { id, source });
        }

        async function deleteUser(id, source) {
            if(!confirm('Permanently delete this admin account? This cannot be undone.')) return;
            postAction('admin-delete', { id, source });
        }

        async function postAction(url, data) {
            try {
                const form = new URLSearchParams();
                for(const k in data) form.append(k, data[k]);
                
                const res = await fetch(url, { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: form 
                });
                const response = await res.json();
                if(response.success) {
                    fetchAllAdminsPage();
                } else {
                    alert(response.message);
                }
            } catch(e) { alert('Action failed.'); }
        }
    </script>
</body>
</html>