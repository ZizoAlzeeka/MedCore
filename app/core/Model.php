<?php
/**
 * Base Model class
 */
class Model
{
    protected $table;
    protected $pk = 'id';

    public function find($id)
    {
        return Database::fetch("SELECT * FROM `{$this->table}` WHERE `{$this->pk}` = ?", [$id]);
    }

    public function all($order = 'id DESC')
    {
        return Database::fetchAll("SELECT * FROM `{$this->table}` ORDER BY $order");
    }

    public function where($where, $params = [], $order = 'id DESC')
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($where) $sql .= " WHERE $where";
        if ($order) $sql .= " ORDER BY $order";
        return Database::fetchAll($sql, $params);
    }

    public function firstWhere($where, $params = [])
    {
        return Database::fetch("SELECT * FROM `{$this->table}` WHERE $where LIMIT 1", $params);
    }

    public function create(array $data)
    {
        return Database::insert($this->table, $data);
    }

    public function update($id, array $data)
    {
        return Database::update($this->table, $data, "`{$this->pk}` = ?", [$id]);
    }

    public function delete($id)
    {
        return Database::delete($this->table, "`{$this->pk}` = ?", [$id]);
    }

    public function count($where = '', $params = [])
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE $where";
        return (int) Database::fetchColumn($sql, $params);
    }
}
