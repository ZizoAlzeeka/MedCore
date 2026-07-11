<?php
class LabTechController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('lab_tech');
    }

    public function dashboard()
    {
        $stats = [
            'pending' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE status='ordered'"),
            'uploaded_today' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_results WHERE DATE(uploaded_at)=CURDATE()"),
            'uploaded_total' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_results"),
        ];
        $pending = (new TestOrder())->pendingForLab();
        $pending = array_slice($pending, 0, 10);
        $title = 'لوحة فني المختبر';
        viewWithLayout('labtech/dashboard', compact('stats', 'pending', 'title'));
    }

    public function orders()
    {
        $status = $_GET['status'] ?? 'ordered';
        $valid = ['ordered', 'in_progress', 'result_uploaded', 'cancelled', 'duplicate_skipped'];
        if (!in_array($status, $valid)) $status = 'ordered';

        $orders = Database::fetchAll(
            "SELECT o.*, t.name_ar, t.name_en, t.loinc_code, t.sample_type,
                    u.full_name AS patient_name, u.unique_id AS patient_uid, u.phone,
                    doc_u.full_name AS doctor_name
             FROM test_orders o
             JOIN tests_catalog t ON o.test_id = t.id
             JOIN users u ON o.patient_id = u.id
             LEFT JOIN doctors d ON o.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             WHERE o.status = ?
             ORDER BY o.ordered_at " . ($status === 'ordered' ? 'ASC' : 'DESC'),
            [$status]
        );
        $title = 'الطلبات — ' . statusLabel($status);
        viewWithLayout('labtech/orders', compact('orders', 'status', 'title'));
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
