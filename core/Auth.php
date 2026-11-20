<?php
class Auth {
    public static function login($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['must_change_password'] = $user['must_change_password'];
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role() {
        return $_SESSION['role'] ?? null;
    }

    public static function logout() {
        $_SESSION = [];
        session_destroy();
    }

public static function requireLogin() {
    if (!self::check()) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $url = $_GET['url'] ?? '';
    
    // کاربر مجبور به تغییر رمز — اجازه دسترسی فقط به این مسیرها
    if (!empty($_SESSION['must_change_password'])) {
        $allowed = ['change-password', 'logout'];
        $is_allowed = false;
        foreach ($allowed as $path) {
            if (strpos($url, $path) !== false) {
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            header('Location: ' . BASE_URL . '/change-password');
            exit;
        }
    }
}

    public static function requireRole($roles) {
        self::requireLogin();
        if (!in_array(self::role(), (array)$roles)) {
            http_response_code(403);
            die('دسترسی غیرمجاز');
        }
    }
}