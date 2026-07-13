<?php
class ProfileController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['admin', 'doctor', 'reception', 'lab_tech', 'patient']);
    }

    public function show()
    {
        $user = Auth::user();
        $doctor = null;
        $medicalSummary = null;

        if ($user['role'] === 'doctor') {
            $doctor = Database::fetch("SELECT d.*, dep.name_ar AS dept_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.user_id = ?", [Auth::id()]);
        }

        // ⚡ For patients: show medical summary (tests, treatments, appointments counts)
        if ($user['role'] === 'patient') {
            $userId = Auth::id();
            $medicalSummary = [
                'total_tests' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ?", [$userId]),
                'completed_tests' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='result_uploaded'", [$userId]),
                'pending_tests' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='ordered'", [$userId]),
                'treatments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM treatment_plans WHERE patient_id = ?", [$userId]),
                'appointments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM appointments WHERE patient_id = ?", [$userId]),
                'upcoming_appointments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appt_date >= NOW() AND status='booked'", [$userId]),
                'unread_notifications' => (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]),
                'last_visit' => Database::fetchColumn("SELECT MAX(appt_date) FROM appointments WHERE patient_id = ? AND status IN ('completed','booked')", [$userId]),
                'referrals_received' => (int) Database::fetchColumn("SELECT COUNT(*) FROM referrals WHERE patient_id = ?", [$userId]),
            ];
        }

        // ⚡ For staff: show work summary
        if (in_array($user['role'], ['doctor', 'reception', 'lab_tech', 'admin'])) {
            $userId = Auth::id();
            $workSummary = [];

            if ($user['role'] === 'doctor') {
                $doctorId = $doctor['id'] ?? 0;
                $workSummary = [
                    'total_orders' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE doctor_id = ?", [$doctorId]),
                    'patients_count' => (int) Database::fetchColumn("SELECT COUNT(DISTINCT patient_id) FROM test_orders WHERE doctor_id = ?", [$doctorId]),
                    'treatments_written' => (int) Database::fetchColumn("SELECT COUNT(*) FROM treatment_plans WHERE doctor_id = ?", [$doctorId]),
                    'appointments_today' => (int) Database::fetchColumn("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appt_date) = CURDATE() AND status='booked'", [$doctorId]),
                    'referrals_made' => (int) Database::fetchColumn("SELECT COUNT(*) FROM referrals WHERE from_doctor_id = ?", [$doctorId]),
                ];
            } elseif ($user['role'] === 'reception') {
                $workSummary = [
                    'booked_appointments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM appointments WHERE receptionist_id = ?", [$userId]),
                    'registered_patients' => (int) Database::fetchColumn("SELECT COUNT(*) FROM users WHERE unique_id IS NOT NULL"),
                ];
            } elseif ($user['role'] === 'lab_tech') {
                $workSummary = [
                    'uploaded_results' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_results WHERE lab_tech_id = ?", [$userId]),
                    'uploaded_today' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_results WHERE lab_tech_id = ? AND DATE(uploaded_at) = CURDATE()", [$userId]),
                ];
            }

            $user['workSummary'] = $workSummary;
        }

        $title = 'الملف الشخصي';
        viewWithLayout('profile/show', compact('user', 'doctor', 'medicalSummary', 'title'));
    }

    public function update()
    {
        Auth::csrfVerify();
        $data = $_POST;
        $update = [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
        ];
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) { flash('error', 'كلمة المرور قصيرة'); redirect('/profile'); }
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        Database::update('users', $update, "id = ?", [Auth::id()]);
        // Update session name
        $_SESSION['name'] = $data['full_name'];
        Logger::audit('update_profile', Auth::id());
        flash('success', 'تم تحديث الملف الشخصي');
        redirect('/profile');
    }
}
