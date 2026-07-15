-- Migration: 2026_07_15_add_unit_range_to_tests_catalog
-- Adds `unit`, `normal_range_min`, `normal_range_max` columns to the tests_catalog
-- table. These provide sensible defaults that the lab-tech upload form pre-fills
-- (the tech can still override them per-result).
--
-- Safe to run multiple times — checks INFORMATION_SCHEMA before altering.

-- ===== unit (VARCHAR 50) =====
SET @has_unit = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tests_catalog'
      AND COLUMN_NAME = 'unit'
);
SET @sql_unit = IF(@has_unit = 0,
    'ALTER TABLE `tests_catalog` ADD COLUMN `unit` VARCHAR(50) NULL DEFAULT NULL AFTER `sample_type`',
    'SELECT "unit column already exists on tests_catalog" AS msg'
);
PREPARE stmt_unit FROM @sql_unit;
EXECUTE stmt_unit;
DEALLOCATE PREPARE stmt_unit;

-- ===== normal_range_min (DECIMAL 10,2) =====
SET @has_min = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tests_catalog'
      AND COLUMN_NAME = 'normal_range_min'
);
SET @sql_min = IF(@has_min = 0,
    'ALTER TABLE `tests_catalog` ADD COLUMN `normal_range_min` DECIMAL(10,2) NULL DEFAULT NULL AFTER `unit`',
    'SELECT "normal_range_min column already exists on tests_catalog" AS msg'
);
PREPARE stmt_min FROM @sql_min;
EXECUTE stmt_min;
DEALLOCATE PREPARE stmt_min;

-- ===== normal_range_max (DECIMAL 10,2) =====
SET @has_max = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tests_catalog'
      AND COLUMN_NAME = 'normal_range_max'
);
SET @sql_max = IF(@has_max = 0,
    'ALTER TABLE `tests_catalog` ADD COLUMN `normal_range_max` DECIMAL(10,2) NULL DEFAULT NULL AFTER `normal_range_min`',
    'SELECT "normal_range_max column already exists on tests_catalog" AS msg'
);
PREPARE stmt_max FROM @sql_max;
EXECUTE stmt_max;
DEALLOCATE PREPARE stmt_max;
