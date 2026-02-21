<?php
$img = imagecreatetruecolor(3000, 3000);
$red = imagecolorallocate($img, 255, 0, 0);
imagefilledrectangle($img, 0, 0, 3000, 3000, $red);
imagejpeg($img, __DIR__ . '/test_large_image.jpg', 100);
imagedestroy($img);
echo 'Created ' . round(filesize(__DIR__ . '/test_large_image.jpg')/1024, 2) . ' KB image.';
?>
