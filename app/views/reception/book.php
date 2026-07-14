<?php /** Reception: book appointment */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-calendar-plus-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">اختر القسم ثم الطبيب ثم الموعد المتاح</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header gradient"><i class="bi bi-calendar-check"></i> بيانات الحجز</div>
            <div class="card-body">
                <form method="post" action="<?= url('/reception/book') ?>" id="bookForm">
                    <?= csrf_field() ?>

                    <!-- Search patient -->
                    <div class="mb-3">
                        <label class="form-label">المريض <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="patientSearch" class="form-control" placeholder="ابحث بالاسم أو الرقم المميز أو الهاتف...">
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

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="appt_date_date" id="apptDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الموعد المتاح <span class="text-danger">*</span></label>
                            <select name="appt_date" id="slotSelect" class="form-select" required disabled>
                                <option value="">اختر التاريخ والطبيب</option>
                            </select>
                        </div>
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
                    <li>ابحث عن المريض بالاسم أو الرقم المميز. إن لم يوجد، سجّله عبر "مريض جديد".</li>
                    <li>اختر القسم الطبي المناسب.</li>
                    <li>سيظهر قائمة الأطباء في القسم — اختر الطبيب.</li>
                    <li>اختر التاريخ — ستظهر المواعيد المتاحة في ذلك اليوم.</li>
                    <li>اختر الموعد المتاح وأكّد الحجز.</li>
                </ol>
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle"></i> المواعيد المحجوزة تظهر مؤشرة باللون الأحمر ولا يمكن اختيارها.
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
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus"></i> تسجيل مريض جديد</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">الاسم الكامل *</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">البريد *</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-2">
                        <label class="form-label">رقم الموبايل *</label>
                        <input type="text" name="phone" class="form-control" inputmode="numeric" pattern="[0-9]*" oninput="forceEnglishDigits(this)" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">الجنس *</label><select name="gender" class="form-select" required><option value="">—</option><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                        <div class="col-6"><label class="form-label">تاريخ الميلاد</label><input type="date" name="birth_date" class="form-control"></div>
                    </div>
                    <div class="mt-2"><label class="form-label">العنوان</label><input type="text" name="address" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> تسجيل</button></div>
            </form>
        </div>
    </div>
</div>

<script>
// Patient search — robust: re-attaches listener on every page load (incl. SPA navigation)
// and resets previous selection state when the user starts a new search.
let patientSearchTimer = null;

function initPatientSearch() {
    var input = document.getElementById('patientSearch');
    if (!input) return;
    // Avoid double-binding if already attached
    if (input.getAttribute('data-search-bound') === '1') return;
    input.setAttribute('data-search-bound', '1');

    input.addEventListener('input', function() {
        // ⚡ Reset previously selected patient state — this is the key fix:
        // when the user starts typing again after a previous selection, clear
        // the hidden patient_id and the info box so stale data isn't submitted.
        if (document.getElementById('patient_id').value !== '') {
            document.getElementById('patient_id').value = '';
            var info = document.getElementById('patientInfo');
            if (info) info.innerHTML = '';
        }

        clearTimeout(patientSearchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('patientResults').innerHTML = '';
            return;
        }
        patientSearchTimer = setTimeout(() => {
            fetch(`/reception/search-patient?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    const c = document.getElementById('patientResults');
                    if (!data.patients || data.patients.length === 0) {
                        c.innerHTML = '<div class="small text-muted p-2">لا نتائج — سجّل مريضاً جديداً</div>';
                        return;
                    }
                    c.innerHTML = data.patients.map(p => `
                        <div class="border rounded p-2 mb-1 cursor-pointer" onclick="selectPatient(${p.id}, '${escapeHtml(p.full_name)}', '${p.unique_id}', '${p.phone}')">
                            <strong>${p.full_name}</strong> <span class="uid-code">${p.unique_id}</span> — ${p.phone}
                        </div>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('patientResults').innerHTML = '<div class="small text-danger p-2">خطأ في البحث — حاول مجدداً</div>';
                });
        }, 300);
    });
}

function selectPatient(id, name, uid, phone) {
    document.getElementById('patient_id').value = id;
    document.getElementById('patientSearch').value = name;
    document.getElementById('patientResults').innerHTML = '';
    document.getElementById('patientInfo').innerHTML = `<div class="alert alert-light border small">المريض المختار: <strong>${name}</strong> — UID: <span class="uid-code">${uid}</span> — الهاتف: ${phone}</div>`;
}

// Department → doctors
function initDeptDoctors() {
    var deptSelect = document.getElementById('deptSelect');
    if (!deptSelect || deptSelect.getAttribute('data-bound') === '1') return;
    deptSelect.setAttribute('data-bound', '1');

    deptSelect.addEventListener('change', function() {
        const deptId = this.value;
        const doctorSelect = document.getElementById('doctorSelect');
        doctorSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        doctorSelect.disabled = true;
        if (!deptId) { doctorSelect.innerHTML = '<option value="">اختر القسم أولاً</option>'; return; }
        fetch(`/ajax/doctors/by-department?department_id=${deptId}`)
            .then(r => r.json())
            .then(data => {
                doctorSelect.innerHTML = '<option value="">— اختر الطبيب —</option>';
                data.doctors.forEach(d => {
                    doctorSelect.innerHTML += `<option value="${d.id}">${d.full_name} ${d.specialty ? '('+d.specialty+')' : ''}</option>`;
                });
                doctorSelect.disabled = false;
            });
    });
}

// Doctor + date → available slots
function loadSlots() {
    const doctorId = document.getElementById('doctorSelect').value;
    const date = document.getElementById('apptDate').value;
    const slotSelect = document.getElementById('slotSelect');
    if (!doctorId || !date) {
        slotSelect.innerHTML = '<option value="">اختر التاريخ والطبيب</option>';
        slotSelect.disabled = true;
        return;
    }
    slotSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    fetch(`/ajax/doctor/${doctorId}/slots?date=${date}`)
        .then(r => r.json())
        .then(data => {
            slotSelect.innerHTML = '';
            if (!data.slots || data.slots.length === 0) {
                slotSelect.innerHTML = '<option value="">لا توجد فترات متاحة في هذا اليوم</option>';
                slotSelect.disabled = true;
                return;
            }
            const available = data.slots.filter(s => !s.booked);
            if (available.length === 0) {
                slotSelect.innerHTML = '<option value="">كل المواعيد محجوزة</option>';
                slotSelect.disabled = true;
                return;
            }
            available.forEach(s => {
                slotSelect.innerHTML += `<option value="${data.date} ${s.start}:00">${s.start} - ${s.end}</option>`;
            });
            slotSelect.disabled = false;
        });
}

function initSlotListeners() {
    var doctorSelect = document.getElementById('doctorSelect');
    var apptDate = document.getElementById('apptDate');
    if (doctorSelect && doctorSelect.getAttribute('data-bound') !== '1') {
        doctorSelect.setAttribute('data-bound', '1');
        doctorSelect.addEventListener('change', loadSlots);
    }
    if (apptDate && apptDate.getAttribute('data-bound') !== '1') {
        apptDate.setAttribute('data-bound', '1');
        apptDate.addEventListener('change', loadSlots);
    }
}

function escapeHtml(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function initBookPage() {
    initPatientSearch();
    initDeptDoctors();
    initSlotListeners();
}

// Run on initial load + re-run on SPA navigation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookPage);
} else {
    initBookPage();
}
document.addEventListener('spa:navigated', initBookPage);
</script>
