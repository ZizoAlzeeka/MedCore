-- Migration: 2026_07_15_add_referral_notif_type
-- Adds 'referral' to the notifications.type ENUM so referral notifications
-- can be categorized distinctly (instead of falling back to 'general').
-- Safe to run multiple times — checks INFORMATION_SCHEMA before altering.

SET @has_referral = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notifications'
      AND COLUMN_NAME = 'type'
      AND COLUMN_TYPE LIKE '%''referral''%'
);
SET @sql = IF(@has_referral = 0,
    'ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM(''result_ready'',''treatment_added'',''appointment_booked'',''duplicate_alert'',''referral'',''general'') NOT NULL DEFAULT ''general''',
    'SELECT "referral type already exists in notifications ENUM" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
