<?php /** Admin: Users list — native HTML table (no AG Grid) */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">إدارة جميع مستخدمي النظام — <?= count($users) ?> مستخدم</div>
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
        <span><i class="bi bi-table text-purple"></i> قائمة المستخدمين</span>
        <small class="text-muted"><?= count($users) ?> سجل</small>
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
                <tbody>
                    <?php
                    $roleLabels = ['admin'=>'مدير','doctor'=>'طبيب','reception'=>'استقبال','lab_tech'=>'فني مختبر','patient'=>'مريض'];
                    $roleBadgeClass = ['admin'=>'bg-danger','doctor'=>'bg-primary','reception'=>'bg-info','lab_tech'=>'bg-warning text-dark','patient'=>'bg-secondary'];
                    $csrf = Auth::csrfToken();
                    foreach ($users as $i => $u):
                        $editUrl = url('/admin/users/' . $u['id'] . '/edit');
                        $toggleUrl = url('/admin/users/' . $u['id'] . '/toggle');
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
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
                                <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary spa-link" data-spa="1" data-url="<?= $editUrl ?>" title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </a>
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
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 24px; opacity: 0.4;"></i>
                            <p class="mt-2">لا مستخدمون مطابقون للبحث</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
