<?php /** Admin: Users list with AG Grid */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة جميع مستخدمي النظام</div>
    </div>
    <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary btn-sm spa-link" data-spa="1" data-url="<?= url('/admin/users/create') ?>">
        <i class="bi bi-person-plus"></i> إضافة مستخدم
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">الدور</label>
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    <option value="admin" <?= $role==='admin'?'selected':'' ?>>مدير</option>
                    <option value="doctor" <?= $role==='doctor'?'selected':'' ?>>طبيب</option>
                    <option value="reception" <?= $role==='reception'?'selected':'' ?>>استقبال</option>
                    <option value="lab_tech" <?= $role==='lab_tech'?'selected':'' ?>>فني مختبر</option>
                    <option value="patient" <?= $role==='patient'?'selected':'' ?>>مريض</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">بحث</label>
                <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="الاسم، البريد، الرقم المميز، الهاتف">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info w-100"><i class="bi bi-search"></i> بحث</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قائمة المستخدمين (<?= count($users) ?>)</span>
        <small class="text-muted">جدول تفاعلي — بحث + ترتيب + تصفية + ترقيم صفحات</small>
    </div>
    <div class="card-body p-2">
        <div id="usersGrid" style="width:100%; height: 560px;"></div>
    </div>
</div>

<script>
// ⚡ Use DOMContentLoaded so this runs AFTER defer scripts (ag-grid + app.js)
window.addEventListener('DOMContentLoaded', function() {
    const usersData = <?= json_encode($users, JSON_UNESCAPED_UNICODE) ?>;
    const roleLabels = {
        admin: 'مدير', doctor: 'طبيب', reception: 'استقبال',
        lab_tech: 'فني مختبر', patient: 'مريض'
    };
    const roleBadgeClass = {
        admin: 'bg-danger', doctor: 'bg-primary', reception: 'bg-info',
        lab_tech: 'bg-warning text-dark', patient: 'bg-secondary'
    };

    const columns = [
        {
            headerName: '#',
            valueGetter: params => params.node.rowIndex + 1,
            width: 60, maxWidth: 80,
            sortable: false, filter: false,
            pinned: 'right',
            cellClass: 'text-center text-muted',
        },
        {
            headerName: 'الاسم',
            field: 'full_name',
            minWidth: 180,
            cellClass: 'fw-bold',
            cellRenderer: params => `<span>${params.value || ''}</span>`,
        },
        {
            headerName: 'الرقم المميز',
            field: 'unique_id',
            width: 130,
            cellRenderer: params => `<span class="uid-code">${params.value || ''}</span>`,
        },
        {
            headerName: 'البريد',
            field: 'email',
            minWidth: 200,
            cellClass: 'small',
            cellRenderer: params => `<span dir="ltr">${params.value || ''}</span>`,
        },
        {
            headerName: 'الهاتف',
            field: 'phone',
            width: 130,
            cellRenderer: params => `<span dir="ltr" class="small">${params.value || ''}</span>`,
        },
        {
            headerName: 'الدور',
            field: 'role',
            width: 110,
            cellRenderer: params => {
                const lbl = roleLabels[params.value] || params.value;
                const cls = roleBadgeClass[params.value] || 'bg-secondary';
                return `<span class="badge ${cls}">${lbl}</span>`;
            },
        },
        {
            headerName: 'الحالة',
            field: 'is_active',
            width: 100,
            cellRenderer: params => params.value
                ? '<span class="badge bg-success">نشط</span>'
                : '<span class="badge bg-danger">معطّل</span>',
        },
        {
            headerName: 'تاريخ التسجيل',
            field: 'created_at',
            width: 130,
            cellRenderer: params => {
                if (!params.value) return '';
                const d = new Date(params.value.replace(' ', 'T'));
                return `<span class="small text-muted">${d.toLocaleDateString('ar-SA')}</span>`;
            },
        },
        {
            headerName: 'إجراءات',
            width: 140,
            sortable: false,
            filter: false,
            pinned: 'left',
            cellRenderer: function(params) {
                const u = params.data;
                const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                const editUrl = '<?= url('/admin/users') ?>/' + u.id + '/edit';
                const toggleUrl = '<?= url('/admin/users') ?>/' + u.id + '/toggle';
                const editBtn = `<a href="${editUrl}" class="btn btn-sm btn-outline-primary spa-link me-1" data-spa="1" data-url="${editUrl}" title="تعديل"><i class="bi bi-pencil"></i></a>`;
                const toggleForm = `<form method="post" action="${toggleUrl}" style="display:inline">` +
                    `<input type="hidden" name="csrf_token" value="${csrf}">` +
                    `<button class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="bi bi-power"></i></button>` +
                    `</form>`;
                return `<div class="text-nowrap">${editBtn}${toggleForm}</div>`;
            },
        },
    ];

    // ⚡ Wait for AG Grid library + helper to be available
    window.waitForAgGrid(function() {
        window.initAgGrid('#usersGrid', columns, usersData, {
            paginationPageSize: 25,
        });
    });
});
</script>
