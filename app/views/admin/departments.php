<?php /** Admin: Departments — native HTML table + card view */ ?>
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
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> جدول الأقسام</span>
        <small class="text-muted"><?= count($depts) ?> سجل</small>
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
                <tbody>
                    <?php $csrf = Auth::csrfToken(); foreach ($depts as $i => $d): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td class="fw-bold"><?= e($d['name_ar']) ?></td>
                        <td dir="ltr" class="text-end small"><?= e($d['name_en'] ?: '-') ?></td>
                        <td class="small text-muted"><?= e(mb_substr($d['description'] ?: 'لا وصف', 0, 60)) ?><?= mb_strlen($d['description'] ?? '') > 60 ? '...' : '' ?></td>
                        <td><span class="badge bg-primary"><?= $d['doctors_count'] ?> طبيب</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" onclick='editDept(<?= json_encode($d, JSON_UNESCAPED_UNICODE) ?>)' title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="<?= url('/admin/departments/' . $d['id'] . '/delete') ?>" style="display:inline" onsubmit="return confirm('تأكيد الحذف؟')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($depts)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 24px; opacity: 0.4;"></i>
                        <p class="mt-2">لا أقسام</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
