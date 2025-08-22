<?php
// Include your DB connection and any necessary functions.
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
$order = "ORDER BY id DESC";
if ($sort === 'price-asc') $order = "ORDER BY price ASC";
if ($sort === 'price-desc') $order = "ORDER BY price DESC";

// Get total count for pagination
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
        $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);

        // Fetch colors (this remains the same)
        $colorStmt = $conn->prepare("SELECT c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
        $colorStmt->execute([$product['id']]);
        $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch price variants
        $variantStmt = $conn->prepare("SELECT variant_name, price FROM product_price_variants WHERE product_id = ? ORDER BY price ASC");
        $variantStmt->execute([$product['id']]);
        $priceVariants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

        // Determine the display price for sorting purposes
        $displayPrice = !empty($priceVariants) ? $priceVariants[0]['price'] : $product['price'];
        
        // --- Card HTML Building with Conditional Logic ---
        $productsHtml .= '<div class="product-card" data-price="'.htmlspecialchars($displayPrice).'">';
        
        $openingTag = $isSoldOut 
            ? '<div class="group block cursor-not-allowed">' 
            : '<a href="shopdetail?id='.urlencode($product['id']).'" class="group block">';
        $closingTag = $isSoldOut ? '</div>' : '</a>';

        $productsHtml .= $openingTag;
        
        // Image container
        $productsHtml .= '<div class="relative w-full overflow-hidden"><div class="aspect-[9/16]">';
        $img1_classes = $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0';
        $img2_classes = $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_one']).'" alt="'.htmlspecialchars($product['name']).'" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img1_classes.'">';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_two']).'" alt="'.htmlspecialchars($product['name']).' Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img2_classes.'">';
        $productsHtml .= '</div>';

        if ($isSoldOut) {
            $productsHtml .= '<div class="absolute inset-0 bg-black/50 flex items-center justify-center">';
            $productsHtml .= '<span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>';
            $productsHtml .= '</div>';
        }
        $productsHtml .= '</div>'; // End of relative image container

        // Product info
        $productsHtml .= '<div class="pt-4 text-center">';
        $productsHtml .= '<h3 class="text-base font-medium text-brand-text mb-2">'.htmlspecialchars($product['name']).'</h3>';
        
        // --- THE FIX: ADDED class="price-display" and data-price-ngn="..." ---
        $productsHtml .= '<div class="text-sm text-brand-gray space-y-1">';
        if (!empty($priceVariants)) {
            foreach($priceVariants as $variant) {
                $productsHtml .= '<p class="price-display" data-price-ngn="' . htmlspecialchars($variant['price']) . '">' . htmlspecialchars($variant['variant_name']) . ' - ₦' . number_format($variant['price'], 2) . '</p>';
            }
        } else {
            $productsHtml .= '<p class="price-display" data-price-ngn="' . htmlspecialchars($product['price']) . '">₦' . number_format($product['price'], 2) . '</p>';
        }
        $productsHtml .= '</div>';
        // --- END OF FIX ---
        
        if (!$isSoldOut && !empty($colors)) {
            $productsHtml .= '<div class="flex justify-center space-x-2 mt-3">';
            foreach ($colors as $color) {
                $productsHtml .= '<span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: '.htmlspecialchars($color).';"></span>';
            }
            $productsHtml .= '</div>';
        }
        
        $productsHtml .= '</div>'; // End of text-center div
        $productsHtml .= $closingTag; // Use the closing tag (</div> or </a>)
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