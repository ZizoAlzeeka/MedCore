<?php
class User extends Model
{
    protected $table = 'users';

    public function findByEmail($email)
    {
        return Database::fetch("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
    }

    public function findByUniqueId($uniqueId)
    {
        return Database::fetch("SELECT * FROM users WHERE unique_id = ? LIMIT 1", [$uniqueId]);
    }

    public function byRole($role)
    {
        return Database::fetchAll(
            "SELECT * FROM users WHERE role = ? ORDER BY full_name",
            [$role]
        );
    }

    public function doctors()
    {
        return Database::fetchAll(
            "SELECT u.*, d.department_id, d.specialty, d.license_no, dep.name_ar AS department_name
             FROM users u
             JOIN doctors d ON u.id = d.user_id
             LEFT JOIN departments dep ON d.department_id = dep.id
             WHERE u.role = 'doctor' AND u.is_active = 1
             ORDER BY u.full_name"
        );
    }

    public function doctorsByDepartment($deptId)
    {
        return Database::fetchAll(
            "SELECT u.*, d.specialty
             FROM users u
             JOIN doctors d ON u.id = d.user_id
             WHERE u.role = 'doctor' AND u.is_active = 1 AND d.department_id = ?
             ORDER BY u.full_name",
            [$deptId]
        );
    }

    public function countByRole($role)
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1",
            [$role]
        );
    }

    public function countAll()
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE is_active = 1");
    }
}
