<?php /** Reception: doctor schedules — calendar view */
$csrf = Auth::csrfToken();
$daysMap = ['sat'=>'السبت','sun'=>'الأحد','mon'=>'الإثنين','tue'=>'الثلاثاء','wed'=>'الأربعاء','thu'=>'الخميس','fri'=>'الجمعة'];
$scheduleMap = [];
foreach ($schedules as $s) {
    $date = $s['work_date'];
    if (!isset($scheduleMap[$date])) $scheduleMap[$date] = [];
    $scheduleMap[$date][] = $s;
}
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar3-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle"><?= $selectedDoctor ? 'الطبيب: ' . e($selectedDoctor['full_name']) . ' — ' . e($selectedDoctor['dept']) : 'اختر طبيباً لعرض جدول دوامه' ?></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-10">
                <label class="form-label small">اختر الطبيب</label>
                <select name="doctor_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— اختر طبيباً —</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $doctorId==$d['id']?'selected':'' ?>><?= e($d['full_name']) ?> — <?= e($d['department_name']) ?> (<?= e($d['specialty'] ?: '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-info btn-sm w-100"><i class="bi bi-eye"></i> عرض</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedDoctor): ?>
<div class="card">
    <div class="card-body p-3">
        <div id="doctorScheduleCalendar"></div>
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
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
var scheduleData = <?= json_encode($scheduleMap, JSON_UNESCAPED_UNICODE) ?>;

function initDoctorScheduleCalendar() {
    if (typeof createCalendar !== 'function') {
        setTimeout(initDoctorScheduleCalendar, 100);
        return;
    }
    createCalendar('doctorScheduleCalendar', {
        currentDate: new Date(),
        dataMap: scheduleData,
        onDayClick: function(dateStr) {
            var daysMap = {0:'الأحد',1:'الإثنين',2:'الثلاثاء',3:'الأربعاء',4:'الخميس',5:'الجمعة',6:'السبت'};
            var d = new Date(dateStr);
            document.getElementById('dayDetailTitle').textContent = daysMap[d.getDay()] + ' — ' + dateStr;

            var slots = scheduleData[dateStr] || [];
            var html = '';
            if (slots.length === 0) {
                html = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x" style="font-size:32px;opacity:0.4;"></i><p class="mt-2">لا توجد فترات دوام في هذا اليوم</p></div>';
            } else {
                html = '<div class="cal-day-slots">';
                slots.forEach(function(s) {
                    var status = s.is_available ? '<span class="badge bg-success">متاح</span>' : '<span class="badge bg-danger">معطّل</span>';
                    html += '<div class="cal-day-slot">' +
                        '<div class="cal-day-slot-time" dir="ltr">' + s.start_time + ' - ' + s.end_time + '</div>' +
                        '<div class="cal-day-slot-label">مدة الكشف: ' + s.slot_duration_min + ' دقيقة</div>' +
                        '<div style="margin-top:4px;">' + status + '</div>' +
                        '</div>';
                });
                html += '</div>';
            }
            document.getElementById('dayDetailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('dayDetailModal')).show();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDoctorScheduleCalendar);
} else {
    initDoctorScheduleCalendar();
}
document.addEventListener('spa:navigated', initDoctorScheduleCalendar);
</script>
<?php endif; ?>
