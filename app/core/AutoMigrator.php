<?php
/**
 * AutoMigrator — runs on every cold start of the app.
 *
 * On each request, checks if the DB schema exists. If not (e.g. fresh
 * deploy, new empty MySQL), automatically:
 *   1) Creates all tables from database/schema.sql (or schema.sqlite.sql)
 *   2) Seeds the initial data (departments, users, doctors, schedules,
 *      tests catalog, settings)
 *
 * This is IDEMPOTENT — if the DB is already populated, it does nothing.
 *
 * Triggered from index.php before any route dispatch.
 */
class AutoMigrator
{
    /**
     * Run auto-migration. Returns an array of human-readable messages.
     * Safe to call on every request — short-circuits if DB is ready.
     */
    public static function runIfNeeded(): array
    {
        $messages = [];

        try {
            $driver = Database::isMysql() ? 'mysql' : 'sqlite';
            $pdo = Database::getInstance()->pdo();

            // ===== Check if migration is needed =====
            $needsMigration = false;
            $needsSeed = false;

            try {
                $tables = Database::tables();
                if (empty($tables)) {
                    $needsMigration = true;
                    $needsSeed = true;
                } elseif (!Database::tableExists('users')) {
                    // Tables exist but `users` is missing — partial schema, run migration
                    $needsMigration = true;
                }

                if (!$needsSeed && in_array('users', $tables, true)) {
                    $count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    if ($count === 0) {
                        $needsSeed = true;
                    }
                }
            } catch (Throwable $e) {
                // Most likely "table doesn't exist" — definitely needs migration
                $needsMigration = true;
                $needsSeed = true;
            }

            if (!$needsMigration && !$needsSeed) {
                return [];
            }

            Logger::info('AutoMigrator: starting (needsMigration=' . ($needsMigration ? '1' : '0') . ', needsSeed=' . ($needsSeed ? '1' : '0') . ')');

            // ===== 1) Run schema =====
            if ($needsMigration) {
                $schemaFile = $driver === 'sqlite'
                    ? dirname(__DIR__, 2) . '/database/schema.sqlite.sql'
                    : dirname(__DIR__, 2) . '/database/schema.sql';

                if (!is_file($schemaFile)) {
                    Logger::error("AutoMigrator: schema file missing: $schemaFile");
                    return ['❌ AutoMigrator: schema file missing: ' . $schemaFile];
                }

                $schema = file_get_contents($schemaFile);
                $stmts = self::splitSql($schema);
                $okCount = 0;
                $skipCount = 0;

                foreach ($stmts as $stmt) {
                    if ($stmt === '') continue;
                    // Skip PRAGMA foreign_keys (already set in Database::connectSqlite)
                    if (preg_match('/^\s*PRAGMA\s+foreign_keys/i', $stmt)) continue;
                    try {
                        $pdo->exec($stmt);
                        $okCount++;
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        // Ignore "already exists" / "Duplicate" errors (idempotent)
                        if (strpos($msg, 'already exists') === false
                            && stripos($msg, 'Duplicate') === false
                            && stripos($msg, '1050') === false /* ER_TABLE_EXISTS_ERROR */
                        ) {
                            Logger::warning('AutoMigrator: SQL stmt failed: ' . substr($stmt, 0, 120) . ' | err: ' . $msg);
                            $skipCount++;
                        }
                    }
                }
                $messages[] = "✅ AutoMigrator: schema executed ($okCount statements, $skipCount skipped)";
                Logger::info("AutoMigrator: schema executed ($okCount ok, $skipCount skipped)");
            }

            // ===== 2) Seed =====
            if ($needsSeed) {
                if ($driver === 'mysql') {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                }

                try {
                    $count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                } catch (Throwable $e) {
                    $count = 0;
                }

                if ($count > 0) {
                    $messages[] = "ℹ️ AutoMigrator: users table already has $count rows, skipping seed";
                } else {
                    $seedMessages = self::seed($pdo);
                    $messages = array_merge($messages, $seedMessages);
                }

                if ($driver === 'mysql') {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                }
            }

            // ===== 3) Ensure logs dir =====
            $logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
                $messages[] = '✅ AutoMigrator: created logs/ directory';
            }

            Logger::info('AutoMigrator: completed successfully');
        } catch (Throwable $e) {
            Logger::error('AutoMigrator failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $messages[] = '❌ AutoMigrator error: ' . $e->getMessage();
        }

        return $messages;
    }

