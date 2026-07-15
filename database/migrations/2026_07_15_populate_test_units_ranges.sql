-- Migration: 2026_07_15_populate_test_units_ranges
-- Populates unit + normal_range_min/max for existing tests based on category.
-- Only updates rows where unit is NULL (safe to run multiple times).

-- Blood / CBC tests (أمراض الدم)
UPDATE tests_catalog SET unit = 'x10^3/μL', normal_range_min = 4.0, normal_range_max = 11.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%كريات البيض%' OR name_ar LIKE '%WBC%' OR name_en LIKE '%WBC%');

UPDATE tests_catalog SET unit = 'x10^6/μL', normal_range_min = 4.5, normal_range_max = 6.5
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%كريات الحمراء%' OR name_ar LIKE '%RBC%' OR name_en LIKE '%RBC%');

UPDATE tests_catalog SET unit = 'g/dL', normal_range_min = 11.0, normal_range_max = 16.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%هيموجلوبين%' OR name_en LIKE '%Hemoglobin%' OR name_en LIKE '%Hb%');

UPDATE tests_catalog SET unit = '%', normal_range_min = 36.0, normal_range_max = 50.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%هيماتوكريت%' OR name_en LIKE '%Hematocrit%');

UPDATE tests_catalog SET unit = 'pg', normal_range_min = 27.0, normal_range_max = 33.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%MCH%' OR name_en LIKE '%MCH%');

UPDATE tests_catalog SET unit = 'fL', normal_range_min = 80.0, normal_range_max = 100.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%MCV%' OR name_en LIKE '%MCV%');

UPDATE tests_catalog SET unit = 'g/dL', normal_range_min = 32.0, normal_range_max = 36.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%MCHC%' OR name_en LIKE '%MCHC%');

UPDATE tests_catalog SET unit = 'x10^3/μL', normal_range_min = 150.0, normal_range_max = 450.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%صفائح%' OR name_en LIKE '%Platelet%');

UPDATE tests_catalog SET unit = '%', normal_range_min = 11.5, normal_range_max = 14.5
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%NEUT%' OR name_en LIKE '%Neutrophil%');

UPDATE tests_catalog SET unit = '%', normal_range_min = 20.0, normal_range_max = 40.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%لمفاويات%' OR name_ar LIKE '%LYM%' OR name_en LIKE '%Lymphocyte%');

UPDATE tests_catalog SET unit = '%', normal_range_min = 2.0, normal_range_max = 10.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%وحيدات%' OR name_ar LIKE '%MONO%' OR name_en LIKE '%Monocyte%');

UPDATE tests_catalog SET unit = 'mm/h', normal_range_min = 0.0, normal_range_max = 20.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%ترسيب%' OR name_en LIKE '%Sed%' OR name_en LIKE '%ESR%');

UPDATE tests_catalog SET unit = 'ng/mL', normal_range_min = 30.0, normal_range_max = 400.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%فيريتين%' OR name_en LIKE '%Ferritin%');

UPDATE tests_catalog SET unit = 'μg/dL', normal_range_min = 60.0, normal_range_max = 170.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%حديد%' OR name_en LIKE '%Iron%');

UPDATE tests_catalog SET unit = 'pg/mL', normal_range_min = 3.0, normal_range_max = 17.0
WHERE unit IS NULL AND category = 'أمراض الدم' AND (name_ar LIKE '%B12%' OR name_en LIKE '%B12%');

