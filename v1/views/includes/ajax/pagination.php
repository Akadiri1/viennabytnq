<?php
$headerSent = headers_sent();
if (!$headerSent) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}
// ...existing code...
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
        // Colors
        $colorStmt = $conn->prepare("SELECT c.hex_code FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = ?");
        $colorStmt->execute([$product['id']]);
        $colors = $colorStmt->fetchAll(PDO::FETCH_COLUMN);
        // Sizes
        $sizeStmt = $conn->prepare("SELECT s.name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ?");
        $sizeStmt->execute([$product['id']]);
        $sizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);
        // Card HTML
        $productsHtml .= '<div class="product-card" data-price="₦'.htmlspecialchars($product['price']).'">';
        $productsHtml .= '<a href="shopdetail?id='.urlencode($product['id']).'&name='.urlencode($product['name']).'&t='.time().'" class="group block">';
        $productsHtml .= '<div class="relative w-full overflow-hidden"><div class="aspect-[9/16]">';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_one']).'" alt="'.htmlspecialchars($product['name']).'" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-100 group-hover:opacity-0">';
        $productsHtml .= '<img src="'.htmlspecialchars($product['image_two']).'" alt="'.htmlspecialchars($product['name']).' Hover" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 opacity-0 group-hover:opacity-100">';
        $productsHtml .= '</div></div>';
        $productsHtml .= '<div class="pt-4 text-center">';
        $productsHtml .= '<h3 class="text-base font-medium text-brand-text">'.htmlspecialchars($product['name']).'</h3>';
        $productsHtml .= '<p class="mt-1 text-sm text-brand-gray">₦'.number_format($product['price'], 2).'</p>';
        $productsHtml .= '<div class="flex flex-col items-center mt-2">';
        $productsHtml .= '<div class="flex justify-center space-x-2 mb-2">';
        foreach ($colors as $color) {
            $productsHtml .= '<span class="block w-4 h-4 rounded-full border border-gray-300" style="background-color: '.htmlspecialchars($color).';"></span>';
        }
        $productsHtml .= '</div><div class="flex justify-center space-x-2">';
        foreach ($sizes as $size) {
            $productsHtml .= '<span class="px-2 py-1 rounded text-xs text-brand-text">'.htmlspecialchars($size).'</span>';
        }
        $productsHtml .= '</div></div></div></a></div>';
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
?>