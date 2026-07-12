<?php
/**
 * Database — PDO singleton supporting both MySQL and SQLite
 *
 * Driver auto-selected from DB_DRIVER env (default: mysql)
 * - mysql:  uses PDO_MYSQL with utf8mb4
 * - sqlite: uses PDO_SQLITE with a local file (DB_PATH or database/platform.sqlite)
 *
 * Both drivers expose the SAME public API:
 *   Database::query($sql, $params) → PDOStatement
 *   Database::fetch($sql, $params) → row|array
 *   Database::fetchAll($sql, $params) → array of rows
 *   Database::fetchColumn($sql, $params) → scalar
 *   Database::insert($table, $data) → last insert id
 *   Database::update($table, $data, $where, $whereParams) → affected rows
 *   Database::delete($table, $where, $params) → affected rows
 *
 * To switch from SQLite to MySQL:
 *   1) In .env set DB_DRIVER=mysql (and DB_HOST/PORT/NAME/USER/PASS)
 *   2) Delete the SQLite file (database/platform.sqlite)
 *   3) Run install.php on the server — it will create MySQL tables
 *
 * To remove SQLite completely:
 *   - Delete database/platform.sqlite
 *   - Delete schema.sqlite.sql
 *   - Set DB_DRIVER=mysql in .env
 *   - Re-run install.php
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $driver; // 'mysql' or 'sqlite'

    private function __construct()
    {
        $this->driver = strtolower(Env::get('DB_DRIVER', 'mysql'));

        if ($this->driver === 'sqlite') {
            $this->connectSqlite();
        } else {
            $this->connectMysql();
        }

        // Common PDO options
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Use real prepared statements (better security + correct type handling)
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    private function connectMysql()
    {
        $host = Env::get('DB_HOST');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME');
        $user = Env::get('DB_USER');
        $pass = Env::get('DB_PASS');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $options = [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            Logger::error("MySQL connection failed: " . $e->getMessage());
            throw new Exception("فشل الاتصال بقاعدة البيانات MySQL: " . $e->getMessage());
        }
    }

    private function connectSqlite()
    {
        // DB_PATH can be absolute or relative to project root
        $dbPath = Env::get('DB_PATH');
        if (!$dbPath) {
            $dbPath = dirname(__DIR__, 2) . '/database/platform.sqlite';
        } elseif ($dbPath[0] !== '/') {
            $dbPath = dirname(__DIR__, 2) . '/' . $dbPath;
        }

        // Ensure directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $dsn = "sqlite:{$dbPath}";

        try {
            $this->pdo = new PDO($dsn);
        } catch (PDOException $e) {
            Logger::error("SQLite connection failed: " . $e->getMessage());
            throw new Exception("فشل الاتصال بقاعدة البيانات SQLite: " . $e->getMessage());
        }

        // SQLite-specific pragmas for better compatibility & performance
        $this->pdo->exec("PRAGMA foreign_keys = ON");
        $this->pdo->exec("PRAGMA journal_mode = WAL");
        $this->pdo->exec("PRAGMA synchronous = NORMAL");
        $this->pdo->exec("PRAGMA encoding = 'UTF-8'");

        // ===== Register MySQL-compatible functions so SQL queries work unchanged =====
        // These let us use the same SQL for both MySQL and SQLite.
        $this->registerMysqlCompatFunctions();
    }

    /**
     * Register MySQL-compatible SQL functions in SQLite
     * so the same SQL queries work on both drivers.
     */
    private function registerMysqlCompatFunctions()
    {
        // NOW() → current datetime
        $this->pdo->sqliteCreateFunction('NOW', function() {
            return date('Y-m-d H:i:s');
        }, 0);

        // CURDATE() → current date
        $this->pdo->sqliteCreateFunction('CURDATE', function() {
            return date('Y-m-d');
        }, 0);

        // CURTIME() → current time
        $this->pdo->sqliteCreateFunction('CURTIME', function() {
            return date('H:i:s');
        }, 0);

        // UNIX_TIMESTAMP() → current epoch
        $this->pdo->sqliteCreateFunction('UNIX_TIMESTAMP', function($date = null) {
            if ($date === null) return time();
            return strtotime($date);
        }, 1);

        // FROM_UNIXTIME(epoch) → datetime
        $this->pdo->sqliteCreateFunction('FROM_UNIXTIME', function($ts) {
            return date('Y-m-d H:i:s', (int)$ts);
        }, 1);

        // DATE_FORMAT(date, format) — partial MySQL format support
        $this->pdo->sqliteCreateFunction('DATE_FORMAT', function($date, $format) {
            if (!$date) return null;
            $ts = strtotime($date);
            // Map MySQL format specifiers to PHP date format
            $map = [
                '%Y' => 'Y', '%m' => 'm', '%d' => 'd',
                '%H' => 'H', '%i' => 'i', '%s' => 's',
                '%h' => 'h', '%p' => 'A',
            ];
            $phpFormat = str_replace(array_keys($map), array_values($map), $format);
            return date($phpFormat, $ts);
        }, 2);

        // DATEDIFF(date1, date2) → days difference
        $this->pdo->sqliteCreateFunction('DATEDIFF', function($d1, $d2) {
            return (int)((strtotime($d1) - strtotime($d2)) / 86400);
        }, 2);

        // DATE_ADD(date, INTERVAL n unit)
        $this->pdo->sqliteCreateFunction('DATE_ADD_DAYS', function($date, $days) {
            return date('Y-m-d', strtotime("$date +$days day"));
        }, 2);

        // DATE_SUB(date, INTERVAL n unit)
        $this->pdo->sqliteCreateFunction('DATE_SUB_DAYS', function($date, $days) {
            return date('Y-m-d', strtotime("$date -$days day"));
        }, 2);

        // YEAR(date), MONTH(date), DAY(date)
        $this->pdo->sqliteCreateFunction('YEAR', function($date) {
            return $date ? (int)date('Y', strtotime($date)) : null;
        }, 1);
        $this->pdo->sqliteCreateFunction('MONTH', function($date) {
            return $date ? (int)date('m', strtotime($date)) : null;
        }, 1);
        $this->pdo->sqliteCreateFunction('DAY', function($date) {
            return $date ? (int)date('d', strtotime($date)) : null;
        }, 1);

        // UCASE / LCASE (uppercase/lowercase)
        $this->pdo->sqliteCreateFunction('UCASE', function($s) {
            return mb_strtoupper((string)$s);
        }, 1);
        $this->pdo->sqliteCreateFunction('LCASE', function($s) {
            return mb_strtolower((string)$s);
        }, 1);

        // IFNULL already exists in SQLite (alias ISNULL not, but rarely used)
        $this->pdo->sqliteCreateFunction('ISNULL', function($v) {
            return $v === null ? 1 : 0;
        }, 1);

        // CONCAT(...) — concatenate strings
        $this->pdo->sqliteCreateFunction('CONCAT', function(...$args) {
            return implode('', $args);
        });

        // ROW_COUNT() for compatibility with Database::update()
        // SQLite doesn't have ROW_COUNT() — we override the method instead
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo()
    {
        return $this->pdo;
    }

    public function driver()
    {
        return $this->driver;
    }

    public static function isSqlite()
    {
        return strtolower(Env::get('DB_DRIVER', 'mysql')) === 'sqlite';
    }

    public static function isMysql()
    {
        return !self::isSqlite();
    }

    public static function query($sql, $params = [])
    {
        return self::getInstance()->_query($sql, $params);
    }

    private function _query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error("SQL Error: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    public static function fetch($sql, $params = [])
    {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn($sql, $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert($table, $data)
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        self::query($sql, array_values($data));
        return self::getInstance()->pdo->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = [])
    {
        // Use positional ? placeholders for both SET and WHERE to avoid
        // mixing named and positional params (which fails in some PDO drivers)
        $set = [];
        $setParams = [];
        foreach ($data as $col => $val) {
            $set[] = "`$col` = ?";
            $setParams[] = $val;
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
        $stmt = self::query($sql, array_merge($setParams, array_values($whereParams)));
        return $stmt->rowCount();
    }

    public static function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM `$table` WHERE $where";
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Get list of existing tables (driver-aware)
     */
    public static function tables()
    {
        $instance = self::getInstance();
        if ($instance->driver === 'sqlite') {
            $rows = self::fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            return array_column($rows, 'name');
        } else {
            $rows = self::fetchAll("SHOW TABLES");
            return array_values(array_map('current', $rows));
        }
    }

    /**
     * Check if a table exists
     */
    public static function tableExists($table)
    {
        return in_array($table, self::tables());
    }
}