    /**
     * Seed initial data (departments, users, doctors, schedules, tests, settings)
     */
    private static function seed(PDO $pdo): array
    {
        $messages = [];

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
        $messages[] = "✅ AutoMigrator: added " . count($departments) . " departments";

        // ===== Users =====
        $users = [];

        // Admin
        $users[] = [
            'unique_id' => self::genUniqueUniqId($pdo),
            'full_name' => 'مدير النظام',
            'email' => 'admin@platform.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'phone' => '0501234567',
            'address' => 'الرياض - المملكة العربية السعودية',
            'birth_date' => '1985-03-15',
            'gender' => 'male',
            'role' => 'admin',
        ];

        // Doctors (10)
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
                'unique_id' => self::genUniqueUniqId($pdo),
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
                'unique_id' => self::genUniqueUniqId($pdo),
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
                'unique_id' => self::genUniqueUniqId($pdo),
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
                'unique_id' => self::genUniqueUniqId($pdo),
                'full_name' => $pn[0],
                'email' => "patient" . ($idx + 1) . "@platform.com",
                'password_hash' => password_hash('patient123', PASSWORD_DEFAULT),
                'phone' => '058' . str_pad((string)($idx + 1), 7, '0', STR_PAD_LEFT),
                'address' => 'الرياض - حي ' . ['النخيل', 'العليا', 'الملقا', 'الورود', 'الياسمين', 'الروضة'][$idx % 6],
                'birth_date' => $pn[1],
                'gender' => $pn[2],
                'role' => 'patient',
            ];
        }

        $uStmt = $pdo->prepare("INSERT INTO users (unique_id, full_name, email, password_hash, phone, address, birth_date, gender, role, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,1,?)");
        $now = date('Y-m-d H:i:s');
        foreach ($users as $u) {
            $uStmt->execute([$u['unique_id'], $u['full_name'], $u['email'], $u['password_hash'], $u['phone'], $u['address'], $u['birth_date'], $u['gender'], $u['role'], $now]);
        }
        $messages[] = "✅ AutoMigrator: added " . count($users) . " users (1 admin + 10 doctors + 2 reception + 2 lab + 12 patients)";

        // ===== Doctors info =====
        $docInsertStmt = $pdo->prepare("INSERT INTO doctors (user_id, department_id, specialty, license_no) VALUES (?,?,?,?)");
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        foreach ($doctorsData as $dd) {
            $userStmt->execute([$dd[1]]);
            $userId = $userStmt->fetchColumn();
            $docInsertStmt->execute([$userId, $dd[5], $dd[6], $dd[7]]);
        }
        $messages[] = "✅ AutoMigrator: linked doctors to departments";

        // ===== Doctor schedules =====
        $schedStmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, work_date, day_of_week, start_time, end_time, slot_duration_min, is_available) VALUES (?,?,?,?,?,?,1)");
        $docIds = $pdo->query("SELECT id, user_id FROM doctors")->fetchAll();
        $today = date('Y-m-d');
        foreach ($docIds as $idx => $dinfo) {
            for ($i = 0; $i < 6; $i++) {
                $date = date('Y-m-d', strtotime("+$i day", strtotime($today)));
                $dow = strtolower(date('D', strtotime($date)));
                $dowMap = ['Sat' => 'sat', 'Sun' => 'sun', 'Mon' => 'mon', 'Tue' => 'tue', 'Wed' => 'wed', 'Thu' => 'thu', 'Fri' => 'fri'];
                $dow = $dowMap[$dow] ?? 'mon';
                if ($dow === 'fri' && $idx % 2 === 0) continue;
                $schedStmt->execute([$dinfo['id'], $date, $dow, '09:00', '13:00', 20]);
                if ($idx % 2 === 0) {
                    $schedStmt->execute([$dinfo['id'], $date, $dow, '16:00', '20:00', 20]);
                }
            }
        }
        $messages[] = "✅ AutoMigrator: added doctor schedules";

        // ===== Tests Catalog (LOINC) — 25 tests =====
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
                if (stripos($e->getMessage(), 'Duplicate') === false && stripos($e->getMessage(), 'UNIQUE') === false) {
                    throw $e;
                }
            }
        }
        $messages[] = "✅ AutoMigrator: added $insertedTests LOINC tests";

        // ===== Settings =====
        $settingStmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
        $settingStmt->execute(['duplicate_window_days', '30']);
        $settingStmt->execute(['site_name', Env::get('APP_NAME', 'منصة كشف التحاليل المكررة')]);
        $messages[] = "✅ AutoMigrator: added default settings";

        return $messages;
    }

    /**
     * Generate a unique 10-digit ID that doesn't exist in users table yet.
     */
    private static function genUniqueUniqId(PDO $pdo): string
    {
        do {
            $id = str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT id FROM users WHERE unique_id = ?");
            $stmt->execute([$id]);
        } while ($stmt->fetch());
        return $id;
    }

    /**
     * Better SQL splitter — handles comments and strings.
     */
    private static function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = null;
        $len = strlen($sql);
        $i = 0;
        while ($i < $len) {
            $c = $sql[$i];
            if ($c === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                continue;
            }
            if ($c === '#') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                continue;
            }
            if ($c === "'" || $c === '"') {
                if ($inString === null) $inString = $c;
                elseif ($inString === $c) $inString = null;
                $current .= $c;
                $i++;
                continue;
            }
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
}
