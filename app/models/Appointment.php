<?php
class Appointment extends Model
{
    protected $table = 'appointments';

    public function forDoctor($doctorId)
    {
        return Database::fetchAll(
            "SELECT a.*, u.full_name AS patient_name, u.unique_id AS patient_uid, u.phone,
                    rec_u.full_name AS receptionist_name
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             LEFT JOIN users rec_u ON a.receptionist_id = rec_u.id
             WHERE a.doctor_id = ?
             ORDER BY a.appt_date DESC",
            [$doctorId]
        );
    }

    public function todayForDoctor($doctorId)
    {
        return Database::fetchAll(
            "SELECT a.*, u.full_name AS patient_name, u.unique_id AS patient_uid, u.phone
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             WHERE a.doctor_id = ? AND DATE(a.appt_date) = CURDATE() AND a.status = 'booked'
             ORDER BY a.appt_date ASC",
            [$doctorId]
        );
    }

    public function forReception()
    {
        return Database::fetchAll(
            "SELECT a.*, u.full_name AS patient_name, u.unique_id AS patient_uid,
                    doc_u.full_name AS doctor_name, dep.name_ar AS department_name,
                    rec_u.full_name AS receptionist_name
             FROM appointments a
             JOIN users u ON a.patient_id = u.id
             LEFT JOIN doctors d ON a.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             LEFT JOIN departments dep ON d.department_id = dep.id
             LEFT JOIN users rec_u ON a.receptionist_id = rec_u.id
             ORDER BY a.appt_date DESC LIMIT 100"
        );
    }

    public function forPatient($patientId)
    {
        return Database::fetchAll(
            "SELECT a.*, doc_u.full_name AS doctor_name, dep.name_ar AS department_name
             FROM appointments a
             LEFT JOIN doctors d ON a.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             LEFT JOIN departments dep ON d.department_id = dep.id
             WHERE a.patient_id = ?
             ORDER BY a.appt_date DESC",
            [$patientId]
        );
    }

    public function countToday()
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM appointments WHERE DATE(appt_date) = CURDATE() AND status = 'booked'"
        );
    }

    public function countByStatus($status)
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM appointments WHERE status = ?",
            [$status]
        );
    }
}
