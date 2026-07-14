<?php /** Doctor: schedule management — multi-day support */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-clock-history"></i> <?= e($title) ?></h2>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-plus-circle"></i> إضافة فترة دوام</div>
            <div class="card-body">
                <form method="post" action="<?= url('/doctor/schedule/store') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">اختر الأيام <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">يمكنك تحديد عدة أيام بنفس الفترة</small>
                        <div id="datePickerContainer">
                            <div class="d-flex gap-1 mb-2">
                                <input type="date" name="dates[]" class="form-control form-control-sm date-input" min="<?= date('Y-m-d') ?>">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDateField()" title="إضافة يوم آخر"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                        <div id="dateList" class="mt-2"></div>
                    </div>

                    <!-- Quick date buttons -->
                    <div class="mb-3">
                        <small class="text-muted">إضافة سريعة:</small>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addQuickDate(0)">اليوم</button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addQuickDate(1)">غداً</button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addQuickDate(2)">بعد غد</button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addWeekdays()">أيام الأسبوع</button>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">من <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control form-control-sm" required value="09:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">إلى <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control form-control-sm" required value="13:00">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">مدة الكشف (دقائق)</label>
                        <input type="text" name="slot_duration_min" class="form-control form-control-sm" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" value="20">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> حفظ الفترات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-week text-purple"></i> فترات الدوام (<?= count($schedules) ?>)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 medcore-table">
                        <thead><tr><th>التاريخ</th><th>اليوم</th><th>من</th><th>إلى</th><th>مدة الكشف</th><th>الحالة</th><th>إجراء</th></tr></thead>
                        <tbody>
                            <?php
                            $daysMap = ['sat'=>'السبت','sun'=>'الأحد','mon'=>'الإثنين','tue'=>'الثلاثاء','wed'=>'الأربعاء','thu'=>'الخميس','fri'=>'الجمعة'];
                            foreach ($schedules as $s):
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= formatDate($s['work_date']) ?></td>
                                    <td><?= $daysMap[$s['day_of_week']] ?? $s['day_of_week'] ?></td>
                                    <td dir="ltr"><?= $s['start_time'] ?></td>
                                    <td dir="ltr"><?= $s['end_time'] ?></td>
                                    <td><?= $s['slot_duration_min'] ?> د</td>
                                    <td>
                                        <?php if ($s['is_available']): ?>
                                            <span class="badge bg-success">متاح</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">معطّل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= url('/doctor/schedule/' . $s['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('حذف الفترة؟')">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($schedules)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> لا فترات دوام — أضف فترة جديدة</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addDateField(dateStr) {
    var container = document.getElementById('datePickerContainer');
    var div = document.createElement('div');
    div.className = 'd-flex gap-1 mb-2 date-row';
    var minDate = '<?= date("Y-m-d") ?>';
    div.innerHTML = '<input type="date" name="dates[]" class="form-control form-control-sm date-input" min="' + minDate + '" value="' + (dateStr||'') + '">' +
        '<button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()" title="حذف"><i class="bi bi-x"></i></button>';
    container.appendChild(div);
}

function addQuickDate(daysFromToday) {
    var d = new Date();
    d.setDate(d.getDate() + daysFromToday);
    var dateStr = d.toISOString().split('T')[0];
    addDateField(dateStr);
}

function addWeekdays() {
    var today = new Date();
    for (var i = 0; i < 7; i++) {
        var d = new Date(today);
        d.setDate(today.getDate() + i);
        var day = d.getDay(); // 0=Sun, 6=Sat
        if (day !== 5) { // Skip Friday (weekend in Saudi)
            var dateStr = d.toISOString().split('T')[0];
            addDateField(dateStr);
        }
    }
}
</script>
