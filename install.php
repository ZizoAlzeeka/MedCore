<?php
/**
 * Installer — runs once to:
 * 1) Verify DB connection (MySQL or SQLite — auto-detected from DB_DRIVER)
 * 2) Create all tables (schema.sql for MySQL, schema.sqlite.sql for SQLite)
 * 3) Seed initial data (departments, users, doctors, lab techs, reception, catalog LOINC, settings)
 * 4) Generate passwords and unique IDs
 *
 * Usage: visit http://your-domain/install.php in browser
 * After install: DELETE this file!
 *
 * Switching drivers:
 *   - SQLite → MySQL: set DB_DRIVER=mysql in .env, run install.php
 *   - MySQL → SQLite: set DB_DRIVER=sqlite in .env, run install.php
 *   The seed data is re-inserted only if the users table is empty.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Riyadh');
if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');

// Local helper (install.php does not load config.php)
function e_local($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Load env
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    die('ملف .env غير موجود. يرجى إنشائه أولاً.');
}
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), '"');
}

// Default driver
$driver = strtolower($env['DB_DRIVER'] ?? 'mysql');

$step = $_GET['step'] ?? '1';
$messages = [];
$errors = [];

function genUniqId() {
    return str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
}

function genUniqueUniqId($pdo) {
    do {
        $id = genUniqId();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE unique_id = ?");
        $stmt->execute([$id]);
    } while ($stmt->fetch());
    return $id;
}

/**
 * Better SQL splitter — handles comments and strings
 */
function splitSql($sql) {
    $statements = [];
    $current = '';
    $inString = null;
    $len = strlen($sql);
    $i = 0;
    while ($i < $len) {
        $c = $sql[$i];
        // Line comment
        if ($c === '-' && isset($sql[$i+1]) && $sql[$i+1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            continue;
        }
        // Hash comment
        if ($c === '#') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            continue;
        }
        // String
        if ($c === "'" || $c === '"') {
            if ($inString === null) $inString = $c;
            elseif ($inString === $c) $inString = null;
            $current .= $c;
            $i++;
            continue;
        }
        // End of statement
        if ($c === ';' && $inString === null) {
            $stmt = trim($current);
            if ($stmt !== '') $statements[] = $stmt;
            $current = '';
            $i++;
            continue;
        }
        $current .= $c;
        $i++;
    }
    $final = trim($current);
    if ($final !== '') $statements[] = $final;
    return $statements;
}

/**
 * Connect to DB based on driver
 */
