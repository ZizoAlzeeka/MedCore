<?php
/**
 * Database Connection Test — Upload this file to your hosting and visit it in browser
 * to verify that the remote MySQL database is reachable from your server.
 *
 * After testing: DELETE this file.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    die('<p style="font-family:Cairo,Arial;direction:rtl">ملف .env غير موجود.</p>');
}

$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), '"');
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>اختبار الاتصال</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">';
echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">';
echo '<style>body{font-family:Cairo,sans-serif;background:#f0f4f8;padding:40px;}.box{max-width:700px;margin:0 auto;background:#fff;border-radius:16px;padding:30px;box-shadow:0 4px 20px rgba(108,99,255,.1);}</style>';
echo '</head><body><div class="box">';
echo '<h2 class="mb-4">🔌 اختبار اتصال قاعدة البيانات</h2>';

echo '<table class="table table-sm table-bordered mb-4">';
echo '<tr><th>DB_HOST</th><td dir="ltr">' . h($env['DB_HOST']) . '</td></tr>';
echo '<tr><th>DB_PORT</th><td dir="ltr">' . h($env['DB_PORT']) . '</td></tr>';
echo '<tr><th>DB_NAME</th><td dir="ltr">' . h($env['DB_NAME']) . '</td></tr>';
echo '<tr><th>DB_USER</th><td dir="ltr">' . h($env['DB_USER']) . '</td></tr>';
echo '<tr><th>DB_PASS</th><td dir="ltr">' . str_repeat('*', strlen($env['DB_PASS'])) . '</td></tr>';
echo '</table>';

// Test 1: TCP connectivity
echo '<h5>1️⃣ اختبار اتصال TCP</h5>';
$host = $env['DB_HOST'];
$port = (int) $env['DB_PORT'];
$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
$elapsed = round((microtime(true) - $start) * 1000);
if ($fp) {
    echo "<div class='alert alert-success'>✅ تم الاتصال بالخادم على المنفذ $port خلال {$elapsed}ms</div>";
    fclose($fp);
} else {
    echo "<div class='alert alert-danger'>❌ فشل الاتصال بـ $host:$port — $errstr ($errno)</div>";
    echo '<div class="alert alert-warning"><strong>أسباب محتملة:</strong><ul>';
    echo '<li>الخادم لا يسمح بالاتصال عن بُعد (Remote MySQL غير مفعّل في cPanel)</li>';
    echo '<li>جدار ناري يحجب المنفذ 3306</li>';
    echo '<li>عنوان IP الخاص بك غير مُدرج في قائمة Access Hosts في cPanel</li>';
    echo '<li>خدمة MySQL متوقفة على الخادم البعيد</li>';
    echo '</ul></div>';
    echo '</div></body></html>';
    exit;
}

// Test 2: PDO MySQL connection
echo '<h5>2️⃣ اختبار اتصال PDO MySQL</h5>';
try {
    $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $start = microtime(true);
    $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);
    $elapsed = round((microtime(true) - $start) * 1000);
    echo "<div class='alert alert-success'>✅ تم الاتصال بنجاح خلال {$elapsed}ms</div>";

    // Test 3: Check version
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    echo "<div class='alert alert-info'>📊 إصدار MySQL: <code>$version</code></div>";

    // Test 4: Check existing tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='alert alert-info'>📋 الجداول الموجودة: <strong>" . count($tables) . "</strong></div>";
    if (count($tables) > 0) {
        echo '<ul class="small">';
        foreach ($tables as $t) echo "<li dir='ltr'>$t</li>";
        echo '</ul>';
    }

    // Test 5: Check charset
    $charset = $pdo->query("SELECT @@character_set_database")->fetchColumn();
    $collation = $pdo->query("SELECT @@collation_database")->fetchColumn();
    echo "<div class='alert alert-info'>🔤 Charset: <code>$charset</code> | Collation: <code>$collation</code></div>";

    // Test 6: Check permissions
    try {
        $pdo->query("CREATE TABLE IF NOT EXISTS `_test_permissions` (id INT)");
        $pdo->query("DROP TABLE IF EXISTS `_test_permissions`");
        echo "<div class='alert alert-success'>✅ لديك صلاحيات CREATE/DROP TABLE</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-warning'>⚠️ لا تملك صلاحية CREATE TABLE — تحتاجها لتشغيل install.php</div>";
    }

    echo '<div class="alert alert-success mt-4"><strong>🎉 كل شيء جاهز!</strong> يمكنك الآن تشغيل <code>install.php</code> لتثبيت المنصة.</div>';

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ فشل اتصال PDO: " . h($e->getMessage()) . "</div>";
    echo '<div class="alert alert-warning"><strong>أسباب شائعة:</strong><ul>';
    echo '<li>اسم المستخدم أو كلمة المرور غير صحيحة</li>';
    echo '<li>اسم قاعدة البيانات غير صحيح</li>';
    echo '<li>المستخدم لا يملك صلاحية الوصول لهذه القاعدة</li>';
    echo '<li>ACCESS HOSTS في cPanel لا يشمل عنوان IP الخاص بخادمك</li>';
    echo '</ul></div>';
}

echo '<hr class="my-4">';
echo '<a href="install.php" class="btn btn-primary">↩ العودة للتثبيت</a>';
echo '</div></body></html>';
