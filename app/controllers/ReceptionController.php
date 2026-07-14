<?php
class ReceptionController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('reception');
    }

    public function dashboard()
    {
        // ⚡ Cache dashboard data in APCu for 30s
        $cacheKey = 'reception_dash';
        $cached = null;
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey);
        }

        if ($cached === false || $cached === null) {
            $counts = Database::fetch(
                "SELECT
                    (SELECT COUNT(*) FROM appointments WHERE DATE(appt_date) = CURDATE() AND status='booked') AS appointments_today,
                    (SELECT COUNT(*) FROM users WHERE role='patient') AS total_patients,
                    (SELECT COUNT(*) FROM doctors) AS total_doctors,
                    (SELECT COUNT(*) FROM departments) AS departments
                "
            );
            $todayAppts = Database::fetchAll(
                "SELECT a.*, u.full_name AS patient_name, u.unique_id AS patient_uid, u.phone,
                        doc_u.full_name AS doctor_name, dep.name_ar AS dept_name
                 FROM appointments a
                 JOIN users u ON a.patient_id = u.id
                 LEFT JOIN doctors d ON a.doctor_id = d.id
                 LEFT JOIN users doc_u ON d.user_id = doc_u.id
                 LEFT JOIN departments dep ON d.department_id = dep.id
                 WHERE DATE(a.appt_date) = CURDATE()
                 ORDER BY a.appt_date ASC LIMIT 20"
            );
            $cached = [
                'stats' => [
                    'appointments_today' => (int) $counts['appointments_today'],
                    'total_patients' => (int) $counts['total_patients'],
                    'total_doctors' => (int) $counts['total_doctors'],
                    'departments' => (int) $counts['departments'],
                ],
                'todayAppts' => $todayAppts,
            ];
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $cached, 30);
            }
        }

        extract($cached);
        $title = 'لوحة الاستقبال';
        viewWithLayout('reception/dashboard', compact('stats', 'todayAppts', 'title'));
    }

    public function searchPatient()
    {
        $q = trim($_GET['q'] ?? '');
        $patients = [];
        if ($q) {
            $qq = "%$q%";
            $patients = Database::fetchAll(
                "SELECT * FROM users WHERE role='patient' AND (full_name LIKE ? OR unique_id LIKE ? OR phone LIKE ? OR email LIKE ?) ORDER BY full_name LIMIT 30",
                [$qq, $qq, $qq, $qq]
            );
        }
        $this->json(['success' => true, 'patients' => $patients]);
    }

    public function showBook()
    {
        $departments = Database::fetchAll("SELECT * FROM departments ORDER BY name_ar");
        $patients = Database::fetchAll("SELECT id, full_name, unique_id, phone FROM users WHERE role='patient' AND is_active=1 ORDER BY full_name");
        $title = 'حجز موعد جديد';
        viewWithLayout('reception/book', compact('departments', 'patients', 'title'));
    }

    public function storeBooking()
    {
        Auth::csrfVerify();
        $patientId = (int) $_POST['patient_id'];
        $doctorId = (int) $_POST['doctor_id'];
        $apptDate = $_POST['appt_date']; // datetime-local format
        $reason = trim($_POST['reason'] ?? '');

        if (!$patientId || !$doctorId || !$apptDate) {
            flash('error', 'بيانات ناقصة'); redirect('/reception/book');
        }

        // Convert datetime-local to MySQL format
        $apptDate = str_replace('T', ' ', $apptDate) . ':00';

        // Check slot is available (no overlap)
        $overlap = Database::fetch(
            "SELECT id FROM appointments WHERE doctor_id = ? AND appt_date = ? AND status='booked'",
            [$doctorId, $apptDate]
        );
        if ($overlap) { flash('error', 'الموعد محجوز بالفعل — اختر وقتاً آخر'); redirect('/reception/book'); }

        $apptId = Database::insert('appointments', [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'receptionist_id' => Auth::id(),
            'appt_date' => $apptDate,
            'status' => 'booked',
            'reason' => $reason,
            'created_at' => now(),
        ]);

        // Notify patient
        (new Notification())->send($patientId, 'appointment_booked', 'حجز موعد جديد', "تم حجز موعد لك. يرجى الحضور في الموعد المحدد.", $apptId);
        Logger::audit('book_appointment', Auth::id(), ['appt_id' => $apptId, 'patient' => $patientId, 'doctor' => $doctorId]);
        flash('success', 'تم حجز الموعد بنجاح');
        redirect('/reception/appointments');
    }

    public function appointments()
    {
        $appts = (new Appointment())->forReception();
        $title = 'قائمة المواعيد';
        viewWithLayout('reception/appointments', compact('appts', 'title'));
    }

    public function cancelAppointment($id)
    {
        Auth::csrfVerify();
        Database::update('appointments', ['status' => 'cancelled'], "id = ?", [$id]);
        flash('success', 'تم إلغاء الموعد');
        redirect('/reception/appointments');
    }

    public function doctorSchedules()
    {
        $doctorId = (int) ($_GET['doctor_id'] ?? 0);
        $doctors = (new User())->doctors();
        $schedules = [];
        $selectedDoctor = null;
        if ($doctorId) {
            $schedules = (new DoctorSchedule())->byDoctor($doctorId);
            $selectedDoctor = Database::fetch("SELECT u.full_name, dep.name_ar AS dept FROM doctors d JOIN users u ON d.user_id=u.id LEFT JOIN departments dep ON d.department_id=dep.id WHERE d.id = ?", [$doctorId]);
        }
        $title = 'جداول الأطباء';
        viewWithLayout('reception/doctor_schedules', compact('doctors', 'doctorId', 'schedules', 'selectedDoctor', 'title'));
    }

    public function registerPatient()
    {
        Auth::csrfVerify();
        $data = $_POST;
        $errors = $this->validate([
            'full_name' => 'required|min:3',
            'email' => 'required|email',
            'phone' => 'required|numeric_en',
            'gender' => 'required|in:male,female',
        ]);
        if ($errors) { flash('error', implode(' | ', $errors)); redirect('/reception/book'); }
        $exists = Database::fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($exists) { flash('error', 'البريد مستخدم'); redirect('/reception/book'); }

        $uid = Auth::generateUniqueId();
        $userId = Database::insert('users', [
            'unique_id' => $uid,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['phone'] ?: '123456', PASSWORD_DEFAULT), // default password = phone
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'],
            'role' => 'patient',
            'is_active' => 1,
            'created_at' => now(),
        ]);
        flash('success', "تم تسجيل المريض. الرقم المميز: $uid");
        redirect('/reception/book');
    }

    // AJAX: get doctors by department
    public function ajaxDoctorsByDept()
    {
        $deptId = (int) ($_GET['department_id'] ?? 0);
        $doctors = $deptId ? (new User())->doctorsByDepartment($deptId) : (new User())->doctors();
        $this->json(['success' => true, 'doctors' => $doctors]);
    }

    // AJAX: get available slots for doctor on date
    public function ajaxDoctorSlots($doctorId)
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $slots = (new DoctorSchedule())->availableSlots($doctorId, $date);
        $this->json(['success' => true, 'slots' => $slots, 'date' => $date]);
    }
}
