<?php /** Patient: print report */
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>';
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-printer-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">تقرير طبي شامل — يمكن تحميله PDF</div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="printToPDF('reportSection', 'تقرير-طبي-<?= e($user['unique_id']) ?>.pdf')">
        <i class="bi bi-download"></i> تحميل PDF
    </button>
</div>

<div id="reportSection">
    <div class="card mb-3">
        <div class="card-header gradient d-flex justify-content-between align-items-center">
            <span><i class="bi bi-file-medical-text"></i> التقرير الطبي الشامل</span>
            <img src="<?= asset('img/logo.png') ?>" style="width:40px;height:40px;border-radius:8px;">
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><td>الاسم:</td><td class="fw-bold"><?= e($user['full_name']) ?></td>
                <td>الرقم المميز:</td><td><span class="uid-code"><?= e($user['unique_id']) ?></span></td></tr>
                <tr><td>الجنس:</td><td><?= genderLabel($user['gender']) ?></td>
                <td>تاريخ الميلاد:</td><td><?= formatDate($user['birth_date']) ?></td></tr>
                <tr><td>الهاتف:</td><td dir="ltr"><?= e($user['phone']) ?></td>
                <td>تاريخ التقرير:</td><td><?= formatDate(now()) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-clipboard2-data text-purple"></i> نتائج التحاليل (<?= count($orders) ?>)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>التحليل</th><th>القيمة</th><th>الوحدة</th><th>النطاق</th><th>العلم</th><th>التاريخ</th><th>الطبيب</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                                <td class="fw-bold"><?= e($o['result_value']) ?></td>
                                <td><?= e($o['unit']) ?></td>
                                <td><?= e($o['normal_range']) ?></td>
                                <td><?= statusBadge($o['flag']) ?></td>
                                <td class="small"><?= formatDate($o['performed_at']) ?></td>
                                <td class="small"><?= e($o['doctor_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">لا نتائج بعد</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($latestTreatment): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-capsules text-pink"></i> آخر خطة علاج</div>
        <div class="card-body">
            <h6 class="fw-bold text-purple"><?= e($latestTreatment['treatment_name']) ?></h6>
            <div class="small text-muted mb-2">بقلم <?= e($latestTreatment['doctor_name']) ?> — <?= formatDate($latestTreatment['created_at'], true) ?></div>
            <div class="treatment-display"><?= $latestTreatment['description_html'] ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="no-print mt-3">
    <button class="btn btn-primary" onclick="printToPDF('reportSection', 'تقرير-طبي-<?= e($user['unique_id']) ?>.pdf')">
        <i class="bi bi-download"></i> تحميل PDF
    </button>
    <button class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> طباعة مباشرة</button>
</div>
