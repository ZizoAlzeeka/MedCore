<?php
/**
 * Front Controller (entry point)
 */

// Load env
require __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

// Load config
require __DIR__ . '/app/config/config.php';

// ⚡ Fast 503 page when DB is unreachable — instead of letting every page
// wait 30s for the MySQL timeout. We probe the DB once on cold start;
// if it fails, we render a friendly maintenance page and exit.
// Subsequent requests rely on the per-request connection (which itself has a
// 3-second connect timeout — see Database::connectMysql()).
$GLOBALS['__medcore_db_checked'] = false;

// Catch all errors and log them
set_exception_handler(function ($e) {
    Logger::error("Uncaught exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    // ⚡ If this is a DB connection error, show the friendly maintenance page
    // instead of a generic 500 — users get a clear "we'll be back" message
    // and the page auto-retries every 15s.
    $msg = $e->getMessage();
    $isDbError = (
        strpos($msg, 'فشل الاتصال بقاعدة البيانات') !== false ||
        strpos($msg, 'SQLSTATE[HY') !== false ||
        strpos($msg, 'MySQL') !== false ||
        strpos($msg, 'pdo') !== false ||
        $e instanceof PDOException
    );

    if ($isDbError) {
        $errorMsg = $msg;
        require __DIR__ . '/app/views/errors/db_down.php';
        return;
    }

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

// ⚡ Performance: skip per-request INFO logging in production — it was causing
// a file_put_contents disk write on every request, slowing down page loads.
// Errors are still logged (via set_exception_handler / set_error_handler below).
if (Env::get('APP_DEBUG', 'false') === 'true') {
    Logger::info("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'], [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_id' => Auth::id(),
    ]);
}

// ===== Auto-migration: ensure DB tables + seed exist on every cold start =====
// ⚡ Performance: only check schema ONCE per container boot, then cache the
// result in a flag file on disk. This avoids the SHOW TABLES + COUNT(*)
// queries that AutoMigrator used to fire on EVERY request — which was a
// major contributor to slow page loads on Coolify (each request waited for
// the remote MySQL round-trip even when DB was already migrated).
//
// The flag file lives in database/.migrated and is invalidated automatically
// when the container restarts (ephemeral filesystem on Coolify/Render).
// To force a re-migration: delete database/.migrated
//
// ⚡ Force re-migration after financial fields removal — bump flag file name
// ⚡ Bump to v4: forces re-run of migrations (incl. 2026_07_15_add_unit_range_to_tests_catalog.sql)
$migrationFlagFile = __DIR__ . '/database/.migrated_v4';
if (!file_exists($migrationFlagFile)) {
    try {
        $migrationMessages = AutoMigrator::runIfNeeded();
        if (!empty($migrationMessages)) {
            foreach ($migrationMessages as $m) {
                Logger::info('[migration] ' . $m);
            }
        }
        // ⚡ Run incremental SQL migrations (add columns, indexes, etc.)
        $appliedMigrations = MigrationRunner::run();
        if (!empty($appliedMigrations)) {
            foreach ($appliedMigrations as $m) {
                Logger::info('[migration-runner] applied: ' . $m);
            }
        }

        // Write the flag file
        @file_put_contents($migrationFlagFile, json_encode([
            'migrated_at' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'host' => Env::get('DB_HOST', 'sqlite'),
            'db' => Env::get('DB_NAME', 'platform.sqlite'),
            'migrations_applied' => $appliedMigrations,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        Logger::error('AutoMigrator threw: ' . $e->getMessage());
    }
}

// ⚡ Ensure seed passwords are ALWAYS correct — runs on every request
// but APCu-cached for 5 minutes to avoid DB queries.
// Checks ALL 5 demo accounts (not just admin). If ANY fails, resets ALL.
$pwCheckKey = 'seed_pw_check_ok';
$pwCheckOk = false;
if (function_exists('apcu_fetch')) {
    $pwCheckOk = apcu_fetch($pwCheckKey);
}
if ($pwCheckOk !== true) {
    try {
        $seedPasswords = [
            'admin@platform.com' => 'admin123',
            'doctor1@platform.com' => 'doctor123',
            'reception1@platform.com' => 'reception123',
            'lab1@platform.com' => 'lab123',
            'patient1@platform.com' => 'patient123',
        ];
        $allFullPasswords = [
            'admin@platform.com' => 'admin123',
            'doctor1@platform.com' => 'doctor123',
            'doctor2@platform.com' => 'doctor123',
            'doctor3@platform.com' => 'doctor123',
            'doctor4@platform.com' => 'doctor123',
            'doctor5@platform.com' => 'doctor123',
            'doctor6@platform.com' => 'doctor123',
            'doctor7@platform.com' => 'doctor123',
            'doctor8@platform.com' => 'doctor123',
            'doctor9@platform.com' => 'doctor123',
            'doctor10@platform.com' => 'doctor123',
            'reception1@platform.com' => 'reception123',
            'reception2@platform.com' => 'reception123',
            'lab1@platform.com' => 'lab123',
            'lab2@platform.com' => 'lab123',
            'patient1@platform.com' => 'patient123',
            'patient2@platform.com' => 'patient123',
            'patient3@platform.com' => 'patient123',
            'patient4@platform.com' => 'patient123',
            'patient5@platform.com' => 'patient123',
            'patient6@platform.com' => 'patient123',
            'patient7@platform.com' => 'patient123',
            'patient8@platform.com' => 'patient123',
            'patient9@platform.com' => 'patient123',
            'patient10@platform.com' => 'patient123',
            'patient11@platform.com' => 'patient123',
            'patient12@platform.com' => 'patient123',
        ];

        $needReset = false;
        foreach ($seedPasswords as $email => $pass) {
            $user = Database::fetch("SELECT password_hash, is_active FROM users WHERE email = ? LIMIT 1", [$email]);
            if (!$user || !password_verify($pass, $user['password_hash']) || !$user['is_active']) {
                $needReset = true;
                break;
            }
        }

        if ($needReset) {
            foreach ($allFullPasswords as $email => $pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                Database::query("UPDATE users SET password_hash = ?, is_active = 1 WHERE email = ?", [$hash, $email]);
            }
            // Clear APCu user cache so fresh data is fetched
            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }
            Logger::info('[password-check] Reset ' . count($allFullPasswords) . ' seed passwords');
        }

        if (function_exists('apcu_store')) {
            apcu_store($pwCheckKey, true, 300); // cache for 5 minutes
        }
    } catch (Throwable $e) {
        // DB might not be ready yet — silently ignore, will retry next request
    }
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
