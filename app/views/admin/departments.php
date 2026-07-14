<?php /** Admin: Departments — native HTML table + live search + pagination + card view */
$csrf = Auth::csrfToken();
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-diagram-3-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة الأقسام الطبية — <?= count($depts) ?> قسم</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="resetDeptForm()">
        <i class="bi bi-plus-circle"></i> إضافة قسم
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small">بحث لحظي</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="deptSearchInput" class="form-control" placeholder="ابحث بالاسم العربي أو الإنجليزي..." oninput="renderDeptTable()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">عدد الصفوف</label>
                <select id="deptPageSizeSelect" class="form-select form-select-sm" onchange="renderDeptTable()">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> جدول الأقسام</span>
        <small class="text-muted" id="deptResultCount"><?= count($depts) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>الاسم (عربي)</th>
                        <th>الاسم (إنجليزي)</th>
                        <th>الوصف</th>
                        <th>الأطباء</th>
                        <th style="width: 110px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="deptsTableBody"></tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="deptPaginationBar"></div>
</div>

<!-- Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('/admin/departments/store') ?>" id="deptForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="id" id="dept_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus"></i> <span id="deptModalTitle">إضافة قسم</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="dept_name_ar" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" id="dept_name_en" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">الوصف</label>
                        <textarea name="description" id="dept_desc" class="form-control form-control-sm" rows="3"></textarea>
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
var allDepts = <?= json_encode($depts, JSON_UNESCAPED_UNICODE) ?>;
var deptCurrentPage = 1;

function renderDeptTable() {
    var search = (document.getElementById('deptSearchInput').value || '').toLowerCase().trim();
    var pageSize = parseInt(document.getElementById('deptPageSizeSelect').value);

    var filtered = allDepts.filter(function(d) {
        if (!search) return true;
        return (d.name_ar||'').toLowerCase().includes(search) ||
               (d.name_en||'').toLowerCase().includes(search) ||
               (d.description||'').toLowerCase().includes(search);
    });

    var totalPages = Math.ceil(filtered.length / pageSize) || 1;
    if (deptCurrentPage > totalPages) deptCurrentPage = totalPages;
    if (deptCurrentPage < 1) deptCurrentPage = 1;
    var start = (deptCurrentPage - 1) * pageSize;
    var pageData = filtered.slice(start, start + pageSize);

    var tbody = document.getElementById('deptsTableBody');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:24px;opacity:0.4;"></i><p class="mt-2">لا نتائج</p></td></tr>';
    } else {
        tbody.innerHTML = pageData.map(function(d, i) {
            var num = start + i + 1;
            var deleteUrl = '<?= url('/admin/departments') ?>/' + d.id + '/delete';
            var desc = d.description || 'لا وصف';
            if (desc.length > 60) desc = desc.substring(0, 60) + '...';
            return '<tr>' +
                '<td class="text-muted">' + num + '</td>' +
                '<td class="fw-bold">' + (d.name_ar||'') + '</td>' +
                '<td dir="ltr" class="text-end small">' + (d.name_en||'-') + '</td>' +
                '<td class="small text-muted">' + desc + '</td>' +
                '<td><span class="badge bg-primary">' + (d.doctors_count||0) + ' طبيب</span></td>' +
                '<td><div class="d-flex gap-1">' +
                '<button class="btn btn-sm btn-outline-primary" onclick=\'editDept(' + JSON.stringify(d) + ')\' title="تعديل"><i class="bi bi-pencil"></i></button>' +
                '<form method="post" action="' + deleteUrl + '" style="display:inline" onsubmit="return confirm(\'تأكيد الحذف؟\')">' +
                '<input type="hidden" name="csrf_token" value="' + csrf + '">' +
                '<button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>' +
                '</form></div></td>' +
                '</tr>';
        }).join('');
    }

    document.getElementById('deptResultCount').textContent = filtered.length + ' سجل';
    renderDeptPagination(totalPages, filtered.length, start, pageData.length);
}

function renderDeptPagination(totalPages, totalRows, start, count) {
    var bar = document.getElementById('deptPaginationBar');
    if (totalPages <= 1) { bar.innerHTML = ''; return; }
    var info = '<div class="medcore-pagination-info">عرض ' + (start+1) + ' إلى ' + (start+count) + ' من ' + totalRows + ' سجل</div>';
    var html = '<div class="medcore-pagination">';
    html += '<button class="page-btn' + (deptCurrentPage <= 1 ? ' disabled' : '') + '" ' + (deptCurrentPage <= 1 ? '' : 'onclick="deptGoPage('+(deptCurrentPage-1)+')"') + '><i class="bi bi-chevron-right"></i></button>';
    for (var i = 1; i <= totalPages; i++) {
        html += '<button class="page-btn' + (i === deptCurrentPage ? ' active' : '') + '" onclick="deptGoPage('+i+')">' + i + '</button>';
    }
    html += '<button class="page-btn' + (deptCurrentPage >= totalPages ? ' disabled' : '') + '" ' + (deptCurrentPage >= totalPages ? '' : 'onclick="deptGoPage('+(deptCurrentPage+1)+')"') + '><i class="bi bi-chevron-left"></i></button>';
    html += '</div>';
    bar.innerHTML = info + html;
}

function deptGoPage(p) { deptCurrentPage = p; renderDeptTable(); }

function resetDeptForm() {
    document.getElementById('deptForm').action = '<?= url("/admin/departments/store") ?>';
    document.getElementById('dept_id').value = '';
    document.getElementById('dept_name_ar').value = '';
    document.getElementById('dept_name_en').value = '';
    document.getElementById('dept_desc').value = '';
    document.getElementById('deptModalTitle').textContent = 'إضافة قسم';
}
function editDept(d) {
    document.getElementById('deptForm').action = '<?= url("/admin/departments") ?>/' + d.id + '/update';
    document.getElementById('dept_id').value = d.id;
    document.getElementById('dept_name_ar').value = d.name_ar;
    document.getElementById('dept_name_en').value = d.name_en || '';
    document.getElementById('dept_desc').value = d.description || '';
    document.getElementById('deptModalTitle').textContent = 'تعديل قسم';
    new bootstrap.Modal(document.getElementById('deptModal')).show();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderDeptTable);
} else {
    renderDeptTable();
}
document.addEventListener('spa:navigated', renderDeptTable);
</script>
