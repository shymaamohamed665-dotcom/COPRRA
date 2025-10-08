<?php

declare(strict_types=1);

/**
 * Generate PWA Icons Script
 * This script creates placeholder PWA icons for the COPRRA project.
 */
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$screenshots = [
    'mobile' => ['width' => 390, 'height' => 844],
    'desktop' => ['width' => 1280, 'height' => 720],
];

echo "Generating PWA icons and screenshots...\n";

// Create a simple colored square as placeholder
foreach ($sizes as $size) {
    $image = imagecreate($size, $size);
    $bgColor = imagecolorallocate($image, 59, 130, 246); // Blue color from theme
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text

    // Add "C" for COPRRA
    $fontSize = $size / 3;
    $text = 'C';
    $textBox = imagettfbbox($fontSize, 0, 'arial.ttf', $text);
    $textWidth = $textBox[4] - $textBox[0];
    $textHeight = $textBox[1] - $textBox[5];
    $x = ($size - $textWidth) / 2;
    $y = ($size + $textHeight) / 2;

    imagettftext($image, $fontSize, 0, $x, $y, $textColor, 'arial.ttf', $text);

    $filename = "public/icon-{$size}x{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Created: {$filename}\n";
}

// Create screenshots
foreach ($screenshots as $type => $dimensions) {
    $image = imagecreate($dimensions['width'], $dimensions['height']);
    $bgColor = imagecolorallocate($image, 255, 255, 255); // White background
    $textColor = imagecolorallocate($image, 0, 0, 0); // Black text

    // Add title
    $title = 'COPRRA - Price Comparison Platform';
    $fontSize = 24;
    imagestring($image, 5, 50, 50, $title, $textColor);

    // Add subtitle
    $subtitle = 'Screenshot - '.ucfirst($type).' View';
    imagestring($image, 3, 50, 100, $subtitle, $textColor);

    $filename = "public/screenshot-{$type}.png";
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Created: {$filename}\n";
}

echo "PWA assets generation completed!\n";
echo "Note: Replace these placeholder files with actual design assets.\n";
