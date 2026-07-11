<?php
class ProfileController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['admin', 'doctor', 'reception', 'lab_tech', 'patient']);
    }

    public function show()
    {
        $user = Auth::user();
        $doctor = null;
        if ($user['role'] === 'doctor') {
            $doctor = Database::fetch("SELECT d.*, dep.name_ar AS dept_name FROM doctors d LEFT JOIN departments dep ON d.department_id = dep.id WHERE d.user_id = ?", [Auth::id()]);
        }
        $title = 'الملف الشخصي';
        viewWithLayout('profile/show', compact('user', 'doctor', 'title'));
    }

    public function update()
    {
        Auth::csrfVerify();
        $data = $_POST;
        $update = [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
        ];
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) { flash('error', 'كلمة المرور قصيرة'); redirect('/profile'); }
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        Database::update('users', $update, "id = ?", [Auth::id()]);
        // Update session name
        $_SESSION['name'] = $data['full_name'];
        Logger::audit('update_profile', Auth::id());
        flash('success', 'تم تحديث الملف الشخصي');
        redirect('/profile');
    }
}
