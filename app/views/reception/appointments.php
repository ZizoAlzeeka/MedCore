<?php /** Reception: appointments list — live search */
$csrf = Auth::csrfToken();
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">جميع المواعيد — يمكن الإلغاء</div>
    </div>
    <a href="<?= url('/reception/book') ?>" class="btn btn-primary btn-sm spa-link" data-spa="1" data-url="<?= url('/reception/book') ?>"><i class="bi bi-plus"></i> حجز جديد</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="apptSearchInput" class="form-control" placeholder="ابحث بالاسم، الرقم المميز، الطبيب، القسم..." oninput="filterAppts()">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead><tr><th>#</th><th>المريض</th><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>سجّلها</th><th>الحالة</th><th>إجراء</th></tr></thead>
                <tbody id="apptTableBody">
                    <?php foreach ($appts as $a): ?>
                        <tr data-search="<?= e(strtolower($a['patient_name'] . ' ' . $a['patient_uid'] . ' ' . $a['doctor_name'] . ' ' . ($a['department_name'] ?? '') . ' ' . $a['receptionist_name'])) ?>">
                            <td><?= $a['id'] ?></td>
                            <td class="fw-bold"><?= e($a['patient_name']) ?> <span class="uid-code"><?= e($a['patient_uid']) ?></span></td>
                            <td class="small"><?= e($a['doctor_name']) ?></td>
                            <td><span class="badge bg-info"><?= e($a['department_name']) ?></span></td>
                            <td><?= formatDate($a['appt_date'], true) ?></td>
                            <td class="small text-muted"><?= e($a['receptionist_name']) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td>
                                <?php if ($a['status'] === 'booked'): ?>
                                    <form method="post" action="<?= url('/reception/appointments/' . $a['id'] . '/cancel') ?>" style="display:inline" onsubmit="return confirm('إلغاء الموعد؟')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> إلغاء</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">لا مواعيد</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterAppts() {
    var q = (document.getElementById('apptSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#apptTableBody tr');
    var visible = 0;
    rows.forEach(function(r) {
        if (!r.dataset.search) return;
        var match = r.dataset.search.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
    });
}
</script>
