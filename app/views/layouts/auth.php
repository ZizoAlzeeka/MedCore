<?php /** Auth layout (split: image+text on right, form on left) */
$appName = Env::get('APP_NAME', 'منصة كشف التحاليل المكررة');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — <?= e($title ?? 'تسجيل') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <!-- Left: form -->
    <div class="auth-right">
        <div class="logo">
            <img src="<?= asset('img/logo.png') ?>" alt="logo">
            <h4><?= e($appName) ?></h4>
        </div>
        <?= $view ?? '' ?>
    </div>
    <!-- Right: image + text -->
    <div class="auth-left">
        <div class="content">
            <h1><i class="bi bi-shield-check"></i> منصة طبية ذكية</h1>
            <p>نظام متكامل لربط أقسام المستشفى ومنع تكرار التحاليل المخبرية غير الضرورية — يوفّر الوقت والمال ويحسن تجربة المريض.</p>
            <ul class="feature-list">
                <li><i class="bi bi-search-heart"></i> كشف تلقائي للتحاليل المكررة بمعيار LOINC</li>
                <li><i class="bi bi-bell"></i> تنبيهات فورية للأطباء قبل تأكيد الطلب</li>
                <li><i class="bi bi-link-45deg"></i> ربط جميع الأقسام بسجل موحد</li>
                <li><i class="bi bi-graph-up"></i> تقارير وإحصائيات لقياس التوفير</li>
                <li><i class="bi bi-file-earmark-pdf"></i> طباعة تقارير المرضى بـ PDF</li>
                <li><i class="bi bi-shield-lock"></i> أمان وحماية البيانات الطبية</li>
            </ul>
            <div class="mt-4" style="font-size: 12px; opacity: 0.7;">
                <i class="bi bi-c-circle"></i> <?= date('Y') ?> — <?= e($appName) ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
