<?php
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {
    public function loginForm() {
        $this->view('auth/login', ['title' => 'ورود']);
    }

    public function login() {
        Helpers::verifyCsrf();
        $username = Helpers::clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $_SESSION['error'] = 'نام کاربری و رمز را وارد کنید';
            return $this->redirect(BASE_URL . '/login');
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'نام کاربری یا رمز اشتباه است';
            return $this->redirect(BASE_URL . '/login');
        }

        if (!$user['is_active']) {
            $_SESSION['error'] = 'حساب شما غیرفعال است';
            return $this->redirect(BASE_URL . '/login');
        }

        Auth::login($user);
        require_once __DIR__ . '/../models/ActivityLog.php';
        (new ActivityLog())->log('login', 'ورود به سیستم');
        $userModel->updateLastLogin($user['id']);
        $this->redirect(BASE_URL . '/dashboard');
    }

    public function logout() {
        require_once __DIR__ . '/../models/ActivityLog.php';
        (new ActivityLog())->log('logout', 'خروج از سیستم');
        Auth::logout();
        $this->redirect(BASE_URL . '/login');
    }

    public function changePasswordForm() {
        Auth::requireLogin();
        $captcha = Captcha::generate();
        $this->view('auth/change_password', [
            'title' => 'تغییر رمز عبور',
            'captcha' => $captcha,
        ]);
    }

    public function changePassword() {
        Auth::requireLogin();
        Helpers::verifyCsrf();

        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $captcha_input = $_POST['captcha'] ?? '';

        // کپچا
        if (!Captcha::verify($captcha_input)) {
            $_SESSION['error'] = 'پاسخ کپچا اشتباه است';
            return $this->redirect(BASE_URL . '/change-password');
        }

        $userModel = new User();
        $user = $userModel->findById(Auth::id());

        if (!password_verify($current, $user['password'])) {
            $_SESSION['error'] = 'رمز فعلی اشتباه است';
            return $this->redirect(BASE_URL . '/change-password');
        }

        if (!Helpers::isStrongPassword($new)) {
            $_SESSION['error'] = 'رمز جدید باید حداقل ۸ کاراکتر و شامل حرف بزرگ، حرف کوچک و عدد باشد';
            return $this->redirect(BASE_URL . '/change-password');
        }

        if ($new !== $confirm) {
            $_SESSION['error'] = 'تکرار رمز مطابقت ندارد';
            return $this->redirect(BASE_URL . '/change-password');
        }

        // چک نکنه رمز جدید با موبایل یکسان باشه
        if ($new === $user['mobile']) {
            $_SESSION['error'] = 'رمز جدید نمی‌تواند با شماره موبایل یکسان باشد';
            return $this->redirect(BASE_URL . '/change-password');
        }

        $userModel->updatePassword(Auth::id(), password_hash($new, PASSWORD_BCRYPT));
        $_SESSION['must_change_password'] = 0;

        require_once __DIR__ . '/../models/ActivityLog.php';
        (new ActivityLog())->log('change_password', 'تغییر رمز عبور اجباری بعد از اولین ورود', 'users', Auth::id());

        $_SESSION['success'] = 'رمز با موفقیت تغییر کرد';
        $this->redirect(BASE_URL . '/dashboard');
    }
}