<?php /** Patient: results list */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-clipboard2-data-fill"></i> <?= e($title) ?></h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>التحليل</th><th>الحالة</th><th>النتيجة</th><th>الوحدة</th><th>العلم</th><th>التاريخ</th><th>إجراء</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><span class="loinc-code"><?= e($o['loinc_code']) ?></span> <?= e($o['name_ar']) ?></td>
                            <td><?= statusBadge($o['status']) ?></td>
                            <td class="fw-bold"><?= $o['result_value'] ? e($o['result_value']) : '-' ?></td>
                            <td class="small"><?= e($o['unit']) ?></td>
                            <td><?= $o['flag'] ? statusBadge($o['flag']) : '-' ?></td>
                            <td class="small text-muted"><?= formatDate($o['ordered_at']) ?></td>
                            <td>
                                <?php if ($o['status'] === 'result_uploaded'): ?>
                                    <a href="<?= url('/patient/results/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> عرض</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">لا تحاليل بعد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
