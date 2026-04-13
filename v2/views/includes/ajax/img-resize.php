<?php
/**
 * On-the-fly Image Resizer with Caching
 * 
 * Usage: /img-resize?src=uploads/filename.jpg&w=400
 * 
 * Generates a resized version, caches it in uploads/thumbs/, 
 * and serves it. Subsequent requests serve the cached version directly.
 */

// --- CONFIG ---
$BASE_DIR = realpath(dirname(__DIR__) . '/../www/') . '/';
$CACHE_DIR = $BASE_DIR . 'uploads/thumbs/';
$ALLOWED_WIDTHS = [200, 400, 600, 800]; // Only allow specific widths to prevent abuse
$QUALITY = 80; // JPEG quality (0-100)
$WEBP_QUALITY = 75;

// --- INPUT VALIDATION ---
$src = $_GET['src'] ?? '';
$width = isset($_GET['w']) ? (int)$_GET['w'] : 400;

// Snap to nearest allowed width
$width = $ALLOWED_WIDTHS[0];
foreach ($ALLOWED_WIDTHS as $aw) {
    if ($aw >= (int)($_GET['w'] ?? 400)) { $width = $aw; break; }
    $width = $aw;
}

if (empty($src)) {
    http_response_code(400);
    exit('Missing src parameter');
}

// Security: prevent directory traversal
$src = str_replace(['..', '\\'], ['', '/'], $src);
$srcPath = $BASE_DIR . $src;

if (!file_exists($srcPath) || !is_file($srcPath)) {
    http_response_code(404);
    exit('Image not found');
}

// Only allow image files
$mime = @mime_content_type($srcPath);
if (!$mime || !str_starts_with($mime, 'image/')) {
    http_response_code(400);
    exit('Not an image');
}

// --- CACHE CHECK ---
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0775, true);
}

$fileInfo = pathinfo($src);
$cacheKey = md5($src . $width) . '_' . $width;

// Prefer WebP if browser supports it
$supportsWebp = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'image/webp');
$cacheExt = $supportsWebp ? '.webp' : '.jpg';
$cachePath = $CACHE_DIR . $cacheKey . $cacheExt;

// Serve cached version if it exists and is newer than the original
if (file_exists($cachePath) && filemtime($cachePath) >= filemtime($srcPath)) {
    $cacheContentType = $supportsWebp ? 'image/webp' : 'image/jpeg';
    header('Content-Type: ' . $cacheContentType);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($cachePath));
    readfile($cachePath);
    exit;
}

// --- RESIZE ---
if (!function_exists('imagecreatefromjpeg')) {
    // GD not available, serve original
    header('Content-Type: ' . $mime);
    readfile($srcPath);
    exit;
}

// Load source image based on type
$sourceImage = null;
switch ($mime) {
    case 'image/jpeg':
        $sourceImage = @imagecreatefromjpeg($srcPath);
        break;
    case 'image/png':
        $sourceImage = @imagecreatefrompng($srcPath);
        break;
    case 'image/webp':
        $sourceImage = @imagecreatefromwebp($srcPath);
        break;
    case 'image/gif':
        $sourceImage = @imagecreatefromgif($srcPath);
        break;
    default:
        // Unsupported format, serve original
        header('Content-Type: ' . $mime);
        readfile($srcPath);
        exit;
}

if (!$sourceImage) {
    // Failed to load, serve original
    header('Content-Type: ' . $mime);
    readfile($srcPath);
    exit;
}

$origWidth = imagesx($sourceImage);
$origHeight = imagesy($sourceImage);

// Only resize if original is larger than target width
if ($origWidth <= $width) {
    // No resize needed, but still compress/convert to webp
    $newWidth = $origWidth;
    $newHeight = $origHeight;
} else {
    $ratio = $origHeight / $origWidth;
    $newWidth = $width;
    $newHeight = (int)round($width * $ratio);
}

$resized = imagecreatetruecolor($newWidth, $newHeight);

// Preserve transparency for PNG
if ($mime === 'image/png') {
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
}

imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

// Save to cache
if ($supportsWebp && function_exists('imagewebp')) {
    imagewebp($resized, $cachePath, $WEBP_QUALITY);
    header('Content-Type: image/webp');
} else {
    imagejpeg($resized, $cachePath, $QUALITY);
    header('Content-Type: image/jpeg');
}

imagedestroy($sourceImage);
imagedestroy($resized);

// Serve the cached file
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($cachePath));
readfile($cachePath);
exit;
?>
