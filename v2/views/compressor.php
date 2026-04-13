<?php
// Autoload Composer dependencies
require 'vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;

try {
    // SQL to select all product image URLs from your table
    $sql = "SELECT id, image_one, image_two FROM panel_products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Starting image compression...\n";

    $imagesToProcess = [];
    foreach ($products as $product) {
        if (!empty($product['image_one'])) {
            $imagesToProcess[] = $product['image_one'];
        }
        if (!empty($product['image_two'])) {
            $imagesToProcess[] = $product['image_two'];
        }
    }

    // Set the base directory for images directly
    // This is the correct way to get the path to your 'viennabyTNQ' folder
    // and then to the 'www/images' folder inside it.
    $imageDirectory = 'C:/wamp64/www/viennabyTNQ/www/images/';
    
    foreach ($imagesToProcess as $imagePath) {
        // Use basename() to get just the filename from the database path.
        $filename = basename($imagePath);
        $fullPath = $imageDirectory . $filename;

        // Check if the image file exists
        if (file_exists($fullPath)) {
            try {
                $img = Image::make($fullPath);
                $img->encode(null, 80); // Reduce quality to 80%
                $img->save($fullPath);

                echo "Compressed: {$fullPath}\n";

            } catch (Exception $e) {
                echo "Error compressing {$fullPath}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Skipped (not found): {$fullPath}\n";
        }
    }

    echo "\nImage compression complete.\n";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>