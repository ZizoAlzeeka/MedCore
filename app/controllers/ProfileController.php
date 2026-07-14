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

        // ⚡ Cache profile data in APCu for 30 seconds per user
        $cacheKey = 'profile_data_' . Auth::id();
        $cached = null;
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey);
        }

        if ($cached === false || $cached === null) {
            if ($user['role'] === 'doctor') {
                $doctor = Database::fetch("SELECT d.*, dep.name_ar AS dept_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.user_id = ?", [Auth::id()]);
            }

            // ⚡ For patients: combine 9 count queries into 1 using subqueries
            if ($user['role'] === 'patient') {
                $userId = Auth::id();
                $counts = Database::fetch(
                    "SELECT
                        (SELECT COUNT(*) FROM test_orders WHERE patient_id = ?) AS total_tests,
                        (SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='result_uploaded') AS completed_tests,
                        (SELECT COUNT(*) FROM test_orders WHERE patient_id = ? AND status='ordered') AS pending_tests,
                        (SELECT COUNT(*) FROM treatment_plans WHERE patient_id = ?) AS treatments,
                        (SELECT COUNT(*) FROM appointments WHERE patient_id = ?) AS appointments,
                        (SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appt_date >= NOW() AND status='booked') AS upcoming_appointments,
                        (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) AS unread_notifications,
                        (SELECT MAX(appt_date) FROM appointments WHERE patient_id = ? AND status IN ('completed','booked')) AS last_visit,
                        (SELECT COUNT(*) FROM referrals WHERE patient_id = ?) AS referrals_received
                    ",
                    [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]
                );
                $medicalSummary = [
                    'total_tests' => (int) $counts['total_tests'],
                    'completed_tests' => (int) $counts['completed_tests'],
                    'pending_tests' => (int) $counts['pending_tests'],
                    'treatments' => (int) $counts['treatments'],
                    'appointments' => (int) $counts['appointments'],
                    'upcoming_appointments' => (int) $counts['upcoming_appointments'],
                    'unread_notifications' => (int) $counts['unread_notifications'],
                    'last_visit' => $counts['last_visit'],
                    'referrals_received' => (int) $counts['referrals_received'],
                ];
            }

            // ⚡ For staff: combine count queries into 1
            $workSummary = [];
            if (in_array($user['role'], ['doctor', 'reception', 'lab_tech', 'admin'])) {
                $userId = Auth::id();
                if ($user['role'] === 'doctor') {
                    $doctorId = $doctor['id'] ?? 0;
                    $counts = Database::fetch(
                        "SELECT
                            (SELECT COUNT(*) FROM test_orders WHERE doctor_id = ?) AS total_orders,
                            (SELECT COUNT(DISTINCT patient_id) FROM test_orders WHERE doctor_id = ?) AS patients_count,
                            (SELECT COUNT(*) FROM treatment_plans WHERE doctor_id = ?) AS treatments_written,
                            (SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appt_date) = CURDATE() AND status='booked') AS appointments_today,
                            (SELECT COUNT(*) FROM referrals WHERE from_doctor_id = ?) AS referrals_made
                        ",
                        [$doctorId, $doctorId, $doctorId, $doctorId, $doctorId]
                    );
                    $workSummary = [
                        'total_orders' => (int) $counts['total_orders'],
                        'patients_count' => (int) $counts['patients_count'],
                        'treatments_written' => (int) $counts['treatments_written'],
                        'appointments_today' => (int) $counts['appointments_today'],
                        'referrals_made' => (int) $counts['referrals_made'],
                    ];
                } elseif ($user['role'] === 'reception') {
                    $counts = Database::fetch(
                        "SELECT
                            (SELECT COUNT(*) FROM appointments WHERE receptionist_id = ?) AS booked_appointments,
                            (SELECT COUNT(*) FROM users WHERE unique_id IS NOT NULL) AS registered_patients
                        ",
                        [$userId]
                    );
                    $workSummary = [
                        'booked_appointments' => (int) $counts['booked_appointments'],
                        'registered_patients' => (int) $counts['registered_patients'],
                    ];
                } elseif ($user['role'] === 'lab_tech') {
                    $counts = Database::fetch(
                        "SELECT
                            (SELECT COUNT(*) FROM test_results WHERE lab_tech_id = ?) AS uploaded_results,
                            (SELECT COUNT(*) FROM test_results WHERE lab_tech_id = ? AND DATE(uploaded_at) = CURDATE()) AS uploaded_today
                        ",
                        [$userId, $userId]
                    );
                    $workSummary = [
                        'uploaded_results' => (int) $counts['uploaded_results'],
                        'uploaded_today' => (int) $counts['uploaded_today'],
                    ];
                }
            }

            $cached = [
                'doctor' => $doctor,
                'medicalSummary' => $medicalSummary,
                'workSummary' => $workSummary,
            ];

            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $cached, 30);
            }
        }

        $doctor = $cached['doctor'];
        $medicalSummary = $cached['medicalSummary'];
        if (!empty($cached['workSummary'])) {
            $user['workSummary'] = $cached['workSummary'];
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
