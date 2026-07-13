-- ============================================================
-- Duplicate Detection System — Database Schema
-- MySQL 5.7+ / 8.0+ with utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===== 1. users =====
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unique_id` VARCHAR(10) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `gender` ENUM('male','female') DEFAULT NULL,
  `role` ENUM('admin','doctor','reception','lab_tech','patient') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_unique_id` (`unique_id`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 2. departments =====
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_ar` VARCHAR(150) NOT NULL,
  `name_en` VARCHAR(150) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_name_ar` (`name_ar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 3. doctors =====
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `specialty` VARCHAR(255) DEFAULT NULL,
  `license_no` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctors_user_id` (`user_id`),
  KEY `idx_doctors_department` (`department_id`),
  CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctors_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 4. doctor_schedules =====
CREATE TABLE IF NOT EXISTS `doctor_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` INT UNSIGNED NOT NULL,
  `work_date` DATE NOT NULL,
  `day_of_week` ENUM('sat','sun','mon','tue','wed','thu','fri') DEFAULT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `slot_duration_min` INT NOT NULL DEFAULT 20,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sched_doctor` (`doctor_id`),
  KEY `idx_sched_date` (`work_date`),
  CONSTRAINT `fk_sched_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 5. tests_catalog =====
CREATE TABLE IF NOT EXISTS `tests_catalog` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loinc_code` VARCHAR(15) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `sample_type` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tests_loinc` (`loinc_code`),
  KEY `idx_tests_category` (`category`),
  KEY `idx_tests_name_ar` (`name_ar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 6. test_orders =====
CREATE TABLE IF NOT EXISTS `test_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `doctor_id` INT UNSIGNED DEFAULT NULL,
  `test_id` INT UNSIGNED NOT NULL,
  `diagnosis_icd` VARCHAR(15) DEFAULT NULL,
  `status` ENUM('ordered','in_progress','result_uploaded','cancelled','duplicate_skipped') NOT NULL DEFAULT 'ordered',
  `ordered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orders_patient` (`patient_id`),
  KEY `idx_orders_doctor` (`doctor_id`),
  KEY `idx_orders_test` (`test_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_test` FOREIGN KEY (`test_id`) REFERENCES `tests_catalog` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 7. test_results =====
CREATE TABLE IF NOT EXISTS `test_results` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `lab_tech_id` INT UNSIGNED NOT NULL,
  `result_value` TEXT NOT NULL,
  `unit` VARCHAR(50) DEFAULT NULL,
  `normal_range` VARCHAR(100) DEFAULT NULL,
  `flag` ENUM('normal','high','low','abnormal') NOT NULL DEFAULT 'normal',
  `performed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_results_order` (`order_id`),
  CONSTRAINT `fk_results_order` FOREIGN KEY (`order_id`) REFERENCES `test_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_results_labtech` FOREIGN KEY (`lab_tech_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 8. duplicate_alerts =====
CREATE TABLE IF NOT EXISTS `duplicate_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `prev_order_id` INT UNSIGNED DEFAULT NULL,
  `days_diff` INT DEFAULT NULL,
  `doctor_decision` ENUM('proceed','cancel','use_previous') DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dup_order` (`order_id`),
  CONSTRAINT `fk_dup_order` FOREIGN KEY (`order_id`) REFERENCES `test_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dup_prev` FOREIGN KEY (`prev_order_id`) REFERENCES `test_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 9. appointments =====
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `doctor_id` INT UNSIGNED NOT NULL,
  `receptionist_id` INT UNSIGNED DEFAULT NULL,
  `appt_date` DATETIME NOT NULL,
  `status` ENUM('booked','completed','cancelled','no_show') NOT NULL DEFAULT 'booked',
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appt_patient` (`patient_id`),
  KEY `idx_appt_doctor` (`doctor_id`),
  KEY `idx_appt_date` (`appt_date`),
  KEY `idx_appt_status` (`status`),
  CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_reception` FOREIGN KEY (`receptionist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 10. treatment_plans =====
CREATE TABLE IF NOT EXISTS `treatment_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `doctor_id` INT UNSIGNED NOT NULL,
  `appointment_id` INT UNSIGNED DEFAULT NULL,
  `treatment_name` VARCHAR(255) NOT NULL,
  `description_html` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tp_patient` (`patient_id`),
  KEY `idx_tp_doctor` (`doctor_id`),
  CONSTRAINT `fk_tp_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_appt` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 11. notifications =====
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('result_ready','treatment_added','appointment_booked','duplicate_alert','general') NOT NULL DEFAULT 'general',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `related_id` INT UNSIGNED DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 12. referrals =====
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `from_doctor_id` INT UNSIGNED NOT NULL,
  `to_doctor_id` INT UNSIGNED NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `referred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref_patient` (`patient_id`),
  CONSTRAINT `fk_ref_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_from` FOREIGN KEY (`from_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_to` FOREIGN KEY (`to_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== 13. settings =====
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
