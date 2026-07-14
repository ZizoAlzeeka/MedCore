<?php /** Admin: Users list — native HTML table + modal form + live search + pagination */
$csrf = Auth::csrfToken();
$roleLabels = ['admin'=>'مدير','doctor'=>'طبيب','reception'=>'استقبال','lab_tech'=>'فني مختبر','patient'=>'مريض'];
$roleBadgeClass = ['admin'=>'bg-danger','doctor'=>'bg-primary','reception'=>'bg-info','lab_tech'=>'bg-warning text-dark','patient'=>'bg-secondary'];
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة جميع مستخدمي النظام — <?= count($users) ?> مستخدم</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
        <i class="bi bi-person-plus"></i> إضافة مستخدم
    </button>
</div>

<!-- Live search + role filter + page size -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">الدور</label>
                <select id="roleFilter" class="form-select form-select-sm" onchange="applyFilters()">
                    <option value="">الكل</option>
                    <option value="admin">مدير</option>
                    <option value="doctor">طبيب</option>
                    <option value="reception">استقبال</option>
                    <option value="lab_tech">فني مختبر</option>
                    <option value="patient">مريض</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">بحث لحظي</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث بالاسم، البريد، الرقم المميز، الهاتف..." oninput="applyFilters()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">عدد الصفوف</label>
                <select id="pageSizeSelect" class="form-select form-select-sm" onchange="renderTable()">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قائمة المستخدمين</span>
        <small class="text-muted" id="resultCount"><?= count($users) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>الاسم</th>
                        <th>الرقم المميز</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                        <th style="width: 110px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $i => $u):
                        $editUrl = url('/admin/users/' . $u['id'] . '/edit');
                        $toggleUrl = url('/admin/users/' . $u['id'] . '/toggle');
                    ?>
                    <tr data-name="<?= e(strtolower($u['full_name'])) ?>"
                        data-email="<?= e(strtolower($u['email'])) ?>"
                        data-uid="<?= e($u['unique_id']) ?>"
                        data-phone="<?= e($u['phone']) ?>"
                        data-role="<?= e($u['role']) ?>">
                        <td class="text-muted row-num"><?= $i + 1 ?></td>
                        <td class="fw-bold"><?= e($u['full_name']) ?></td>
                        <td><span class="uid-code"><?= e($u['unique_id']) ?></span></td>
                        <td dir="ltr" class="text-end small"><?= e($u['email']) ?></td>
                        <td dir="ltr" class="text-end small"><?= e($u['phone']) ?></td>
                        <td><span class="badge <?= $roleBadgeClass[$u['role']] ?? 'bg-secondary' ?>"><?= $roleLabels[$u['role']] ?? $u['role'] ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">نشط</span>
                            <?php else: ?>
                                <span class="badge bg-danger">معطّل</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($u, JSON_UNESCAPED_UNICODE) ?>)' title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="<?= $toggleUrl ?>" style="display:inline" onsubmit="return confirm('تأكيد تغيير حالة الحساب؟')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 24px; opacity: 0.4;"></i>
                        <p class="mt-2">لا مستخدمون</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="paginationBar"></div>
</div>

<!-- Modal: Add/Edit User -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="userForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="userModalTitle">إضافة مستخدم</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="uf_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="uf_email" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">كلمة المرور <span class="text-danger">*</span> <small id="pwHint" class="text-muted"></small></label>
                            <div class="input-group input-group-sm">
                                <input type="password" name="password" id="uf_password" class="form-control" placeholder="••••••">
                                <button class="btn btn-eye" type="button" onclick="togglePassword('uf_password', this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">رقم الموبايل <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="uf_phone" class="form-control form-control-sm" inputmode="numeric" pattern="[0-9]*" required oninput="forceEnglishDigits(this)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">الدور <span class="text-danger">*</span></label>
                            <select name="role" id="uf_role" class="form-select form-select-sm" required onchange="toggleDoctorFields()">
                                <option value="">— اختر —</option>
                                <option value="admin">مدير</option>
                                <option value="doctor">طبيب</option>
                                <option value="reception">استقبال</option>
                                <option value="lab_tech">فني مختبر</option>
                                <option value="patient">مريض</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">الجنس <span class="text-danger">*</span></label>
                            <select name="gender" id="uf_gender" class="form-select form-select-sm" required>
                                <option value="">— اختر —</option>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" id="uf_birth" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">العنوان</label>
                            <input type="text" name="address" id="uf_address" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div id="doctorFields" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid #eee;">
                        <h6 class="text-purple small mb-2"><i class="bi bi-stethoscope"></i> بيانات الطبيب</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">القسم</label>
                                <select name="department_id" id="uf_dept" class="form-select form-select-sm">
                                    <option value="">— اختر —</option>
                                    <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['name_ar']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">التخصص</label>
                                <input type="text" name="specialty" id="uf_specialty" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">رقم الترخيص</label>
                                <input type="text" name="license_no" id="uf_license" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var allUsers = <?= json_encode($users, JSON_UNESCAPED_UNICODE) ?>;
var currentPage = 1;

function applyFilters() {
    currentPage = 1;
    renderTable();
}

