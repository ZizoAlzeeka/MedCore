<?php
// PHP built-in server router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
$path = __DIR__ . '/public' . $uri;
if (preg_match('/^\/public\//', $uri) && file_exists($path)) {
    return false; // Let PHP serve the static file
}

// For all other requests, route to index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
