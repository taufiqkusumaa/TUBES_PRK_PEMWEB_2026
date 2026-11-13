<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

// FITUR LOGIN ADMIN - ORANG 4
if (is_logged_in()) redirect('admin/dashboard.html');
$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $stmt = db()->prepare(
        "SELECT u.*, f.name faculty_name, f.is_active faculty_active "
            . "FROM users u LEFT JOIN faculties f ON f.id=u.faculty_id "
            . "WHERE u.email=? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (
        $user
        && $user['status'] === 'active'
        && ($user['role'] !== 'editor' || ($user['faculty_id'] !== null && (int)$user['faculty_active'] === 1))
        && password_verify($password, $user['password_hash'])
    ) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'faculty_id' => $user['faculty_id'] ? (int)$user['faculty_id'] : null,
            'faculty_name' => $user['faculty_name'],
        ];
        db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
        redirect('admin/dashboard.html');
    }
    $error = 'Email atau kata sandi tidak valid, atau akun sedang nonaktif.';
}
$success=flash('success');$flashError=flash('error');

$view = [];
$view['success'] = [];
if($success) {
    $item1 = [];
    $item1['success2'] = e($success);
    $view['success'][] = $item1;
}
$view['flashError'] = [];
if($flashError) {
    $item1 = [];
    $item1['flashError2'] = e($flashError);
    $view['flashError'][] = $item1;
}
$view['error'] = [];
if($error) {
    $item1 = [];
    $item1['error2'] = e($error);
    $view['error'][] = $item1;
}
$view['csrf_token'] = csrf_token();

json_response([
    'content' => $view,
    'title' => 'Masuk | Pena Kampus',
    'layout' => [],
]);