function renderTable() {
    var search = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    var roleFilter = document.getElementById('roleFilter').value;
    var pageSize = parseInt(document.getElementById('pageSizeSelect').value);

    var filtered = allUsers.filter(function(u) {
        var matchSearch = !search ||
            u.full_name.toLowerCase().includes(search) ||
            u.email.toLowerCase().includes(search) ||
            u.unique_id.includes(search) ||
            u.phone.includes(search);
        var matchRole = !roleFilter || u.role === roleFilter;
        return matchSearch && matchRole;
    });

    var totalPages = Math.ceil(filtered.length / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    var start = (currentPage - 1) * pageSize;
    var pageData = filtered.slice(start, start + pageSize);

    var tbody = document.getElementById('usersTableBody');
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:24px;opacity:0.4;"></i><p class="mt-2">لا نتائج مطابقة</p></td></tr>';
    } else {
        var roleLabels = {admin:'مدير',doctor:'طبيب',reception:'استقبال',lab_tech:'فني مختبر',patient:'مريض'};
        var roleBadgeClass = {admin:'bg-danger',doctor:'bg-primary',reception:'bg-info',lab_tech:'bg-warning text-dark',patient:'bg-secondary'};
        tbody.innerHTML = pageData.map(function(u, i) {
            var num = start + i + 1;
            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            var toggleUrl = '<?= url('/admin/users') ?>/' + u.id + '/toggle';
            return '<tr>' +
                '<td class="text-muted">' + num + '</td>' +
                '<td class="fw-bold">' + (u.full_name || '') + '</td>' +
                '<td><span class="uid-code">' + (u.unique_id || '') + '</span></td>' +
                '<td dir="ltr" class="text-end small">' + (u.email || '') + '</td>' +
                '<td dir="ltr" class="text-end small">' + (u.phone || '') + '</td>' +
                '<td><span class="badge ' + (roleBadgeClass[u.role]||'bg-secondary') + '">' + (roleLabels[u.role]||u.role) + '</span></td>' +
                '<td>' + (u.is_active ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطّل</span>') + '</td>' +
                '<td class="small text-muted">' + (u.created_at || '').split(' ')[0] + '</td>' +
                '<td><div class="d-flex gap-1">' +
                '<button class="btn btn-sm btn-outline-primary" onclick=\'editUser(' + JSON.stringify(u) + ')\' title="تعديل"><i class="bi bi-pencil"></i></button>' +
                '<form method="post" action="' + toggleUrl + '" style="display:inline" onsubmit="return confirm(\'تأكيد تغيير حالة الحساب؟\')">' +
                '<input type="hidden" name="csrf_token" value="' + csrf + '">' +
                '<button class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="bi bi-power"></i></button>' +
                '</form></div></td>' +
                '</tr>';
        }).join('');
    }

    document.getElementById('resultCount').textContent = filtered.length + ' سجل';

    // Pagination controls
    var paginationBar = document.getElementById('paginationBar');
    if (totalPages > 1) {
        var html = '<div class="d-flex justify-content-between align-items-center"><small class="text-muted">صفحة ' + currentPage + ' من ' + totalPages + '</small><div class="btn-group btn-group-sm">';
        if (currentPage > 1) html += '<button class="btn btn-outline-primary" onclick="goPage(' + (currentPage-1) + ')">السابق</button>';
        if (currentPage < totalPages) html += '<button class="btn btn-outline-primary" onclick="goPage(' + (currentPage+1) + ')">التالي</button>';
        html += '</div></div>';
        paginationBar.innerHTML = html;
    } else {
        paginationBar.innerHTML = '';
    }
}

function goPage(p) { currentPage = p; renderTable(); }

function resetUserForm() {
    document.getElementById('userForm').action = '<?= url('/admin/users/store') ?>';
    document.getElementById('userModalTitle').textContent = 'إضافة مستخدم';
    document.getElementById('userForm').reset();
    document.getElementById('uf_password').required = true;
    document.getElementById('uf_password').minLength = 6;
    document.getElementById('pwHint').textContent = '';
    document.getElementById('doctorFields').style.display = 'none';
    document.getElementById('uf_role').disabled = false;
}

function editUser(u) {
    document.getElementById('userForm').action = '<?= url('/admin/users') ?>/' + u.id + '/update';
    document.getElementById('userModalTitle').textContent = 'تعديل مستخدم';
    document.getElementById('uf_name').value = u.full_name || '';
    document.getElementById('uf_email').value = u.email || '';
    document.getElementById('uf_password').value = '';
    document.getElementById('uf_password').required = false;
    document.getElementById('uf_password').minLength = '';
    document.getElementById('pwHint').textContent = '(اتركها فارغة للإبقاء)';
    document.getElementById('uf_phone').value = u.phone || '';
    document.getElementById('uf_role').value = u.role || '';
    document.getElementById('uf_role').disabled = true;
    document.getElementById('uf_gender').value = u.gender || '';
    document.getElementById('uf_birth').value = u.birth_date || '';
    document.getElementById('uf_address').value = u.address || '';
    if (u.department_id) document.getElementById('uf_dept').value = u.department_id;
    document.getElementById('uf_specialty').value = u.specialty || '';
    document.getElementById('uf_license').value = u.license_no || '';
    toggleDoctorFields();
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function toggleDoctorFields() {
    var role = document.getElementById('uf_role').value;
    document.getElementById('doctorFields').style.display = (role === 'doctor') ? 'block' : 'none';
}

// Initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderTable);
} else {
    renderTable();
}
document.addEventListener('spa:navigated', renderTable);
</script>
