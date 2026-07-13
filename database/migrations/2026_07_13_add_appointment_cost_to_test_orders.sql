-- Migration: 2026_07_13_add_appointment_cost_to_test_orders
-- Adds appointment_id and estimated_cost columns to test_orders table.
-- Safe to run multiple times.

-- 1. Add appointment_id column
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND COLUMN_NAME = 'appointment_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `test_orders` ADD COLUMN `appointment_id` INT UNSIGNED DEFAULT NULL AFTER `test_id`',
    'SELECT "appointment_id column already exists" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add estimated_cost column
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND COLUMN_NAME = 'estimated_cost'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `test_orders` ADD COLUMN `estimated_cost` DECIMAL(10,2) DEFAULT NULL AFTER `notes`',
    'SELECT "estimated_cost column already exists" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add index on appointment_id if not exists
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND INDEX_NAME = 'idx_orders_appt'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `test_orders` ADD INDEX `idx_orders_appt` (`appointment_id`)',
    'SELECT "idx_orders_appt already exists" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