function connectDb($env) {
    $driver = strtolower($env['DB_DRIVER'] ?? 'mysql');
    if ($driver === 'sqlite') {
        $dbPath = $env['DB_PATH'] ?? __DIR__ . '/database/platform.sqlite';
        if ($dbPath[0] !== '/') $dbPath = __DIR__ . '/' . $dbPath;
        // Ensure directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $dsn = "sqlite:{$dbPath}";
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");
        $pdo->exec("PRAGMA encoding = 'UTF-8'");
        return [$pdo, 'sqlite', $dbPath];
    }
    // MySQL
    $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES utf8mb4");
    return [$pdo, 'mysql', $env['DB_NAME'] . '@' . $env['DB_HOST']];
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تثبيت المنصة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{font-family:'Cairo',sans-serif;background:#f0f4f8;padding:40px;}.installer{max-width:900px;margin:0 auto;background:#fff;border-radius:20px;padding:40px;box-shadow:0 10px 40px rgba(108,99,255,.1);}.step-badge{background:linear-gradient(135deg,#6C63FF,#4FC3F7);color:#fff;padding:6px 16px;border-radius:20px;font-size:14px;font-weight:700;}.driver-badge{background:linear-gradient(135deg,#43E97B,#38F9D7);color:#004D40;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}</style>
</head>
<body>
<div class="installer">
<h1 class="mb-3"><i class="bi bi-gear-fill text-primary"></i> تثبيت منصة كشف التحاليل المكررة</h1>
<div class="mb-3"><span class="driver-badge"><i class="bi bi-database"></i> القاعدة: <?= e_local(strtoupper($driver)) ?></span></div>
<hr>

<?php if ($step === '1'): ?>
<h4><span class="step-badge">الخطوة 1</span> اختبار الاتصال بقاعدة البيانات</h4>
<form method="get" action="?step=2">
    <button type="submit" class="btn btn-primary">بدء الاختبار والتثبيت</button>
</form>

<?php elseif ($step === '2'):
    // Step 2: connect + create tables + seed
    try {
        list($pdo, $drv, $connInfo) = connectDb($env);
        $messages[] = "✅ تم الاتصال بنجاح — " . strtoupper($drv) . " ($connInfo)";
    } catch (PDOException $e) {
        $errors[] = "❌ فشل الاتصال: " . $e->getMessage();
    }

    if (empty($errors)) {
        // ===== Run schema =====
        $schemaFile = $drv === 'sqlite'
            ? __DIR__ . '/database/schema.sqlite.sql'
            : __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            $errors[] = "❌ ملف الـ schema غير موجود: $schemaFile";
        } else {
            $schema = file_get_contents($schemaFile);
            $stmts = splitSql($schema);
            $okCount = 0;
            foreach ($stmts as $stmt) {
                if ($stmt === '') continue;
                // Skip PRAGMA foreign_keys (we set it in connectDb for sqlite)
                if (preg_match('/^\s*PRAGMA\s+foreign_keys/i', $stmt)) continue;
                try {
                    $pdo->exec($stmt);
                    $okCount++;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    // Ignore "already exists" errors
                    if (strpos($msg, 'already exists') === false && strpos($msg, 'Duplicate') === false) {
                        $errors[] = "SQL: " . $msg . " | stmt: " . substr($stmt, 0, 120);
                    }
                }
            }
            $messages[] = "✅ تم تنفيذ الـ schema ($okCount جملة)";
        }
    }

    if (empty($errors)) {
        // ===== Seed =====
        if ($drv === 'mysql') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        }

        // Check if already seeded
        $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($check > 0) {
            $messages[] = "ℹ️ القاعدة تحتوي على بيانات بالفعل (" . $check . " مستخدم). تم تخطي الـ seeding.";
        } else {
            // ===== Departments =====
            $departments = [
                ['قسم الباطنة', 'Internal Medicine', 'تشخيص وعلاج أمراض البالغين الداخلية'],
                ['قسم القلب', 'Cardiology', 'أمراض القلب والأوعية الدموية'],
                ['قسم العيون', 'Ophthalmology', 'فحوصات وعلاجات العيون'],
                ['قسم الأطفال', 'Pediatrics', 'رعاية الأطفال الصحية'],
                ['قسم الجهاز الهضمي', 'Gastroenterology', 'أمراض المعدة والأمعاء والكبد'],
                ['قسم النساء والولادة', 'Obstetrics & Gynecology', 'صحة المرأة والولادة'],
            ];
            $deptStmt = $pdo->prepare("INSERT INTO departments (name_ar, name_en, description) VALUES (?,?,?)");
            foreach ($departments as $d) $deptStmt->execute($d);
            $messages[] = "✅ تمت إضافة " . count($departments) . " أقسام طبية";

            // ===== Users =====
            $users = [];

            // Admin
            $users[] = [
                'unique_id' => genUniqueUniqId($pdo),
                'full_name' => 'مدير النظام',
                'email' => 'admin@platform.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'phone' => '0501234567',
                'address' => 'الرياض - المملكة العربية السعودية',
                'birth_date' => '1985-03-15',
                'gender' => 'male',
                'role' => 'admin',
            ];

            // Doctors (10) — assigned to departments
            $doctorsData = [
                ['د. أحمد علي الباطني', 'doctor1@platform.com', '0551111111', '1980-05-10', 'male', 1, 'أمراض الباطنة العامة', 'LIC-1001'],
                ['د. محمد نور السالم', 'doctor2@platform.com', '0552222222', '1978-08-22', 'male', 1, 'الجهاز الهضمي', 'LIC-1002'],
                ['د. خالد عبدالله القلبي', 'doctor3@platform.com', '0553333333', '1975-12-03', 'male', 2, 'أمراض القلب', 'LIC-1003'],
                ['د. منى سعيد الحربي', 'doctor4@platform.com', '0554444444', '1982-04-18', 'female', 2, 'قصور القلب', 'LIC-1004'],
                ['د. ليلى حسن العتيبي', 'doctor5@platform.com', '0555555555', '1985-09-30', 'female', 3, 'شبكية العين', 'LIC-1005'],
                ['د. نوران علي الشمري', 'doctor6@platform.com', '0556666666', '1983-07-12', 'female', 3, 'المياه الزرقاء', 'LIC-1006'],
                ['د. فاطمة الزهراء الأحمدي', 'doctor7@platform.com', '0557777777', '1986-02-25', 'female', 4, 'طب الأطفال العام', 'LIC-1007'],
                ['د. عمر ياسر القحطاني', 'doctor8@platform.com', '0558888888', '1981-11-08', 'male', 4, 'حديثي الولادة', 'LIC-1008'],
                ['د. سامي إبراهيم الدوسري', 'doctor9@platform.com', '0559999999', '1979-06-14', 'male', 5, 'التهابات الكبد', 'LIC-1009'],
                ['د. ريم عبدالعزيز الغامدي', 'doctor10@platform.com', '0551010101', '1984-10-20', 'female', 6, 'النساء والولادة', 'LIC-1010'],
            ];
            foreach ($doctorsData as $dd) {
                $users[] = [
                    'unique_id' => genUniqueUniqId($pdo),
                    'full_name' => $dd[0],
                    'email' => $dd[1],
                    'password_hash' => password_hash('doctor123', PASSWORD_DEFAULT),
                    'phone' => $dd[2],
                    'address' => 'الرياض - الحي الطبي',
                    'birth_date' => $dd[3],
                    'gender' => $dd[4],
                    'role' => 'doctor',
                ];
            }

            // Reception (2)
            $receptionData = [
                ['سارة محمد الاستقبال', 'reception1@platform.com', '0561111111', '1990-01-15', 'female'],
                ['فهد ناصر الاستقبال', 'reception2@platform.com', '0562222222', '1988-09-10', 'male'],
            ];
            foreach ($receptionData as $r) {
                $users[] = [
                    'unique_id' => genUniqueUniqId($pdo),
                    'full_name' => $r[0],
                    'email' => $r[1],
                    'password_hash' => password_hash('reception123', PASSWORD_DEFAULT),
                    'phone' => $r[2],
                    'address' => 'الرياض - المستشفى',
                    'birth_date' => $r[3],
                    'gender' => $r[4],
                    'role' => 'reception',
                ];
            }

            // Lab techs (2)
            $labData = [
                ['م. أحمد المختبر', 'lab1@platform.com', '0571111111', '1987-04-05', 'male'],
                ['م. نورة المخبرية', 'lab2@platform.com', '0572222222', '1989-11-22', 'female'],
            ];
            foreach ($labData as $l) {
                $users[] = [
                    'unique_id' => genUniqueUniqId($pdo),
                    'full_name' => $l[0],
                    'email' => $l[1],
                    'password_hash' => password_hash('lab123', PASSWORD_DEFAULT),
                    'phone' => $l[2],
                    'address' => 'الرياض - المختبر',
                    'birth_date' => $l[3],
                    'gender' => $l[4],
                    'role' => 'lab_tech',
                ];
            }

            // Patients (12)
            $patientNames = [
                ['سارة أحمد المالكي', '1995-03-12', 'female'],
                ['محمد علي العنزي', '1990-07-25', 'male'],
                ['فاطمة عبدالله السبيعي', '1988-11-30', 'female'],
                ['عبدالرحمن خالد المطيري', '1992-05-18', 'male'],
                ['نورة سعد الدوسري', '1996-02-14', 'female'],
                ['خالد إبراهيم الشهري', '1985-09-08', 'male'],
                ['ريم محمد البقمي', '1998-12-03', 'female'],
                ['أحمد فهد الحربي', '1983-06-20', 'male'],
                ['هند ناصر العتيبي', '1994-08-17', 'female'],
                ['سلطان عبدالعزيز القرني', '1987-04-22', 'male'],
                ['العنود سعيد الزهراني', '1997-10-11', 'female'],
                ['ماجد عبدالله الغامدي', '1991-01-28', 'male'],
            ];
            foreach ($patientNames as $idx => $pn) {
                $users[] = [
                    'unique_id' => genUniqueUniqId($pdo),
                    'full_name' => $pn[0],
                    'email' => "patient" . ($idx+1) . "@platform.com",
                    'password_hash' => password_hash('patient123', PASSWORD_DEFAULT),
                    'phone' => '058' . str_pad((string)($idx+1), 7, '0', STR_PAD_LEFT),
                    'address' => 'الرياض - حي ' . ['النخيل','العليا','الملقا','الورود','الياسمين','الروضة'][$idx % 6],
                    'birth_date' => $pn[1],
                    'gender' => $pn[2],
                    'role' => 'patient',
                ];
            }

            // Insert all users
            $uStmt = $pdo->prepare("INSERT INTO users (unique_id, full_name, email, password_hash, phone, address, birth_date, gender, role, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,1,?)");
            $now = date('Y-m-d H:i:s');
            foreach ($users as $u) {
                $uStmt->execute([$u['unique_id'], $u['full_name'], $u['email'], $u['password_hash'], $u['phone'], $u['address'], $u['birth_date'], $u['gender'], $u['role'], $now]);
            }
            $messages[] = "✅ تمت إضافة " . count($users) . " مستخدم (1 أدمن + 10 أطباء + 2 استقبال + 2 مختبر + 12 مريض)";

            // ===== Doctors info =====
            $docInsertStmt = $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialty, license_no) VALUES (?,?,?,?)");
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            foreach ($doctorsData as $dd) {
                $userStmt->execute([$dd[1]]);
                $userId = $userStmt->fetchColumn();
                $docInsertStmt->execute([$userId, $dd[5], $dd[6], $dd[7]]);
            }
            $messages[] = "✅ تمت إضافة بيانات الأطباء للأقسام";

            // ===== Doctor schedules (sample) =====
            $schedStmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, work_date, day_of_week, start_time, end_time, slot_duration_min, is_available) VALUES (?,?,?,?,?,?,1)");
            $docIds = $pdo->query("SELECT id, user_id FROM doctors")->fetchAll();
            $today = date('Y-m-d');
            foreach ($docIds as $idx => $dinfo) {
                // Add 6 days of schedule for each doctor
                for ($i = 0; $i < 6; $i++) {
                    $date = date('Y-m-d', strtotime("+$i day", strtotime($today)));
                    $dow = strtolower(date('D', strtotime($date)));
                    $dowMap = ['Sat'=>'sat','Sun'=>'sun','Mon'=>'mon','Tue'=>'tue','Wed'=>'wed','Thu'=>'thu','Fri'=>'fri'];
                    $dow = $dowMap[$dow] ?? 'mon';
                    if ($dow === 'fri' && $idx % 2 === 0) continue;
                    $schedStmt->execute([$dinfo['id'], $date, $dow, '09:00', '13:00', 20]);
                    if ($idx % 2 === 0) {
                        $schedStmt->execute([$dinfo['id'], $date, $dow, '16:00', '20:00', 20]);
                    }
                }
            }
            $messages[] = "✅ تمت إضافة جداول دوام للأطباء";

            // ===== Tests Catalog (LOINC) — 25 unique tests =====
            $tests = [
                ['58410-2', 'صورة دم كاملة (CBC)', 'CBC Panel', 'أمراض الدم', 'دم وريدي'],
                ['6690-2', 'عد كريات الدم البيضاء', 'WBC Count', 'أمراض الدم', 'دم وريدي'],
                ['789-8', 'عد كريات الدم الحمراء', 'RBC Count', 'أمراض الدم', 'دم وريدي'],
                ['4544-3', 'هيموجلوبين', 'Hemoglobin', 'أمراض الدم', 'دم وريدي'],
                ['2345-7', 'سكر صائم', 'Glucose Fasting', 'الكيمياء الحيوية', 'بلازما'],
                ['2339-0', 'سكر بعد الأكل', 'Glucose Postprandial', 'الكيمياء الحيوية', 'بلازما'],
                ['33914-3', 'وظائف الكبد الشاملة', 'Liver Panel', 'الكيمياء الحيوية', 'مصل'],
                ['6768-6', 'ألانين أمينوترانسفيراز', 'ALT', 'الكيمياء الحيوية', 'مصل'],
                ['1742-6', 'أسبارتات أمينوترانسفيراز', 'AST', 'الكيمياء الحيوية', 'مصل'],
                ['1975-2', 'بيليروبين كلي', 'Bilirubin Total', 'الكيمياء الحيوية', 'مصل'],
                ['1751-7', 'بيليروبين مباشر', 'Bilirubin Direct', 'الكيمياء الحيوية', 'مصل'],
                ['2160-0', 'الكرياتينين', 'Creatinine', 'وظائف الكلى', 'مصل'],
                ['3094-0', 'يوريا', 'Urea', 'وظائف الكلى', 'مصل'],
                ['2951-2', 'صوديوم', 'Sodium', 'الكيمياء الحيوية', 'مصل'],
                ['2823-3', 'بوتاسيوم', 'Potassium', 'الكيمياء الحيوية', 'مصل'],
                ['2069-3', 'كلوريد', 'Chloride', 'الكيمياء الحيوية', 'مصل'],
                ['17861-6', 'كالسيوم', 'Calcium', 'الكيمياء الحيوية', 'مصل'],
                ['2921-8', 'حديد', 'Iron', 'أمراض الدم', 'مصل'],
                ['2284-8', 'فيريتين', 'Ferritin', 'أمراض الدم', 'مصل'],
                ['33959-8', 'فحص البروتين الكلي', 'Total Protein', 'الكيمياء الحيوية', 'مصل'],
                ['2885-2', 'ألبومين', 'Albumin', 'الكيمياء الحيوية', 'مصل'],
                ['30934-4', 'تحليل بول شامل', 'Urinalysis', 'التحاليل العامة', 'بول'],
                ['25428-4', 'دهون ثلاثية', 'Triglycerides', 'الدهون', 'مصل'],
                ['2093-3', 'كوليسترول كلي', 'Cholesterol Total', 'الدهون', 'مصل'],
                ['2089-1', 'كوليسترول HDL', 'HDL Cholesterol', 'الدهون', 'مصل'],
            ];
            $tStmt = $pdo->prepare("INSERT INTO tests_catalog (loinc_code, name_ar, name_en, category, sample_type) VALUES (?,?,?,?,?)");
            $insertedTests = 0;
            foreach ($tests as $t) {
                try {
                    $tStmt->execute($t);
                    $insertedTests++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'UNIQUE') === false) throw $e;
                }
            }
            $messages[] = "✅ تمت إضافة $insertedTests تحليل لكتالوج LOINC (من " . count($tests) . " المحددة)";

            // ===== Settings =====
            $settingStmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
            $settingStmt->execute(['duplicate_window_days', '30']);
            $settingStmt->execute(['site_name', $env['APP_NAME'] ?? 'منصة كشف التحاليل المكررة']);
            $messages[] = "✅ تمت إضافة الإعدادات الافتراضية";

            if ($drv === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        }

        // Create logs directory
        @mkdir(__DIR__ . '/logs', 0775, true);
        $messages[] = "✅ تم إنشاء مجلد logs";

    }
    ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5><i class="bi bi-exclamation-triangle"></i> حدثت أخطاء:</h5>
            <ul><?php foreach ($errors as $e): ?><li><?= e_local($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <h5><i class="bi bi-check-circle"></i> اكتمل التثبيت بنجاح!</h5>
            <ul><?php foreach ($messages as $m): ?><li><?= e_local($m) ?></li><?php endforeach; ?></ul>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <strong><i class="bi bi-key"></i> بيانات الدخول الافتراضية</strong>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>الدور</th><th>البريد</th><th>كلمة المرور</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>مدير</td><td>admin@platform.com</td><td>admin123</td></tr>
                        <tr><td>طبيب</td><td>doctor1@platform.com</td><td>doctor123</td></tr>
                        <tr><td>استقبال</td><td>reception1@platform.com</td><td>reception123</td></tr>
                        <tr><td>فني مختبر</td><td>lab1@platform.com</td><td>lab123</td></tr>
                        <tr><td>مريض</td><td>patient1@platform.com</td><td>patient123</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <strong><i class="bi bi-exclamation-triangle"></i> مهم:</strong>
            احذف ملف <code>install.php</code> بعد التثبيت لأسباب أمنية.
        </div>

        <a href="<?= e_local($env['APP_URL'] ?? '/') ?>" class="btn btn-primary btn-lg">
            <i class="bi bi-house-door"></i> الذهاب للمنصة
        </a>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
