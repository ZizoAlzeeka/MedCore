<?php /** Doctor: patients list */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المرضى الذين عالجتهم + المحالون إليك</div>
    </div>
</div>

<form method="get" class="mb-3">
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="ابحث بالاسم أو الرقم المميز أو الهاتف">
        <button class="btn btn-info">بحث</button>
    </div>
</form>

<?php if (!empty($referred)): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-arrow-left-right text-pink"></i> مرضى محالون إليك</div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($referred as $p): ?>
                <a href="<?= url('/doctor/patients/' . $p['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <div><span class="fw-bold"><?= e($p['full_name']) ?></span> <span class="uid-code"><?= e($p['unique_id']) ?></span></div>
                    <small class="text-muted"><?= e($p['phone']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-person-lines-fill text-purple"></i> مرضاي (<?= count($patients) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>الاسم</th><th>الرقم المميز</th><th>الهاتف</th><th>الجنس</th><th>العمر</th><th>إجراء</th></tr></thead>
                <tbody>
                    <?php foreach ($patients as $p):
                        $age = $p['birth_date'] ? date('Y') - date('Y', strtotime($p['birth_date'])) : '-';
                    ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td class="fw-bold"><?= e($p['full_name']) ?></td>
                            <td><span class="uid-code"><?= e($p['unique_id']) ?></span></td>
                            <td class="small" dir="ltr"><?= e($p['phone']) ?></td>
                            <td><?= genderLabel($p['gender']) ?></td>
                            <td><?= $age ?></td>
                            <td>
                                <a href="<?= url('/doctor/patients/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-folder"></i> الملف</a>
                                <a href="<?= url('/doctor/patients/' . $p['id'] . '/order-test') ?>" class="btn btn-sm btn-info"><i class="bi bi-plus"></i> طلب تحليل</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">لا مرضى</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
