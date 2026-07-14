<?php
class LabTechController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('lab_tech');
    }

    public function dashboard()
    {
        // ⚡ Cache dashboard data in APCu for 30s
        $cacheKey = 'labtech_dash';
        $cached = null;
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey);
        }

        if ($cached === false || $cached === null) {
            $counts = Database::fetch(
                "SELECT
                    (SELECT COUNT(*) FROM test_orders WHERE status='ordered') AS pending,
                    (SELECT COUNT(*) FROM test_results WHERE DATE(uploaded_at)=CURDATE()) AS uploaded_today,
                    (SELECT COUNT(*) FROM test_results) AS uploaded_total
                "
            );
            $pending = (new TestOrder())->pendingForLab();
            $pending = array_slice($pending, 0, 10);

            $cached = [
                'stats' => [
                    'pending' => (int) $counts['pending'],
                    'uploaded_today' => (int) $counts['uploaded_today'],
                    'uploaded_total' => (int) $counts['uploaded_total'],
                ],
                'pending' => $pending,
            ];
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $cached, 30);
            }
        }

        extract($cached);
        $title = 'لوحة فني المختبر';
        viewWithLayout('labtech/dashboard', compact('stats', 'pending', 'title'));
    }

    public function orders()
    {
        // ⚡ Fetch ALL orders — status filtering is now client-side.
        // Results data is also fetched so the lab tech can view completed results.
        $cacheKey = 'labtech_orders_all';
        $orders = null;
        if (function_exists('apcu_fetch')) {
            $orders = apcu_fetch($cacheKey);
        }
        if ($orders === false || $orders === null) {
            $orders = Database::fetchAll(
                "SELECT o.*, t.name_ar, t.name_en, t.loinc_code, t.sample_type,
                        u.full_name AS patient_name, u.unique_id AS patient_uid, u.phone,
                        doc_u.full_name AS doctor_name,
                        r.result_value, r.unit, r.normal_range, r.flag, r.performed_at, r.uploaded_at,
                        r.notes AS result_notes, lt_u.full_name AS lab_tech_name
                 FROM test_orders o
                 JOIN tests_catalog t ON o.test_id = t.id
                 JOIN users u ON o.patient_id = u.id
                 LEFT JOIN doctors d ON o.doctor_id = d.id
                 LEFT JOIN users doc_u ON d.user_id = doc_u.id
                 LEFT JOIN test_results r ON o.id = r.order_id
                 LEFT JOIN users lt_u ON r.lab_tech_id = lt_u.id
                 ORDER BY o.ordered_at DESC
                 LIMIT 500"
            );
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $orders, 15);
            }
        }

        $title = 'الطلبات الواردة';
        viewWithLayout('labtech/orders', compact('orders', 'title'));
    }

    public function showUpload($id)
    {
        $order = (new TestOrder())->findDetail($id);
        if (!$order) { flash('error', 'الطلب غير موجود'); redirect('/labtech/orders'); }
        if ($order['status'] !== 'ordered') {
            flash('error', 'لا يمكن رفع نتيجة لطلب بحالة: ' . statusLabel($order['status']));
            redirect('/labtech/orders');
        }
        $title = 'رفع نتيجة التحليل';
        viewWithLayout('labtech/upload', compact('order', 'title'));
    }

    public function storeUpload($id)
    {
        Auth::csrfVerify();
        $order = Database::fetch("SELECT * FROM test_orders WHERE id = ?", [$id]);
        if (!$order || $order['status'] !== 'ordered') {
            flash('error', 'الطلب غير صالح'); redirect('/labtech/orders');
        }

        $resultValue = trim($_POST['result_value'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $normalRange = trim($_POST['normal_range'] ?? '');
        $flag = $_POST['flag'] ?? 'normal';
        $performedAt = $_POST['performed_at'] ?? now();
        $notes = trim($_POST['notes'] ?? '');

        if (!$resultValue) { flash('error', 'قيمة النتيجة مطلوبة'); redirect("/labtech/orders/$id/upload"); }

        Database::insert('test_results', [
            'order_id' => $id,
            'lab_tech_id' => Auth::id(),
            'result_value' => $resultValue,
            'unit' => $unit,
            'normal_range' => $normalRange,
            'flag' => $flag,
            'performed_at' => $performedAt,
            'uploaded_at' => now(),
            'notes' => $notes,
        ]);
        Database::update('test_orders', ['status' => 'result_uploaded'], "id = ?", [$id]);

        // Notify patient
        (new Notification())->send($order['patient_id'], 'result_ready', 'نتيجة تحليل جاهزة', "تم رفع نتيجة تحليلك. يمكنك عرضها من صفحتك.", $id);

        // Notify doctor
        $doctorUserId = Database::fetchColumn("SELECT d.user_id FROM doctors d WHERE d.id = ?", [$order['doctor_id']]);
        if ($doctorUserId) {
            $patientName = Database::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$order['patient_id']]);
            (new Notification())->send($doctorUserId, 'result_ready', 'رفع نتيجة تحليل', "تم رفع نتيجة تحليل للمريض: $patientName", $id);
        }

        Logger::audit('upload_result', Auth::id(), ['order_id' => $id, 'patient' => $order['patient_id']]);
        flash('success', 'تم رفع النتيجة وإشعار المريض والطبيب');
        redirect('/labtech/orders');
    }
}
