<?php
/**
 * Env Loader — parses .env file into $_ENV
 */
class Env
{
    private static $loaded = false;

    public static function load($path = null)
    {
        if (self::$loaded) return;
        $path = $path ?? dirname(__DIR__, 2) . '/.env';
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: $path");
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value)-1] === '"') {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
        self::$loaded = true;
    }

    public static function get($key, $default = null)
    {
        $val = getenv($key);
        return $val === false ? $default : $val;
    }
}
