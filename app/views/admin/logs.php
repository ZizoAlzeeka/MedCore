<?php /** Admin: Logs viewer */ ?>
<div class="page-header">
    <h2 class="page-title"><i class="bi bi-bug-fill"></i> <?= e($title) ?></h2>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-files text-purple"></i> ملفات السجل</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($files as $f): ?>
                        <div class="list-group-item small">
                            <div class="fw-bold"><i class="bi bi-file-text text-danger"></i> <?= e($f['name']) ?></div>
                            <div class="text-muted" style="font-size:11px;">
                                <?= round($f['size']/1024, 1) ?> KB • <?= $f['lines'] ?> سطر • <?= e($f['modified']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($files)): ?>
                        <div class="list-group-item text-center text-muted py-3">لا ملفات</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-text text-danger"></i> محتوى آخر ملف سجل (آخر 200 سطر)</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> تحديث</button>
            </div>
            <div class="card-body p-0">
                <pre style="direction:ltr;text-align:left;background:#1e1e2e;color:#e0e0e0;padding:14px;font-size:11px;max-height:600px;overflow:auto;border-radius:0 0 14px 14px;"><?= e($latestContent ?: 'لا محتوى') ?></pre>
            </div>
        </div>
    </div>
</div>
