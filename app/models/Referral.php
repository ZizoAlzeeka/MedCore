<?php
class Referral extends Model
{
    protected $table = 'referrals';

    public function forPatient($patientId)
    {
        return Database::fetchAll(
            "SELECT r.*, from_u.full_name AS from_doctor, to_u.full_name AS to_doctor,
                    from_dep.name_ar AS from_dept, to_dep.name_ar AS to_dept
             FROM referrals r
             LEFT JOIN doctors from_d ON r.from_doctor_id = from_d.id
             LEFT JOIN users from_u ON from_d.user_id = from_u.id
             LEFT JOIN departments from_dep ON from_d.department_id = from_dep.id
             LEFT JOIN doctors to_d ON r.to_doctor_id = to_d.id
             LEFT JOIN users to_u ON to_d.user_id = to_u.id
             LEFT JOIN departments to_dep ON to_d.department_id = to_dep.id
             WHERE r.patient_id = ?
             ORDER BY r.referred_at DESC",
            [$patientId]
        );
    }
}
