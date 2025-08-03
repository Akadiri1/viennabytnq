<?php
// =================================================================================================
// INITIALIZATION & SECURITY
// =================================================================================================
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit();
}
// =================================================================================================
//  IMAGE UPLOAD CONFIGURATION (ROBUST & SECURE)
// =================================================================================================
define('UPLOAD_DIR_SERVER', dirname(__DIR__) . '/../www/uploads/');
define('UPLOAD_PATH_WEB', 'uploads/');

// =================================================================================================
// POST ACTION HANDLER
// =================================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- UNIVERSAL DELETE ACTION ---
    if ($_POST['action'] === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $table = $_POST['table'] ?? '';
        $allowed_tables = ['panel_products', 'colors', 'sizes', 'product_images'];
        if ($id && in_array($table, $allowed_tables)) {
            if ($table === 'panel_products') {
                $conn->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
            }
            $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Item deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Invalid delete request.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // --- PRODUCT ADD/EDIT ACTION ---
    if ($_POST['action'] === 'add_product' || $_POST['action'] === 'edit_product') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']));
        $product_text = htmlspecialchars(trim($_POST['product_text']));
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
        $visibility = $_POST['visibility'] === 'show' ? 'show' : 'hide';

        $uploadSingleImage = function ($fileKey, $currentImageKey) {
            $imagePath = $_POST[$currentImageKey] ?? '';
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
                if (!is_dir(UPLOAD_DIR_SERVER)) {
                    mkdir(UPLOAD_DIR_SERVER, 0775, true);
                }
                $filename = time() . '_' . basename(preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", $_FILES[$fileKey]["name"]));
                $target_file = UPLOAD_DIR_SERVER . $filename;
                if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $target_file)) {
                    $imagePath = UPLOAD_PATH_WEB . $filename;
                } else {
                    $_SESSION['error_message'] = "Upload failed for '{$fileKey}'. Check folder permissions.";
                }
            }
            return $imagePath;
        };
        $image_one = $uploadSingleImage('image_one', 'current_image_one');
        $image_two = $uploadSingleImage('image_two', 'current_image_two');

        if ($_POST['action'] === 'add_product') {
            $sql = "INSERT INTO panel_products (name, product_text, price, stock_quantity, visibility, image_one, image_two, date_created, time_created) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";
            $conn->prepare($sql)->execute([$name, $product_text, $price, $stock_quantity, $visibility, $image_one, $image_two]);
            $productId = $conn->lastInsertId();
            $_SESSION['success_message'] = "Product added successfully.";
        } else { // This handles 'edit_product'
            $sql = "UPDATE panel_products SET name=?, product_text=?, price=?, stock_quantity=?, visibility=?, image_one=?, image_two=? WHERE id=?";
            $conn->prepare($sql)->execute([$name, $product_text, $price, $stock_quantity, $visibility, $image_one, $image_two, $id]);
            $productId = $id;
            $_SESSION['success_message'] = "Product updated successfully.";
        }

        if (isset($_FILES['gallery_images']) && !empty(array_filter($_FILES['gallery_images']['name']))) {
            if (!is_dir(UPLOAD_DIR_SERVER)) {
                mkdir(UPLOAD_DIR_SERVER, 0775, true);
            }
            foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['gallery_images']['error'][$key] == UPLOAD_ERR_OK) {
                    $filename = time() . '_gallery_' . basename(preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", $_FILES["gallery_images"]["name"][$key]));
                    $target_file = UPLOAD_DIR_SERVER . $filename;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $db_path = UPLOAD_PATH_WEB . $filename;
                        $conn->prepare("INSERT INTO product_images (product_id, image_path, alt_text) VALUES (?, ?, ?)")->execute([$productId, $db_path, $name]);
                    }
                }
            }
        }

        $conn->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$productId]);
        if (!empty($_POST['colors'])) {
            $stmt = $conn->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
            foreach ($_POST['colors'] as $color_id) {
                $stmt->execute([$productId, $color_id]);
            }
        }
        $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$productId]);
        if (!empty($_POST['sizes'])) {
            $stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size_id) VALUES (?, ?)");
            foreach ($_POST['sizes'] as $size_id) {
                $stmt->execute([$productId, $size_id]);
            }
        }
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
}

