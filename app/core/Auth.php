<?php
/**
 * Auth — session management, login, role checks, CSRF
 */
class Auth
{
    private static $user = null;

    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            ini_set('session.use_strict_mode', '0');
            session_start();

            // Ensure CSRF token is generated immediately
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }

    public static function attempt($email, $password)
    {
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1",
            [$email]
        );
        if (!$user) {
            Logger::warning("Login failed — user not found", ['email' => $email]);
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            Logger::warning("Login failed — wrong password", ['email' => $email, 'user_id' => $user['id']]);
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['unique_id'] = $user['unique_id'];
        self::$user = $user;
        Logger::audit('login', $user['id']);
    }

    public static function logout()
    {
        if (!empty($_SESSION['user_id'])) {
            Logger::audit('logout', $_SESSION['user_id']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function check()
    {
        return !empty($_SESSION['user_id']);
    }

    public static function user()
    {
        if (self::$user === null && self::check()) {
            $userId = $_SESSION['user_id'];
            // ⚡ Cache user data in APCu for 30s to avoid DB query on every request
            $cacheKey = 'user_' . $userId;
            if (function_exists('apcu_fetch')) {
                $cached = apcu_fetch($cacheKey);
                if ($cached !== false && $cached !== null) {
                    self::$user = $cached;
                    return self::$user;
                }
            }
            self::$user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (self::$user && function_exists('apcu_store')) {
                apcu_store($cacheKey, self::$user, 30);
            }
        }
        return self::$user;
    }

    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role()
    {
        return $_SESSION['role'] ?? null;
    }

    public static function name()
    {
        return $_SESSION['name'] ?? '';
    }

    public static function uniqueId()
    {
        return $_SESSION['unique_id'] ?? '';
    }

    public static function is($role)
    {
        return self::role() === $role;
    }

    public static function requireRole($roles)
    {
        if (!self::check()) {
            // For AJAX requests, return JSON 401
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'غير مصرح — يرجى تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . url('/login'));
            exit;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        if (!in_array(self::role(), $roles)) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            http_response_code(403);
            view('errors/403', [], 403);
            exit;
        }
    }

    public static function generateUniqueId()
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

    // ===== CSRF =====
    public static function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfVerify()
    {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        // Reject empty tokens explicitly (hash_equals returns true for two empty strings)
        if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            die('CSRF token mismatch');
        }
    }
}
