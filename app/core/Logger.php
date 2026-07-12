<?php
/**
 * Logger — file-based logging for debugging and audit
 */
class Logger
{
    private static $logDir;

    private static function init()
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0775, true);
            }
        }
    }

    private static function write($level, $message, $context = [])
    {
        self::init();
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $file = self::$logDir . "/app-{$date}.log";

        $line = "[{$time}] [{$level}] {$message}";
        if (!empty($context)) {
            $line .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND);

        // Separate error log
        if ($level === 'ERROR') {
            @file_put_contents(self::$logDir . "/error-{$date}.log", $line, FILE_APPEND);
        }
    }

    public static function info($msg, $ctx = []) { self::write('INFO', $msg, $ctx); }
    public static function error($msg, $ctx = []) { self::write('ERROR', $msg, $ctx); }
    public static function warning($msg, $ctx = []) { self::write('WARNING', $msg, $ctx); }
    public static function debug($msg, $ctx = []) {
        if (Env::get('APP_DEBUG', 'false') === 'true') {
            self::write('DEBUG', $msg, $ctx);
        }
    }
    public static function audit($action, $userId, $details = [])
    {
        self::write('AUDIT', "User #$userId: $action", $details);
    }

    public static function logDir()
    {
        self::init();
        return self::$logDir;
    }

    public static function allFiles()
    {
        self::init();
        $files = glob(self::$logDir . '/*.log');
        rsort($files);
        return $files;
    }

    public static function diagnosticSnapshot()
    {
        $lines = [];
        $lines[] = str_repeat('=', 78);
        $lines[] = 'MedCore — Diagnostic Snapshot';
        $lines[] = 'Generated: ' . date('Y-m-d H:i:s') . ' (' . date_default_timezone_get() . ')';
        $lines[] = str_repeat('=', 78);

        $lines[] = '';
        $lines[] = '##### 1. RUNTIME / SERVER #####';
        $lines[] = 'PHP Version:        ' . PHP_VERSION;
        $lines[] = 'PHP SAPI:           ' . php_sapi_name();
        $lines[] = 'OS:                 ' . php_uname();
        $lines[] = 'Server Software:    ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A');
        $lines[] = 'HTTP Host:          ' . ($_SERVER['HTTP_HOST'] ?? 'N/A');
        $lines[] = 'Request Scheme:     ' . ($_SERVER['REQUEST_SCHEME'] ?? 'N/A');
        $lines[] = 'HTTPS:              ' . ($_SERVER['HTTPS'] ?? 'off');
        $lines[] = 'Document Root:      ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A');
        $lines[] = 'Script Filename:    ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A');
        $lines[] = 'Remote Addr:        ' . ($_SERVER['REMOTE_ADDR'] ?? 'N/A');
        $lines[] = 'Request Method:     ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A');
        $lines[] = 'Request URI:        ' . ($_SERVER['REQUEST_URI'] ?? 'N/A');
        $lines[] = 'Request Time:       ' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time());
        $lines[] = 'User Agent:         ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
        $lines[] = 'Process ID:         ' . getmypid();
        $lines[] = 'Memory Limit:       ' . ini_get('memory_limit');
        $lines[] = 'Memory Usage:       ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
        $lines[] = 'Peak Memory Usage:  ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';
        $lines[] = 'Max Execution Time: ' . ini_get('max_execution_time') . 's';
        $lines[] = 'Upload Max Filesize:' . ini_get('upload_max_filesize');
        $lines[] = 'Post Max Size:      ' . ini_get('post_max_size');
        $lines[] = 'Display Errors:     ' . ini_get('display_errors');
        $lines[] = 'Error Reporting:    ' . error_reporting();
        $lines[] = 'Default Timezone:   ' . date_default_timezone_get();
        $lines[] = 'Locale:             ' . setlocale(LC_ALL, 0);

        $lines[] = '';
        $lines[] = '##### 2. ENVIRONMENT CONFIG (masked) #####';
        $envKeys = [
            'APP_NAME', 'APP_URL', 'APP_ENV', 'APP_DEBUG', 'APP_TIMEZONE', 'APP_LANG',
            'DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_CHARSET',
            'DB_PATH', 'CSRF_ENABLED', 'SESSION_LIFETIME',
            'DEFAULT_DUP_WINDOW_DAYS', 'DEFAULT_LANG', 'PORT',
        ];
        foreach ($envKeys as $k) {
            $v = Env::get($k);
            if ($v === null) {
                $lines[] = sprintf('%-22s = (not set)', $k);
            } else {
                $lines[] = sprintf('%-22s = %s', $k, $v);
            }
        }
        $pass = Env::get('DB_PASS');
        $lines[] = sprintf('%-22s = %s', 'DB_PASS', $pass === null ? '(not set)' : str_repeat('*', max(4, strlen($pass))));

        $lines[] = '';
        $lines[] = '##### 3. PHP EXTENSIONS #####';
        $exts = ['pdo','pdo_mysql','pdo_sqlite','mbstring','openssl','curl','gd','intl','json','session','fileinfo','xml','zip','bcmath','opcache'];
        foreach ($exts as $ext) {
            $lines[] = sprintf('  %-15s %s', $ext, extension_loaded($ext) ? 'YES' : 'NO');
        }

        $lines[] = '';
        $lines[] = '##### 4. DATABASE STATUS #####';
        try {
            $driver = Database::isMysql() ? 'MySQL' : 'SQLite';
            $lines[] = 'Active Driver: ' . $driver;
            $pdo = Database::getInstance()->pdo();
            if (Database::isMysql()) {
                $lines[] = 'MySQL Version: ' . $pdo->query("SELECT VERSION()")->fetchColumn();
                $lines[] = 'MySQL Charset: ' . $pdo->query("SELECT @@character_set_database")->fetchColumn();
                $lines[] = 'MySQL Collation: ' . $pdo->query("SELECT @@collation_database")->fetchColumn();
            }
            $tables = Database::tables();
            $lines[] = 'Tables Count:  ' . count($tables);
            $lines[] = 'Tables:        ' . (count($tables) ? implode(', ', $tables) : '(none — install.php may need to be run)');
        } catch (Throwable $e) {
            $lines[] = 'DB Error:      ' . $e->getMessage();
        }

        $lines[] = '';
        $lines[] = '##### 5. SESSION #####';
        $lines[] = 'Session ID:    ' . (session_id() ?: '(none)');
        $lines[] = 'Session Name:  ' . session_name();
        $lines[] = 'Session Save Path: ' . session_save_path();
        $lines[] = 'Session Status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : (session_status() === PHP_SESSION_NONE ? 'NONE' : 'DISABLED'));
        $lines[] = 'Auth User ID:  ' . (class_exists('Auth') ? (Auth::id() ?: '(guest)') : '(Auth not loaded)');

        $lines[] = '';
        $lines[] = '##### 6. LOG FILES ON DISK #####';
        $files = self::allFiles();
        if (empty($files)) {
            $lines[] = '(no log files yet)';
        } else {
            foreach ($files as $f) {
                $lines[] = sprintf('  %-30s  %s bytes  %s  %s lines',
                    basename($f),
                    filesize($f),
                    date('Y-m-d H:i:s', filemtime($f)),
                    count(file($f) ?: [])
                );
            }
        }

        $lines[] = '';
        $lines[] = '##### 7. PHP ERROR LOG (last 100 lines, if accessible) #####';
        $errLog = ini_get('error_log');
        $lines[] = 'Configured error_log: ' . ($errLog ?: '(default)');
        if ($errLog && is_file($errLog) && is_readable($errLog)) {
            $content = @file_get_contents($errLog);
            if ($content !== false) {
                $arr = explode("\n", $content);
                if (count($arr) > 100) $arr = array_slice($arr, -100);
                foreach ($arr as $l) $lines[] = '  ' . $l;
            } else {
                $lines[] = '  (error_log not readable)';
            }
        } else {
            $lines[] = '  (no readable error_log file)';
        }

        $lines[] = '';
        $lines[] = '##### 8. APACHE/RENDER ENV (if present) #####';
        foreach (['PORT','RENDER','RENDER_SERVICE_ID','RENDER_GIT_COMMIT','RENDER_EXTERNAL_URL','DYNO','PATH_INFO'] as $k) {
            $v = getenv($k);
            if ($v !== false) {
                $lines[] = sprintf('  %-22s = %s', $k, $v);
            }
        }

        return implode("\n", $lines);
    }
}
