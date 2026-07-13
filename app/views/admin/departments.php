<?php /** Admin: Departments — card view + AG Grid table */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-diagram-3-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة الأقسام الطبية — أضف قسماً جديداً أو عدّل القائم</div>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="resetDeptForm()">
        <i class="bi bi-plus-circle"></i> إضافة قسم
    </button>
</div>

<!-- AG Grid view for sorting/filtering -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> جدول الأقسام (<?= count($depts) ?>)</span>
        <small class="text-muted">جدول تفاعلي — بحث + ترتيب + تصفية</small>
    </div>
    <div class="card-body p-2">
        <div id="deptsGrid" style="width:100%; height: 360px;"></div>
    </div>
</div>

<h6 class="text-muted mb-3"><i class="bi bi-grid-3x3-gap"></i> عرض بطاقات:</h6>
<div class="row g-3">
    <?php foreach ($depts as $d): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-folder-fill text-purple"></i> <?= e($d['name_ar']) ?></span>
                    <span class="badge bg-primary"><?= $d['doctors_count'] ?> طبيب</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($d['name_en'])): ?>
                        <div class="small text-muted mb-2"><?= e($d['name_en']) ?></div>
                    <?php endif; ?>
                    <p class="small mb-3"><?= e($d['description'] ?: 'لا يوجد وصف') ?></p>
                    <?php if (!empty($d['doctors'])): ?>
                        <div class="small fw-bold mb-1">الأطباء:</div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($d['doctors'] as $doc): ?>
                                <span class="badge bg-light text-dark"><i class="bi bi-person"></i> <?= e($doc['full_name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small"><i class="bi bi-info-circle"></i> لا أطباء مسندين</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick='editDept(<?= json_encode($d, JSON_UNESCAPED_UNICODE) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" action="<?= url('/admin/departments/' . $d['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= url('/admin/departments/store') ?>" id="deptForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="dept_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus"></i> <span id="deptModalTitle">إضافة قسم</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" id="dept_name_ar" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" id="dept_name_en" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" id="dept_desc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ⚡ AG Grid for departments — runs after defer scripts
window.addEventListener('DOMContentLoaded', function() {
    const deptsData = <?= json_encode($depts, JSON_UNESCAPED_UNICODE) ?>;
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
            headerName: 'الاسم (عربي)',
            field: 'name_ar',
            minWidth: 180,
            cellClass: 'fw-bold',
        },
        {
            headerName: 'الاسم (إنجليزي)',
            field: 'name_en',
            minWidth: 150,
            cellRenderer: params => `<span dir="ltr">${params.value || ''}</span>`,
        },
        {
            headerName: 'الوصف',
            field: 'description',
            minWidth: 220,
            cellClass: 'small',
        },
        {
            headerName: 'عدد الأطباء',
            field: 'doctors_count',
            width: 120,
            cellRenderer: params => `<span class="badge bg-primary">${params.value || 0} طبيب</span>`,
        },
        {
            headerName: 'إجراءات',
            width: 140,
            sortable: false,
            filter: false,
            pinned: 'left',
            cellRenderer: function(params) {
                const d = params.data;
                const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                const editBtn = `<button class="btn btn-sm btn-outline-primary me-1" onclick='editDept(${JSON.stringify(d)})' title="تعديل"><i class="bi bi-pencil"></i></button>`;
                const deleteForm = `<form method="post" action="<?= url('/admin/departments') ?>/${d.id}/delete" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">` +
                    `<input type="hidden" name="csrf_token" value="${csrf}">` +
                    `<button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>` +
                    `</form>`;
                return `<div class="text-nowrap">${editBtn}${deleteForm}</div>`;
            },
        },
    ];

    // ⚡ Wait for AG Grid library + helper to be available
    window.waitForAgGrid(function() {
        window.initAgGrid('#deptsGrid', columns, deptsData, {
            pagination: false,
        });
    });
});

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
</script>
