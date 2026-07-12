<?php
/**
 * Helper functions
 */

// ===== mbstring polyfill (for environments without mbstring extension) =====
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) {
        return substr($string, $start, $length);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) {
        return strlen($string);
    }
}

// ===== URL helpers =====
function url($path = '')
{
    $base = rtrim(Env::get('APP_URL', ''), '/');
    if ($base === '' || $base === 'auto') {
        $scheme = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        } elseif (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            $scheme = 'https';
        } elseif (($_SERVER['REQUEST_SCHEME'] ?? 'http') === 'https') {
            $scheme = 'https';
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $base = $scheme . '://' . $host;
        }
    }
    if ($path && $path[0] !== '/') $path = '/' . $path;
    return $base . $path;
}

function asset($path)
{
    return url('/public/assets/' . ltrim($path, '/'));
}

function redirect($path)
{
    header('Location: ' . (strpos($path, 'http') === 0 ? $path : url($path)));
    exit;
}

// ===== View helper =====
function view($name, $data = [], $statusCode = 200)
{
    http_response_code($statusCode);
    extract($data);
    $viewFile = dirname(__DIR__) . '/views/' . $name . '.php';
    if (!file_exists($viewFile)) {
        throw new Exception("View not found: {$name}");
    }
    require $viewFile;
}

function viewWithLayout($name, $data = [], $layout = 'app')
{
    extract($data);
    $contentFile = dirname(__DIR__) . '/views/' . $name . '.php';
    if (!file_exists($contentFile)) {
        throw new Exception("View not found: {$name}");
    }
    ob_start();
    require $contentFile;
    $content = ob_get_clean();
    $view = $content;
    require dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
}

function partial($name, $data = [])
{
    extract($data);
    require dirname(__DIR__) . '/views/partials/' . $name . '.php';
}

// ===== Flash messages =====
function flash($key, $message)
{
    $_SESSION['_flash'][$key] = $message;
}

function getFlash($key)
{
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function hasFlash($key)
{
    return !empty($_SESSION['_flash'][$key]);
}

// ===== Output helpers =====
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function old($key, $default = '')
{
    return e($_POST[$key] ?? $default);
}

// ===== Date/time helpers =====
function now()
{
    return date('Y-m-d H:i:s');
}

function formatDate($date, $withTime = false)
{
    if (!$date) return '';
    $ts = strtotime($date);
    if ($withTime) {
        return date('Y/m/d - H:i', $ts);
    }
    return date('Y/m/d', $ts);
}

function timeAgo($datetime)
{
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return floor($diff/60) . ' دقيقة';
    if ($diff < 86400) return floor($diff/3600) . ' ساعة';
    if ($diff < 2592000) return floor($diff/86400) . ' يوم';
    return date('Y/m/d', $ts);
}

// ===== Arabic helpers =====
function roleLabel($role)
{
    $map = [
        'admin' => 'مدير',
        'doctor' => 'طبيب',
        'reception' => 'استقبال',
        'lab_tech' => 'فني مختبر',
        'patient' => 'مريض',
    ];
    return $map[$role] ?? $role;
}

function statusLabel($status)
{
    $map = [
        'ordered' => 'بانتظار التنفيذ',
        'in_progress' => 'قيد التنفيذ',
        'result_uploaded' => 'تم رفع النتيجة',
        'cancelled' => 'ملغى',
        'duplicate_skipped' => 'اكتفاء بالسابق',
        'booked' => 'محجوز',
        'completed' => 'مكتمل',
        'no_show' => 'لم يحضر',
        'proceed' => 'تنفيذ',
        'cancel' => 'إلغاء',
        'use_previous' => 'اكتفاء بالسابق',
        'normal' => 'طبيعي',
        'high' => 'مرتفع',
        'low' => 'منخفض',
        'abnormal' => 'غير طبيعي',
        'result_ready' => 'نتيجة جاهزة',
        'treatment_added' => 'خطة علاج جديدة',
        'appointment_booked' => 'حجز موعد',
        'duplicate_alert' => 'تنبيه تكرار',
    ];
    return $map[$status] ?? $status;
}

function statusBadge($status)
{
    $colors = [
        'ordered' => 'warning',
        'in_progress' => 'info',
        'result_uploaded' => 'success',
        'cancelled' => 'danger',
        'duplicate_skipped' => 'secondary',
        'booked' => 'primary',
        'completed' => 'success',
        'no_show' => 'danger',
        'normal' => 'success',
        'high' => 'warning',
        'low' => 'info',
        'abnormal' => 'danger',
    ];
    $color = $colors[$status] ?? 'secondary';
    return "<span class=\"badge bg-{$color}\">" . statusLabel($status) . "</span>";
}

function genderLabel($g)
{
    return $g === 'male' ? 'ذكر' : 'أنثى';
}

// ===== Random helpers =====
function generateUniqueId()
{
    do {
        $id = '';
        for ($i = 0; $i < 10; $i++) {
            $id .= random_int(0, 9);
        }
        $exists = Database::fetch("SELECT id FROM users WHERE unique_id = ?", [$id]);
    } while ($exists);
    return $id;
}

// ===== Sanitize HTML (for Quill output) =====
function sanitizeHtml($html)
{
    // Allow basic tags from Quill, strip dangerous ones
    $allowed = '<p><br><strong><em><u><s><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><a><img><span><div><pre><code><hr>';
    return strip_tags($html, $allowed);
}

// ===== CSRF helpers =====
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . Auth::csrfToken() . '">';
}

function csrf_meta()
{
    return '<meta name="csrf-token" content="' . Auth::csrfToken() . '">';
}
