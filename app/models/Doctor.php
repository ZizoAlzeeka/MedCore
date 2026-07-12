<?php
class Doctor extends Model
{
    protected $table = 'doctors';

    public function findByUserId($userId)
    {
        return Database::fetch(
            "SELECT d.*, dep.name_ar AS department_name
             FROM doctors d
             LEFT JOIN departments dep ON d.department_id = dep.id
             WHERE d.user_id = ?",
            [$userId]
        );
    }

    public function findWithUser($doctorId)
    {
        return Database::fetch(
            "SELECT d.*, u.full_name, u.email, u.phone, u.unique_id, dep.name_ar AS department_name
             FROM doctors d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN departments dep ON d.department_id = dep.id
             WHERE d.id = ?",
            [$doctorId]
        );
    }
}
