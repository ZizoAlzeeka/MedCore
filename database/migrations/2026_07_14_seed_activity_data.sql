-- Migration: 2026_07_14_seed_activity_data
-- Adds sample appointments, test orders, results, and notifications
-- so the platform has data to display on first deploy.
-- Safe to run multiple times (checks for existing data first).

-- Only seed if no appointments exist
SET @appt_count = (SELECT COUNT(*) FROM appointments);
SET @order_count = (SELECT COUNT(*) FROM test_orders);

-- 1. Sample appointments (5 appointments: 2 today, 2 tomorrow, 1 next week)
-- Patient 16 (سارة) with doctor 1 (د. أحمد) — receptionist 12
INSERT INTO appointments (patient_id, doctor_id, receptionist_id, appt_date, status, reason, created_at)
SELECT 16, 1, 12, NOW(), 'booked', 'كشف باطنة عام', NOW()
WHERE @appt_count = 0
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE patient_id = 16 AND doctor_id = 1 LIMIT 1);

INSERT INTO appointments (patient_id, doctor_id, receptionist_id, appt_date, status, reason, created_at)
SELECT 17, 1, 12, DATE_ADD(NOW(), INTERVAL 2 HOUR), 'booked', 'متابعة', NOW()
WHERE @appt_count = 0
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE patient_id = 17 AND doctor_id = 1 LIMIT 1);

INSERT INTO appointments (patient_id, doctor_id, receptionist_id, appt_date, status, reason, created_at)
SELECT 18, 3, 12, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'booked', 'فحص قلب', NOW()
WHERE @appt_count = 0
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE patient_id = 18 AND doctor_id = 3 LIMIT 1);

INSERT INTO appointments (patient_id, doctor_id, receptionist_id, appt_date, status, reason, created_at)
SELECT 19, 3, 12, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'booked', 'ألم صدر', NOW()
WHERE @appt_count = 0
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE patient_id = 19 AND doctor_id = 3 LIMIT 1);

INSERT INTO appointments (patient_id, doctor_id, receptionist_id, appt_date, status, reason, created_at)
SELECT 20, 5, 12, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'booked', 'فحص عيون', NOW()
WHERE @appt_count = 0
  AND NOT EXISTS (SELECT 1 FROM appointments WHERE patient_id = 20 AND doctor_id = 5 LIMIT 1);

-- 2. Sample test orders — use first 5 tests from catalog
-- Need to get test IDs dynamically via subqueries
INSERT INTO test_orders (patient_id, doctor_id, test_id, diagnosis_icd, status, ordered_at, notes)
SELECT 16, 1, (SELECT id FROM tests_catalog WHERE loinc_code = '58410-2' LIMIT 1), 'R10', 'result_uploaded', DATE_SUB(NOW(), INTERVAL 5 DAY), 'كشف عام'
WHERE @order_count = 0
  AND NOT EXISTS (SELECT 1 FROM test_orders WHERE patient_id = 16 LIMIT 1);

INSERT INTO test_orders (patient_id, doctor_id, test_id, diagnosis_icd, status, ordered_at, notes)
SELECT 17, 1, (SELECT id FROM tests_catalog WHERE loinc_code = '2345-7' LIMIT 1), 'E11.9', 'result_uploaded', DATE_SUB(NOW(), INTERVAL 3 DAY), 'فحص سكر'
WHERE @order_count = 0
  AND NOT EXISTS (SELECT 1 FROM test_orders WHERE patient_id = 17 LIMIT 1);

INSERT INTO test_orders (patient_id, doctor_id, test_id, diagnosis_icd, status, ordered_at, notes)
SELECT 18, 3, (SELECT id FROM tests_catalog WHERE loinc_code = '33914-3' LIMIT 1), 'K76.9', 'ordered', DATE_SUB(NOW(), INTERVAL 1 DAY), 'وظائف كبد'
WHERE @order_count = 0
  AND NOT EXISTS (SELECT 1 FROM test_orders WHERE patient_id = 18 LIMIT 1);

INSERT INTO test_orders (patient_id, doctor_id, test_id, diagnosis_icd, status, ordered_at, notes)
SELECT 19, 3, (SELECT id FROM tests_catalog WHERE loinc_code = '2160-0' LIMIT 1), 'N17.9', 'ordered', NOW(), 'وظائف كلى'
WHERE @order_count = 0
  AND NOT EXISTS (SELECT 1 FROM test_orders WHERE patient_id = 19 LIMIT 1);

-- 3. Test results for the completed orders (only for status=result_uploaded)
INSERT INTO test_results (order_id, lab_tech_id, result_value, unit, normal_range, flag, performed_at, uploaded_at, notes)
SELECT o.id, 14, '7.2', 'x10^3/μL', '4.0 - 11.0', 'normal', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 'نتيجة طبيعية'
FROM test_orders o
WHERE o.status = 'result_uploaded'
  AND o.patient_id = 16
  AND NOT EXISTS (SELECT 1 FROM test_results WHERE order_id = o.id LIMIT 1);

INSERT INTO test_results (order_id, lab_tech_id, result_value, unit, normal_range, flag, performed_at, uploaded_at, notes)
SELECT o.id, 14, '145', 'mg/dL', '70 - 110', 'high', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 'سكر مرتفع قليلاً'
FROM test_orders o
WHERE o.status = 'result_uploaded'
  AND o.patient_id = 17
  AND NOT EXISTS (SELECT 1 FROM test_results WHERE order_id = o.id LIMIT 1);

-- 4. Notifications for patients and doctors
INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at)
SELECT 16, 'result_ready', 'نتيجة تحليل جاهزة', 'تم رفع نتيجة تحليل صورة دم كاملة (CBC). القيمة: 7.2 x10^3/μL — طبيعي.', NULL, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 16 AND type = 'result_ready' LIMIT 1);

INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at)
SELECT 16, 'appointment_booked', 'حجز موعد جديد', 'تم حجز موعد لك اليوم. يرجى الحضور في الموعد المحدد.', NULL, 0, NOW()
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 16 AND type = 'appointment_booked' LIMIT 1);

INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at)
SELECT 17, 'result_ready', 'نتيجة تحليل جاهزة', 'تم رفع نتيجة فحص السكر. القيمة: 145 mg/dL — مرتفع قليلاً.', NULL, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 17 AND type = 'result_ready' LIMIT 1);

INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at)
SELECT 2, 'result_ready', 'رفع نتيجة تحليل', 'تم رفع نتيجة تحليل للمريض: سارة أحمد المالكي', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE user_id = 2 AND type = 'result_ready' LIMIT 1);

-- 5. Add a duplicate alert (test the duplicate detection feature)
INSERT INTO duplicate_alerts (order_id, prev_order_id, days_diff, doctor_decision, created_at)
SELECT
    (SELECT id FROM test_orders WHERE patient_id = 17 LIMIT 1),
    (SELECT id FROM test_orders WHERE patient_id = 16 LIMIT 1),
    3,
    'use_previous',
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM duplicate_alerts LIMIT 1);

-- 6. Add avg_test_cost setting if not exists
INSERT INTO settings (`key`, `value`)
SELECT 'avg_test_cost', '150'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'avg_test_cost' LIMIT 1);
