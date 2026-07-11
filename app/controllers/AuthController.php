<?php
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            redirect('/');
        }
        $view = $this->renderAuthForm('auth/login', []);
        require dirname(__DIR__) . '/views/layouts/auth.php';
    }

    public function login()
    {
        Auth::csrfVerify();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('/login');
        }

        if (Auth::attempt($email, $password)) {
            Logger::info("User logged in", ['email' => $email]);
            redirect('/');
        }

        flash('error', 'بيانات الدخول غير صحيحة أو الحساب معطّل');
        redirect('/login');
    }

    public function showRegister()
    {
        if (Auth::check()) redirect('/');
        $view = $this->renderAuthForm('auth/register', []);
        require dirname(__DIR__) . '/views/layouts/auth.php';
    }

    public function register()
    {
        Auth::csrfVerify();
        $data = $_POST;
        $errors = $this->validate([
            'full_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone' => 'required|numeric_en',
            'birth_date' => 'required',
            'gender' => 'required|in:male,female',
            'address' => 'required',
        ]);

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('/register');
        }

        // Check email unique
        $exists = Database::fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($exists) {
            flash('error', 'البريد الإلكتروني مستخدم بالفعل');
            redirect('/register');
        }

        $uniqueId = Auth::generateUniqueId();
        $userId = Database::insert('users', [
            'unique_id' => $uniqueId,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'],
            'address' => $data['address'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'role' => 'patient',
            'is_active' => 1,
            'created_at' => now(),
        ]);

        Logger::info("New patient registered", ['id' => $userId, 'email' => $data['email'], 'uid' => $uniqueId]);

        // Auto-login
        $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        Auth::login($user);

        flash('success', "تم إنشاء الحساب بنجاح! رقمك المميز: $uniqueId");
        redirect('/patient');
    }

    public function logout()
    {
        Auth::logout();
        redirect('/login');
    }

    private function renderAuthForm($viewName, $data)
    {
        extract($data);
        ob_start();
        require dirname(__DIR__) . '/views/' . $viewName . '.php';
        return ob_get_clean();
    }
}
