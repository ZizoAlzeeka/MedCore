<?php /** Doctor: schedule management — calendar view + multi-day add */
$csrf = Auth::csrfToken();
$daysMap = ['sat'=>'السبت','sun'=>'الأحد','mon'=>'الإثنين','tue'=>'الثلاثاء','wed'=>'الأربعاء','thu'=>'الخميس','fri'=>'الجمعة'];

// Build dataMap for calendar: { '2026-07-15': [{start, end, duration, id, available}], ... }
$scheduleMap = [];
foreach ($schedules as $s) {
    $date = $s['work_date'];
    if (!isset($scheduleMap[$date])) $scheduleMap[$date] = [];
    $scheduleMap[$date][] = $s;
}
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar3"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">اضغط على أي يوم في التقويم لعرض أو إضافة فترات الدوام</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
        <i class="bi bi-plus-circle"></i> إضافة فترات
    </button>
</div>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-3">
                <div id="scheduleCalendar"></div>
            </div>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header gradient"><i class="bi bi-bar-chart"></i> إحصائيات الدوام</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="small text-muted">إجمالي الفترات:</span>
                    <strong><?= count($schedules) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small text-muted">أيام متاحة:</span>
                    <strong><?= count(array_unique(array_column($schedules, 'work_date'))) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="small text-muted">الفترة القادمة:</span>
                    <strong class="small"><?= !empty($schedules) ? formatDate($schedules[0]['work_date']) : '—' ?></strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-list-check text-purple"></i> القادمة</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                    <?php foreach (array_slice($schedules, 0, 10) as $s): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small"><?= formatDate($s['work_date']) ?></div>
                                <div class="text-muted" style="font-size:11px;" dir="ltr"><?= $s['start_time'] ?> - <?= $s['end_time'] ?></div>
                            </div>
                            <form method="post" action="<?= url('/doctor/schedule/' . $s['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('حذف الفترة؟')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($schedules)): ?>
                        <div class="list-group-item text-center text-muted py-3 small">لا فترات دوام</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day Detail Modal -->
<div class="modal fade" id="dayDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-day text-purple"></i> <span id="dayDetailTitle">تفاصيل اليوم</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dayDetailContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" onclick="openAddForDay()"><i class="bi bi-plus"></i> إضافة فترة لهذا اليوم</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= url('/doctor/schedule/store') ?>" id="scheduleForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> إضافة فترات دوام</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">اختر الأيام <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">يمكنك تحديد عدة أيام بنفس الفترة</small>
                        <div id="datePickerContainer">
                            <div class="d-flex gap-1 mb-2">
                                <input type="date" name="dates[]" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" required>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()" title="حذف"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addDateField()">+ يوم</button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addQuickDate(0)">اليوم</button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addQuickDate(1)">غداً</button>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> حفظ الفترات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var scheduleData = <?= json_encode($scheduleMap, JSON_UNESCAPED_UNICODE) ?>;
var selectedDayDate = null;

function initScheduleCalendar() {
    if (typeof createCalendar !== 'function') {
        setTimeout(initScheduleCalendar, 100);
        return;
    }
    var minDate = '<?= date("Y-m-d") ?>';
    createCalendar('scheduleCalendar', {
        currentDate: new Date(),
        minDate: minDate,
        dataMap: scheduleData,
        onDayClick: function(dateStr) {
            showDayDetail(dateStr);
        }
    });
}

function showDayDetail(dateStr) {
    selectedDayDate = dateStr;
    var daysMap = {0:'الأحد',1:'الإثنين',2:'الثلاثاء',3:'الأربعاء',4:'الخميس',5:'الجمعة',6:'السبت'};
    var d = new Date(dateStr);
    var dayName = daysMap[d.getDay()];
    document.getElementById('dayDetailTitle').textContent = dayName + ' — ' + dateStr;

    var slots = scheduleData[dateStr] || [];
    var html = '';
    if (slots.length === 0) {
        html = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x" style="font-size:32px;opacity:0.4;"></i><p class="mt-2">لا توجد فترات دوام في هذا اليوم</p></div>';
    } else {
        html = '<div class="cal-day-slots">';
        slots.forEach(function(s) {
            html += '<div class="cal-day-slot">' +
                '<div class="cal-day-slot-time" dir="ltr">' + s.start_time + ' - ' + s.end_time + '</div>' +
                '<div class="cal-day-slot-label">مدة الكشف: ' + s.slot_duration_min + ' دقيقة</div>' +
                '<form method="post" action="<?= url("/doctor/schedule") ?>/' + s.id + '/delete" style="margin-top:8px;" onsubmit="return confirm(\'حذف الفترة؟\')">' +
                '<input type="hidden" name="csrf_token" value="<?= $csrf ?>">' +
                '<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> حذف</button>' +
                '</form></div>';
        });
        html += '</div>';
    }
    document.getElementById('dayDetailContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('dayDetailModal')).show();
}

function openAddForDay() {
    bootstrap.Modal.getInstance(document.getElementById('dayDetailModal')).hide();
    // Clear existing dates and add the selected day
    document.getElementById('datePickerContainer').innerHTML = '';
    addDateField(selectedDayDate);
    new bootstrap.Modal(document.getElementById('addScheduleModal')).show();
}

function addDateField(dateStr) {
    var container = document.getElementById('datePickerContainer');
    var div = document.createElement('div');
    div.className = 'd-flex gap-1 mb-2';
    var minDate = '<?= date("Y-m-d") ?>';
    div.innerHTML = '<input type="date" name="dates[]" class="form-control form-control-sm" min="' + minDate + '" required value="' + (dateStr||'') + '">' +
        '<button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>';
    container.appendChild(div);
}

function addQuickDate(daysFromToday) {
    var d = new Date();
    d.setDate(d.getDate() + daysFromToday);
    addDateField(d.toISOString().split('T')[0]);
}

function addWeekdays() {
    var today = new Date();
    for (var i = 0; i < 7; i++) {
        var d = new Date(today);
        d.setDate(today.getDate() + i);
        if (d.getDay() !== 5) {
            addDateField(d.toISOString().split('T')[0]);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScheduleCalendar);
} else {
    initScheduleCalendar();
}
document.addEventListener('spa:navigated', initScheduleCalendar);
</script>
