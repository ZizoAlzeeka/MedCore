<?php
/**
 * Front Controller (entry point)
 */

// Load env
require __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

// Load config
require __DIR__ . '/app/config/config.php';

// Catch all errors and log them
set_exception_handler(function ($e) {
    Logger::error("Uncaught exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    http_response_code(500);
    if (Env::get('APP_DEBUG', 'false') === 'true') {
        echo "<div style='direction:rtl;font-family:Cairo,Arial;text-align:center;padding:40px;'>";
        echo "<h2>خطأ في النظام</h2><p>" . e($e->getMessage()) . "</p>";
        echo "<pre style='text-align:right;background:#f5f5f5;padding:15px;border-radius:8px;direction:ltr;'>" . e($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='direction:rtl;font-family:Cairo,Arial;text-align:center;padding:40px;'>";
        echo "<h2>حدث خطأ غير متوقع</h2><p>يرجى المحاولة لاحقاً أو مراجعة ملف السجل.</p>";
        echo "</div>";
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    Logger::error("PHP error [{$severity}]: {$message}", ['file' => $file, 'line' => $line]);
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

Logger::info("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'], [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_id' => Auth::id(),
]);

// ===== Auto-migration: ensure DB tables + seed exist on every cold start =====
// Safe to call on every request — short-circuits if DB is already populated.
// This handles fresh deploys (Render, Docker) where install.php hasn't been run.
try {
    $migrationMessages = AutoMigrator::runIfNeeded();
    if (!empty($migrationMessages)) {
        foreach ($migrationMessages as $m) {
            Logger::info('[migration] ' . $m);
        }
    }
} catch (Throwable $e) {
    Logger::error('AutoMigrator threw: ' . $e->getMessage());
}

// Load routes and dispatch
$router = require __DIR__ . '/app/routes/web.php';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
// Remove base path if APP_URL contains a path
$baseUrl = parse_url(Env::get('APP_URL', ''), PHP_URL_PATH);
if ($baseUrl && $baseUrl !== '/' && strpos($uri, $baseUrl) === 0) {
    $uri = substr($uri, strlen($baseUrl));
}
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
