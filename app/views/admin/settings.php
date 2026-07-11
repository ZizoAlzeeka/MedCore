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

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-info-circle text-purple"></i> معلومات النظام</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>إصدار النظام</td><td>1.0.0</td></tr>
            <tr><td>التقنية</td><td>PHP + MySQL (MVC بدون إطار عمل)</td></tr>
            <tr><td>معيار التحاليل</td><td>LOINC</td></tr>
            <tr><td>الخط</td><td>Cairo</td></tr>
            <tr><td>الإطار</td><td>Bootstrap 5 RTL</td></tr>
            <tr><td>التنبيهات</td><td>SweetAlert2</td></tr>
            <tr><td>محرر النصوص</td><td>Quill</td></tr>
            <tr><td>طباعة PDF</td><td>html2pdf.js</td></tr>
        </table>
    </div>
</div>