// =================================================================================================
// GET DATA FETCHER
// =================================================================================================
$page = $_GET['page'] ?? 'dashboard';
if ($page === 'dashboard') {
    $totalRevenue = $conn->query("SELECT SUM(grand_total) FROM orders WHERE order_status = 'paid'")->fetchColumn() ?? 0;
    $totalOrders = $conn->query("SELECT COUNT(id) FROM orders")->fetchColumn() ?? 0;
    $newCustomers = $conn->query("SELECT COUNT(id) FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?? 0;
    $averageOrderValue = ($totalOrders > 0) ? $totalRevenue / $totalOrders : 0;
    $recentOrders = $conn->query("SELECT o.order_number, o.grand_total, o.order_status, o.created_at, c.full_name FROM orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'manage_products') {
    $products = $conn->query("SELECT * FROM panel_products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'add_product') {
    $colors = $conn->query("SELECT * FROM colors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sizes = $conn->query("SELECT * FROM sizes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $productToEdit = null;
    $productColors = [];
    $productSizes = [];
    $productImages = [];
    if (isset($_GET['edit_id'])) {
        $editId = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
        $stmt = $conn->prepare("SELECT * FROM panel_products WHERE id = ?");
        $stmt->execute([$editId]);
        $productToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($productToEdit) {
            // CORRECTED: Use prepared statements for security and consistency.
            $stmtColors = $conn->prepare("SELECT color_id FROM product_colors WHERE product_id = ?");
            $stmtColors->execute([$editId]);
            $productColors = $stmtColors->fetchAll(PDO::FETCH_COLUMN);

            $stmtSizes = $conn->prepare("SELECT size_id FROM product_sizes WHERE product_id = ?");
            $stmtSizes->execute([$editId]);
            $productSizes = $stmtSizes->fetchAll(PDO::FETCH_COLUMN);

            $stmtImages = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id DESC");
            $stmtImages->execute([$editId]);
            $productImages = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
        }
    }
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
    $sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sizeToEdit = null;
    if (isset($_GET['edit_id'])) {
        $stmt = $conn->prepare("SELECT * FROM sizes WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $sizeToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
function getStatusBadge($status)
{
    $status = strtolower($status);
    $colorClasses = 'bg-gray-100 text-gray-800';
    if (in_array($status, ['paid', 'show', 'completed'])) {
        $colorClasses = 'bg-green-100 text-green-800';
    } elseif (in_array($status, ['pending', 'processing'])) {
        $colorClasses = 'bg-yellow-100 text-yellow-800';
    } elseif (in_array($status, ['failed', 'hide', 'cancelled'])) {
        $colorClasses = 'bg-red-100 text-red-800';
    }
    return '<span class="' . $colorClasses . ' text-xs font-medium me-2 px-2.5 py-1 rounded-full">' . htmlspecialchars(ucfirst($status)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= $site_name ?? 'VIENNA' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --sidebar-bg: #111827;
            --main-bg: #F9FAFB;
            --card-bg: #FFFFFF;
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --accent-start: #6D28D9;
            --accent-end: #4F46E5;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--main-bg);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .sidebar {
            background-image: linear-gradient(180deg, var(--sidebar-bg) 0%, #1f2937 100%);
        }

        .sidebar-link {
            border-left: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: var(--accent-start);
        }

        .sidebar-link.active {
            background-image: linear-gradient(to right, var(--accent-start), var(--accent-end));
            color: white;
            border-left-color: #A78BFA;
        }

        .dropdown-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .form-card {
            background-color: var(--card-bg);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            padding: 1.5rem 2rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            display: block;
            width: 100%;
            padding: 0.65rem 1rem;
            background-color: #F3F4F6;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-end);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            background-color: white;
        }

        .drop-zone {
            border: 2px dashed #D1D5DB;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #F9FAFB;
        }

        .drop-zone.drag-over {
            border-color: var(--accent-start);
            background-color: #F5F3FF;
            transform: scale(1.02);
        }

        .drop-zone input[type="file"] {
            display: none;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .image-preview-item {
            position: relative;
            width: 7rem;
            height: 7rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background-color: #f3f4f6;
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-item .remove-btn {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            width: 1.5rem;
            height: 1.5rem;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .image-preview-item:hover .remove-btn {
            opacity: 1;
        }

        .choice-pill-input {
            display: none;
        }

        .choice-pill-label {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .choice-pill-input:checked+.choice-pill-label {
            background-image: linear-gradient(to right, var(--accent-start), var(--accent-end));
            color: white;
            border-color: var(--accent-end);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            color: white;
            background-image: linear-gradient(to right, var(--accent-start) 0%, var(--accent-end) 51%, var(--accent-start) 100%);
            background-size: 200% auto;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px -5px var(--accent-start);
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            background-color: #F9FAFB;
            padding: 1rem 1.5rem;
            text-align: left;
        }

        .content-table tbody tr {
            border-bottom: 1px solid #E5E7EB;
        }

        .content-table tbody tr:hover {
            background-color: #F9FAFB;
        }

        .content-table tbody td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-10 md:hidden" style="display: none;" x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-end="opacity-0"></div>

        <div class="relative min-h-screen md:flex">
            <!-- Sidebar -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="sidebar w-64 text-gray-300 fixed inset-y-0 left-0 transform md:relative md:translate-x-0 transition-transform duration-300 ease-in-out z-20">
                <!-- START: Updated Logo Header -->
                <div class="p-7 h-18 flex items-center justify-center border-b border-gray-900/20">
                     <div class="w-9 h-9 rounded-full flex items-center justify-center overflow-hidden bg-gray-100">
                        <img src="<?=$logo_directory?>" alt="Site Logo" class="w-full h-full object-cover">
                    </div>
                    <h1 class="text-2x1 font-bold tracking-wider text-white"><?=$site_name?></h1>
                </div>
                <nav class="mt-6 flex flex-col gap-2 px-4">
                    <a href="?page=dashboard" class="sidebar-link flex items-center px-4 py-3 rounded-lg"><i
                            class="fa-solid fa-fw fa-home w-5 h-5"></i><span class="ml-4">Dashboard</span></a>
                    <h3 class="px-4 mt-4 mb-1 text-xs font-semibold uppercase text-gray-500">Manage Store</h3>
                    <div x-data="{ open: false }"
                        x-init="open = ['add_product', 'manage_products'].includes('<?= $page ?>')">
                        <button @click="open = !open"
                            class="sidebar-link w-full flex items-center justify-between px-4 py-3 rounded-lg">
                            <span class="flex items-center"><i class="fa-solid fa-fw fa-box-archive w-5 h-5"></i><span
                                    class="ml-4">Products</span></span>
                            <i class="fa-solid fa-chevron-down w-4 h-4 transition-transform text-xs"
                                :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                            <a href="?page=add_product"
                                class="dropdown-link sidebar-link block px-4 py-2 rounded-lg text-sm">Add Product</a>
                            <a href="?page=manage_products"
                                class="dropdown-link sidebar-link block px-4 py-2 rounded-lg text-sm">Manage Products</a>
                        </div>
                    </div>
                    <a href="?page=colors" class="sidebar-link flex items-center px-4 py-3 rounded-lg"><i
                            class="fa-solid fa-fw fa-palette w-5 h-5"></i><span class="ml-4">Manage Colors</span></a>
                    <a href="?page=sizes" class="sidebar-link flex items-center px-4 py-3 rounded-lg"><i
                            class="fa-solid fa-fw fa-ruler-horizontal w-5 h-5"></i><span class="ml-4">Manage
                            Sizes</span></a>
                    <a href="/login"
                        class="sidebar-link flex items-center px-4 py-3 rounded-lg mt-auto absolute bottom-4 w-56"><i
                            class="fa-solid fa-fw fa-right-from-bracket w-5 h-5"></i><span class="ml-4">Logout</span></a>
                </nav>
            </aside>
            
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                <header class="bg-white shadow-sm h-20 flex justify-between items-center px-4 sm:px-8 border-b border-gray-200">
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden mr-4 text-gray-600 focus:outline-none">
                            <i class="fa-solid fa-bars w-6 h-6"></i>
                        </button>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 capitalize"><?= str_replace('_', ' ', $page) ?></h1>
                    </div>
                    <div><span class="text-sm text-gray-600">Welcome, Admin!</span></div>
                </header>

                <!-- Main Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-8">
                    <div class="animate-fadeInUp">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg"
                                role="alert">
                                <p class="font-bold">Success</p>
                                <p><?= $_SESSION['success_message']; ?></p>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg" role="alert">
                                <p class="font-bold">Error</p>
                                <p><?= $_SESSION['error_message']; ?></p>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <?php // =================================== FIX: PHP SWITCH STATEMENT BLOCK =================================== ?>
                        <?php switch ($page):
                            case 'dashboard': ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                                    <div
                                        class="bg-white p-6 rounded-xl shadow-md flex items-center gap-5 transform hover:-translate-y-1 transition-transform duration-300">
                                        <div class="bg-green-100 p-4 rounded-full"><i
                                                class="fa-solid fa-dollar-sign w-7 h-7 text-green-600"></i></div>
                                        <div>
                                            <p class="text-sm text-gray-500">Total Revenue</p>
                                            <p class="text-2xl font-bold text-gray-800">₦<?= number_format($totalRevenue, 2) ?></p>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-white p-6 rounded-xl shadow-md flex items-center gap-5 transform hover:-translate-y-1 transition-transform duration-300">
                                        <div class="bg-blue-100 p-4 rounded-full"><i
                                                class="fa-solid fa-shopping-cart w-7 h-7 text-blue-600"></i></div>
                                        <div>
                                            <p class="text-sm text-gray-500">Total Orders</p>
                                            <p class="text-2xl font-bold text-gray-800"><?= number_format($totalOrders) ?></p>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-white p-6 rounded-xl shadow-md flex items-center gap-5 transform hover:-translate-y-1 transition-transform duration-300">
                                        <div class="bg-purple-100 p-4 rounded-full"><i
                                                class="fa-solid fa-users w-7 h-7 text-purple-600"></i></div>
                                        <div>
                                            <p class="text-sm text-gray-500">New Customers (30d)</p>
                                            <p class="text-2xl font-bold text-gray-800"><?= number_format($newCustomers) ?></p>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-white p-6 rounded-xl shadow-md flex items-center gap-5 transform hover:-translate-y-1 transition-transform duration-300">
                                        <div class="bg-yellow-100 p-4 rounded-full"><i
                                                class="fa-solid fa-chart-line w-7 h-7 text-yellow-600"></i></div>
                                        <div>
                                            <p class="text-sm text-gray-500">Avg. Order Value</p>
                                            <p class="text-2xl font-bold text-gray-800">₦<?= number_format($averageOrderValue, 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8 form-card overflow-hidden p-0">
                                    <div class="p-6 border-b">
                                        <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="content-table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($recentOrders)): ?>
                                                <?php // If there are orders, loop through them and display each one ?>
                                                <?php foreach ($recentOrders as $order): ?>
                                                    <tr>
                                                        <td class="font-mono text-sm">#<?= $order['order_number'] ?></td>
                                                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                                                        <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
                                                        <td class="font-semibold">₦<?= number_format($order['grand_total'], 2) ?></td>
                                                        <td><?= getStatusBadge($order['order_status']) ?></td>
                                                        <td class="text-right">
                                                            <a href="/invoice?order_number=<?= $order['order_number'] ?>"
                                                            target="_blank"
                                                            class="font-medium text-indigo-600 hover:text-indigo-800">View Invoice</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php // If there are no orders, display a message row ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-gray-500">
                                                        There are no recent orders.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'add_product': ?>
                                <div class="form-card">
                                    <h2 class="text-2xl font-bold mb-6 text-gray-800">
                                        <?= $productToEdit ? 'Edit Product' : 'Add New Product' ?></h2>
                                    <form action="?page=add_product" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action"
                                            value="<?= $productToEdit ? 'edit_product' : 'add_product' ?>"><?php if ($productToEdit): ?><input
                                                type="hidden" name="id" value="<?= $productToEdit['id'] ?>"><?php endif; ?>
                                        <div class="space-y-8">
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6">
                                                <div><label class="form-label" for="name">Product Name</label><input id="name"
                                                        type="text" name="name"
                                                        value="<?= htmlspecialchars($productToEdit['name'] ?? '') ?>"
                                                        class="form-input" required></div>
                                                <div><label class="form-label" for="price">Price (₦)</label><input id="price"
                                                        type="number" name="price" step="0.01"
                                                        value="<?= htmlspecialchars($productToEdit['price'] ?? '') ?>"
                                                        class="form-input" required></div>
                                                <div><label class="form-label" for="stock">Stock Quantity</label><input id="stock"
                                                        type="number" name="stock_quantity"
                                                        value="<?= $productToEdit['stock_quantity'] ?? '0' ?>" class="form-input"
                                                        required></div>
                                                <div><label class="form-label" for="visibility">Visibility</label><select
                                                        id="visibility" name="visibility" class="form-select">
                                                        <option value="show" <?= ($productToEdit['visibility'] ?? 'show') == 'show' ? 'selected' : '' ?>>Show</option>
                                                        <option value="hide" <?= ($productToEdit['visibility'] ?? '') == 'hide' ? 'selected' : '' ?>>Hide</option>
                                                    </select></div>
                                                <div class="lg:col-span-2"><label class="form-label"
                                                        for="desc">Description</label><textarea id="desc" name="product_text"
                                                        rows="4"
                                                        class="form-textarea"><?= htmlspecialchars($productToEdit['product_text'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                                <div><label class="form-label">Main Image</label>
                                                    <div class="drop-zone" data-input-id="image_one"><i
                                                            class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
                                                        <p class="text-sm text-gray-500 mt-2">Drag & drop or click to upload</p>
                                                        <input type="file" name="image_one" id="image_one" accept="image/*">
                                                    </div>
                                                    <div class="image-preview-container" id="preview_image_one">
                                                        <?php if (!empty($productToEdit['image_one'])): ?><input type="hidden"
                                                                name="current_image_one" value="<?= $productToEdit['image_one'] ?>">
                                                            <div class="image-preview-item"><img
                                                                    src="/<?= htmlspecialchars($productToEdit['image_one']) ?>"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div><label class="form-label">Hover Image</label>
                                                    <div class="drop-zone" data-input-id="image_two"><i
                                                            class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
                                                        <p class="text-sm text-gray-500 mt-2">Drag & drop or click to upload</p>
                                                        <input type="file" name="image_two" id="image_two" accept="image/*">
                                                    </div>
                                                    <div class="image-preview-container" id="preview_image_two">
                                                        <?php if (!empty($productToEdit['image_two'])): ?><input type="hidden"
                                                                name="current_image_two" value="<?= $productToEdit['image_two'] ?>">
                                                            <div class="image-preview-item"><img
                                                                    src="../<?= htmlspecialchars($productToEdit['image_two']) ?>"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div><label class="form-label">Product Gallery (Add multiple images)</label>
                                                <div class="drop-zone" data-input-id="gallery_images"><i
                                                        class="fa-solid fa-images text-3xl text-gray-400"></i>
                                                    <p class="text-sm text-gray-500 mt-2">Drag & drop or click to upload</p><input
                                                        type="file" name="gallery_images[]" id="gallery_images" multiple
                                                        accept="image/*">
                                                </div>
                                                <div class="image-preview-container" id="preview_gallery_images">
                                                    <?php foreach ($productImages as $image): ?>
                                                        <div class="image-preview-item"><img
                                                                src="../<?= htmlspecialchars($image['image_path']) ?>">
                                                            <form method="POST" onsubmit="return confirm('Delete this image?');"><input
                                                                    type="hidden" name="action" value="delete"><input type="hidden"
                                                                    name="table" value="product_images"><input type="hidden" name="id"
                                                                    value="<?= $image['id'] ?>"><button type="submit" class="remove-btn"
                                                                    title="Delete image"><i
                                                                        class="fa-solid fa-times text-xs"></i></button></form>
                                                        </div><?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div><label class="form-label">Available Colors</label>
                                                <div class="flex flex-wrap gap-4 mt-4"><?php foreach ($colors as $color): ?>
                                                        <div class="choice-pill-wrapper"><input type="checkbox" name="colors[]"
                                                                id="color_<?= $color['id'] ?>" value="<?= $color['id'] ?>"
                                                                class="choice-pill-input" <?= in_array($color['id'], $productColors) ? 'checked' : '' ?>><label for="color_<?= $color['id'] ?>"
                                                                class="choice-pill-label"><span
                                                                    class="w-6 h-6 rounded-full mr-2 border border-gray-300"
                                                                    style="background-color:<?= $color['hex_code'] ?>"></span><span><?= $color['name'] ?></span></label>
                                                        </div><?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div><label class="form-label">Available Sizes</label>
                                                <div class="flex flex-wrap gap-3 mt-2"><?php foreach ($sizes as $size): ?>
                                                        <div class="choice-pill-wrapper"><input type="checkbox" name="sizes[]"
                                                                id="size_<?= $size['id'] ?>" value="<?= $size['id'] ?>"
                                                                class="choice-pill-input" <?= in_array($size['id'], $productSizes) ? 'checked' : '' ?>><label for="size_<?= $size['id'] ?>"
                                                                class="choice-pill-label"><span><?= $size['name'] ?></span></label>
                                                        </div><?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-8 flex justify-end"><button type="submit"
                                                class="btn btn-primary"><?= $productToEdit ? 'Update Product' : 'Save Product' ?></button>
                                        </div>
                                    </form>
                                </div>
                                <?php break; ?>

                            <?php case 'manage_products': ?>
                                <div class="form-card overflow-hidden p-0">
                                    <div class="p-6 border-b flex justify-between items-center">
                                        <h2 class="text-xl font-semibold text-gray-800">All Products</h2><a href="?page=add_product"
                                            class="btn btn-primary">Add New Product</a>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="content-table">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Name</th>
                                                    <th>Price</th>
                                                    <th>Stock</th>
                                                    <th>Status</th>
                                                    <th class="text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!empty($products)): ?>
                                                <?php // If products exist, loop through them ?>
                                                <?php foreach ($products as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($product['image_one'])): ?>
                                                                <img src="/<?= htmlspecialchars($product['image_one']) ?>"
                                                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                                                    class="h-14 w-14 object-cover rounded-lg">
                                                            <?php else: ?>
                                                                <div class="h-14 w-14 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
                                                                    <i class="fa-solid fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="font-medium"><?= htmlspecialchars($product['name']) ?></td>
                                                        <td>₦<?= number_format($product['price']) ?></td>
                                                        <td><?= $product['stock_quantity'] ?></td>
                                                        <td><?= getStatusBadge($product['visibility']) ?></td>
                                                        <td class="text-right">
                                                            <div class="flex gap-4 justify-end items-center h-full">
                                                                <a href="?page=add_product&edit_id=<?= $product['id'] ?>"
                                                                class="font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                                                <i class="fa-solid fa-pen-to-square w-4 h-4"></i>Edit
                                                                </a>
                                                                <form action="?page=manage_products" method="POST" onsubmit="return confirm('Are you sure?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="table" value="panel_products">
                                                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                                    <button type="submit" class="text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                                                                        <i class="fa-solid fa-trash-can w-4 h-4"></i>Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php // If there are no products, display this message row ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-gray-500">
                                                        No products have been added yet.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php break; ?>

                            <?php case 'colors':
                            case 'sizes':
                                $is_colors_page = ($page === 'colors');
                                $title_singular = $is_colors_page ? 'Color' : 'Size';
                                $title_plural = $is_colors_page ? 'Colors' : 'Sizes';
                                $items = $is_colors_page ? $colors : $sizes;
                                $itemToEdit = $is_colors_page ? $colorToEdit : $sizeToEdit; ?>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    <div class="lg:col-span-1">
                                        <div class="form-card">
                                            <h2 class="text-2xl font-bold mb-6 text-gray-800">
                                                <?= $itemToEdit ? "Edit $title_singular" : "Add New $title_singular" ?></h2>
                                            <form action="?page=<?= $page ?>" method="POST"><input type="hidden" name="action"
                                                    value="<?= $itemToEdit ? "edit_" . strtolower($title_singular) : "add_" . strtolower($title_singular) ?>"><?php if ($itemToEdit): ?><input
                                                        type="hidden" name="id" value="<?= $itemToEdit['id'] ?>"><?php endif; ?>
                                                <div><label class="form-label" for="name"><?= $title_singular ?> Name</label><input
                                                        id="name" type="text" name="name"
                                                        value="<?= htmlspecialchars($itemToEdit['name'] ?? '') ?>"
                                                        class="form-input" required></div><?php if ($is_colors_page): ?>
                                                    <div class="mt-4"><label class="form-label" for="hex_code">Hex Code</label><input
                                                            type="color" name="hex_code"
                                                            value="<?= $itemToEdit['hex_code'] ?? '#000000' ?>"
                                                            class="w-full h-12 p-1 rounded-md border-gray-300 cursor-pointer"></div>
                                                <?php endif; ?>
                                                <div class="mt-8"><button type="submit"
                                                        class="btn btn-primary w-full"><?= $itemToEdit ? "Update $title_singular" : "Add $title_singular" ?></button><?php if ($itemToEdit): ?><a
                                                            href="?page=<?= $page ?>"
                                                            class="block text-center mt-3 text-sm text-gray-600 hover:text-gray-900">Cancel
                                                            Edit</a><?php endif; ?></div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="form-card">
                                            <h2 class="text-2xl font-bold mb-6 text-gray-800">Available <?= $title_plural ?></h2>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                                                <?php foreach ($items as $item): ?>
                                                    <div
                                                        class="border p-4 rounded-lg flex items-center justify-between gap-3 transform hover:-translate-y-1 transition-transform duration-300 bg-white">
                                                        <div class="flex items-center gap-3 font-medium text-gray-800">
                                                            <?php if ($is_colors_page): ?><span class="w-6 h-6 rounded-full border"
                                                                    style="background-color:<?= $item['hex_code'] ?>"></span><?php endif; ?><span><?= htmlspecialchars($item['name']) ?></span>
                                                        </div>
                                                        <div class="flex gap-2 text-xs"><a
                                                                href="?page=<?= $page ?>&edit_id=<?= $item['id'] ?>"
                                                                class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                                            <form action="?page=<?= $page ?>" method="POST"
                                                                onsubmit="return confirm('Are you sure?');"><input type="hidden"
                                                                    name="action" value="delete"><input type="hidden" name="table"
                                                                    value="<?= strtolower($title_plural) ?>"><input type="hidden"
                                                                    name="id" value="<?= $item['id'] ?>"><button type="submit"
                                                                    class="font-medium text-red-500 hover:text-red-700">Del</button>
                                                            </form>
                                                        </div>
                                                    </div><?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php break; ?>

                        <?php endswitch; ?>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Active Sidebar Link Logic
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            const activeLink = document.querySelector(`.sidebar-link[href*="?page=${currentPage}"], .dropdown-link[href*="?page=${currentPage}"]`);
            if (activeLink) { activeLink.classList.add('active'); }

            // Drag & Drop and Image Preview Script
            function setupDropZone(dropZone) {
                const input = document.getElementById(dropZone.dataset.inputId);
                if (!input) return;
                const previewContainer = document.getElementById('preview_' + input.id);

                dropZone.addEventListener('click', () => input.click());
                input.addEventListener('change', () => handleFiles(input.files));
                dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
                ['dragleave', 'dragend'].forEach(type => { dropZone.addEventListener(type, () => dropZone.classList.remove('drag-over')); });
                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; handleFiles(e.dataTransfer.files); }
                });

                function handleFiles(files) {
                    if (!input.multiple) { previewContainer.innerHTML = ''; }
                    for (const file of files) {
                        if (!file.type.startsWith('image/')) continue;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'image-preview-item';
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            previewItem.appendChild(img);
                            previewContainer.appendChild(previewItem);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
            document.querySelectorAll('.drop-zone').forEach(setupDropZone);
        });
    </script>

</body>

</html>