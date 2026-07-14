<?php /** Reception: book appointment — calendar date picker + live patient search */
$csrf = Auth::csrfToken();
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar-plus-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">اختر المريض ثم القسم ثم الطبيب ثم التاريخ من التقويم</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-calendar-check"></i> بيانات الحجز</div>
            <div class="card-body">
                <form method="post" action="<?= url('/reception/book') ?>" id="bookForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <!-- Search patient -->
                    <div class="mb-3">
                        <label class="form-label">المريض <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="patientSearch" class="form-control" placeholder="ابحث بالاسم أو الرقم المميز أو الهاتف..." autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#newPatientModal">
                                <i class="bi bi-person-plus"></i> مريض جديد
                            </button>
                        </div>
                        <input type="hidden" name="patient_id" id="patient_id" required>
                        <div id="patientResults" class="mt-1" style="max-height:200px;overflow-y:auto;"></div>
                        <div id="patientInfo" class="mt-2"></div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">القسم <span class="text-danger">*</span></label>
                            <select name="department" id="deptSelect" class="form-select" required>
                                <option value="">— اختر —</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['name_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الطبيب <span class="text-danger">*</span></label>
                            <select name="doctor_id" id="doctorSelect" class="form-select" required disabled>
                                <option value="">اختر القسم أولاً</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="hidden" name="appt_date" id="appt_date" required>
                        <div id="bookCalendar" style="max-width:500px;"></div>
                        <div id="selectedDateInfo" class="mt-2"></div>
                    </div>

                    <div class="mt-3" id="slotsSection" style="display:none;">
                        <label class="form-label">المواعيد المتاحة <span class="text-danger">*</span></label>
                        <div id="slotsContainer"></div>
                    </div>

                    <div class="mt-2">
                        <label class="form-label">سبب الزيارة</label>
                        <input type="text" name="reason" class="form-control" placeholder="اختياري">
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> تأكيد الحجز</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle text-purple"></i> إرشادات الحجز</div>
            <div class="card-body">
                <ol class="small">
                    <li>ابحث عن المريض بالاسم أو الرقم المميز.</li>
                    <li>اختر القسم الطبي المناسب.</li>
                    <li>سيظهر قائمة الأطباء في القسم — اختر الطبيب.</li>
                    <li>اضغط على يوم في التقويم لعرض المواعيد المتاحة.</li>
                    <li>اختر الموعد المتاح وأكّد الحجز.</li>
                </ol>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle"></i> المواعيد المحجوزة تظهر باللون الأحمر.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Patient Modal -->
<div class="modal fade" id="newPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('/reception/register-patient') ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus"></i> تسجيل مريض جديد</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small">الاسم الكامل *</label><input type="text" name="full_name" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small">البريد *</label><input type="email" name="email" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small">رقم الموبايل *</label><input type="text" name="phone" class="form-control form-control-sm" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">الجنس *</label><select name="gender" class="form-select form-select-sm" required><option value="">—</option><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                        <div class="col-6"><label class="form-label small">تاريخ الميلاد</label><input type="date" name="birth_date" class="form-control form-control-sm"></div>
                    </div>
                    <div class="mt-2"><label class="form-label small">العنوان</label><input type="text" name="address" class="form-control form-control-sm"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> تسجيل</button></div>
            </form>
        </div>
    </div>
</div>

<script>
// ===== Patient search — robust, re-binds on SPA navigation =====
function initPatientSearch() {
    var input = document.getElementById('patientSearch');
    if (!input || input.dataset.bound === '1') return;
    input.dataset.bound = '1';

    var timer = null;
    input.addEventListener('input', function() {
        // Clear selected patient when user starts typing again
        document.getElementById('patient_id').value = '';
        document.getElementById('patientInfo').innerHTML = '';

        clearTimeout(timer);
        var q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('patientResults').innerHTML = '';
            return;
        }
        timer = setTimeout(function() {
            fetch('/reception/search-patient?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var c = document.getElementById('patientResults');
                    if (!data.patients || data.patients.length === 0) {
                        c.innerHTML = '<div class="small text-muted p-2">لا نتائج — سجّل مريضاً جديداً</div>';
                        return;
                    }
                    c.innerHTML = data.patients.map(function(p) {
                        return '<div class="border rounded p-2 mb-1 cursor-pointer" onclick="selectPatient(' + p.id + ', \'' + (p.full_name||'').replace(/'/g, "\\'") + '\', \'' + p.unique_id + '\', \'' + p.phone + '\')">' +
                            '<strong>' + p.full_name + '</strong> <span class="uid-code">' + p.unique_id + '</span> — ' + p.phone + '</div>';
                    }).join('');
                })
                .catch(function() {});
        }, 300);
    });
}

function selectPatient(id, name, uid, phone) {
    document.getElementById('patient_id').value = id;
    document.getElementById('patientSearch').value = name;
    document.getElementById('patientResults').innerHTML = '';
    document.getElementById('patientInfo').innerHTML = '<div class="alert alert-light border small">المريض المختار: <strong>' + name + '</strong> — UID: <span class="uid-code">' + uid + '</span> — الهاتف: ' + phone + '</div>';
}

