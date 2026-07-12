<?php
class AdminController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function dashboard()
    {
        $userModel = new User();
        $stats = [
            'doctors' => $userModel->countByRole('doctor'),
            'patients' => $userModel->countByRole('patient'),
            'reception' => $userModel->countByRole('reception'),
            'lab_tech' => $userModel->countByRole('lab_tech'),
            'departments' => (int) Database::fetchColumn("SELECT COUNT(*) FROM departments"),
            'tests' => (int) Database::fetchColumn("SELECT COUNT(*) FROM tests_catalog"),
            'orders_today' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE DATE(ordered_at) = CURDATE()"),
            'appointments_today' => (int) Database::fetchColumn("SELECT COUNT(*) FROM appointments WHERE DATE(appt_date) = CURDATE() AND status='booked'"),
        ];
        $dupStats = (new DuplicateAlert())->stats();
        $recentAlerts = (new DuplicateAlert())->recent(8);
        $recentUsers = Database::fetchAll("SELECT * FROM users ORDER BY id DESC LIMIT 6");

        $title = 'لوحة تحكم المدير';
        viewWithLayout('admin/dashboard', compact('stats', 'dupStats', 'recentAlerts', 'recentUsers', 'title'));
    }

    // ===== Users =====
    public function users()
    {
        $role = $_GET['role'] ?? '';
        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];
        if ($role) { $sql .= " AND role = ?"; $params[] = $role; }
        if ($q) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR unique_id LIKE ? OR phone LIKE ?)";
            $qq = "%$q%"; $params = array_merge($params, [$qq, $qq, $qq, $qq]);
        }
        $sql .= " ORDER BY created_at DESC LIMIT 200";
        $users = Database::fetchAll($sql, $params);
        $title = 'إدارة المستخدمين';
        viewWithLayout('admin/users', compact('users', 'role', 'q', 'title'));
    }

    public function createUser()
    {
        $departments = Database::fetchAll("SELECT * FROM departments ORDER BY name_ar");
        $title = 'إضافة مستخدم';
        viewWithLayout('admin/user_form', compact('departments', 'title'));
    }

    public function storeUser()
    {
        Auth::csrfVerify();
        $data = $_POST;
        $errors = $this->validate([
            'full_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone' => 'required|numeric_en',
            'role' => 'required|in:admin,doctor,reception,lab_tech,patient',
            'gender' => 'required|in:male,female',
        ]);
        if ($errors) { flash('error', implode(' | ', $errors)); redirect('/admin/users/create'); }

        $exists = Database::fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($exists) { flash('error', 'البريد مستخدم'); redirect('/admin/users/create'); }

        $uniqueId = Auth::generateUniqueId();
        $userId = Database::insert('users', [
            'unique_id' => $uniqueId,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'],
            'role' => $data['role'],
            'is_active' => 1,
            'created_at' => now(),
        ]);

        if ($data['role'] === 'doctor' && !empty($data['department_id'])) {
            Database::insert('doctors', [
                'user_id' => $userId,
                'department_id' => $data['department_id'],
                'specialty' => $data['specialty'] ?? null,
                'license_no' => $data['license_no'] ?? null,
            ]);
        }

        Logger::audit('create_user', Auth::id(), ['new_user_id' => $userId, 'role' => $data['role']]);
        flash('success', "تم إضافة المستخدم. الرقم المميز: $uniqueId");
        redirect('/admin/users');
    }

    public function editUser($id)
    {
        $user = Database::fetch("SELECT u.*, d.department_id, d.specialty, d.license_no FROM users u LEFT JOIN doctors d ON u.id = d.user_id WHERE u.id = ?", [$id]);
        if (!$user) { flash('error', 'المستخدم غير موجود'); redirect('/admin/users'); }
        $departments = Database::fetchAll("SELECT * FROM departments ORDER BY name_ar");
        $title = 'تعديل مستخدم';
        viewWithLayout('admin/user_form', compact('user', 'departments', 'title'));
    }

    public function updateUser($id)
    {
        Auth::csrfVerify();
        $data = $_POST;
        $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) { flash('error', 'غير موجود'); redirect('/admin/users'); }

        $update = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'],
        ];
        if (!empty($data['password'])) {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        Database::update('users', $update, "id = ?", [$id]);

        if ($user['role'] === 'doctor') {
            $doc = Database::fetch("SELECT * FROM doctors WHERE user_id = ?", [$id]);
            if ($doc) {
                Database::update('doctors', [
                    'department_id' => $data['department_id'] ?? null,
                    'specialty' => $data['specialty'] ?? null,
                    'license_no' => $data['license_no'] ?? null,
                ], "user_id = ?", [$id]);
            } else if (!empty($data['department_id'])) {
                Database::insert('doctors', [
                    'user_id' => $id,
                    'department_id' => $data['department_id'],
                    'specialty' => $data['specialty'] ?? null,
                    'license_no' => $data['license_no'] ?? null,
                ]);
            }
        }
        Logger::audit('update_user', Auth::id(), ['user_id' => $id]);
        flash('success', 'تم تحديث المستخدم');
        redirect('/admin/users');
    }

    public function toggleUser($id)
    {
        Auth::csrfVerify();
        Database::query("UPDATE users SET is_active = 1 - is_active WHERE id = ?", [$id]);
        flash('success', 'تم تغيير حالة الحساب');
        redirect('/admin/users');
    }

    // ===== Departments =====
    public function departments()
    {
        $depts = (new Department())->allWithDoctors();
        $title = 'الأقسام الطبية';
        viewWithLayout('admin/departments', compact('depts', 'title'));
    }

    public function storeDepartment()
    {
        Auth::csrfVerify();
        $data = $_POST;
        Database::insert('departments', [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
        flash('success', 'تمت إضافة القسم');
        redirect('/admin/departments');
    }

    public function updateDepartment($id)
    {
        Auth::csrfVerify();
        $data = $_POST;
        Database::update('departments', [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'description' => $data['description'] ?? null,
        ], "id = ?", [$id]);
        flash('success', 'تم تحديث القسم');
        redirect('/admin/departments');
    }

    public function deleteDepartment($id)
    {
        Auth::csrfVerify();
        Database::delete('departments', "id = ?", [$id]);
        flash('success', 'تم حذف القسم');
        redirect('/admin/departments');
    }

    // ===== Tests Catalog =====
    public function testsCatalog()
    {
        $q = trim($_GET['q'] ?? '');
        if ($q) {
            $qq = "%$q%";
            $tests = Database::fetchAll(
                "SELECT * FROM tests_catalog WHERE name_ar LIKE ? OR name_en LIKE ? OR loinc_code LIKE ? OR category LIKE ? ORDER BY name_ar",
                [$qq, $qq, $qq, $qq]
            );
        } else {
            $tests = Database::fetchAll("SELECT * FROM tests_catalog ORDER BY name_ar");
        }
        $title = 'كتالوج التحاليل (LOINC)';
        viewWithLayout('admin/tests', compact('tests', 'q', 'title'));
    }

    public function storeTest()
    {
        Auth::csrfVerify();
        $data = $_POST;
        Database::insert('tests_catalog', [
            'loinc_code' => $data['loinc_code'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'category' => $data['category'] ?? null,
            'sample_type' => $data['sample_type'] ?? null,
        ]);
        flash('success', 'تمت إضافة التحليل');
        redirect('/admin/tests');
    }

    public function updateTest($id)
    {
        Auth::csrfVerify();
        $data = $_POST;
        Database::update('tests_catalog', [
            'loinc_code' => $data['loinc_code'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'category' => $data['category'] ?? null,
            'sample_type' => $data['sample_type'] ?? null,
        ], "id = ?", [$id]);
        flash('success', 'تم تحديث التحليل');
        redirect('/admin/tests');
    }

    public function deleteTest($id)
    {
        Auth::csrfVerify();
        try { Database::delete('tests_catalog', "id = ?", [$id]); flash('success', 'تم الحذف'); }
        catch (Exception $e) { flash('error', 'لا يمكن حذف التحليل — مستخدم في طلبات سابقة'); }
        redirect('/admin/tests');
    }

    // ===== Reports =====
    public function reports()
    {
        $dupStats = (new DuplicateAlert())->stats();
        $recentAlerts = (new DuplicateAlert())->recent(50);
        $ordersByStatus = [
            'ordered' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE status='ordered'"),
            'result_uploaded' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE status='result_uploaded'"),
            'cancelled' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE status='cancelled'"),
            'duplicate_skipped' => (int) Database::fetchColumn("SELECT COUNT(*) FROM test_orders WHERE status='duplicate_skipped'"),
        ];
        $deptStats = Database::fetchAll(
            "SELECT dep.name_ar, COUNT(d.id) AS doctors_count
             FROM departments dep
             LEFT JOIN doctors d ON dep.id = d.department_id
             GROUP BY dep.id ORDER BY doctors_count DESC"
        );
        $topDoctors = Database::fetchAll(
            "SELECT u.full_name, COUNT(o.id) AS orders_count
             FROM test_orders o
             JOIN doctors d ON o.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             GROUP BY d.id ORDER BY orders_count DESC LIMIT 5"
        );
        $title = 'التقارير والإحصائيات';
        viewWithLayout('admin/reports', compact('dupStats', 'recentAlerts', 'ordersByStatus', 'deptStats', 'topDoctors', 'title'));
    }

    // ===== Settings =====
    public function settings()
    {
        $setting = new Setting();
        $all = $setting->getAll();
        $title = 'إعدادات النظام';
        viewWithLayout('admin/settings', compact('all', 'title'));
    }

    public function updateSettings()
    {
        Auth::csrfVerify();
        $setting = new Setting();
        $setting->set('duplicate_window_days', (int) ($_POST['duplicate_window_days'] ?? 30));
        $setting->set('site_name', $_POST['site_name'] ?? '');
        flash('success', 'تم تحديث الإعدادات');
        redirect('/admin/settings');
    }

    // ===== Logs =====
    public function logs()
    {
        $logDir = dirname(__DIR__, 2) . '/logs';
        $files = [];
        if (is_dir($logDir)) {
            $allFiles = glob($logDir . '/*.log');
            rsort($allFiles);
            foreach (array_slice($allFiles, 0, 10) as $f) {
                $files[] = [
                    'name' => basename($f),
                    'size' => filesize($f),
                    'modified' => date('Y-m-d H:i:s', filemtime($f)),
                    'lines' => count(file($f)),
                ];
            }
        }
        // Show latest log content
        $latestContent = '';
        if (!empty($files)) {
            $latestFile = $logDir . '/' . $files[0]['name'];
            $latestContent = file_get_contents($latestFile);
            // Last 200 lines
            $lines = explode("\n", $latestContent);
            if (count($lines) > 200) {
                $latestContent = implode("\n", array_slice($lines, -200));
            }
        }
        $title = 'سجل الأخطاء';
        viewWithLayout('admin/logs', compact('files', 'latestContent', 'title'));
    }
}
