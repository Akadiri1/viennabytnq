<?php
// Since this is an API endpoint, it's good practice to include your DB connection and any necessary functions.
// require_once 'path/to/your/db_connection.php';

// Your header logic is fine, but can be simplified.
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';

$perPage = 8;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE visibility = 'show'";
$params = [];
if ($search !== '') {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
}
$order = "ORDER BY id DESC";
if ($sort === 'price-asc') $order = "ORDER BY price ASC";
if ($sort === 'price-desc') $order = "ORDER BY price DESC";

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM panel_products $where");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$query = "SELECT * FROM panel_products $where $order LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build HTML
$productsHtml = '';
if ($products) {
    foreach ($products as $product) {
        // **FIX 1**: Check the stock quantity for each product.
        $isSoldOut = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0);

        // Colors
        $colorStmt = $conn->prepare("SELECT c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
        $colorStmt->execute([$product['id']]);
        $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);
        // Sizes
        $sizeStmt = $conn->prepare("SELECT s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ?");
        $sizeStmt->execute([$product['id']]);
        $sizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // --- Card HTML Building with Conditional Logic ---
        $productsHtml .= '<div class="product-card" data-price="₦'.htmlspecialchars($product['price']).'">';
        
        // **FIX 2**: Conditionally create the link. If sold out, it's just a div. Otherwise, it's an `<a>` tag.
        // We use a variable for the opening tag to keep the code clean.
        $openingTag = $isSoldOut 
            ? '<div class="group block cursor-not-allowed">' 
            : '<a href="shopdetail?id='.urlencode($product['id']).'" class="group block">';
        $closingTag = $isSoldOut ? '</div>' : '</a>';

        $productsHtml .= $openingTag;
        
        // Image container
        $productsHtml .= '<div class="relative w-full overflow-hidden"><div class="aspect-[9/16]">';
        // **FIX 3**: Disable hover effect if sold out by changing the class logic.
        $img1_classes = $isSoldOut ? 'opacity-100' : 'opacity-100 group-hover:opacity-0';
        $img2_classes = $isSoldOut ? 'opacity-0' : 'opacity-0 group-hover:opacity-100';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_one']).'" alt="'.htmlspecialchars($product['name']).'" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img1_classes.'">';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_two']).'" alt="'.htmlspecialchars($product['name']).' Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 '.$img2_classes.'">';
        $productsHtml .= '</div>';

        // **FIX 4**: Add the "Sold Out" overlay badge if the item is sold out.
        if ($isSoldOut) {
            $productsHtml .= '<div class="absolute inset-0 bg-black/50 flex items-center justify-center">';
            $productsHtml .= '<span class="bg-white text-brand-text text-xs font-semibold tracking-wider uppercase px-4 py-2">Sold Out</span>';
            $productsHtml .= '</div>';
        }
        $productsHtml .= '</div>'; // End of relative image container

        // Product info
        $productsHtml .= '<div class="pt-4 text-center">';
        $productsHtml .= '<h3 class="text-base font-medium text-brand-text">'.htmlspecialchars($product['name']).'</h3>';
        $productsHtml .= '<p class="mt-1 text-sm text-brand-gray">₦'.number_format($product['price'], 2).'</p>';
        
        // **FIX 5**: Conditionally show color/size swatches.
        if (!$isSoldOut) {
            $productsHtml .= '<div class="flex flex-col items-center mt-2">';
            $productsHtml .= '<div class="flex justify-center space-x-2 mb-2">';
            foreach ($colors as $color) {
                $productsHtml .= '<span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: '.htmlspecialchars($color).';"></span>';
            }
            $productsHtml .= '</div><div class="flex justify-center space-x-2">';
            foreach ($sizes as $size) {
                $productsHtml .= '<span class="px-2 py-1 rounded text-xs text-brand-text">'.htmlspecialchars($size).'</span>';
            }
            $productsHtml .= '</div></div>';
        }
        
        $productsHtml .= '</div>'; // End of text-center div
        $productsHtml .= $closingTag; // Use the closing tag (</div> or </a>)
        $productsHtml .= '</div>'; // End of product-card
    }
} else {
    $productsHtml = '<p class="col-span-full text-center text-brand-gray py-8">No products found matching your search.</p>';
}

// Output JSON
$response = [
    'productsHtml' => $productsHtml,
    'currentPage' => $page,
    'totalPages' => $totalPages
];
echo json_encode($response);
exit;