<?php /** Notifications list */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-bell-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">جميع إشعاراتك</div>
    </div>
    <form method="post" action="<?= url('/notifications/read-all') ?>" style="display:inline">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2-all"></i> تعليم الكل كمقروء</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($notifs)): ?>
            <div class="empty-state"><i class="bi bi-bell-slash"></i><h5>لا إشعارات</h5></div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifs as $n):
                    $icons = [
                        'result_ready' => 'bi-clipboard2-check text-success',
                        'treatment_added' => 'bi-capsules text-pink',
                        'appointment_booked' => 'bi-calendar-check text-blue',
                        'duplicate_alert' => 'bi-exclamation-triangle text-warning',
                        'general' => 'bi-info-circle text-purple',
                    ];
                    $icon = $icons[$n['type']] ?? $icons['general'];
                ?>
                    <div class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'list-group-item-primary' ?>">
                        <div class="d-flex gap-2">
                            <i class="bi <?= $icon ?> fs-5"></i>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong class="small"><?= e($n['title']) ?></strong>
                                    <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                                </div>
                                <div class="small text-muted mt-1"><?= e($n['message']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
