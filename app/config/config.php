<?php
/**
 * Main configuration loader
 */

// Detect HTTPS behind Render's reverse proxy / load balancer.
// Render terminates TLS at the edge and forwards to Apache over HTTP,
// passing the original protocol in X-Forwarded-Proto. We must set
// $_SERVER['HTTPS'] = 'on' so PHP, $_SERVER['REQUEST_SCHEME'], and
// any code checking HTTPS all see the correct protocol.
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $xfp = strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO']));
    if ($xfp === 'https' || strpos($xfp, 'https') !== false) {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_SCHEME'] = 'https';
    } elseif ($xfp === 'http') {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_SCHEME'] = 'http';
    }
}

// Error reporting
if (Env::get('APP_DEBUG', 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Riyadh'));

// Charset (mbstring optional)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

// Autoloader for core classes
spl_autoload_register(function ($class) {
    $paths = [
        dirname(__DIR__) . '/core/' . $class . '.php',
        dirname(__DIR__) . '/models/' . $class . '.php',
        dirname(__DIR__) . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

// Load helper functions
require dirname(__DIR__) . '/helpers/functions.php';

// Start session
Auth::start();

// Set default header
header('Content-Type: text/html; charset=utf-8');
