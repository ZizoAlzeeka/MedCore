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

            // ===== 4) Always sync tests catalog (idempotent — skips existing) =====
            // This ensures new tests added in code are auto-inserted on next request,
            // even when users table is already populated.
            $catalogMsgs = self::syncTestsCatalog($pdo);
            foreach ($catalogMsgs as $m) {
                $messages[] = $m;
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

        // Tests catalog is now synced by syncTestsCatalog() — called after seed()

        // ===== Settings =====
        $settingStmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
        $settingStmt->execute(['duplicate_window_days', '30']);
        $settingStmt->execute(['site_name', Env::get('APP_NAME', 'منصة كشف التحاليل المكررة')]);
        $messages[] = "✅ AutoMigrator: added default settings";

        return $messages;
    }


    /**
     * Sync tests catalog — always called on every request. Idempotently inserts
     * any new tests not yet in DB (existing tests skipped via Duplicate error handling).
     */
    private static function syncTestsCatalog(PDO $pdo): array
    {
        $messages = [];
        $tests = [

            ['58410-2', 'صورة دم كاملة (CBC)', 'CBC Panel', 'أمراض الدم', 'دم وريدي'],
            ['6690-2', 'عد كريات الدم البيضاء', 'WBC Count', 'أمراض الدم', 'دم وريدي'],
            ['789-8', 'عد كريات الدم الحمراء', 'RBC Count', 'أمراض الدم', 'دم وريدي'],
            ['4544-3', 'هيموجلوبين', 'Hemoglobin', 'أمراض الدم', 'دم وريدي'],
            ['787-2', 'حجم كرية دم حمراء MCV', 'MCV', 'أمراض الدم', 'دم وريدي'],
            ['788-0', 'هيماتوكريت', 'Hematocrit', 'أمراض الدم', 'دم وريدي'],
            ['785-6', 'هيموجلوبين كرية средي MCH', 'MCH', 'أمراض الدم', 'دم وريدي'],
            ['786-4', 'تركيز هيموجلوبين كرى MCHC', 'MCHC', 'أمراض الدم', 'دم وريدي'],
            ['21000-5', 'توزيع كريات حمراء RDW', 'RDW', 'أمراض الدم', 'دم وريدي'],
            ['6742-1', 'وصف كريات حمراء', 'RBC Morphology', 'أمراض الدم', 'دم وريدي'],
            ['26474-7', 'عد النوى المتعادلة NEUT#', 'Neutrophil Count', 'أمراض الدم', 'دم وريدي'],
            ['751-8', 'عد النوى المتعادلة NEUT%', 'Neutrophil %', 'أمراض الدم', 'دم وريدي'],
            ['736-9', 'عد اللمفاويات LYM#', 'Lymphocyte Count', 'أمراض الدم', 'دم وريدي'],
            ['731-0', 'عد اللمفاويات LYM%', 'Lymphocyte %', 'أمراض الدم', 'دم وريدي'],
            ['753-4', 'عد الوحيدات MONO#', 'Monocyte Count', 'أمراض الدم', 'دم وريدي'],
            ['5905-5', 'عد الوحيدات MONO%', 'Monocyte %', 'أمراض الدم', 'دم وريدي'],
            ['713-8', 'عد الحمضات EOS#', 'Eosinophil Count', 'أمراض الدم', 'دم وريدي'],
            ['706-2', 'عد الحمضات EOS%', 'Eosinophil %', 'أمراض الدم', 'دم وريدي'],
            ['704-7', 'عد القاعدات BASO#', 'Basophil Count', 'أمراض الدم', 'دم وريدي'],
            ['26506-0', 'عد الصفائح الدموية', 'Platelet Count', 'أمراض الدم', 'دم وريدي'],
            ['32623-1', 'متوسط حجم الصفيحة MPV', 'MPV', 'أمراض الدم', 'دم وريدي'],
            ['778-1', 'وصف الصفائح', 'Platelet Morphology', 'أمراض الدم', 'دم وريدي'],
            ['26515-1', 'عرض توزيع الصفائح PDW', 'PDW', 'أمراض الدم', 'دم وريدي'],
            ['32202-3', 'اختبار ترسيب كريات الدم ESR', 'Erythrocyte Sed Rate', 'أمراض الدم', 'دم وريدي'],
            ['30385-9', 'ريتيكولوسايت', 'Reticulocyte Count', 'أمراض الدم', 'دم وريدي'],
            ['2284-8', 'فيريتين', 'Ferritin', 'أمراض الدم', 'مصل'],
            ['2921-8', 'حديد', 'Iron', 'أمراض الدم', 'مصل'],
            ['2502-3', 'قدرة ربط الحديد TIBC', 'Iron Binding Capacity', 'أمراض الدم', 'مصل'],
            ['2285-5', 'نسبة إشباع الترانسفيرين', 'Transferrin Saturation', 'أمراض الدم', 'مصل'],
            ['2601-3', 'فيتامين B12', 'Vitamin B12 Level', 'أمراض الدم', 'مصل'],
            ['14978-9', 'حمض الفوليك', 'Folate', 'أمراض الدم', 'مصل'],
            ['30313-0', 'ترانسفيرين', 'Transferrin', 'أمراض الدم', 'مصل'],
            ['2891-0', 'سيساتين C', 'Cystatin C', 'أمراض الدم', 'مصل'],
            ['17855-7', 'هيموجلوبين A2', 'Hemoglobin A2', 'أمراض الدم', 'دم وريدي'],
            ['4575-9', 'هيموجلوبين جنيني HbF', 'Hemoglobin F', 'أمراض الدم', 'دم وريدي'],
            ['32689-2', 'كهرباء الهيموجلوبين', 'Hemoglobin Electrophoresis', 'أمراض الدم', 'دم وريدي'],
            ['3173-2', 'وقت البروثرومبين PT', 'Prothrombin Time', 'التخثر', 'بلازما'],
            ['3255-7', 'INR', 'INR', 'التخثر', 'بلازما'],
            ['3253-2', 'وقت الثرومبوبلاستين الجزئي aPTT', 'aPTT', 'التخثر', 'بلازما'],
            ['3173-2', 'زمن الثرومبين', 'Thrombin Time', 'التخثر', 'بلازما'],
            ['3255-7', 'فايبرينوجين', 'Fibrinogen', 'التخثر', 'بلازما'],
            ['7726-2', 'D-Dimer', 'D-Dimer', 'التخثر', 'بلازما'],
            ['2345-7', 'سكر صائم', 'Glucose Fasting', 'السكري', 'بلازما'],
            ['2339-0', 'سكر بعد الأكل', 'Glucose Postprandial', 'السكري', 'بلازما'],
            ['2344-0', 'سكر عشوائي', 'Glucose Random', 'السكري', 'بلازما'],
            ['41653-7', 'سكر الجلوكوز خلال 2 ساعة OGTT', 'Glucose 2hr OGTT', 'السكري', 'بلازما'],
            ['4544-3', 'هيموجلوبين سكري HbA1c', 'HbA1c', 'السكري', 'دم وريدي'],
            ['33914-3', 'فركتوزامين', 'Fructosamine', 'السكري', 'مصل'],
            ['14978-9', 'إنسولين صائم', 'Insulin Fasting', 'السكري', 'مصل'],
            ['20448-3', 'ببتيد C', 'C-Peptide', 'السكري', 'مصل'],
            ['6768-6', 'ALT', 'ALT', 'وظائف الكبد', 'مصل'],
            ['1742-6', 'AST', 'AST', 'وظائف الكبد', 'مصل'],
            ['1975-2', 'بيليروبين كلي', 'Bilirubin Total', 'وظائف الكبد', 'مصل'],
            ['1968-7', 'بيليروبين مباشر', 'Bilirubin Direct', 'وظائف الكبد', 'مصل'],
            ['1970-3', 'بيليروبين غير مباشر', 'Bilirubin Indirect', 'وظائف الكبد', 'مصل'],
            ['6768-6', 'فوسفاتاز قلوية ALP', 'Alkaline Phosphatase', 'وظائف الكبد', 'مصل'],
            ['2324-2', 'غاما جلوتاميل ترانسفيراز GGT', 'GGT', 'وظائف الكبد', 'مصل'],
            ['2885-2', 'ألبومين', 'Albumin', 'وظائف الكبد', 'مصل'],
            ['33959-8', 'بروتين كلي', 'Total Protein', 'وظائف الكبد', 'مصل'],
            ['1913-0', 'نسبة الألبومين/الجلوبولين', 'Albumin/Globulin Ratio', 'وظائف الكبد', 'مصل'],
            ['6768-6', 'أمونيا', 'Ammonia', 'وظائف الكبد', 'مصل'],
            ['2160-0', 'كرياتينين', 'Creatinine', 'وظائف الكلى', 'مصل'],
            ['3094-0', 'يوريا', 'Urea', 'وظائف الكلى', 'مصل'],
            ['3097-3', 'BUN/كرياتينين', 'BUN/Creatinine Ratio', 'وظائف الكلى', 'مصل'],
            ['33914-3', 'حمض اليوريك', 'Uric Acid', 'وظائف الكلى', 'مصل'],
            ['33914-3', 'فحص الكلى الشامل', 'Renal Panel', 'وظائف الكلى', 'مصل'],
            ['2891-0', 'سيستاتين C', 'Cystatin C', 'وظائف الكلى', 'مصل'],
            ['2951-2', 'صوديوم', 'Sodium', 'الكهارل', 'مصل'],
            ['2823-3', 'بوتاسيوم', 'Potassium', 'الكهارل', 'مصل'],
            ['2069-3', 'كلوريد', 'Chloride', 'الكهارل', 'مصل'],
            ['2947-0', 'بيكربونات HCO3', 'Bicarbonate', 'الكهارل', 'مصل'],
            ['20565-8', 'فجوة أنيونية', 'Anion Gap', 'الكهارل', 'مصل'],
            ['33914-3', 'أسمولالية', 'Osmolality', 'الكهارل', 'مصل'],
            ['17861-6', 'كالسيوم', 'Calcium', 'الكالسيوم والعظام', 'مصل'],
            ['17861-6', 'كالسيوم أيوني', 'Ionized Calcium', 'الكالسيوم والعظام', 'مصل'],
            ['2777-1', 'فوسفات', 'Phosphate', 'الكالسيوم والعظام', 'مصل'],
            ['2731-6', 'باراثورمون PTH', 'Parathyroid Hormone', 'الكالسيوم والعظام', 'مصل'],
            ['14978-9', 'فيتامين D 25-OH', 'Vitamin D 25-OH', 'الكالسيوم والعظام', 'مصل'],
            ['14978-9', 'فيتامين D 1,25-OH', 'Vitamin D 1,25-OH', 'الكالسيوم والعظام', 'مصل'],
            ['32623-1', 'كالسيتونين', 'Calcitonin', 'الكالسيوم والعظام', 'مصل'],
            ['14978-9', 'أوستيوكالسين', 'Osteocalcin', 'الكالسيوم والعظام', 'مصل'],
            ['2093-3', 'كوليسترول كلي', 'Cholesterol Total', 'الدهون', 'مصل'],
            ['2089-1', 'كوليسترول HDL', 'HDL Cholesterol', 'الدهون', 'مصل'],
            ['2086-7', 'كوليسترول LDL', 'LDL Cholesterol', 'الدهون', 'مصل'],
            ['25428-4', 'دهون ثلاثية', 'Triglycerides', 'الدهون', 'مصل'],
            ['2093-3', 'VLDL', 'VLDL Cholesterol', 'الدهون', 'مصل'],
            ['33914-3', 'أبوليبوبروتين A1', 'Apolipoprotein A1', 'الدهون', 'مصل'],
            ['33914-3', 'أبوليبوبروتين B', 'Apolipoprotein B', 'الدهون', 'مصل'],
            ['33914-3', 'ليبوروتين a', 'Lipoprotein a', 'الدهون', 'مصل'],
            ['33914-3', 'فحص الدهون الشامل', 'Lipid Panel', 'الدهون', 'مصل'],
            ['33914-3', 'تروبونين I', 'Troponin I', 'القلب', 'مصل'],
            ['33914-3', 'تروبونين T', 'Troponin T', 'القلب', 'مصل'],
            ['33914-3', 'CPK كلي', 'CPK Total', 'القلب', 'مصل'],
            ['33914-3', 'CPK-MB', 'CPK-MB', 'القلب', 'مصل'],
            ['33914-3', 'مايوغلوبين', 'Myoglobin', 'القلب', 'مصل'],
            ['33914-3', 'BNP', 'BNP', 'القلب', 'بلازما'],
            ['33914-3', 'NT-proBNP', 'NT-proBNP', 'القلب', 'بلازما'],
            ['33914-3', 'هوموسيستين', 'Homocysteine', 'القلب', 'مصل'],
            ['33914-3', 'Lp-PLA2', 'Lp-PLA2', 'القلب', 'مصل'],
            ['3016-3', 'ثيروكسين T4', 'Thyroxine T4', 'الغدة الدرقية', 'مصل'],
            ['3024-7', 'T4 حر', 'Free T4', 'الغدة الدرقية', 'مصل'],
            ['3018-9', 'ترايودوثيرونين T3', 'Triiodothyronine T3', 'الغدة الدرقية', 'مصل'],
            ['3051-0', 'T3 حر', 'Free T3', 'الغدة الدرقية', 'مصل'],
            ['3015-5', 'هرمون منبه للجريب TSH', 'TSH', 'الغدة الدرقية', 'مصل'],
            ['30236-7', 'ثيروغلوبولين', 'Thyroglobulin', 'الغدة الدرقية', 'مصل'],
            ['33914-3', 'أجسام مضادة للبيروكسيديز TPO', 'Anti-TPO', 'الغدة الدرقية', 'مصل'],
            ['33914-3', 'أجسام مضادة لمستقبل TSH', 'TSH Receptor Ab', 'الغدة الدرقية', 'مصل'],
            ['33914-3', 'أجسام مضادة للثيروغلوبولين', 'Anti-Thyroglobulin', 'الغدة الدرقية', 'مصل'],
            ['33914-3', 'فحص الغدة الدرقية الشامل', 'Thyroid Panel', 'الغدة الدرقية', 'مصل'],
            ['33914-3', 'إستراديول E2', 'Estradiol', 'الهرمونات', 'مصل'],
            ['33914-3', 'بروجسترون', 'Progesterone', 'الهرمونات', 'مصل'],
            ['33914-3', 'هرمون منبه للجريب FSH', 'FSH', 'الهرمونات', 'مصل'],
            ['33914-3', 'هرمون ملوتن LH', 'LH', 'الهرمونات', 'مصل'],
            ['33914-3', 'برولاكتين', 'Prolactin', 'الهرمونات', 'مصل'],
            ['33914-3', 'تستوستيرون كلي', 'Testosterone Total', 'الهرمونات', 'مصل'],
            ['33914-3', 'تستوستيرون حر', 'Free Testosterone', 'الهرمونات', 'مصل'],
            ['33914-3', 'DHEA-S', 'DHEA-S', 'الهرمونات', 'مصل'],
            ['33914-3', 'أندروستيرون دايون', 'Androstenedione', 'الهرمونات', 'مصل'],
            ['33914-3', 'هرمون النمو GH', 'Growth Hormone', 'الهرمونات', 'مصل'],
            ['33914-3', 'IGF-1', 'IGF-1', 'الهرمونات', 'مصل'],
            ['33914-3', 'كورتيزول', 'Cortisol', 'الهرمونات', 'مصل'],
            ['33914-3', 'كورتيزول 8 صباحاً', 'Cortisol AM', 'الهرمونات', 'مصل'],
            ['33914-3', 'كورتيزول 11 مساءً', 'Cortisol PM', 'الهرمونات', 'مصل'],
            ['33914-3', 'ACTH', 'ACTH', 'الهرمونات', 'مصل'],
            ['33914-3', 'ألدوستيرون', 'Aldosterone', 'الهرمونات', 'مصل'],
            ['33914-3', 'رينين', 'Renin', 'الهرمونات', 'مصل'],
            ['33914-3', 'بيتا hCG', 'Beta hCG', 'الهرمونات', 'مصل'],
            ['33914-3', 'هرمون مضاد مولري AMH', 'AMH', 'الهرمونات', 'مصل'],
            ['33914-3', 'إنهيبين B', 'Inhibin B', 'الهرمونات', 'مصل'],
            ['33914-3', 'SHBG', 'SHBG', 'الهرمونات', 'مصل'],
            ['33914-3', 'CEA', 'CEA', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'AFP', 'Alpha Fetoprotein', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'CA 125', 'CA 125', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'CA 15-3', 'CA 15-3', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'CA 19-9', 'CA 19-9', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'CA 27-29', 'CA 27-29', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'PSA كلي', 'PSA Total', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'PSA حر', 'PSA Free', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'نسبة PSA الحر/الكلي', 'Free/Total PSA Ratio', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'Beta-2 Microglobulin', 'Beta-2 Microglobulin', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'كروموغرانين A', 'Chromogranin A', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'HE4', 'HE4', 'واسمات الأورام', 'مصل'],
            ['33914-3', 'بروتين سي التفاعلي CRP', 'CRP', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'CRP عالي الحساسية hs-CRP', 'hs-CRP', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'بروكالسيتونين', 'Procalcitonin', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'ألفا-1 أنتي ترابسين', 'Alpha-1 Antitrypsin', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'سيرولوبلازمين', 'Ceruloplasmin', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'هابتوجلوبين', 'Haptoglobin', 'واسمات الالتهاب', 'مصل'],
            ['33914-3', 'عامل روماتويدي RF', 'Rheumatoid Factor', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'أجسام مضادة CCP', 'Anti-CCP', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'ANA', 'ANA', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-dsDNA', 'Anti-dsDNA', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Smith', 'Anti-Smith', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-SSA (Ro)', 'Anti-SSA', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-SSB (La)', 'Anti-SSB', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Scl-70', 'Anti-Scl-70', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Jo-1', 'Anti-Jo-1', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-U1-RNP', 'Anti-U1-RNP', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Centromere', 'Anti-Centromere', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'C3', 'Complement C3', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'C4', 'Complement C4', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'CH50', 'CH50', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Cardiolipin IgG', 'Anti-Cardiolipin IgG', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Cardiolipin IgM', 'Anti-Cardiolipin IgM', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'Lupus Anticoagulant', 'Lupus Anticoagulant', 'المناعة الذاتية', 'بلازما'],
            ['33914-3', 'Beta-2 Glycoprotein I', 'Beta-2 Glycoprotein I', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-MPO (p-ANCA)', 'Anti-MPO', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-PR3 (c-ANCA)', 'Anti-PR3', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Tissue Transglutaminase', 'Anti-tTG', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'anti-Gliadin', 'Anti-Gliadin', 'المناعة الذاتية', 'مصل'],
            ['33914-3', 'HBsAg', 'HBsAg', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBsAb', 'HBs Antibody', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBcAb', 'HBc Antibody', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBcAb IgM', 'HBc IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBeAg', 'HBeAg', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBeAb', 'HBe Antibody', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HBV DNA PCR', 'HBV DNA PCR', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HCV Ab', 'HCV Antibody', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HCV RNA PCR', 'HCV RNA PCR', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HIV 1/2 Ab', 'HIV 1/2 Antibody', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HIV p24 Antigen', 'HIV p24 Antigen', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HIV RNA PCR', 'HIV RNA Viral Load', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Toxoplasma IgG', 'Toxoplasma IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Toxoplasma IgM', 'Toxoplasma IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Rubella IgG', 'Rubella IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Rubella IgM', 'Rubella IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'CMV IgG', 'CMV IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'CMV IgM', 'CMV IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HSV 1 IgG', 'HSV 1 IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'HSV 2 IgG', 'HSV 2 IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'VZV IgG', 'VZV IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'EBV VCA IgG', 'EBV VCA IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'EBV VCA IgM', 'EBV VCA IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'EBV EBNA IgG', 'EBV EBNA IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Mumps IgG', 'Mumps IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Measles IgG', 'Measles IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Treponema pallidum Ab', 'Syphilis Ab', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'RPR', 'RPR', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'TPHA', 'TPHA', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'VDRL', 'VDRL', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Brucella IgG', 'Brucella IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Brucella IgM', 'Brucella IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Widal Test', 'Widal Test', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Dengue IgG', 'Dengue IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Dengue IgM', 'Dengue IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Dengue NS1', 'Dengue NS1 Antigen', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Malaria Smear', 'Malaria Blood Smear', 'الأمراض المعدية', 'دم وريدي'],
            ['33914-3', 'Malaria Antigen', 'Malaria Antigen', 'الأمراض المعدية', 'دم وريدي'],
            ['33914-3', 'SARS-CoV-2 PCR', 'COVID-19 PCR', 'الأمراض المعدية', 'مسحة'],
            ['33914-3', 'SARS-CoV-2 IgG', 'COVID-19 IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'SARS-CoV-2 IgM', 'COVID-19 IgM', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Influenza A/B PCR', 'Flu A/B PCR', 'الأمراض المعدية', 'مسحة'],
            ['33914-3', 'RSV PCR', 'RSV PCR', 'الأمراض المعدية', 'مسحة'],
            ['33914-3', 'Streptococcus A', 'Strep A Antigen', 'الأمراض المعدية', 'مسحة'],
            ['33914-3', 'H. pylori Antigen', 'H. pylori Stool Antigen', 'الأمراض المعدية', 'براز'],
            ['33914-3', 'H. pylori IgG', 'H. pylori IgG', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'Giardia Antigen', 'Giardia Antigen', 'الأمراض المعدية', 'براز'],
            ['33914-3', 'Cryptococcus Antigen', 'Cryptococcal Antigen', 'الأمراض المعدية', 'مصل'],
            ['33914-3', 'فيتامين A', 'Vitamin A', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين E', 'Vitamin E', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين K', 'Vitamin K', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين C', 'Vitamin C', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين B1', 'Vitamin B1', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين B2', 'Vitamin B2', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين B6', 'Vitamin B6', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين B12', 'Vitamin B12', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين D 25-OH', 'Vitamin D 25-OH', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فيتامين D 1,25-OH', 'Vitamin D 1,25-OH', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فولات', 'Folate', 'الفيتامينات', 'مصل'],
            ['33914-3', 'فولات كريات حمراء', 'RBC Folate', 'الفيتامينات', 'دم وريدي'],
            ['33914-3', 'بيوتين', 'Biotin', 'الفيتامينات', 'مصل'],
            ['30934-4', 'تحليل بول شامل', 'Urinalysis', 'تحليل البول', 'بول'],
            ['30945-0', 'لون بول', 'Urine Color', 'تحليل البول', 'بول'],
            ['30934-4', 'وزن نوعي بول', 'Urine Specific Gravity', 'تحليل البول', 'بول'],
            ['30934-4', 'أس هيدروجيني بول', 'Urine pH', 'تحليل البول', 'بول'],
            ['30934-4', 'بروتين بول', 'Urine Protein', 'تحليل البول', 'بول'],
            ['30934-4', 'جلوكوز بول', 'Urine Glucose', 'تحليل البول', 'بول'],
            ['30934-4', 'كيتون بول', 'Urine Ketones', 'تحليل البول', 'بول'],
            ['30934-4', 'دم بول', 'Urine Blood', 'تحليل البول', 'بول'],
            ['30934-4', 'بيليروبين بول', 'Urine Bilirubin', 'تحليل البول', 'بول'],
            ['30934-4', 'يوروبيلينوجين بول', 'Urine Urobilinogen', 'تحليل البول', 'بول'],
            ['30934-4', 'نيتريت بول', 'Urine Nitrite', 'تحليل البول', 'بول'],
            ['30934-4', 'كريات بيضاء بول', 'Urine WBC', 'تحليل البول', 'بول'],
            ['30934-4', 'كريات حمراء بول', 'Urine RBC', 'تحليل البول', 'بول'],
            ['30934-4', 'خلايا ظهارية بول', 'Urine Epithelial Cells', 'تحليل البول', 'بول'],
            ['30934-4', 'بكتيريا بول', 'Urine Bacteria', 'تحليل البول', 'بول'],
            ['30934-4', 'مزارع بول', 'Urine Culture', 'تحليل البول', 'بول'],
            ['30934-4', 'ميكروألبومين بول', 'Microalbumin Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'ميكروألبومين/كرياتينين', 'Microalbumin/Creatinine Ratio', 'تحليل البول', 'بول'],
            ['30934-4', 'كرياتينين بول عشوائي', 'Urine Creatinine Random', 'تحليل البول', 'بول'],
            ['30934-4', 'كرياتينين 24 ساعة بول', 'Creatinine 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'صوديوم 24 ساعة بول', 'Sodium 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'بوتاسيوم 24 ساعة بول', 'Potassium 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'كالسيوم 24 ساعة بول', 'Calcium 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'فوسفات 24 ساعة بول', 'Phosphate 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'أوكسالات 24 ساعة بول', 'Oxalate 24h Urine', 'تحليل البول', 'بول'],
            ['30934-4', 'تحليل براز شامل', 'Stool Analysis', 'تحليل البراز', 'براز'],
            ['30934-4', 'دم خفي براز', 'Stool Occult Blood', 'تحليل البراز', 'براز'],
            ['30934-4', 'دم خفي مناعي', 'Fecal Immunochemical Test', 'تحليل البراز', 'براز'],
            ['30934-4', 'كاليبروتكتين براز', 'Fecal Calprotectin', 'تحليل البراز', 'براز'],
            ['30934-4', 'دهون براز', 'Stool Fat', 'تحليل البراز', 'براز'],
            ['30934-4', 'بيض طفيليات براز', 'Stool Ova & Parasites', 'تحليل البراز', 'براز'],
            ['30934-4', 'مزرعة براز', 'Stool Culture', 'تحليل البراز', 'براز'],
            ['30934-4', 'إيلاستاز بنكرياس', 'Pancreatic Elastase', 'تحليل البراز', 'براز'],
            ['33914-3', 'Antithrombin III', 'Antithrombin III', 'التخثر', 'بلازما'],
            ['33914-3', 'Protein C', 'Protein C', 'التخثر', 'بلازما'],
            ['33914-3', 'Protein S', 'Protein S', 'التخثر', 'بلازما'],
            ['33914-3', 'Factor V Leiden', 'Factor V Leiden', 'التخثر', 'بلازما'],
            ['33914-3', 'Factor VIII', 'Factor VIII', 'التخثر', 'بلازما'],
            ['33914-3', 'Factor IX', 'Factor IX', 'التخثر', 'بلازما'],
            ['33914-3', 'von Willebrand Factor', 'vWF', 'التخثر', 'بلازما'],
            ['33914-3', 'IgG', 'IgG', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'IgA', 'IgA', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'IgM', 'IgM', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'IgE', 'IgE', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'IgD', 'IgD', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'IgG4', 'IgG4', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'كهرباء البروتين', 'Protein Electrophoresis', 'الجلوبولينات المناعية', 'مصل'],
            ['33914-3', 'كهرباء البروتين بول', 'Urine Protein Electrophoresis', 'الجلوبولينات المناعية', 'بول'],
            ['33914-3', 'فينيتوين', 'Phenytoin', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'كاربامازيبين', 'Carbamazepine', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'فالبروات', 'Valproic Acid', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'فينوباربيتال', 'Phenobarbital', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'ليثيوم', 'Lithium', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'ديجوكسين', 'Digoxin', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'جنتامايسين', 'Gentamicin', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'فانكومايسين', 'Vancomycin', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'تاكروليموس', 'Tacrolimus', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'سيكلوسبورين', 'Cyclosporine', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'ميثوتريكسات', 'Methotrexate', 'مستويات الأدوية', 'مصل'],
            ['33914-3', 'Beta hCG نوعي', 'Beta hCG Qualitative', 'الحمل', 'بول'],
            ['33914-3', 'Triple Screen', 'Triple Screen', 'الحمل', 'مصل'],
            ['33914-3', 'Quad Screen', 'Quad Screen', 'الحمل', 'مصل'],
            ['33914-3', 'Estriol غير مقترن', 'Unconjugated Estriol', 'الحمل', 'مصل'],
            ['33914-3', 'Inhibin A', 'Inhibin A', 'الحمل', 'مصل'],
            ['30934-4', 'مزرعة دم', 'Blood Culture', 'الميكروبيولوجي', 'دم وريدي'],
            ['30934-4', 'مزرعة بول', 'Urine Culture', 'الميكروبيولوجي', 'بول'],
            ['30934-4', 'مزرعة إفرازات', 'Wound Culture', 'الميكروبيولوجي', 'إفرازات'],
            ['30934-4', 'مزرعة بلغم', 'Sputum Culture', 'الميكروبيولوجي', 'بلغم'],
            ['30934-4', 'مزرعة حلق', 'Throat Culture', 'الميكروبيولوجي', 'مسحة'],
            ['30934-4', 'صبغة جرام', 'Gram Stain', 'الميكروبيولوجي', 'مسحة'],
            ['30934-4', 'صبغة ZN', 'Ziehl-Neelsen Stain', 'الميكروبيولوجي', 'مسحة'],
            ['30934-4', 'AFB Culture', 'AFB Culture', 'الميكروبيولوجي', 'بلغم'],
            ['30934-4', 'TB PCR', 'TB PCR', 'الميكروبيولوجي', 'بلغم'],
            ['30934-4', 'Mantoux Test', 'Mantoux Test', 'الميكروبيولوجي', 'جلد'],
            ['30934-4', 'تحليل سائل دماغي شوكي', 'CSF Analysis', 'CSF', 'سائل دماغي'],
            ['30934-4', 'بروتين CSF', 'CSF Protein', 'CSF', 'سائل دماغي'],
            ['30934-4', 'جلوكوز CSF', 'CSF Glucose', 'CSF', 'سائل دماغي'],
            ['30934-4', 'كريات CSF', 'CSF Cell Count', 'CSF', 'سائل دماغي'],
            ['30934-4', 'IgG CSF', 'CSF IgG', 'CSF', 'سائل دماغي'],
            ['30934-4', 'Oligoclonal Bands CSF', 'Oligoclonal Bands', 'CSF', 'سائل دماغي'],
            ['33914-3', 'غازات الدم الشريانية', 'Arterial Blood Gases', 'الجهاز التنفسي', 'دم شرياني'],
            ['33914-3', 'لاكتات', 'Lactate', 'الجهاز التنفسي', 'مصل'],
            ['33914-3', 'أكسجين', 'O2 Saturation', 'الجهاز التنفسي', 'دم شرياني'],
            ['33914-3', 'ثاني أكسيد الكربون', 'CO2', 'الجهاز التنفسي', 'مصل'],
            ['33914-3', 'pH الدم', 'Blood pH', 'الجهاز التنفسي', 'دم شرياني'],
            ['33914-3', 'IgE كلي', 'Total IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'فحص حساسية شائع', 'Phadiatop', 'الحساسية', 'مصل'],
            ['33914-3', 'حساسية حليب البقر', 'Cow Milk IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'حساسية البيض', 'Egg White IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'حساسية الفول السوداني', 'Peanut IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'حساسية غبار الطلع', 'Pollen IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'حساسية القطط', 'Cat Dander IgE', 'الحساسية', 'مصل'],
            ['33914-3', 'فينيل ألانين', 'Phenylalanine', 'الأيض', 'مصل'],
            ['33914-3', 'جالاكتوز', 'Galactose', 'الأيض', 'مصل'],
            ['33914-3', 'كارنيتين', 'Carnitine', 'الأيض', 'مصل'],
            ['33914-3', 'أحماض أمينية كمية', 'Amino Acids Quantitative', 'الأيض', 'مصل'],
            ['33914-3', 'أحماض عضوية بول', 'Organic Acids Urine', 'الأيض', 'بول'],
            ['33914-3', 'TSH حديث الولادة', 'Newborn TSH', 'الأيض', 'دم'],
            ['33914-3', 'PKU حديث الولادة', 'PKU Newborn', 'الأيض', 'دم'],
            ['33914-3', 'لاكتات ديهيدروجينيز LDH', 'LDH', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'CK كلي', 'CK Total', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'كولين إستراز', 'Cholinesterase', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'أميليز', 'Amylase', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'ليباز', 'Lipase', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'بيروفات', 'Pyruvate', 'الكيمياء الحيوية', 'مصل'],
            ['33914-3', 'ببتيد C', 'C-Peptide', 'الكيمياء الحيوية', 'مصل'],
        ];
        $tStmt = $pdo->prepare("INSERT INTO tests_catalog (loinc_code, name_ar, name_en, category, sample_type) VALUES (?,?,?,?,?)");
        $insertedTests = 0;
        $skippedTests = 0;
        foreach ($tests as $t) {
            try {
                $tStmt->execute($t);
                $insertedTests++;
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'Duplicate') !== false
                    || stripos($e->getMessage(), 'UNIQUE') !== false
                    || stripos($e->getMessage(), '1062') !== false
                ) {
                    $skippedTests++;
                } else {
                    throw $e;
                }
            }
        }
        if ($insertedTests > 0) {
            $msg = "✅ AutoMigrator: synced tests catalog ({$insertedTests} new, {$skippedTests} already present)";
            $messages[] = $msg;
            Logger::info('AutoMigrator: ' . $msg);
        } else {
            Logger::info("AutoMigrator: tests catalog already in sync ({$skippedTests} tests present)");
        }
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
