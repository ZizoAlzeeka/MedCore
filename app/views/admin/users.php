<?php /** Admin: Users list */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة جميع مستخدمي النظام</div>
    </div>
    <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> إضافة مستخدم</a>
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
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الرقم المميز</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td class="fw-bold"><?= e($u['full_name']) ?></td>
                            <td><span class="uid-code"><?= e($u['unique_id']) ?></span></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td class="small" dir="ltr"><?= e($u['phone']) ?></td>
                            <td><span class="badge bg-primary"><?= roleLabel($u['role']) ?></span></td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">نشط</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">معطّل</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
                            <td>
                                <a href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="تعديل"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/toggle') ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="bi bi-power"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">لا مستخدمين</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
