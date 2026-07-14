<?php
class Department extends Model
{
    protected $table = 'departments';

    public function allWithDoctors()
    {
        // ⚡ Single query with LEFT JOIN + GROUP_CONCAT — avoids N+1 queries.
        // Was: 1 + N queries (N = number of departments). Now: 1 query total.
        $sql = "SELECT dep.*,
                    COUNT(d.id) AS doctors_count,
                    GROUP_CONCAT(
                        DISTINCT CONCAT_WS('|', u.id, u.full_name, d.specialty)
                        SEPARATOR ';;'
                    ) AS doctors_csv
                FROM departments dep
                LEFT JOIN doctors d ON dep.id = d.department_id
                LEFT JOIN users u ON d.user_id = u.id AND u.is_active = 1
                GROUP BY dep.id
                ORDER BY dep.name_ar";
        $rows = Database::fetchAll($sql);

        // Parse the GROUP_CONCAT result into PHP array
        foreach ($rows as &$d) {
            $d['doctors'] = [];
            if (!empty($d['doctors_csv'])) {
                $parts = explode(';;', $d['doctors_csv']);
                foreach ($parts as $p) {
                    if (empty($p)) continue;
                    $fields = explode('|', $p);
                    if (count($fields) >= 2) {
                        $d['doctors'][] = [
                            'id' => $fields[0],
                            'full_name' => $fields[1],
                            'specialty' => $fields[2] ?? null,
                        ];
                    }
                }
            }
            unset($d['doctors_csv']);
        }
        return $rows;
    }

    public function findWithDoctors($id)
    {
        $dept = $this->find($id);
        if (!$dept) return null;
        $dept['doctors'] = Database::fetchAll(
            "SELECT u.*, d.specialty, d.license_no
             FROM users u
             JOIN doctors d ON u.id = d.user_id
             WHERE d.department_id = ?
             ORDER BY u.full_name",
            [$id]
        );
        return $dept;
    }
}
