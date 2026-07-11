<?php /** Patient: treatment plans */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-capsules"></i> <?= e($title) ?></h2>
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
            <button class="btn btn-sm btn-primary mt-3 no-print" onclick="printToPDF('treatment-<?= $t['id'] ?>', 'خطة-علاج-<?= $t['id'] ?>.pdf')">
                <i class="bi bi-printer"></i> طباعة
            </button>
        </div>
    </div>
    <div id="treatment-<?= $t['id'] ?>" style="display:none;">
        <h3>خطة العلاج</h3>
        <p>المريض: <?= e(Auth::name()) ?> — <?= e(Auth::uniqueId()) ?></p>
        <p>الطبيب: <?= e($t['doctor_name']) ?></p>
        <p>التاريخ: <?= formatDate($t['created_at'], true) ?></p>
        <h4><?= e($t['treatment_name']) ?></h4>
        <div><?= $t['description_html'] ?></div>
    </div>
<?php endforeach; endif; ?>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
