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
}
