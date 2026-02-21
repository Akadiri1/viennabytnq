<?php
// Retroactive Image Compression Script for Existing Images
// This script compresses all existing large images in the uploads directory

// --- SETTINGS ---
// Paths to process (relative to script location)
$directories_to_scan = [
    __DIR__ . '/uploads/',
    __DIR__ . '/images/'
];

// Compression Settings
$max_width = 1200; // Resize to this width if larger
$quality = 75;     // JPEG quality (1-100)
$max_file_size_kb = 300; // Only compress images larger than this (e.g., 300KB)

// --- EXECUTION ---
echo "<pre>";
echo "<h2>Vienna eCommerce Image Optimizer</h2>";
echo "Starting scan of existing images...\n\n";

$total_processed = 0;
$total_saved_bytes = 0;
$supported_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG];

foreach ($directories_to_scan as $dir) {
    if (!is_dir($dir)) {
        echo "Directory not found: $dir\n";
        continue;
    }

    echo "<b>Scanning: $dir</b>\n";
    
    // Get all files
    $files = glob($dir . '*.*');
    
    foreach ($files as $file) {
        // Skip obvious non-images
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

        // Skip small files
        $original_size = filesize($file);
        if ($original_size < ($max_file_size_kb * 1024)) continue;

        // Verify it's actually an image we can process
        $image_info = @getimagesize($file);
        if (!$image_info || !in_array($image_info[2], $supported_types)) continue;

        $width = $image_info[0];
        $height = $image_info[1];
        $type = $image_info[2];

        try {
            // Load the image into memory
            if ($type === IMAGETYPE_JPEG) {
                $source_img = @imagecreatefromjpeg($file);
            } elseif ($type === IMAGETYPE_PNG) {
                $source_img = @imagecreatefrompng($file);
            }
            
            if (!$source_img) {
                echo "Failed to read image data: " . basename($file) . "\n";
                continue;
            }

            // Calculate new dimensions
            $new_width = $width;
            $new_height = $height;
            
            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = (int)(($height / $width) * $max_width);
            }

            // Create a new blank optimized image
            $optimized_img = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG if converting to PNG (we are forcing JPEG here for size)
            // But we fill background with white instead of black for transparent PNGs turning to JPEG
            if ($type === IMAGETYPE_PNG) {
                $white = imagecolorallocate($optimized_img, 255, 255, 255);
                imagefill($optimized_img, 0, 0, $white);
            }

            // Resize and Resample
            imagecopyresampled($optimized_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            // Construct new filename (We strongly prefer .jpg for web speed)
            // Even if the original was .png, we overwrite the actual file data with JPEG format
            // but keep the extension so we don't break the database paths!
            $final_path = $file; 
            
            // Save over the original file
            if (imagejpeg($optimized_img, $final_path, $quality)) {
                
                // Clear stat cache so filesize() is accurate
                clearstatcache(true, $final_path);
                
                $new_size = filesize($final_path);
                $saved = $original_size - $new_size;
                $total_saved_bytes += $saved;
                
                $original_kb = round($original_size / 1024);
                $new_kb = round($new_size / 1024);
                $pct = round(($saved / $original_size) * 100);
                
                echo "Optimized: " . basename($file) . " | {$original_kb}KB -> <b>{$new_kb}KB</b> (-{$pct}%)\n";
                $total_processed++;
            }

            // Free up server memory
            imagedestroy($source_img);
            imagedestroy($optimized_img);

        } catch (Exception $e) {
             echo "Error compressing " . basename($file) . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n<b>--- FINISHED ---</b>\n";
echo "Total Images Processed: $total_processed\n";
echo "Total Space Saved: " . round($total_saved_bytes / 1024 / 1024, 2) . " MB\n";
echo "</pre>";
?>
