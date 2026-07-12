<?php
class TreatmentPlan extends Model
{
    protected $table = 'treatment_plans';

    public function forPatient($patientId)
    {
        return Database::fetchAll(
            "SELECT tp.*, doc_u.full_name AS doctor_name, t.name_ar AS test_name
             FROM treatment_plans tp
             LEFT JOIN doctors d ON tp.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             LEFT JOIN test_orders o ON tp.appointment_id = o.id
             LEFT JOIN tests_catalog t ON o.test_id = t.id
             WHERE tp.patient_id = ?
             ORDER BY tp.created_at DESC",
            [$patientId]
        );
    }

    public function latestForPatient($patientId)
    {
        return Database::fetch(
            "SELECT tp.*, doc_u.full_name AS doctor_name
             FROM treatment_plans tp
             LEFT JOIN doctors d ON tp.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             WHERE tp.patient_id = ?
             ORDER BY tp.created_at DESC LIMIT 1",
            [$patientId]
        );
    }

    public function forDoctor($doctorId)
    {
        return Database::fetchAll(
            "SELECT tp.*, u.full_name AS patient_name, u.unique_id AS patient_uid
             FROM treatment_plans tp
             JOIN users u ON tp.patient_id = u.id
             WHERE tp.doctor_id = ?
             ORDER BY tp.created_at DESC",
            [$doctorId]
        );
    }
}
