<?php /** Admin: Settings */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-gear-fill"></i> <?= e($title) ?></h2>
</div>

<div class="card">
    <div class="card-header gradient"><i class="bi bi-sliders"></i> إعدادات النظام العامة</div>
    <div class="card-body">
        <form method="post" action="<?= url('/admin/settings') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الموقع</label>
                    <input type="text" name="site_name" class="form-control" value="<?= e($all['site_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">نافذة كشف التكرار (بالأيام)</label>
                    <input type="text" name="duplicate_window_days" class="form-control" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" value="<?= e($all['duplicate_window_days'] ?? '30') ?>" required>
                    <div class="form-text">إذا طُلب نفس التحليل (نفس كود LOINC) لنفس المريض خلال هذه المدة، يظهر تنبيه للطبيب.</div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ الإعدادات</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header gradient"><i class="bi bi-database-down"></i> النسخ الاحتياطي</div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h6 class="mb-1">تحميل نسخة احتياطية من قاعدة البيانات</h6>
                <p class="text-muted small mb-0">
                    يقوم بإنشاء ملف <code dir="ltr">.sql</code> يحتوي على جميع الجداول والبيانات.
                    يمكن استعادته لاحقاً عبر أي أداة إدارة قواعد بيانات (phpMyAdmin, DBeaver, ...).
                </p>
            </div>
            <a href="<?= url('/admin/backup-db') ?>" class="btn btn-success" id="backupBtn">
                <i class="bi bi-download"></i> تحميل نسخة احتياطية
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('backupBtn');
    if (!btn) return;
    btn.addEventListener('click', function(e) {
        // Don't prevent default — we want the browser to download the file.
        // Just show a temporary "جاري التحضير..." state on the button.
        var original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري التحضير...';
        btn.classList.add('disabled');
        // Restore after 20s (the download should have started by then)
        setTimeout(function() {
            btn.innerHTML = original;
            btn.classList.remove('disabled');
        }, 20000);
    });
})();
</script>
