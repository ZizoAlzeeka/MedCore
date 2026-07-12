# منصة كشف التحاليل المكررة (Duplicate Detection System)

نظام طبي متكامل مبني بـ PHP (بدون إطار عمل) + MySQL بنمط MVC، يكشف تكرار التحاليل المخبرية وينبه الأطباء قبل تأكيد الطلب.

## المواصفات

| البند | القيمة |
|------|-------|
| اللغة البرمجية | PHP 8+ (بدون إطار عمل) |
| قاعدة البيانات | MySQL 5.7+ / 8.0+ (PDO) |
| النمط | MVC يدوي |
| الواجهة | Bootstrap 5 RTL + Cairo font |
| التنبيهات | SweetAlert2 |
| محرر النصوص | Quill (مجاني، بدون API key) |
| طباعة PDF | html2pdf.js |
| اللغة | العربية (RTL) |
| الأدوار | 5 (admin, doctor, reception, lab_tech, patient) |

## الأدوار

| الدور | الوصف |
|------|------|
| **المدير** (admin) | إدارة المستخدمين، الأقسام، كتالوج التحاليل، التقارير، الإعدادات |
| **الطبيب** (doctor) | جدولة الدوام، طلب التحاليل، كشف التكرار، كتابة خطة العلاج، الإحالة |
| **الاستقبال** (reception) | تسجيل المرضى، حجز المواعيد (حصرياً)، عرض جداول الأطباء |
| **فني المختبر** (lab_tech) | عرض الطلبات، رفع نتائج التحاليل، إشعار المريض والطبيب |
| **المريض** (patient) | إنشاء حساب ذاتي، عرض النتائج وخطة العلاج، طباعة PDF |

## الميزات الرئيسية

1. **كشف التكرار (Duplicate Detection)** — عند طلب طبيب لتحليل، يفحص النظام تاريخ المريض بنفس كود LOINC خلال نافذة زمنية (افتراضي 30 يوم).
2. **كتالوج LOINC** — قاعدة بيانات موحدة للتحاليل بأكواد LOINC العالمية، يديرها المدير.
3. **خطة العلاج بمحرر Quill** — الطبيب يكتب خطة العلاج بعد رفع النتائج عبر محرر rich text.
4. **إشعارات in-app** — تنبيهات داخلية (لا SMTP) عند رفع النتائج أو إضافة خطة علاج.
5. **طباعة PDF** — تقارير المرضى وخطط العلاج عبر html2pdf.js.
6. **تسجيل logs** — كل العمليات والأخطاء تُسجّل في `logs/` لأغراض التتبع.

## الحسابات الافتراضية (بعد التثبيت)

| الدور | البريد | كلمة المرور |
|------|-------|------------|
| مدير | admin@platform.com | admin123 |
| طبيب | doctor1@platform.com | doctor123 |
| استقبال | reception1@platform.com | reception123 |
| فني مختبر | lab1@platform.com | lab123 |
| مريض | patient1@platform.com | patient123 |

## التثبيت

انظر `INSTALL.md` للحصول على تعليمات التثبيت الكاملة.

## الاختبار

انظر `TESTING.md` لخطة الاختبار والسيناريوهات.

## بنية المشروع

```
platform/
├── .env                    # إعدادات البيئة
├── .htaccess               # إعادة كتابة URL
├── index.php               # نقطة الدخول (front controller)
├── install.php             # مثبت لمرة واحدة (احذفه بعد التثبيت!)
├── public/
│   └── assets/
│       ├── css/style.css
│       ├── js/app.js
│       └── img/logo.png
├── app/
│   ├── config/config.php
│   ├── core/               # Database, Model, Controller, Router, Auth, Logger, Env
│   ├── helpers/functions.php
│   ├── controllers/        # Auth, Admin, Doctor, Reception, LabTech, Patient, ...
│   ├── models/             # 13 نموذج
│   ├── views/              # layouts + auth + admin + doctor + ...
│   └── routes/web.php
├── database/
│   ├── schema.sql          # 13 جدول
│   └── (seed مدمج في install.php)
└── logs/                   # ملفات السجل
```

## الأمان

- كلمات المرور مشفّرة بـ `password_hash()` (BCRYPT).
- حماية CSRF في كل النماذج.
- Prepared statements في كل الاستعلامات (PDO).
- التحقق من الصلاحيات قبل كل controller method.
- ملف `.env` و`logs/` محميّان عبر `.htaccess`.

## الترخيص

مشروع تعليمي/تخرج — حر الاستخدام.
