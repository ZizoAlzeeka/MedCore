<?php
class PatientController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('patient');
    }

    public function dashboard()
    {
        $userId = Auth::id();
        $stats = [
            'total_tests' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ?", [$userId]),
            'pending' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='ordered'", [$userId]),
            'completed' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='result_uploaded'", [$userId]),
            'treatments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM treatment_plans WHERE patient_id = ?", [$userId]),
        ];
        $recentOrders = (new TestOrder())->forPatient($userId);
        $recentOrders = array_slice($recentOrders, 0, 6);
        $latestTreatment = (new TreatmentPlan())->latestForPatient($userId);
        $upcomingAppts = Database::fetchAll(
            "SELECT a.*, doc_u.full_name AS doctor_name, dep.name_ar AS dept_name
             FROM appointments a
             LEFT JOIN doctors d ON a.doctor_id = d.id
             LEFT JOIN users doc_u ON d.user_id = doc_u.id
             LEFT JOIN departments dep ON d.department_id = dep.id
             WHERE a.patient_id = ? AND a.appt_date >= NOW() AND a.status='booked'
             ORDER BY a.appt_date ASC LIMIT 5",
            [$userId]
        );
        $title = 'لوحة المريض';
        viewWithLayout('patient/dashboard', compact('stats', 'recentOrders', 'latestTreatment', 'upcomingAppts', 'title'));
    }

    public function results()
    {
        $orders = (new TestOrder())->forPatient(Auth::id());
        $title = 'نتائج التحاليل';
        viewWithLayout('patient/results', compact('orders', 'title'));
    }

    public function resultDetail($id)
    {
        $order = (new TestOrder())->findDetail($id);
        if (!$order || $order['patient_id'] != Auth::id()) {
            flash('error', 'غير مصرح'); redirect('/patient/results');
        }
        $title = 'تفاصيل النتيجة';
        viewWithLayout('patient/result_detail', compact('order', 'title'));
    }

    public function treatment()
    {
        $treatments = (new TreatmentPlan())->forPatient(Auth::id());
        $title = 'خطط العلاج';
        viewWithLayout('patient/treatment', compact('treatments', 'title'));
    }

    public function appointments()
    {
        $appts = (new Appointment())->forPatient(Auth::id());
        $title = 'مواعيدي';
        viewWithLayout('patient/appointments', compact('appts', 'title'));
    }

    public function printReport()
    {
        $userId = Auth::id();
        $user = Auth::user();
        $orders = (new TestOrder())->forPatient($userId);
        $orders = array_filter($orders, fn($o) => $o['status'] === 'result_uploaded');
        $latestTreatment = (new TreatmentPlan())->latestForPatient($userId);
        $title = 'طباعة التقرير الطبي';
        viewWithLayout('patient/print_report', compact('user', 'orders', 'latestTreatment', 'title'));
    }
}
