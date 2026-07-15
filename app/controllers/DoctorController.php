<?php
class DoctorController extends Controller
{
    private $doctorId;

    public function __construct()
    {
        Auth::requireRole('doctor');
        // ⚡ Cache doctor ID lookup in APCu (was querying DB on EVERY doctor page load)
        $cacheKey = 'doctor_id_' . Auth::id();
        $docId = null;
        if (function_exists('apcu_fetch')) {
            $docId = apcu_fetch($cacheKey);
        }
        if ($docId === false || $docId === null) {
            $doc = Database::fetch("SELECT * FROM doctors WHERE user_id = ?", [Auth::id()]);
            if (!$doc) { flash('error', 'بيانات الطبيب غير مكتملة'); redirect('/logout'); }
            $docId = $doc['id'];
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $docId, 300);
            }
        }
        $this->doctorId = $docId;
    }

    public function dashboard()
    {
        // ⚡ Cache dashboard data in APCu for 30s
        $cacheKey = 'doctor_dash_' . $this->doctorId;
        $cached = null;
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey);
        }

        if ($cached === false || $cached === null) {
            $todayAppts = (new Appointment())->todayForDoctor($this->doctorId);
            // ⚡ Combine 3 count queries into 1
            $counts = Database::fetch(
                "SELECT
                    (SELECT COUNT(DISTINCT patient_id) FROM test_orders WHERE doctor_id = ?) AS total_patients,
                    (SELECT COUNT(*) FROM test_orders WHERE doctor_id = ? AND status='ordered') AS pending_orders,
                    (SELECT COUNT(*) FROM test_orders o JOIN test_results r ON o.id = r.order_id WHERE o.doctor_id = ? AND DATE(r.uploaded_at) = CURDATE()) AS uploaded_today
                ",
                [$this->doctorId, $this->doctorId, $this->doctorId]
            );
            $recentOrders = (new TestOrder())->forDoctor($this->doctorId);
            $recentOrders = array_slice($recentOrders, 0, 8);

            $cached = [
                'todayAppts' => $todayAppts,
                'totalPatients' => (int) $counts['total_patients'],
                'pendingOrders' => (int) $counts['pending_orders'],
                'uploadedToday' => (int) $counts['uploaded_today'],
                'recentOrders' => $recentOrders,
            ];
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $cached, 30);
            }
        }

        extract($cached);
        $title = 'لوحة تحكم الطبيب';
        viewWithLayout('doctor/dashboard', compact('todayAppts', 'totalPatients', 'pendingOrders', 'uploadedToday', 'recentOrders', 'title'));
    }

    public function appointments()
    {
        $appts = (new Appointment())->forDoctor($this->doctorId);
        $title = 'مواعيدي';
        viewWithLayout('doctor/appointments', compact('appts', 'title'));
    }

    public function patients()
    {
        // ⚡ Fetch ALL patients — filtering is now done client-side (live search)
        $patients = Database::fetchAll(
            "SELECT DISTINCT u.* FROM users u
             JOIN test_orders o ON u.id = o.patient_id
             WHERE o.doctor_id = ?
             ORDER BY u.full_name",
            [$this->doctorId]
        );
        // Also include patients from referrals to me
        $referred = Database::fetchAll(
            "SELECT DISTINCT u.* FROM users u
             JOIN referrals r ON u.id = r.patient_id
             WHERE r.to_doctor_id = ?
             ORDER BY u.full_name",
            [$this->doctorId]
        );
        $title = 'مرضاي';
        viewWithLayout('doctor/patients', compact('patients', 'referred', 'title'));
    }

    public function patientProfile($id)
    {
        $patient = Database::fetch("SELECT * FROM users WHERE id = ? AND role='patient'", [$id]);
        if (!$patient) { flash('error', 'المريض غير موجود'); redirect('/doctor/patients'); }
        $orders = (new TestOrder())->forPatient($id);
        $treatments = (new TreatmentPlan())->forPatient($id);
        $referrals = (new Referral())->forPatient($id);
        // List of all active doctors for the referral dropdown (exclude current doctor in the view)
        $allDoctors = (new User())->doctors();
        $currentDoctorId = $this->doctorId;
        $title = 'ملف المريض';
        viewWithLayout('doctor/patient_profile', compact('patient', 'orders', 'treatments', 'referrals', 'allDoctors', 'currentDoctorId', 'title'));
    }

    public function showOrderTest($id)
    {
        $patient = Database::fetch("SELECT * FROM users WHERE id = ? AND role='patient'", [$id]);
        if (!$patient) { flash('error', 'المريض غير موجود'); redirect('/doctor/patients'); }
        $recentOrders = (new TestOrder())->forPatient($id);
        $recentOrders = array_slice($recentOrders, 0, 5);
        $title = 'طلب تحاليل جديد';
        viewWithLayout('doctor/order_test', compact('patient', 'recentOrders', 'title'));
    }

    public function storeOrderTest($id)
    {
        Auth::csrfVerify();
        $testId = (int) ($_POST['test_id'] ?? 0);
        $diagnosis = trim($_POST['diagnosis_icd'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $decision = $_POST['decision'] ?? 'proceed'; // proceed | cancel | use_previous

        if (!$testId) { flash('error', 'اختر تحليلاً'); redirect("/doctor/patients/$id/order-test"); }

        // Check duplicate
        $test = Database::fetch("SELECT * FROM tests_catalog WHERE id = ?", [$testId]);
        $setting = new Setting();
        $windowDays = $setting->getDuplicateWindowDays();
        $dup = (new TestOrder())->checkDuplicate($id, $test['loinc_code'], $windowDays);

        if ($dup && $decision === 'proceed') {
            // Doctor chose to proceed anyway — show alert first
            flash('error', 'هذا التحليل مكرر! اضغط زر "تنفيذ مع التكرار" للتأكيد أو اختر "اكتفاء بالسابق".');
            redirect("/doctor/patients/$id/order-test");
        }

        if ($dup && $decision === 'use_previous') {
            // Skip the order
            $orderId = Database::insert('test_orders', [
                'patient_id' => $id,
                'doctor_id' => $this->doctorId,
                'test_id' => $testId,
                'diagnosis_icd' => $diagnosis,
                'status' => 'duplicate_skipped',
                'ordered_at' => now(),
                'notes' => $notes,
            ]);
            Database::insert('duplicate_alerts', [
                'order_id' => $orderId,
                'prev_order_id' => $dup['id'],
                'days_diff' => (int) ((time() - strtotime($dup['ordered_at'])) / 86400),
                'doctor_decision' => 'use_previous',
                'created_at' => now(),
            ]);
            Logger::audit('duplicate_skipped', Auth::id(), ['patient' => $id, 'test' => $testId]);
            flash('success', 'تم الاكتفاء بالنتيجة السابقة — تم تسجيل القرار');
            redirect("/doctor/patients/$id");
        }

        // proceed — create the order
        $orderId = Database::insert('test_orders', [
            'patient_id' => $id,
            'doctor_id' => $this->doctorId,
            'test_id' => $testId,
            'diagnosis_icd' => $diagnosis,
            'status' => 'ordered',
            'ordered_at' => now(),
            'notes' => $notes,
        ]);

        if ($dup) {
            Database::insert('duplicate_alerts', [
                'order_id' => $orderId,
                'prev_order_id' => $dup['id'],
                'days_diff' => (int) ((time() - strtotime($dup['ordered_at'])) / 86400),
                'doctor_decision' => 'proceed',
                'created_at' => now(),
            ]);
        }

        Logger::audit('order_test', Auth::id(), ['order_id' => $orderId, 'patient' => $id]);
        flash('success', 'تم إنشاء طلب التحليل — سيصل للمختبر');
        redirect("/doctor/patients/$id");
    }

    public function orderDecision($id)
    {
        // Used for AJAX decision (not implemented as full route — kept for API)
        $this->json(['success' => false, 'message' => 'استخدم النموذج الكامل']);
    }

    public function schedule()
    {
        $schedules = (new DoctorSchedule())->byDoctor($this->doctorId);
        $title = 'جدولة الدوام';
        viewWithLayout('doctor/schedule', compact('schedules', 'title'));
    }

    public function storeSchedule()
    {
        Auth::csrfVerify();
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $duration = (int) ($_POST['slot_duration_min'] ?? 20);
        if (!$start || !$end) { flash('error', 'بيانات ناقصة'); redirect('/doctor/schedule'); }

        // ⚡ Support multiple dates at once: dates[] array or single work_date
        $dates = $_POST['dates'] ?? [];
        if (!is_array($dates) || empty($dates)) {
            $singleDate = $_POST['work_date'] ?? '';
            if ($singleDate) $dates = [$singleDate];
        }

        if (empty($dates)) { flash('error', 'اختر تاريخاً واحداً على الأقل'); redirect('/doctor/schedule'); }

        $dowMap = ['Sat'=>'sat','Sun'=>'sun','Mon'=>'mon','Tue'=>'tue','Wed'=>'wed','Thu'=>'thu','Fri'=>'fri'];
        $added = 0;
        foreach ($dates as $date) {
            if (!$date) continue;
            $dow = strtolower(date('D', strtotime($date)));
            $dow = $dowMap[$dow] ?? null;
            Database::insert('doctor_schedules', [
                'doctor_id' => $this->doctorId,
                'work_date' => $date,
                'day_of_week' => $dow,
                'start_time' => $start,
                'end_time' => $end,
                'slot_duration_min' => $duration,
                'is_available' => 1,
            ]);
            $added++;
        }
        flash('success', "تمت إضافة $added فترة دوام بنجاح");
        redirect('/doctor/schedule');
    }

    public function deleteSchedule($id)
    {
        Auth::csrfVerify();
        Database::delete('doctor_schedules', "id = ? AND doctor_id = ?", [$id, $this->doctorId]);
        flash('success', 'تم الحذف');
        redirect('/doctor/schedule');
    }

    public function showTreatmentForm($orderId)
    {
        $order = (new TestOrder())->findDetail($orderId);
        if (!$order || (int)$order['doctor_id'] !== $this->doctorId) {
            flash('error', 'غير مصرح'); redirect('/doctor');
        }
        if ($order['status'] !== 'result_uploaded') {
            flash('error', 'لا يمكن إضافة خطة علاج قبل رفع نتيجة التحليل');
            redirect('/doctor/patients/' . $order['patient_id']);
        }
        $title = 'كتابة خطة العلاج';
        viewWithLayout('doctor/treatment_form', compact('order', 'title'));
    }

    public function storeTreatment($orderId)
    {
        Auth::csrfVerify();
        $order = Database::fetch("SELECT * FROM test_orders WHERE id = ?", [$orderId]);
        if (!$order || $order['doctor_id'] != $this->doctorId) {
            flash('error', 'غير مصرح'); redirect('/doctor');
        }
        $name = trim($_POST['treatment_name'] ?? '');
        $html = $_POST['description_html'] ?? '';
        if (!$name || !$html) { flash('error', 'بيانات ناقصة'); redirect("/doctor/orders/$orderId/treatment"); }

        $apptId = Database::fetchColumn("SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ? ORDER BY appt_date DESC LIMIT 1", [$order['patient_id'], $this->doctorId]);
        $tpId = Database::insert('treatment_plans', [
            'patient_id' => $order['patient_id'],
            'doctor_id' => $this->doctorId,
            'appointment_id' => $apptId,
            'treatment_name' => $name,
            'description_html' => sanitizeHtml($html),
            'created_at' => now(),
        ]);

        // Notify patient
        (new Notification())->send($order['patient_id'], 'treatment_added', 'خطة علاج جديدة', "تمت إضافة خطة علاج جديدة من قبل الطبيب: $name", $tpId);

        // Invalidate APCu notification cache for the patient so the badge + toast
        // appear immediately on the next poll (instead of waiting for cache expiry).
        if (function_exists('apcu_delete')) {
            apcu_delete('notif_data_' . $order['patient_id']);
            apcu_delete('notif_count_' . $order['patient_id']);
            apcu_delete('notif_unread_data_' . $order['patient_id']);
        }

        Logger::audit('add_treatment', Auth::id(), ['tp_id' => $tpId, 'patient' => $order['patient_id']]);
        flash('success', 'تم حفظ خطة العلاج وإشعار المريض');
        redirect("/doctor/patients/" . $order['patient_id']);
    }

    public function referPatient($id)
    {
        Auth::csrfVerify();
        $toDoctorId = (int) ($_POST['to_doctor_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if (!$toDoctorId) { flash('error', 'اختر طبيباً للإحالة'); redirect("/doctor/patients/$id"); }
        if ($toDoctorId === $this->doctorId) { flash('error', 'لا يمكن الإحالة لنفس الطبيب'); redirect("/doctor/patients/$id"); }

        // Verify patient exists
        $patient = Database::fetch("SELECT full_name FROM users WHERE id = ? AND role='patient'", [$id]);
        if (!$patient) { flash('error', 'المريض غير موجود'); redirect('/doctor/patients'); }

        $referralId = Database::insert('referrals', [
            'patient_id' => $id,
            'from_doctor_id' => $this->doctorId,
            'to_doctor_id' => $toDoctorId,
            'reason' => $reason,
            'referred_at' => now(),
        ]);

        // Notify the TARGET doctor (to_doctor_id → get user_id from doctors table)
        $toDoctorUserId = Database::fetchColumn("SELECT user_id FROM doctors WHERE id = ?", [$toDoctorId]);
        if ($toDoctorUserId) {
            (new Notification())->send(
                $toDoctorUserId,
                'referral',
                'إحالة مريض جديد',
                "تمت إحالة المريض " . $patient['full_name'] . " إليك" . ($reason ? " — السبب: " . $reason : ''),
                $id
            );
            // Invalidate target doctor's notification APCu cache so the badge updates immediately
            if (function_exists('apcu_delete')) {
                apcu_delete('notif_data_' . $toDoctorUserId);
            }
        }

        Logger::audit('referral', Auth::id(), ['referral_id' => $referralId, 'patient' => $id, 'to_doctor' => $toDoctorId]);
        flash('success', 'تمت الإحالة بنجاح — تم إشعار الطبيب المعني');
        redirect("/doctor/patients/$id");
    }
}
