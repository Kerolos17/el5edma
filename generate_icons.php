<?php

$srcFile = 'C:\Users\kerol\.gemini\antigravity\brain\23ec4a6c-4a45-450e-879b-e6a2233e1291\pwa_app_icon_1774094728225.png';
$destDir = __DIR__ . '/public/icons';

if (!is_dir($destDir)) {
    mkdir($destDir, 0777, true);
}

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$srcImage = imagecreatefrompng($srcFile);

if (!$srcImage) {
    die("Failed to open source image.\n");
}

$origWidth = imagesx($srcImage);
$origHeight = imagesy($srcImage);

foreach ($sizes as $size) {
    $destImage = imagecreatetruecolor($size, $size);
    // preserve transparency
    imagealphablending($destImage, false);
    imagesavealpha($destImage, true);
    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
    imagefilledrectangle($destImage, 0, 0, $size, $size, $transparent);
    
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $size, $size, $origWidth, $origHeight);
    
    $destPath = $destDir . "/icon-{$size}x{$size}.png";
    imagepng($destImage, $destPath);
    imagedestroy($destImage);
    echo "Created {$destPath}\n";
}

// Apple touch icon (180x180)
$destImage = imagecreatetruecolor(180, 180);
imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, 180, 180, $origWidth, $origHeight);
imagepng($destImage, $destDir . "/apple-touch-icon.png");
imagedestroy($destImage);
echo "Created Apple Touch Icon\n";

imagedestroy($srcImage);
echo "Done resizing.\n";
