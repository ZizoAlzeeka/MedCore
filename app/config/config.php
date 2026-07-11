<?php
/**
 * Main configuration loader
 */

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
