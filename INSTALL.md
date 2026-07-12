# دليل التثبيت

## المتطلبات

- PHP 8.0 أو أحدث مع إضافات: pdo_mysql, mbstring, openssl
- MySQL 5.7+ أو 8.0+
- خادم ويب (Apache مع mod_rewrite، أو Nginx، أو PHP built-in server للاختبار)
- اتصال إنترنت لتحميل المكتبات من CDN (Bootstrap, SweetAlert2, Quill, html2pdf, Cairo font)

## الخطوة 1: رفع الملفات

ارفع كامل مجلد `platform/` إلى خادمك (عبر FTP أو cPanel File Manager):
- مثال: `/public_html/platform/`

## الخطوة 2: إعداد ملف .env

افتح ملف `.env` وعدّل القيم حسب خادمك:

```env
APP_NAME=منصة كشف التحاليل المكررة
APP_URL=https://your-domain.com/platform
APP_DEBUG=true

DB_HOST=193.203.184.246
DB_PORT=3306
DB_NAME=u864760987_platformdb
DB_USER=u864760987_platformdb
DB_PASS=platformdb99Ksa
DB_CHARSET=utf8mb4

DEFAULT_DUP_WINDOW_DAYS=30
```

> **ملاحظة:** `APP_URL` يجب أن يشير إلى عنوان تثبيت المنصة. إذا كانت في جذر النطاق استخدم `https://your-domain.com`.

## الخطوة 3: التأكد من الصلاحيات

تأكد من أن المجلدات التالية قابلة للكتابة:
```bash
chmod 775 logs/
chmod 775 public/assets/img/
```

## الخطوة 4: اختبار الاتصال (موصى به)

افتح المتصفح على:
```
https://your-domain.com/platform/test-db.php
```

هذا سيتحقق من:
1. ✅ اتصال TCP بالخادم على المنفذ 3306
2. ✅ اتصال PDO MySQL ببيانات `.env`
3. ✅ إصدار MySQL و Charset
4. ✅ وجود جداول سابقة
5. ✅ صلاحيات CREATE/DROP TABLE

إذا فشل أي اختبار، ستظهر رسالة خطأ واضحة مع الأسباب والحلول.

## الخطوة 5: تشغيل المثبت

افتح المتصفح على:
```
https://your-domain.com/platform/install.php
```

سيقوم المثبت بـ:
1. اختبار الاتصال بقاعدة البيانات.
2. إنشاء 13 جدول (schema.sql).
3. إضافة البيانات الأولية:
   - 1 مدير
   - 10 أطباء موزعين على 6 أقسام
   - 2 موظفي استقبال
   - 2 فني مختبر
   - 12 مريضاً
   - 25 تحليلاً في كتالوج LOINC
   - جداول دوام للأطباء
   - الإعدادات الافتراضية

عند نجاح التثبيت، ستظهر شاشة ببيانات الدخول الافتراضية.

## الخطوة 6: حذف install.php و test-db.php

**مهم جداً:** احذف ملفات التثبيت بعد الانتهاء لأسباب أمنية:
```bash
rm install.php
rm test-db.php
```

## الخطوة 7: تسجيل الدخول

افتح:
```
https://your-domain.com/platform/login
```

استخدم بيانات المدير:
- البريد: `admin@platform.com`
- كلمة المرور: `admin123`

## تشغيل محلي للتجربة (PHP built-in server)

إذا أردت التجربة محلياً قبل الرفع:

```bash
cd platform
php -S localhost:8000
```

ثم افتح: `http://localhost:8000/install.php`

> **ملاحظة:** في هذه الحالة، تحتاج MySQL مثبتة محلياً، وعدّل `.env` ليشير إلى `127.0.0.1`.

## إعدادات Apache (.htaccess)

ملف `.htaccess` المرفق يقوم بـ:
- منع الوصول إلى `.env`
- منع الوصول إلى `logs/` و `app/` و `database/`
- توجيه كل الطلبات إلى `index.php` (front controller)

تأكد أن `mod_rewrite` مفعّل في Apache:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## إعدادات Nginx (بديل)

إذا كنت تستخدم Nginx، أضف:
```nginx
location /platform/ {
    try_files $uri $uri/ /platform/index.php?$query_string;
}
location /platform/.env { deny all; }
location /platform/logs/ { deny all; }
```

## استكشاف الأخطاء

### فشل الاتصال بقاعدة البيانات (في test-db.php)

**السبب 1: منفذ 3306 محجوب على الخادم البعيد**
- الحل: في cPanel الخاص بـ `193.203.184.246`، اذهب إلى "Remote MySQL" وأضف عنوان IP الخاص بخادمك الحالي (أو `%` للسماح من أي IP).

**السبب 2: المستخدم لا يملك صلاحية الوصول للقاعدة**
- الحل: في cPanel، اذهب إلى "MySQL Databases" وتأكد أن المستخدم `u864760987_platformdb` مُسنَد للقاعدة `u864760987_platformdb` مع ALL PRIVILEGES.

**السبب 3: بيانات اعتماد خاطئة**
- الحل: تحقق من `DB_USER` و `DB_PASS` في `.env`.

### فشل إنشاء الجداول في install.php

**السبب: المستخدم لا يملك صلاحية CREATE TABLE**
- الحل: تأكد من إعطاء ALL PRIVILEGES للمستخدم في cPanel.

### خطأ 500 بعد التثبيت
- فعّل `APP_DEBUG=true` في `.env` لعرض الخطأ.
- راجع `logs/error-YYYY-MM-DD.log`.

### لا تظهر التنسيقات (CSS)
- تأكد أن `APP_URL` في `.env` يشير لعنوان التثبيت الصحيح.
- تحقق أن مجلد `public/assets/` قابل للقراءة.
- إذا كانت المنصة في مجلد فرعي (مثل `/platform/`)، تأكد أن `APP_URL=https://your-domain.com/platform`.

### مشكلة في الأحرف العربية
- تأكد أن ترميز قاعدة البيانات `utf8mb4_unicode_ci` (يمكن التحقق من test-db.php).
- تأكد أن ملفات PHP محفوظة بترميز UTF-8 (بدون BOM).

### رسالة "CSRF token mismatch"
- تأكد أن الجلسات تعمل على خادمك (تحقق من `session.save_path` في php.ini).
- امسح cookies المتصفح وأعد المحاولة.

## النسخ الاحتياطي

للنسخ الاحتياطي الدوري لقاعدة البيانات:
```bash
mysqldump -u USER -p PASSWORD DB_NAME > backup-$(date +%Y%m%d).sql
```

## التحديث

لتحديث المنصة بنسخة جديدة:
1. خذ نسخة احتياطية من قاعدة البيانات.
2. استبدل الملفات (ما عدا `.env`).
3. راجع `CHANGELOG.md` (إن وُجد) لأي تحديثات schema.
4. اختبر المنصة.
