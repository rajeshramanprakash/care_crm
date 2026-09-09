<?php
// Profile image fallback script
$imageId = $_GET['id'] ?? 'default';

// Try different image formats
$imageFormats = ['png', 'jpg', 'jpeg'];
$imagePath = null;

foreach ($imageFormats as $format) {
    $testPath = storage_path("app/public/profile_images/{$imageId}.{$format}");
    if (file_exists($testPath)) {
        $imagePath = $testPath;
        break;
    }
}

// If the specific image doesn't exist, use default
if (!$imagePath) {
    $imagePath = public_path('images/default-user.png');
}

// Check if default exists
if (!file_exists($imagePath)) {
    http_response_code(404);
    exit('Image not found');
}

// Set appropriate headers
$mimeType = mime_content_type($imagePath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Output the image
readfile($imagePath);
?>
