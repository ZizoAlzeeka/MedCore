<?php
class Setting extends Model
{
    protected $table = 'settings';

    private static $cache = null;

    public function getAll()
    {
        if (self::$cache === null) {
            $rows = Database::fetchAll("SELECT * FROM settings");
            self::$cache = [];
            foreach ($rows as $r) self::$cache[$r['key']] = $r['value'];
        }
        return self::$cache;
    }

    public function get($key, $default = null)
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $exists = Database::fetch("SELECT id FROM settings WHERE `key` = ?", [$key]);
        if ($exists) {
            Database::update('settings', ['value' => $value], "`key` = ?", [$key]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => $value]);
        }
        self::$cache[$key] = $value;
    }

    public function getDuplicateWindowDays()
    {
        return (int) $this->get('duplicate_window_days', 30);
    }
}
