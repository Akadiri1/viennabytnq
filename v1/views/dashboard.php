<?php
// =================================================================================================
// INITIALIZATION & SECURITY (Preserved)
// =================================================================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin_login');
    exit();
}

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
    
    // AJAX Action for deleting gallery image
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

        if ($pid && !empty($vName) && $vStock > 0) {
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
                if (!empty($vName)) {
                    $vPrice = !empty($_POST['variants_price'][$index]) ? $_POST['variants_price'][$index] : ($price ?: 0.00);
                    $vStock = !empty($_POST['variants_stock'][$index]) ? $_POST['variants_stock'][$index] : 0;
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
        header('Location: /dashboard?page=sizes');
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
            $totalVisits = 0; 
        }
    }

    // Visitor Location Stats
    $locationStats = [];
    try {
        $stmt = $conn->query("SELECT country, region, COUNT(DISTINCT ip_address) as visitors FROM site_visits WHERE country IS NOT NULL GROUP BY country, region ORDER BY visitors DESC LIMIT 5");
        if ($stmt) $locationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* Ignore */ }
    
    // Recent Orders Logic with Search
    $dashboardSearch = $_GET['dashboard_search'] ?? '';
    $recentWhere = "";
    $recentParams = [];
    if($dashboardSearch) {
        $recentWhere = "WHERE (o.order_number LIKE ? OR c.full_name LIKE ?)";
        $recentParams = ["%$dashboardSearch%", "%$dashboardSearch%"];
    }
    
    $recentOrders = $conn->prepare("SELECT o.id, o.order_number, o.grand_total, o.shipping_address, o.order_status, o.created_at, c.full_name, c.email 
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

if ($page === 'customers') {
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
    <title>Admin Dashboard | <?= $site_name ?? 'VIENNA' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .glass-sidebar { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }
        .nav-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .nav-link.active { background: rgba(255,255,255,0.05); border-left-color: #6366f1; color: white !important; }
        .glass-card { background: white; border: 1px solid #e2e8f0; border-radius: 1.25rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stats-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        input:focus, select:focus, textarea:focus { border-color: #6366f1 !important; ring: 2px #6366f120; }
    </style>
</head>
<body x-data="{ sidebarOpen: false }">

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/60 z-40 md:hidden backdrop-blur-sm"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
                <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="glass-sidebar w-64 text-slate-400 fixed inset-y-0 left-0 z-50 transform md:relative md:translate-x-0 transition duration-300 ease-in-out">
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
                <a href="?page=customers" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'customers' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users w-5"></i><span class="ml-3 font-medium">Customers</span>
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
                <!-- <a href="?page=colors" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'colors' ? 'active' : '' ?>">
                    <i class="fa-solid fa-palette w-5"></i><span class="ml-3 font-medium">Colors</span>
                </a>
                <a href="?page=sizes" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'sizes' ? 'active' : '' ?>">
                    <i class="fa-solid fa-ruler-combined w-5"></i><span class="ml-3 font-medium">Sizes</span>
                </a> -->
                <a href="?page=manage_admins" @click="sidebarOpen = false" class="nav-link flex items-center px-4 py-3 rounded-xl hover:text-white <?= $page == 'manage_admins' ? 'active' : '' ?>">
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
        <div class="flex-1 flex flex-col min-w-0">
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
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">New Customers</p>
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
                                        if (count($analyticsOrders) >= 2) {
                                            $lastMonth = end($analyticsOrders)['total'];
                                            $prevMonth = prev($analyticsOrders)['total'];
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
                            <!-- Visitor Locations -->
                            <div class="glass-card p-6">
                                <h4 class="font-bold text-slate-800 mb-6">Visitor Locations</h4>
                                <?php if (!empty($locationStats)): ?>
                                    <div class="space-y-4">
                                        <?php foreach ($locationStats as $loc): ?>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                                        <?= strtoupper(substr($loc['country'], 0, 2)) ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-700"><?= $loc['country'] ?></p>
                                                        <p class="text-[10px] text-slate-400 font-medium"><?= $loc['region'] ?></p>
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold text-slate-600"><?= $loc['visitors'] ?> Visitors</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="h-72 flex flex-col items-center justify-center text-slate-300 border-2 border-dashed border-slate-100 rounded-xl">
                                        <i class="fa-solid fa-map-location-dot text-4xl mb-3"></i>
                                        <p class="font-bold text-sm">No Location Data Yet</p>
                                    </div>
                                <?php endif; ?>
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
                                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Customer</th>
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
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer</p>
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
                                            <!-- Price & Stock Row -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Base Price (₦)</label>
                                                    <input type="number" name="price" value="<?= $productToEdit['price'] ?? '' ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none font-bold text-slate-700" placeholder="0.00">
                                                </div>
                                                <!-- <div>
                                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Stock</label>
                                                    <input type="number" name="stock_quantity" value="<?= $productToEdit['stock_quantity'] ?? '0' ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none font-bold text-slate-700">
                                                </div> -->
                                                <input type="hidden" name="stock_quantity" value="<?= $productToEdit['stock_quantity'] ?? '0' ?>">
                                            </div>
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
                                                <input type="file" name="gallery_images[]" id="gery_images" class="hidden" multiple onchange="handleGallerySelect(this)">
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
                                                    if (empty($existingVariants)) {
                                                        $existingVariants = [['variant_name' => '', 'price' => '', 'stock_quantity' => '']];
                                                    }
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

                                                                // Select all variant inputs
                                                                const variantNames = document.querySelectorAll('input[name="variants_name[]"]');
                                                                const variantPrices = document.querySelectorAll('input[name="variants_price[]"]');
                                                                const variantStocks = document.querySelectorAll('input[name="variants_stock[]"]');

                                                                // Check if at least one variant exists (OPTIONAL NOW)
                                                                // if (variantNames.length === 0) {
                                                                //    alert('Please add at least one product variant (e.g. Size/Color option).');
                                                                //    e.preventDefault();
                                                                //    return;
                                                                // }

                                                                // Helper to validate group
                                                                const validateGroup = (inputs) => {
                                                                    inputs.forEach(input => {
                                                                        if(!input.value.trim()) {
                                                                            isValid = false;
                                                                            input.classList.add('border-rose-500', 'animate-shake');
                                                                            if(!firstError) firstError = input;
                                                                            
                                                                            // Add error message if not exists
                                                                            let errorMsg = input.parentNode.querySelector('.variant-error-msg');
                                                                            if (!errorMsg) {
                                                                                errorMsg = document.createElement('p');
                                                                                errorMsg.className = 'variant-error-msg text-[10px] text-rose-500 font-bold mt-1 animate-pulse';
                                                                                errorMsg.innerText = 'Required';
                                                                                input.parentNode.appendChild(errorMsg);
                                                                            }
                                                                            
                                                                            // Remove shake after animation (keep border)
                                                                            setTimeout(() => {
                                                                                input.classList.remove('animate-shake');
                                                                            }, 400);

                                                                            // Remove error on input
                                                                            input.addEventListener('input', function() {
                                                                                this.classList.remove('border-rose-500');
                                                                                const msg = this.parentNode.querySelector('.variant-error-msg');
                                                                                if(msg) msg.remove();
                                                                            }, {once: true});
                                                                        }
                                                                    });
                                                                };

                                                                validateGroup(variantNames);
                                                                validateGroup(variantPrices);
                                                                validateGroup(variantPrices);
                                                                validateGroup(variantNames);
                                                                validateGroup(variantPrices);
                                                                // Custom validation for Stock to ensure > 0
                                                                variantStocks.forEach(input => {
                                                                    if (!input.value.trim() || parseInt(input.value) < 1) {
                                                                        isValid = false;
                                                                        input.classList.add('border-rose-500', 'animate-shake');
                                                                        if(!firstError) firstError = input;
                                                                        
                                                                        let errorMsg = input.parentNode.querySelector('.variant-error-msg');
                                                                        if (!errorMsg) {
                                                                            errorMsg = document.createElement('p');
                                                                            errorMsg.className = 'variant-error-msg text-[10px] text-rose-500 font-bold mt-1 animate-pulse';
                                                                            input.parentNode.appendChild(errorMsg);
                                                                        }
                                                                        errorMsg.innerText = !input.value.trim() ? 'Required' : 'Must be > 0';

                                                                        setTimeout(() => input.classList.remove('animate-shake'), 400);
                                                                        input.addEventListener('input', function() {
                                                                            this.classList.remove('border-rose-500');
                                                                            const msg = this.parentNode.querySelector('.variant-error-msg');
                                                                            if(msg) msg.remove();
                                                                        }, {once: true});
                                                                    }
                                                                });

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
                                                                    // Optional: Toast or small alert
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
                                                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-100 transition active:scale-[0.98]">
                                                    <?= $productToEdit ? 'Save Changes' : 'Publish Product' ?>
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
                                                            $displayPrice = '₦' . number_format($product['price']);
                                                            // Use Variant Price Range if Base Price is 0 and variants exist
                                                            if ($product['price'] == 0 && $product['min_variant_price'] > 0) {
                                                                if ($product['min_variant_price'] == $product['max_variant_price']) {
                                                                    $displayPrice = '₦' . number_format($product['min_variant_price']);
                                                                } else {
                                                                    $displayPrice = '₦' . number_format($product['min_variant_price']) . ' - ₦' . number_format($product['max_variant_price']);
                                                                }
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
                                            <th class="px-8 py-4">Customer</th>
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
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer</p>
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
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.order_total_amount)"></span>
                                                </div>
                                                <div class="flex justify-between text-sm text-slate-500">
                                                    <span>Shipping</span>
                                                    <span class="font-bold text-slate-700" x-text="'₦' + new Intl.NumberFormat().format(selectedOrder?.shipping_fee)"></span>
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

                    <?php case 'customers': ?>
                        <div class="glass-card overflow-hidden">
                            <div class="px-8 py-6 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h4 class="font-bold text-slate-800">Customer Database <span class="text-slate-400 ml-1 font-medium">(<?= $totalCustomers ?>)</span></h4>
                                <form method="GET" class="relative">
                                    <input type="hidden" name="page" value="customers">
                                    <input type="text" name="search" placeholder="Search customers..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="live-search pl-10 pr-4 py-2 bg-slate-50 border rounded-xl text-sm outline-none w-full sm:w-64">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                </form>
                            </div>
                            <div id="search-results-container">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b">
                                                <th class="px-8 py-4">Customer</th>
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
                                                    <form method="POST" onsubmit="return confirm('Delete customer <?= htmlspecialchars($cust['full_name'], ENT_QUOTES) ?>? Warning: This may delete all their orders too.')" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="customers">
                                                        <input type="hidden" name="id" value="<?= $cust['id'] ?>">
                                                        <button type="submit" class="text-rose-400 hover:text-rose-600 p-2"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($customers)): ?>
                                                <tr><td colspan="5" class="text-center py-8 text-slate-400 font-medium">No customers found.</td></tr>
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
                                            <a href="?page=customers&p=<?= $currentPage - 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Prev</a>
                                        <?php endif; ?>
                                        <?php if ($currentPage < $totalPages): ?>
                                            <a href="?page=customers&p=<?= $currentPage + 1 ?>" class="px-3 py-1 bg-white border rounded-lg text-xs font-bold hover:bg-slate-50 transition">Next</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
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
        });
    </script>
    <?php endif; ?>

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
                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 mb-6">
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
        let currentUserId = 0;

        // Auto-run if on manage_admins page
        document.addEventListener('DOMContentLoaded', () => {
            const listContainer = document.getElementById('page-list-active');
            if(listContainer) {
                fetchAllAdminsPage();
            }
        });

        const generateBtn = document.getElementById('generate-invite-link-btn');
        if(generateBtn) {
            generateBtn.addEventListener('click', async () => {
                const resultContainer = document.getElementById('invite-result-container');
                try {
                    const res = await fetch('/generate-invite', { method: 'POST' });
                    const data = await res.json();
                    if(data.success) {
                        const linkInput = document.getElementById('invite-link-field');
                        linkInput.value = data.link;
                        resultContainer.classList.remove('hidden');
                        document.getElementById('copy-link-btn').innerHTML = 'COPY';
                    } else { alert('Error: ' + data.message); }
                } catch(e) { alert('Failed to generate link.'); }
            });
        }

        const copyBtn = document.getElementById('copy-link-btn');
        if(copyBtn) {
            copyBtn.addEventListener('click', () => {
                const linkInput = document.getElementById('invite-link-field');
                linkInput.select();
                document.execCommand('copy');
                copyBtn.innerHTML = 'COPIED!';
                setTimeout(() => copyBtn.innerHTML = 'COPY', 2000);
            });
        }

        async function fetchAllAdminsPage() {
            const listActive = document.getElementById('page-list-active');
            const listPending = document.getElementById('page-list-pending');
            const listSuspended = document.getElementById('page-list-suspended');
            
            listActive.innerHTML = listPending.innerHTML = listSuspended.innerHTML = '<p class="text-xs text-slate-400 italic text-center py-4">Loading...</p>';

            try {
                const res = await fetch('/admin-list-all');
                const data = await res.json();
                
                if(data.success) {
                    currentUserId = data.current_user_id;
                    const users = data.users;
                    
                    const renderUser = (u, type) => {
                        const isSelf = (u.id == currentUserId);
                        let actions = '';
                        
                        if (!isSelf) {
                            if (type === 'active') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="changeStatus(${u.id}, 'suspend')" class="text-amber-500 hover:text-amber-700 text-xs font-bold" title="Suspend"><i class="fa-solid fa-ban"></i></button>
                                        <button onclick="deleteUser(${u.id})" class="text-rose-400 hover:text-rose-600 text-xs font-bold" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>`;
                            } else if (type === 'pending') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="approveUser(${u.id})" class="text-emerald-500 hover:text-emerald-700 text-xs font-bold uppercase border border-emerald-200 bg-emerald-50 px-2 py-1 rounded">Approve</button>
                                        <button onclick="deleteUser(${u.id})" class="text-rose-400 hover:text-rose-600 text-xs font-bold px-2 py-1"><i class="fa-solid fa-xmark"></i></button>
                                    </div>`;
                            } else if (type === 'suspended') {
                                actions = `
                                    <div class="flex gap-2">
                                        <button onclick="changeStatus(${u.id}, 'reactivate')" class="text-emerald-500 hover:text-emerald-700 text-xs font-bold uppercase">Reactivate</button>
                                        <button onclick="deleteUser(${u.id})" class="text-rose-400 hover:text-rose-600 text-xs font-bold" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>`;
                            }
                        } else {
                            actions = `<span class="text-[10px] bg-indigo-100 text-indigo-600 px-2 py-1 rounded font-bold">YOU</span>`;
                        }

                        return `
                        <div class="flex justify-between items-center py-4 px-2 hover:bg-slate-50 transition border-b border-dashed border-slate-100 last:border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                    ${u.username.substring(0,2).toUpperCase()}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 leading-tight">${u.username}</p>
                                    <p class="text-[10px] text-slate-400">Joined ${new Date(u.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                            ${actions}
                        </div>`;
                    };

                    const active = users.filter(u => u.status === 'active');
                    const pending = users.filter(u => u.status === 'pending');
                    const suspended = users.filter(u => u.status === 'suspended');

                    const badge = document.getElementById('page-pending-badge');
                    if(badge) badge.classList.toggle('hidden', pending.length === 0);

                    listActive.innerHTML = active.length ? active.map(u => renderUser(u, 'active')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No active admins.</p>';
                    listPending.innerHTML = pending.length ? pending.map(u => renderUser(u, 'pending')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No pending requests.</p>';
                    listSuspended.innerHTML = suspended.length ? suspended.map(u => renderUser(u, 'suspended')).join('') : '<p class="text-xs text-slate-400 text-center py-4">No suspended accounts.</p>';

                } else {
                    alert('Failed to load admins.');
                }
            } catch(e) { console.error(e); }
        }

        async function approveUser(id) {
            if(!confirm('Approve this user?')) return;
            postAction('/admin-approve', { id });
        }

        async function changeStatus(id, action) {
            const endpoint = (action === 'suspend') ? '/admin-suspend' : '/admin-reactivate';
            if(!confirm(`Are you sure you want to ${action} this user?`)) return;
            postAction(endpoint, { id });
        }

        async function deleteUser(id) {
            if(!confirm('Permanently delete this admin account? This cannot be undone.')) return;
            postAction('/admin-delete', { id });
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