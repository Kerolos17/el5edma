<?php

/**
 * Custom routing script for PHP's built-in server
 *
 * This script intercepts requests to /storage/* and serves files from storage/app/public
 * directly, bypassing the symlink issue on Windows with PHP's built-in server.
 *
 * All other requests are passed to Laravel's public/index.php router.
 */

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Check if the request is for a file in public/storage
if (str_starts_with($uri, '/storage/')) {
    // Map to the actual storage path
    // Remove '/storage/' prefix (8 characters) and prepend actual storage path
    $storagePath = __DIR__ . '/storage/app/public' . substr($uri, 8);

    if (file_exists($storagePath) && is_file($storagePath)) {
        // Determine MIME type
        $mimeType = mime_content_type($storagePath);

        // Set appropriate headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($storagePath));

        // Output the file
        readfile($storagePath);
        exit;
    }
}

// For all other requests, pass to Laravel's index.php
require_once __DIR__ . '/public/index.php';
