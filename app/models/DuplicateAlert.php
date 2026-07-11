<?php
class DuplicateAlert extends Model
{
    protected $table = 'duplicate_alerts';

    public function recent($limit = 20)
    {
        return Database::fetchAll(
            "SELECT da.*, o.ordered_at, t.name_ar AS test_name, t.loinc_code,
                    u.full_name AS patient_name,
                    doc_u.full_name AS doctor_name
             FROM duplicate_alerts da
             JOIN test_orders o ON da.order_id = o.id
             JOIN tests_catalog t ON o.test_id = t.id
             JOIN users u ON o.patient_id = u.id
             LEFT JOIN doctors d ON o.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             ORDER BY da.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function stats()
    {
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM duplicate_alerts");
        $prevented = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM duplicate_alerts WHERE doctor_decision IN ('cancel','use_previous')"
        );
        return ['total' => $total, 'prevented' => $prevented];
    }
}
