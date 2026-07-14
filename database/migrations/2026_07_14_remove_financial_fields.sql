-- Migration: 2026_07_14_remove_financial_fields
-- Removes financial tracking fields per user request.
-- - Drops 'estimated_cost' column from test_orders
-- - Drops 'avg_test_cost' row from settings table
-- Safe to run multiple times.

-- 1. Drop estimated_cost column from test_orders (if exists)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND COLUMN_NAME = 'estimated_cost'
);
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `test_orders` DROP COLUMN `estimated_cost`',
    'SELECT "estimated_cost column already removed" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Remove avg_test_cost setting (if exists)
DELETE FROM `settings` WHERE `key` = 'avg_test_cost';

-- 3. Drop idx_orders_appt index (cleanup — appointment_id was added but never used)
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND INDEX_NAME = 'idx_orders_appt'
);
SET @sql = IF(@idx_exists > 0,
    'ALTER TABLE `test_orders` DROP INDEX `idx_orders_appt`',
    'SELECT "idx_orders_appt already removed" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Drop appointment_id column from test_orders (if exists)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'test_orders'
      AND COLUMN_NAME = 'appointment_id'
);
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `test_orders` DROP COLUMN `appointment_id`',
    'SELECT "appointment_id column already removed" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
