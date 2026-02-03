<?php
// Include your DB connection and any necessary functions.
// NOTE: Ensure your database connection ($conn) is properly set up here.
// require_once 'path/to/your/db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Get parameters from the request
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

$perPage = 8;
$offset = ($page - 1) * $perPage;

// Build the main query
$where = "WHERE visibility = 'show'";
$params = [];
if ($search !== '') {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
}
// Sorting is now based on the base price column only
$order = "ORDER BY id DESC";
if ($sort === 'price-asc') $order = "ORDER BY price ASC";
if ($sort === 'price-desc') $order = "ORDER BY price DESC";

// --- Assuming $conn (PDO connection) is available from db_connection.php ---

// Get total count for pagination
// Use price column for sorting logic within the database
$countStmt = $conn->prepare("SELECT COUNT(*) FROM panel_products $where");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get the paginated products
$query = "SELECT * FROM panel_products $where $order LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the HTML string
$productsHtml = '';
if ($products) {
    foreach ($products as $product) {
        // --- Preparation Logic ---
        $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);

        // Price variants and colors are removed, so the display price is just the base price
        $displayPrice = $product['price'];
        
        // --- Card HTML Building with Conditional Logic ---
        // data-price is now the base price
        $productSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product['name']), '-'));
        $productsHtml .= '<div class="product-card" data-price="'.htmlspecialchars($displayPrice).'">';
        
        $openingTag = $isSoldOut 
            ? '<div class="group block cursor-not-allowed">' 
            : '<a href="/product/'.urlencode($productSlug).'?id='.urlencode($product['id']).'" class="group block">';
        $closingTag = $isSoldOut ? '</div>' : '</a>';

        $productsHtml .= $openingTag;
        
        // Image container with skeleton loader
        $productsHtml .= '<div class="relative w-full overflow-hidden"><div class="aspect-[9/16] skeleton-container">';
        $img1_classes = $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-0';
        $img2_classes = $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100';
        $productsHtml .= '<img data-src="'.htmlspecialchars($product['image_one']).'" alt="'.htmlspecialchars($product['name']).'" class="lazy-img product-main-img absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img1_classes.'">';
        $productsHtml .= '<img data-src="'.htmlspecialchars($product['image_two']).'" alt="'.htmlspecialchars($product['name']).' Hover" class="lazy-img product-hover-img absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img2_classes.'">';
        $productsHtml .= '</div>';

        if ($isSoldOut) {
            $productsHtml .= '<div class="absolute inset-0 bg-black/50 flex items-center justify-center">';
            $productsHtml .= '<span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>';
            $productsHtml .= '</div>';
        }
        $productsHtml .= '</div>'; // End of relative image container

        // Product info
        $productsHtml .= '<div class="pt-4 text-center">';

        // Display product name
        $productsHtml .= '<h3 class="text-base font-medium text-brand-text mb-2">'.htmlspecialchars($product['name']).'</h3>';
        
        // Price display
        $productsHtml .= '<div class="text-sm text-brand-gray space-y-1">';
        
        // Single price display (since variants are removed)
        $productsHtml .= '<p class="price-display" data-price-ngn="' . htmlspecialchars($product['price']) . '">₦' . number_format($product['price'], 2) . '</p>';
        
        $productsHtml .= '</div>';
        
        $productsHtml .= '</div>'; // End of text-center div
        $productsHtml .= $closingTag; // Use the closing tag (</div> or <a>)
        $productsHtml .= '</div>'; // End of product-card
    }
} else {
    $productsHtml = '<p class="col-span-full text-center text-brand-gray py-8">No products found matching your search.</p>';
}

// Output final JSON response
$response = [
    'productsHtml' => $productsHtml,
    'currentPage' => $page,
    'totalPages' => $totalPages
];
echo json_encode($response);
exit;