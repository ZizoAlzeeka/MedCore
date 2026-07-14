<?php
/** Patient: print report — uses window.print() with print-specific CSS (no html2pdf) */
$userUid = $user['unique_id'] ?? '';
?>
<style>
/* ===== Print-specific report layout ===== */
@media print {
    /* Hide everything except the report */
    body * { visibility: hidden !important; }
    #reportArea, #reportArea * { visibility: visible !important; }
    #reportArea { position: absolute; top: 0; right: 0; left: 0; width: 100%; }
    .no-print { display: none !important; }
    .sidebar, .topbar, .app-footer, .conn-status, #page-loader-bar { display: none !important; }

    @page { margin: 12mm; }
    body { background: #fff !important; }

    .pr-card { box-shadow: none !important; border: 1px solid #ccc !important; page-break-inside: avoid; }
    .pr-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pr-results-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

#reportArea { direction: rtl; }

.pr-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.pr-header {
    background: linear-gradient(135deg, #6C63FF, #9D4EDD);
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pr-header .title { font-weight: 700; font-size: 16px; }
.pr-header img { width: 42px; height: 42px; border-radius: 8px; background: #fff; padding: 3px; }
.pr-body { padding: 16px 20px; }

.pr-info-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.pr-info-table td { padding: 6px 10px; border: 1px solid #e5e7eb; }
.pr-info-table td.label { background: #f8faff; color: #6C63FF; font-weight: 700; width: 18%; }
.pr-info-table td.value { font-weight: 600; width: 32%; }

.pr-section-title {
    font-weight: 700;
    color: #6C63FF;
    padding: 8px 12px;
    background: rgba(108,99,255,0.06);
    border-bottom: 2px solid rgba(108,99,255,0.2);
    margin: 0;
    font-size: 13px;
}

.pr-results-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
.pr-results-table th, .pr-results-table td { padding: 5px 8px; border: 1px solid #e5e7eb; text-align: right; }
.pr-results-table th {
    background: rgba(108,99,255,0.08);
    color: #6C63FF;
    font-weight: 700;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.pr-results-table td.center { text-align: center; }
.pr-results-table .loinc { font-family: monospace; font-weight: 700; color: #6C63FF; direction: ltr; unicode-bidi: embed; }

.pr-flag { padding: 2px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 700; }
.pr-flag.normal { background: #d1fae5; color: #065f46; }
.pr-flag.high { background: #fef3c7; color: #92400e; }
.pr-flag.low { background: #dbeafe; color: #1e3a8a; }
.pr-flag.abnormal { background: #fee2e2; color: #991b1b; }

.pr-treatment {
    border-right: 3px solid #FF6584;
    padding: 8px 12px;
    background: #fff5f7;
    border-radius: 4px;
    margin-top: 6px;
}
.pr-treatment .name { font-weight: 700; color: #6C63FF; margin-bottom: 4px; }
.pr-treatment .meta { font-size: 11px; color: #636E72; margin-bottom: 6px; }
.pr-treatment .desc { font-size: 12px; line-height: 1.7; }
.pr-treatment .desc p { margin: 4px 0; }

.pr-footer {
    text-align: center;
    padding: 10px;
    color: #636E72;
    font-size: 11px;
    border-top: 1px solid #e5e7eb;
    margin-top: 16px;
}
.pr-footer .sig { display: inline-block; margin: 0 30px; }
</style>

<div class="page-header no-print">
    <div>
        <h2 class="page-title"><i class="bi bi-printer-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">تقرير طبي شامل — استخدم زر الطباعة أو Ctrl+P</div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> طباعة / حفظ PDF
    </button>
</div>

<div id="reportArea">
    <!-- Header / Patient info -->
    <div class="pr-card">
        <div class="pr-header pr-header">
            <span class="title"><i class="bi bi-file-medical-text"></i> التقرير الطبي الشامل</span>
            <img src="<?= asset('img/logo.png') ?>" alt="logo">
        </div>
        <div class="pr-body">
            <table class="pr-info-table">
                <tr>
                    <td class="label">الاسم</td>
                    <td class="value"><?= e($user['full_name']) ?></td>
                    <td class="label">الرقم المميز</td>
                    <td class="value"><?= e($user['unique_id']) ?></td>
                </tr>
                <tr>
                    <td class="label">الجنس</td>
                    <td class="value"><?= genderLabel($user['gender']) ?></td>
                    <td class="label">تاريخ الميلاد</td>
                    <td class="value"><?= formatDate($user['birth_date']) ?></td>
                </tr>
                <tr>
                    <td class="label">الهاتف</td>
                    <td class="value" dir="ltr"><?= e($user['phone']) ?></td>
                    <td class="label">تاريخ التقرير</td>
                    <td class="value"><?= formatDate(now()) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Results -->
    <div class="pr-card">
        <div class="pr-section-title"><i class="bi bi-clipboard2-data"></i> نتائج التحاليل (<?= count($orders) ?>)</div>
        <div class="pr-body" style="padding:0;">
            <table class="pr-results-table">
                <thead>
                    <tr>
                        <th>التحليل</th>
                        <th>القيمة</th>
                        <th>الوحدة</th>
                        <th>النطاق الطبيعي</th>
                        <th>العلم</th>
                        <th>تاريخ التنفيذ</th>
                        <th>الطبيب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $flagColors = [
                                'normal' => 'normal',
                                'high' => 'high',
                                'low' => 'low',
                                'abnormal' => 'abnormal',
                            ];
                            $flagClass = $flagColors[$o['flag']] ?? 'normal';
                            $flagLabel = statusLabel($o['flag']);
                        ?>
                        <tr>
                            <td><span class="loinc" dir="ltr"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                            <td class="center fw-bold" dir="ltr"><?= e($o['result_value']) ?></td>
                            <td class="center" dir="ltr"><?= e($o['unit']) ?></td>
                            <td class="center" dir="ltr"><?= e($o['normal_range']) ?></td>
                            <td class="center"><span class="pr-flag <?= $flagClass ?>"><?= $flagLabel ?></span></td>
                            <td class="center"><?= formatDate($o['performed_at']) ?></td>
                            <td class="center"><?= e($o['doctor_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="center" style="padding:16px;color:#636E72;">لا نتائج تحاليل مكتملة بعد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Treatment -->
    <?php if ($latestTreatment): ?>
    <div class="pr-card">
        <div class="pr-section-title"><i class="bi bi-capsules"></i> آخر خطة علاج</div>
        <div class="pr-body">
            <div class="pr-treatment">
                <div class="name"><?= e($latestTreatment['treatment_name']) ?></div>
                <div class="meta">بقلم <?= e($latestTreatment['doctor_name']) ?> — <?= formatDate($latestTreatment['created_at'], true) ?></div>
                <div class="desc"><?= $latestTreatment['description_html'] ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="pr-footer">
        <div>تم إنشاء هذا التقرير بواسطة منصة MedCore في <?= formatDate(now(), true) ?></div>
        <div style="margin-top:8px;">
            <span class="sig">توقيع الطبيب: ____________________</span>
            <span class="sig">ختم المستشفى: ____________________</span>
        </div>
    </div>
</div>

<div class="no-print mt-3 d-flex gap-2">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> طباعة</button>
    <a href="<?= url('/patient') ?>" class="btn btn-secondary spa-link" data-spa="1"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<script>
// Auto-open print dialog when arriving via ?auto=1
if (new URLSearchParams(window.location.search).get('auto') === '1') {
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() { window.print(); }, 600);
    });
}
</script>
