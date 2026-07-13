<?php
/** App layout — sidebar + topbar (fixed) + main content (dynamic) */
$appName = Env::get('APP_NAME', 'منصة كشف التحاليل المكررة');
$role = Auth::role();
$user = Auth::user();
$doctor = null;
if ($role === 'doctor') {
    $doctor = Database::fetch("SELECT d.*, dep.name_ar AS dept_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.user_id = ?", [Auth::id()]);
}

// ⚡ Performance: cache notification count in APCu for 15 seconds per user
// to avoid hitting the DB on every page load. The dropdown still fetches
// the actual list, but the badge count is cached.
$notifCount = 0;
$notifCacheKey = 'notif_count_' . Auth::id();
if (function_exists('apcu_fetch') && apcu_exists($notifCacheKey)) {
    $notifCount = (int) apcu_fetch($notifCacheKey);
} else {
    try {
        $notifCount = (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [Auth::id()]);
        if (function_exists('apcu_store')) {
            apcu_store($notifCacheKey, $notifCount, 15);
        }
    } catch (Throwable $e) {
        $notifCount = 0;
    }
}
$recentNotifs = Database::fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [Auth::id()]);

// Determine if AJAX request
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

// Sidebar menu per role
$menu = [];
switch ($role) {
    case 'admin':
        $menu = [
            ['section' => 'الرئيسية'],
            ['label' => 'لوحة التحكم', 'url' => '/admin', 'icon' => 'bi-grid-1x2'],
            ['section' => 'الإدارة'],
            ['label' => 'المستخدمون', 'url' => '/admin/users', 'icon' => 'bi-people'],
            ['label' => 'الأقسام الطبية', 'url' => '/admin/departments', 'icon' => 'bi-diagram-3'],
            ['label' => 'كتالوج التحاليل', 'url' => '/admin/tests', 'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'التقارير', 'url' => '/admin/reports', 'icon' => 'bi-bar-chart'],
            ['label' => 'الإعدادات', 'url' => '/admin/settings', 'icon' => 'bi-gear'],
            ['label' => 'سجل الأخطاء', 'url' => '/admin/logs', 'icon' => 'bi-bug'],
        ];
        break;
    case 'doctor':
        $menu = [
            ['section' => 'الرئيسية'],
            ['label' => 'لوحة التحكم', 'url' => '/doctor', 'icon' => 'bi-grid-1x2'],
            ['section' => 'العيادة'],
            ['label' => 'مواعيد اليوم', 'url' => '/doctor/appointments', 'icon' => 'bi-calendar-check'],
            ['label' => 'مرضاي', 'url' => '/doctor/patients', 'icon' => 'bi-people'],
            ['label' => 'جدولة الدوام', 'url' => '/doctor/schedule', 'icon' => 'bi-clock-history'],
            ['section' => 'أخرى'],
            ['label' => 'الملف الشخصي', 'url' => '/profile', 'icon' => 'bi-person-badge'],
        ];
        break;
    case 'reception':
        $menu = [
            ['section' => 'الرئيسية'],
            ['label' => 'لوحة التحكم', 'url' => '/reception', 'icon' => 'bi-grid-1x2'],
            ['section' => 'الاستقبال'],
            ['label' => 'حجز موعد', 'url' => '/reception/book', 'icon' => 'bi-calendar-plus'],
            ['label' => 'المواعيد', 'url' => '/reception/appointments', 'icon' => 'bi-calendar-check'],
            ['label' => 'جداول الأطباء', 'url' => '/reception/doctor-schedules', 'icon' => 'bi-clock'],
            ['section' => 'أخرى'],
            ['label' => 'الملف الشخصي', 'url' => '/profile', 'icon' => 'bi-person-badge'],
        ];
        break;
    case 'lab_tech':
        $menu = [
            ['section' => 'الرئيسية'],
            ['label' => 'لوحة التحكم', 'url' => '/labtech', 'icon' => 'bi-grid-1x2'],
            ['section' => 'المختبر'],
            ['label' => 'الطلبات الواردة', 'url' => '/labtech/orders', 'icon' => 'bi-list-task'],
            ['section' => 'أخرى'],
            ['label' => 'الملف الشخصي', 'url' => '/profile', 'icon' => 'bi-person-badge'],
        ];
        break;
    case 'patient':
        $menu = [
            ['section' => 'الرئيسية'],
            ['label' => 'لوحة التحكم', 'url' => '/patient', 'icon' => 'bi-grid-1x2'],
            ['section' => 'طبي'],
            ['label' => 'نتائج التحاليل', 'url' => '/patient/results', 'icon' => 'bi-clipboard2-data'],
            ['label' => 'خطة العلاج', 'url' => '/patient/treatment', 'icon' => 'bi-capsules'],
            ['label' => 'مواعيدي', 'url' => '/patient/appointments', 'icon' => 'bi-calendar-check'],
            ['label' => 'طباعة تقرير', 'url' => '/patient/print-report', 'icon' => 'bi-printer'],
            ['section' => 'أخرى'],
            ['label' => 'الملف الشخصي', 'url' => '/profile', 'icon' => 'bi-person-badge'],
        ];
        break;
}

// Current URL
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$basePath = parse_url(Env::get('APP_URL', ''), PHP_URL_PATH) ?: '';
if ($basePath && strpos($currentUrl, $basePath) === 0) {
    $currentUrl = substr($currentUrl, strlen($basePath));
}
?>
<?php if ($isAjax): ?>
<!-- AJAX: only render main content + metadata header for SPA nav -->
<script data-ajax-meta type="application/json"><?= json_encode([
    'title' => $title ?? 'لوحة التحكم',
    'subtitle' => $subtitle ?? '',
    'currentUrl' => $currentUrl,
], JSON_UNESCAPED_UNICODE) ?></script>
<?= $view ?>
<?php else: ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — <?= e($title ?? 'لوحة التحكم') ?></title>
<meta name="csrf-token" content="<?= Auth::csrfToken() ?>">
<meta name="theme-color" content="#6C63FF">

<!-- ⚡ PWA: manifest for installable app -->
<link rel="manifest" href="<?= asset('manifest.json') ?>">

<!-- ⚡ Performance: preconnect to CDNs to warm up DNS/TLS -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- ⚡ Bootstrap RTL (load stylesheet non-blocking) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- ⚡ Cairo font with display=swap to avoid blocking text render -->
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

<!-- Local app styles -->
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">

<!-- ⚡ AG Grid styles — loaded only when needed (deferred via JS below if not present) -->
<link href="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/styles/ag-grid.min.css" rel="stylesheet" media="print" onload="this.media='all'">
<link href="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/styles/ag-theme-quartz.min.css" rel="stylesheet" media="print" onload="this.media='all'">
</head>
<body>
<!-- ⚡ Top loading progress bar -->
<div id="page-loader-bar"></div>

<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= asset('img/logo.png') ?>" alt="logo">
            <div>
                <div class="name">منصة التحاليل</div>
                <div class="sub">كشف التكرار</div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="name"><?= e(Auth::name()) ?></div>
            <div class="role"><?= roleLabel($role) ?><?= !empty($doctor['dept_name']) ? ' — ' . e($doctor['dept_name']) : '' ?></div>
            <div class="uid">UID: <?= e(Auth::uniqueId()) ?></div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $item): ?>
                <?php if (isset($item['section'])): ?>
                    <div class="nav-section"><?= e($item['section']) ?></div>
                <?php else: ?>
                    <a href="<?= url($item['url']) ?>" class="spa-link <?= $currentUrl === $item['url'] ? 'active' : '' ?>" data-spa="1" data-url="<?= url($item['url']) ?>">
                        <i class="bi <?= e($item['icon']) ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= url('/logout') ?>" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:12px;">
                <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-area">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-icon-btn sidebar-toggle d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="topbar-title"><?= e($title ?? 'لوحة التحكم') ?></div>
                    <div class="topbar-subtitle"><?= e($subtitle ?? '') ?></div>
                </div>
            </div>
            <div class="topbar-right">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="topbar-icon-btn" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($notifCount > 0): ?>
                            <span class="badge-count"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px;">
                        <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                            <strong>الإشعارات</strong>
                            <a href="<?= url('/notifications') ?>" class="btn btn-sm btn-link p-0">عرض الكل</a>
                        </div>
                        <div class="notif-list">
                            <?php if (empty($recentNotifs)): ?>
                                <div class="empty-state"><i class="bi bi-bell-slash"></i><p>لا إشعارات</p></div>
                            <?php else: foreach ($recentNotifs as $n): ?>
                                <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                                    <i class="bi bi-info-circle notif-icon"></i>
                                    <div>
                                        <div class="notif-title"><?= e($n['title']) ?></div>
                                        <div class="notif-msg"><?= e(mb_substr($n['message'], 0, 80)) ?><?= mb_strlen($n['message']) > 80 ? '...' : '' ?></div>
                                        <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Profile dropdown -->
                <div class="dropdown topbar-profile-dropdown">
                    <button class="topbar-icon-btn topbar-profile-trigger" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="حسابي">
                        <i class="bi bi-person-circle"></i>
                        <i class="bi bi-chevron-down topbar-profile-caret"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end topbar-profile-menu">
                        <div class="topbar-profile-header">
                            <div class="avatar-circle"><?= mb_substr(Auth::name(), 0, 1) ?></div>
                            <div class="info">
                                <div class="name"><?= e(Auth::name()) ?></div>
                                <div class="role"><?= roleLabel($role) ?><?= !empty($doctor['dept_name']) ? ' — ' . e($doctor['dept_name']) : '' ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= url('/profile') ?>" class="dropdown-item spa-link" data-spa="1">
                            <i class="bi bi-person-badge"></i>
                            <span>الملف الشخصي</span>
                        </a>
                        <a href="<?= url('/logout') ?>" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>تسجيل الخروج</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main content (loads dynamically via AJAX) -->
        <main class="main-content" id="main-content">
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= e(getFlash('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?= e(getFlash('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $view ?>
        </main>

        <footer class="app-footer">
            <?= e($appName) ?> &copy; <?= date('Y') ?> — جميع الحقوق محفوظة
            <span class="mx-2">|</span>
            <span class="text-purple"><i class="bi bi-shield-check"></i> PHP MVC + MySQL</span>
        </footer>
    </div>
</div>

<!-- ⚡ Defer all JS to avoid blocking first paint -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@32.3.3/dist/ag-grid-community.min.js" defer></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>

<a href="<?= url('/download-logs') ?>" class="floating-logs-btn" title="تحميل سجلات النظام والأخطاء" download>
    <i class="bi bi-download"></i>
    <span>تحميل السجلات</span>
</a>

<!-- ⚡ Connection status indicator -->
<div class="conn-status" id="conn-status">
    <span class="dot"></span>
    <span class="text">متصل</span>
</div>

</body>
</html>
<?php endif; ?>
