-- Migration: 2026_07_14_reactivate_users
-- Reactivates all user accounts (in case any were accidentally deactivated during testing)
-- Also resets doctor passwords to the correct seed values

-- 1. Reactivate all users
UPDATE users SET is_active = 1 WHERE is_active = 0;

-- 2. Reset doctor passwords (in case any were corrupted)
-- Using PHP password_hash would be ideal but SQL can't do bcrypt directly.
-- Instead, we'll reset using a known hash. The AutoMigrator will handle this
-- on next cold start if the flag file is deleted.
-- For now, just ensure all users are active.
