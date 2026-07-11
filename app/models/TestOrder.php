<?php
class TestOrder extends Model
{
    protected $table = 'test_orders';

    /**
     * Check if a duplicate exists for this patient+test within window
     */
    public function checkDuplicate($patientId, $loincCode, $windowDays = 30)
    {
        // Use driver-aware SQL: SQLite has DATE_SUB_DAYS function (registered in Database),
        // MySQL uses DATE_SUB(NOW(), INTERVAL n DAY).
        $sql = "SELECT o.*, r.result_value, r.unit, r.normal_range, r.flag, r.performed_at,
                    t.name_ar, t.name_en, t.loinc_code,
                    u.full_name AS doctor_name
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             LEFT JOIN test_results r ON o.id = r.order_id
             LEFT JOIN users u ON o.doctor_id = (SELECT d.user_id FROM doctors d WHERE d.id = o.doctor_id)
             WHERE o.patient_id = ?
               AND t.loinc_code = ?
               AND o.status IN ('ordered', 'in_progress', 'result_uploaded')
               AND o.ordered_at >= ";
        if (Database::isSqlite()) {
            // SQLite: use registered DATE_SUB_DAYS function
            $sql .= "DATE_SUB_DAYS(DATE('now'), ?)";
        } else {
            // MySQL: native INTERVAL syntax
            $sql .= "DATE_SUB(NOW(), INTERVAL ? DAY)";
        }
        $sql .= " ORDER BY o.ordered_at DESC LIMIT 1";
        return Database::fetch($sql, [$patientId, $loincCode, $windowDays]);
    }

    public function forPatient($patientId)
    {
        return Database::fetchAll(
            "SELECT o.*, t.name_ar, t.name_en, t.loinc_code, t.category,
                    r.result_value, r.unit, r.normal_range, r.flag, r.performed_at, r.uploaded_at,
                    u.full_name AS doctor_name
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             LEFT JOIN test_results r ON o.id = r.order_id
             LEFT JOIN doctors d ON o.doctor_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             WHERE o.patient_id = ?
             ORDER BY o.ordered_at DESC",
            [$patientId]
        );
    }

    public function forDoctor($doctorId)
    {
        return Database::fetchAll(
            "SELECT o.*, t.name_ar, t.name_en, t.loinc_code,
                    r.result_value, r.flag, r.uploaded_at,
                    u.full_name AS patient_name, u.unique_id AS patient_uid
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             LEFT JOIN test_results r ON o.id = r.order_id
             LEFT JOIN users u ON o.patient_id = u.id
             WHERE o.doctor_id = ?
             ORDER BY o.ordered_at DESC",
            [$doctorId]
        );
    }

    public function pendingForLab()
    {
        return Database::fetchAll(
            "SELECT o.*, t.name_ar, t.name_en, t.loinc_code, t.sample_type,
                    u.full_name AS patient_name, u.unique_id AS patient_uid,
                    doc_u.full_name AS doctor_name
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             JOIN users u ON o.patient_id = u.id
             LEFT JOIN doctors d ON o.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             WHERE o.status = 'ordered'
             ORDER BY o.ordered_at ASC"
        );
    }

    public function findDetail($id)
    {
        return Database::fetch(
            "SELECT o.*, t.name_ar, t.name_en, t.loinc_code, t.sample_type, t.category,
                    r.result_value, r.unit, r.normal_range, r.flag, r.performed_at, r.uploaded_at,
                    r.lab_tech_id,
                    u.full_name AS patient_name, u.unique_id AS patient_uid, u.gender, u.birth_date, u.phone,
                    doc_u.full_name AS doctor_name,
                    lt_u.full_name AS lab_tech_name
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             LEFT JOIN test_results r ON o.id = r.order_id
             LEFT JOIN users u ON o.patient_id = u.id
             LEFT JOIN doctors d ON o.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             LEFT JOIN users lt_u ON r.lab_tech_id = lt_u.id
             WHERE o.id = ?",
            [$id]
        );
    }

    public function countByStatus($status)
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM test_orders WHERE status = ?",
            [$status]
        );
    }
}
