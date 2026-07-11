<?php
class TestResult extends Model
{
    protected $table = 'test_results';

    public function findByOrder($orderId)
    {
        return Database::fetch("SELECT * FROM test_results WHERE order_id = ?", [$orderId]);
    }
}
