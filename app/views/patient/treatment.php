<?php /** Patient: treatment plans — uses window.print() */ ?>
<div class="page-header no-print">
    <h2 class="page-title"><i class="bi bi-prescription2"></i> <?= e($title) ?></h2>
</div>

<?php if (empty($treatments)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state"><i class="bi bi-capsule"></i><h5>لا توجد خطط علاج</h5><p>ستظهر هنا خطط العلاج التي يكتبها لك طبيبك بعد إجراء التحاليل.</p></div>
        </div>
    </div>
<?php else: foreach ($treatments as $t): ?>
    <div class="card mb-3">
        <div class="card-header gradient">
            <i class="bi bi-capsule"></i>
            <?= e($t['treatment_name']) ?>
            <?php if (!empty($t['test_name'])): ?>
                <span class="badge bg-light text-dark">لتحليل: <?= e($t['test_name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">
                <i class="bi bi-person-badge"></i> <?= e($t['doctor_name']) ?> —
                <i class="bi bi-clock"></i> <?= formatDate($t['created_at'], true) ?>
            </div>
            <div class="treatment-display"><?= $t['description_html'] ?></div>
            <button class="btn btn-sm btn-primary mt-3 no-print" onclick="printTreatment('treatment-print-<?= $t['id'] ?>')">
                <i class="bi bi-printer"></i> طباعة
            </button>
        </div>
    </div>
    <!-- Hidden printable version of this treatment plan -->
    <div id="treatment-print-<?= $t['id'] ?>" class="treatment-print-area" style="display:none;">
        <div style="text-align:center;border-bottom:2px solid #6C63FF;padding-bottom:10px;margin-bottom:16px;">
            <h2 style="color:#6C63FF;margin:0;">خطة العلاج</h2>
            <div style="font-size:12px;color:#636E72;margin-top:4px;">منصة MedCore — <?= formatDate(now(), true) ?></div>
        </div>
        <table style="width:100%;font-size:13px;border-collapse:collapse;margin-bottom:14px;">
            <tr><td style="padding:5px 8px;border:1px solid #eee;background:#f8faff;width:25%;font-weight:700;color:#6C63FF;">المريض</td><td style="padding:5px 8px;border:1px solid #eee;"><?= e(Auth::name()) ?> — <span style="font-family:monospace;"><?= e(Auth::uniqueId()) ?></span></td></tr>
            <tr><td style="padding:5px 8px;border:1px solid #eee;background:#f8faff;font-weight:700;color:#6C63FF;">الطبيب</td><td style="padding:5px 8px;border:1px solid #eee;"><?= e($t['doctor_name']) ?></td></tr>
            <tr><td style="padding:5px 8px;border:1px solid #eee;background:#f8faff;font-weight:700;color:#6C63FF;">التاريخ</td><td style="padding:5px 8px;border:1px solid #eee;"><?= formatDate($t['created_at'], true) ?></td></tr>
            <?php if (!empty($t['test_name'])): ?>
            <tr><td style="padding:5px 8px;border:1px solid #eee;background:#f8faff;font-weight:700;color:#6C63FF;">التحليل</td><td style="padding:5px 8px;border:1px solid #eee;"><?= e($t['test_name']) ?></td></tr>
            <?php endif; ?>
        </table>
        <h3 style="color:#6C63FF;margin:10px 0 6px 0;"><?= e($t['treatment_name']) ?></h3>
        <div style="font-size:13px;line-height:1.8;"><?= $t['description_html'] ?></div>
        <div style="margin-top:30px;text-align:center;color:#636E72;font-size:11px;border-top:1px solid #ddd;padding-top:10px;">
            <span style="margin:0 30px;">توقيع الطبيب: ____________________</span>
            <span>ختم المستشفى: ____________________</span>
        </div>
    </div>
<?php endforeach; endif; ?>

<style>
@media print {
    body * { visibility: hidden !important; }
    .treatment-print-area, .treatment-print-area * { visibility: visible !important; }
    .treatment-print-area { display: block !important; position: absolute; top: 0; right: 0; left: 0; width: 100%; padding: 20mm; }
    .no-print { display: none !important; }
    .sidebar, .topbar, .app-footer, .conn-status { display: none !important; }
    @page { margin: 12mm; }
}
</style>

<script>
function printTreatment(id) {
    var area = document.getElementById(id);
    if (!area) return;
    // Show the printable area temporarily, trigger print, then hide
    area.style.display = 'block';
    area.classList.add('active-print');
    window.print();
    // Hide again after print dialog
    setTimeout(function() {
        area.style.display = 'none';
        area.classList.remove('active-print');
    }, 500);
}
</script>
