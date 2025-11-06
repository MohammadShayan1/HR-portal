<?php
/**
 * Theme Color Extractor
 * Extracts dominant colors from uploaded logo
 */

/**
 * Extract dominant colors from an image
 * @param string $image_path Path to the image file
 * @param int $num_colors Number of colors to extract
 * @return array Array of hex color codes
 */
function extract_colors_from_image($image_path, $num_colors = 3) {
    if (!file_exists($image_path)) {
        return ['#0d6efd', '#6c757d', '#0dcaf0']; // Default Bootstrap colors
    }
    
    // Get image info
    $info = getimagesize($image_path);
    if (!$info) {
        return ['#0d6efd', '#6c757d', '#0dcaf0'];
    }
    
    // Create image resource based on type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($image_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($image_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($image_path);
            break;
        default:
            return ['#0d6efd', '#6c757d', '#0dcaf0'];
    }
    
    if (!$image) {
        return ['#0d6efd', '#6c757d', '#0dcaf0'];
    }
    
    // Resize image for faster processing
    $thumb_width = 150;
    $thumb_height = 150;
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, imagesx($image), imagesy($image));
    
    // Extract colors
    $colors = [];
    for ($x = 0; $x < $thumb_width; $x++) {
        for ($y = 0; $y < $thumb_height; $y++) {
            $rgb = imagecolorat($thumb, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Skip very light colors (close to white)
            if ($r > 240 && $g > 240 && $b > 240) continue;
            
            // Skip very dark colors (close to black)
            if ($r < 15 && $g < 15 && $b < 15) continue;
            
            $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
            
            if (isset($colors[$hex])) {
                $colors[$hex]++;
            } else {
                $colors[$hex] = 1;
            }
        }
    }
    
    // Sort by frequency
    arsort($colors);
    
    // Get top N colors
    $result = array_slice(array_keys($colors), 0, $num_colors);
    
    // Clean up
    imagedestroy($image);
    imagedestroy($thumb);
    
    // If we didn't get enough colors, fill with defaults
    while (count($result) < $num_colors) {
        $defaults = ['#0d6efd', '#6c757d', '#0dcaf0', '#198754', '#ffc107'];
        $result[] = $defaults[count($result)];
    }
    
    return $result;
}

/**
 * Adjust color brightness
 * @param string $hex Hex color code
 * @param int $percent Percent to darken (negative) or lighten (positive)
 * @return string Adjusted hex color code
 */
function adjust_brightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
