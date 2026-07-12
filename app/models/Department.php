<?php
class Department extends Model
{
    protected $table = 'departments';

    public function allWithDoctors()
    {
        $depts = Database::fetchAll("SELECT * FROM departments ORDER BY name_ar");
        foreach ($depts as &$d) {
            $d['doctors'] = Database::fetchAll(
                "SELECT u.id, u.full_name, d.specialty
                 FROM users u
                 JOIN doctors d ON u.id = d.user_id
                 WHERE d.department_id = ? AND u.is_active = 1
                 ORDER BY u.full_name",
                [$d['id']]
            );
            $d['doctors_count'] = count($d['doctors']);
        }
        return $depts;
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