// ===== Department → Doctors =====
function initDeptSelect() {
    var sel = document.getElementById('deptSelect');
    if (!sel || sel.dataset.bound === '1') return;
    sel.dataset.bound = '1';

    sel.addEventListener('change', function() {
        var deptId = this.value;
        var doctorSelect = document.getElementById('doctorSelect');
        doctorSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        doctorSelect.disabled = true;
        if (!deptId) { doctorSelect.innerHTML = '<option value="">اختر القسم أولاً</option>'; return; }
        fetch('/ajax/doctors/by-department?department_id=' + deptId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                doctorSelect.innerHTML = '<option value="">— اختر الطبيب —</option>';
                data.doctors.forEach(function(d) {
                    doctorSelect.innerHTML += '<option value="' + d.id + '">' + d.full_name + (d.specialty ? ' (' + d.specialty + ')' : '') + '</option>';
                });
                doctorSelect.disabled = false;
            });
    });
}

// ===== Doctor change → init calendar =====
function initDoctorSelect() {
    var sel = document.getElementById('doctorSelect');
    if (!sel || sel.dataset.bound === '1') return;
    sel.dataset.bound = '1';

    sel.addEventListener('change', function() {
        if (this.value) initBookCalendar(this.value);
    });
}

// ===== Calendar for date selection =====
function initBookCalendar(doctorId) {
    if (typeof createCalendar !== 'function') {
        setTimeout(function() { initBookCalendar(doctorId); }, 100);
        return;
    }
    var minDate = '<?= date("Y-m-d") ?>';
    createCalendar('bookCalendar', {
        currentDate: new Date(),
        minDate: minDate,
        onDayClick: function(dateStr) {
            loadSlotsForDate(doctorId, dateStr);
        }
    });
}

function loadSlotsForDate(doctorId, dateStr) {
    document.getElementById('appt_date').value = '';
    document.getElementById('selectedDateInfo').innerHTML = '<small class="text-muted">جاري تحميل المواعيد المتاحة...</small>';
    document.getElementById('slotsSection').style.display = 'none';

    fetch('/ajax/doctor/' + doctorId + '/slots?date=' + dateStr)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var daysMap = {0:'الأحد',1:'الإثنين',2:'الثلاثاء',3:'الأربعاء',4:'الخميس',5:'الجمعة',6:'السبت'};
            var d = new Date(dateStr);
            document.getElementById('selectedDateInfo').innerHTML = '<div class="alert alert-light border small"><i class="bi bi-calendar-day text-purple"></i> ' + daysMap[d.getDay()] + ' — ' + dateStr + '</div>';

            var slotsSection = document.getElementById('slotsSection');
            var slotsContainer = document.getElementById('slotsContainer');

            if (!data.slots || data.slots.length === 0) {
                slotsContainer.innerHTML = '<div class="text-muted text-center py-3 small"><i class="bi bi-calendar-x"></i> لا توجد فترات متاحة في هذا اليوم</div>';
                slotsSection.style.display = 'block';
                return;
            }

            var available = data.slots.filter(function(s) { return !s.booked; });
            if (available.length === 0) {
                slotsContainer.innerHTML = '<div class="text-muted text-center py-3 small"><i class="bi bi-x-circle"></i> كل المواعيد محجوزة في هذا اليوم</div>';
                slotsSection.style.display = 'block';
                return;
            }

            var html = '<div class="cal-day-slots">';
            available.forEach(function(s) {
                var val = dateStr + ' ' + s.start + ':00';
                html += '<div class="cal-day-slot" onclick="selectSlot(\'' + val + '\', this)">' +
                    '<div class="cal-day-slot-time" dir="ltr">' + s.start + ' - ' + s.end + '</div>' +
                    '<div class="cal-day-slot-label">متاح</div></div>';
            });
            html += '</div>';
            slotsContainer.innerHTML = html;
            slotsSection.style.display = 'block';
        })
        .catch(function() {
            document.getElementById('selectedDateInfo').innerHTML = '<small class="text-danger">تعذّر تحميل المواعيد</small>';
        });
}

function selectSlot(val, el) {
    document.getElementById('appt_date').value = val;
    // Highlight selected slot
    document.querySelectorAll('#slotsContainer .cal-day-slot').forEach(function(s) {
        s.style.background = '';
        s.style.borderColor = '';
    });
    el.style.background = 'linear-gradient(135deg, #0d9488, #0891b2)';
    el.style.borderColor = 'transparent';
    el.querySelector('.cal-day-slot-time').style.color = '#fff';
    el.querySelector('.cal-day-slot-label').style.color = 'rgba(255,255,255,0.8)';
}

// ===== Init all =====
function initBookPage() {
    initPatientSearch();
    initDeptSelect();
    initDoctorSelect();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookPage);
} else {
    initBookPage();
}
document.addEventListener('spa:navigated', initBookPage);
</script>
