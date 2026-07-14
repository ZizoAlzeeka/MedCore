-- ============================================================
-- Duplicate Detection System — SQLite Schema
-- Compatible with the MySQL schema (same table/column names)
-- Differences:
--   - No ENGINE/CHARSET clauses
--   - INTEGER PRIMARY KEY AUTOINCREMENT instead of INT AI
--   - ENUM replaced by TEXT + CHECK constraint
--   - No UNSIGNED (ignored in SQLite)
--   - No ON UPDATE CURRENT_TIMESTAMP (handled in app code)
-- ============================================================

PRAGMA foreign_keys = OFF;

-- ===== 1. users =====
CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `unique_id` TEXT NOT NULL UNIQUE,
  `full_name` TEXT NOT NULL,
  `email` TEXT NOT NULL UNIQUE,
  `password_hash` TEXT NOT NULL,
  `phone` TEXT,
  `address` TEXT,
  `birth_date` TEXT,
  `gender` TEXT CHECK (gender IN ('male','female')),
  `role` TEXT NOT NULL CHECK (role IN ('admin','doctor','reception','lab_tech','patient')),
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_users_role` ON `users`(`role`);
CREATE INDEX IF NOT EXISTS `idx_users_is_active` ON `users`(`is_active`);

-- ===== 2. departments =====
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name_ar` TEXT NOT NULL UNIQUE,
  `name_en` TEXT,
  `description` TEXT,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===== 3. doctors =====
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL UNIQUE,
  `department_id` INTEGER,
  `specialty` TEXT,
  `license_no` TEXT,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_doctors_department` ON `doctors`(`department_id`);

-- ===== 4. doctor_schedules =====
CREATE TABLE IF NOT EXISTS `doctor_schedules` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `doctor_id` INTEGER NOT NULL,
  `work_date` TEXT NOT NULL,
  `day_of_week` TEXT CHECK (day_of_week IN ('sat','sun','mon','tue','wed','thu','fri')),
  `start_time` TEXT NOT NULL,
  `end_time` TEXT NOT NULL,
  `slot_duration_min` INTEGER NOT NULL DEFAULT 20,
  `is_available` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_sched_doctor` ON `doctor_schedules`(`doctor_id`);
CREATE INDEX IF NOT EXISTS `idx_sched_date` ON `doctor_schedules`(`work_date`);

-- ===== 5. tests_catalog =====
CREATE TABLE IF NOT EXISTS `tests_catalog` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `loinc_code` TEXT NOT NULL UNIQUE,
  `name_ar` TEXT NOT NULL,
  `name_en` TEXT,
  `category` TEXT,
  `sample_type` TEXT,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS `idx_tests_category` ON `tests_catalog`(`category`);
CREATE INDEX IF NOT EXISTS `idx_tests_name_ar` ON `tests_catalog`(`name_ar`);

-- ===== 6. test_orders =====
CREATE TABLE IF NOT EXISTS `test_orders` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `patient_id` INTEGER NOT NULL,
  `doctor_id` INTEGER,
  `test_id` INTEGER NOT NULL,
  `diagnosis_icd` TEXT,
  `status` TEXT NOT NULL DEFAULT 'ordered' CHECK (status IN ('ordered','in_progress','result_uploaded','cancelled','duplicate_skipped')),
  `ordered_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`test_id`) REFERENCES `tests_catalog`(`id`) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS `idx_orders_patient` ON `test_orders`(`patient_id`);
CREATE INDEX IF NOT EXISTS `idx_orders_doctor` ON `test_orders`(`doctor_id`);
CREATE INDEX IF NOT EXISTS `idx_orders_test` ON `test_orders`(`test_id`);
CREATE INDEX IF NOT EXISTS `idx_orders_status` ON `test_orders`(`status`);

-- ===== 7. test_results =====
CREATE TABLE IF NOT EXISTS `test_results` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INTEGER NOT NULL UNIQUE,
  `lab_tech_id` INTEGER NOT NULL,
  `result_value` TEXT NOT NULL,
  `unit` TEXT,
  `normal_range` TEXT,
  `flag` TEXT NOT NULL DEFAULT 'normal' CHECK (flag IN ('normal','high','low','abnormal')),
  `performed_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`order_id`) REFERENCES `test_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lab_tech_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);

-- ===== 8. duplicate_alerts =====
CREATE TABLE IF NOT EXISTS `duplicate_alerts` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INTEGER NOT NULL,
  `prev_order_id` INTEGER,
  `days_diff` INTEGER,
  `doctor_decision` TEXT CHECK (doctor_decision IN ('proceed','cancel','use_previous')),
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `test_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`prev_order_id`) REFERENCES `test_orders`(`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_dup_order` ON `duplicate_alerts`(`order_id`);

-- ===== 9. appointments =====
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `patient_id` INTEGER NOT NULL,
  `doctor_id` INTEGER NOT NULL,
  `receptionist_id` INTEGER,
  `appt_date` TEXT NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'booked' CHECK (status IN ('booked','completed','cancelled','no_show')),
  `reason` TEXT,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receptionist_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_appt_patient` ON `appointments`(`patient_id`);
CREATE INDEX IF NOT EXISTS `idx_appt_doctor` ON `appointments`(`doctor_id`);
CREATE INDEX IF NOT EXISTS `idx_appt_date` ON `appointments`(`appt_date`);
CREATE INDEX IF NOT EXISTS `idx_appt_status` ON `appointments`(`status`);

-- ===== 10. treatment_plans =====
CREATE TABLE IF NOT EXISTS `treatment_plans` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `patient_id` INTEGER NOT NULL,
  `doctor_id` INTEGER NOT NULL,
  `appointment_id` INTEGER,
  `treatment_name` TEXT NOT NULL,
  `description_html` TEXT NOT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS `idx_tp_patient` ON `treatment_plans`(`patient_id`);
CREATE INDEX IF NOT EXISTS `idx_tp_doctor` ON `treatment_plans`(`doctor_id`);

-- ===== 11. notifications =====
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `type` TEXT NOT NULL DEFAULT 'general' CHECK (type IN ('result_ready','treatment_added','appointment_booked','duplicate_alert','referral','general')),
  `title` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `related_id` INTEGER,
  `is_read` INTEGER NOT NULL DEFAULT 0,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_notif_user` ON `notifications`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_notif_read` ON `notifications`(`is_read`);

-- ===== 12. referrals =====
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `patient_id` INTEGER NOT NULL,
  `from_doctor_id` INTEGER NOT NULL,
  `to_doctor_id` INTEGER NOT NULL,
  `reason` TEXT,
  `referred_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS `idx_ref_patient` ON `referrals`(`patient_id`);

-- ===== 13. settings =====
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `key` TEXT NOT NULL UNIQUE,
  `value` TEXT,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

PRAGMA foreign_keys = ON;
