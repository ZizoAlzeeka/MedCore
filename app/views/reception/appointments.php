<?php /** Reception: appointments list with live search */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar-check-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">جميع المواعيد — <?= count($appts) ?> موعد — يمكن الإلغاء</div>
    </div>
    <a href="<?= url('/reception/book') ?>" class="btn btn-primary btn-sm spa-link" data-spa="1" data-url="<?= url('/reception/book') ?>"><i class="bi bi-plus"></i> حجز جديد</a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="apptSearchInput" class="form-control" placeholder="ابحث برقم المريض، اسم المريض، الطبيب، القسم، التاريخ..." oninput="filterAppointments()">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قائمة المواعيد</span>
        <small class="text-muted" id="apptResultCount"><?= count($appts) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead><tr><th>#</th><th>المريض</th><th>الطبيب</th><th>القسم</th><th>الموعد</th><th>سجّلها</th><th>الحالة</th><th>إجراء</th></tr></thead>
                <tbody id="apptTableBody">
                    <?php foreach ($appts as $a): ?>
                        <tr data-search="<?php
                            $searchStr = '';
                            $searchStr .= e($a['patient_name'] ?? '') . ' ';
                            $searchStr .= e($a['patient_uid'] ?? '') . ' ';
                            $searchStr .= e($a['doctor_name'] ?? '') . ' ';
                            $searchStr .= e($a['department_name'] ?? '') . ' ';
                            $searchStr .= e($a['receptionist_name'] ?? '') . ' ';
                            $searchStr .= e($a['status'] ?? '') . ' ';
                            $searchStr .= e(formatDate($a['appt_date'], true)) . ' ';
                            $searchStr .= e(statusLabel($a['status']));
                            echo strtolower($searchStr);
                        ?>">
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
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> إلغاء</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
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
function filterAppointments() {
    var q = (document.getElementById('apptSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#apptTableBody tr[data-search]');
    var visible = 0;
    rows.forEach(function(row) {
        var hay = row.getAttribute('data-search') || '';
        var match = !q || hay.indexOf(q) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var countEl = document.getElementById('apptResultCount');
    if (countEl) countEl.textContent = visible + ' سجل';
}
</script>