-- Diabetes tests (السكري)
UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 70.0, normal_range_max = 110.0
WHERE unit IS NULL AND category = 'السكري' AND (name_ar LIKE '%صائم%' OR name_en LIKE '%Fasting%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 70.0, normal_range_max = 140.0
WHERE unit IS NULL AND category = 'السكري' AND (name_ar LIKE '%بعد الأكل%' OR name_en LIKE '%Postprandial%');

UPDATE tests_catalog SET unit = '%', normal_range_min = 4.0, normal_range_max = 6.0
WHERE unit IS NULL AND category = 'السكري' AND (name_ar LIKE '%HbA1c%' OR name_ar LIKE '%سكري%' OR name_en LIKE '%HbA1c%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 70.0, normal_range_max = 100.0
WHERE unit IS NULL AND category = 'السكري' AND (name_ar LIKE '%عشوائي%' OR name_en LIKE '%Random%');

-- Liver tests (وظائف الكبد)
UPDATE tests_catalog SET unit = 'U/L', normal_range_min = 7.0, normal_range_max = 56.0
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%ALT%' OR name_en LIKE '%ALT%');

UPDATE tests_catalog SET unit = 'U/L', normal_range_min = 10.0, normal_range_max = 40.0
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%AST%' OR name_en LIKE '%AST%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.1, normal_range_max = 1.2
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%بيليروبين كلي%' OR name_en LIKE '%Bilirubin Total%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.0, normal_range_max = 0.4
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%بيليروبين مباشر%' OR name_en LIKE '%Bilirubin Direct%');

UPDATE tests_catalog SET unit = 'U/L', normal_range_min = 44.0, normal_range_max = 147.0
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%فوسفاتاز%' OR name_en LIKE '%Alkaline%');

UPDATE tests_catalog SET unit = 'U/L', normal_range_min = 8.0, normal_range_max = 61.0
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%غاما%' OR name_en LIKE '%GGT%');

UPDATE tests_catalog SET unit = 'g/dL', normal_range_min = 3.5, normal_range_max = 5.0
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%ألبومين%' OR name_en LIKE '%Albumin%');

UPDATE tests_catalog SET unit = 'g/dL', normal_range_min = 6.0, normal_range_max = 8.3
WHERE unit IS NULL AND category = 'وظائف الكبد' AND (name_ar LIKE '%بروتين كلي%' OR name_en LIKE '%Total Protein%');

-- Kidney tests (وظائف الكلى)
UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.6, normal_range_max = 1.2
WHERE unit IS NULL AND category = 'وظائف الكلى' AND (name_ar LIKE '%كرياتينين%' OR name_en LIKE '%Creatinine%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 7.0, normal_range_max = 20.0
WHERE unit IS NULL AND category = 'وظائف الكلى' AND (name_ar LIKE '%يوريا%' OR name_en LIKE '%Urea%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 10.0, normal_range_max = 50.0
WHERE unit IS NULL AND category = 'وظائف الكلى' AND (name_ar LIKE '%حمض اليوريك%' OR name_en LIKE '%Uric%');

-- Coagulation (التخثر)
UPDATE tests_catalog SET unit = 's', normal_range_min = 11.0, normal_range_max = 13.5
WHERE unit IS NULL AND category = 'التخثر' AND (name_ar LIKE '%بروثرومبين%' OR name_ar LIKE '%PT%' OR name_en LIKE '%Prothrombin%');

UPDATE tests_catalog SET unit = 's', normal_range_min = 25.0, normal_range_max = 35.0
WHERE unit IS NULL AND category = 'التخثر' AND (name_ar LIKE '%aPTT%' OR name_en LIKE '%aPTT%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 200.0, normal_range_max = 400.0
WHERE unit IS NULL AND category = 'التخثر' AND (name_ar LIKE '%فايبرينوجين%' OR name_en LIKE '%Fibrinogen%');

UPDATE tests_catalog SET unit = 'ng/mL', normal_range_min = 0.0, normal_range_max = 0.5
WHERE unit IS NULL AND category = 'التخثر' AND (name_ar LIKE '%D-Dimer%' OR name_en LIKE '%D-Dimer%');

-- Thyroid (الغدة الدرقية)
UPDATE tests_catalog SET unit = 'mIU/L', normal_range_min = 0.4, normal_range_max = 4.0
WHERE unit IS NULL AND category = 'الغدة الدرقية' AND (name_ar LIKE '%TSH%' OR name_en LIKE '%TSH%');

UPDATE tests_catalog SET unit = 'ng/dL', normal_range_min = 80.0, normal_range_max = 180.0
WHERE unit IS NULL AND category = 'الغدة الدرقية' AND (name_ar LIKE '%T3%' OR name_en LIKE '%T3%');

UPDATE tests_catalog SET unit = 'μg/dL', normal_range_min = 5.0, normal_range_max = 12.0
WHERE unit IS NULL AND category = 'الغدة الدرقية' AND (name_ar LIKE '%T4%' OR name_en LIKE '%T4%');

-- Lipids (الدهون)
UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.0, normal_range_max = 200.0
WHERE unit IS NULL AND category = 'الدهون' AND (name_ar LIKE '%كوليسترول كلي%' OR name_en LIKE '%Cholesterol%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.0, normal_range_max = 150.0
WHERE unit IS NULL AND category = 'الدهون' AND (name_ar LIKE '% triglycerides%' OR name_ar LIKE '%Triglyceride%' OR name_en LIKE '%Triglyceride%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 40.0, normal_range_max = 60.0
WHERE unit IS NULL AND category = 'الدهون' AND (name_ar LIKE '%HDL%' OR name_en LIKE '%HDL%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.0, normal_range_max = 130.0
WHERE unit IS NULL AND category = 'الدهون' AND (name_ar LIKE '%LDL%' OR name_en LIKE '%LDL%');

-- Electrolytes (الكهارل)
UPDATE tests_catalog SET unit = 'mmol/L', normal_range_min = 135.0, normal_range_max = 145.0
WHERE unit IS NULL AND category = 'الكهارل' AND (name_ar LIKE '%صوديوم%' OR name_en LIKE '%Sodium%');

UPDATE tests_catalog SET unit = 'mmol/L', normal_range_min = 3.5, normal_range_max = 5.0
WHERE unit IS NULL AND category = 'الكهارل' AND (name_ar LIKE '%بوتاسيوم%' OR name_en LIKE '%Potassium%');

UPDATE tests_catalog SET unit = 'mmol/L', normal_range_min = 98.0, normal_range_max = 107.0
WHERE unit IS NULL AND category = 'الكهارل' AND (name_ar LIKE '%كلور%' OR name_en LIKE '%Chloride%');

UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 8.6, normal_range_max = 10.3
WHERE unit IS NULL AND category = 'الكهارل' AND (name_ar LIKE '%كالسيوم%' OR name_en LIKE '%Calcium%');

-- Calcium/Bone (الكالسيوم والعظام)
UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 8.6, normal_range_max = 10.3
WHERE unit IS NULL AND category = 'الكالسيوم والعظام' AND (name_ar LIKE '%كالسيوم%' OR name_en LIKE '%Calcium%');

UPDATE tests_catalog SET unit = 'ng/mL', normal_range_min = 30.0, normal_range_max = 100.0
WHERE unit IS NULL AND category = 'الكالسيوم والعظام' AND (name_ar LIKE '%فيتامين D%' OR name_en LIKE '%Vitamin D%');

-- Generic fallback for remaining tests without unit
UPDATE tests_catalog SET unit = 'mg/dL', normal_range_min = 0.0, normal_range_max = 0.0
WHERE unit IS NULL AND category IN ('أمراض الدم', 'السكري', 'وظائف الكبد', 'وظائف الكلى', 'الكهارل', 'الدهون');
