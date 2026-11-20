<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Setting.php';

class ProfileController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator', 'accountant', 'content_manager']);
        
        $model = new User();
        $user = $model->findById(Auth::id());
        
        // لاگ‌های اخیر کاربر
        $logs = $model->fetchAll(
            "SELECT action, description, created_at FROM activity_logs 
             WHERE user_id = ? ORDER BY id DESC LIMIT 10",
            [Auth::id()]
        );
        
        // لوگو برای عکس پیش‌فرض
        $settingModel = new Setting();
        $clinic_logo = $settingModel->get('clinic_logo', '');
        
        $captcha = Captcha::generate();
        
        $this->view('profile/index', [
            'title' => 'پروفایل من',
            'user' => $user,
            'logs' => $logs,
            'captcha' => $captcha,
            'clinic_logo' => $clinic_logo,
        ]);
    }
    
    public function changePassword() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator', 'accountant', 'content_manager']);
        Helpers::verifyCsrf();
        
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $captcha_input = $_POST['captcha'] ?? '';
        
        if (!Captcha::verify($captcha_input)) {
            $_SESSION['error'] = 'پاسخ کپچا اشتباه است';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        $model = new User();
        $user = $model->findById(Auth::id());
        
        if (!password_verify($current, $user['password'])) {
            $_SESSION['error'] = 'رمز فعلی اشتباه است';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        // حداقل ۸ کاراکتر + حرف بزرگ + حرف کوچک + عدد
        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,}$/', $new)) {
            $_SESSION['error'] = 'رمز جدید باید حداقل ۸ کاراکتر، شامل حرف بزرگ، حرف کوچک و عدد باشد';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        if ($new !== $confirm) {
            $_SESSION['error'] = 'تکرار رمز جدید مطابقت ندارد';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $model->updatePassword(Auth::id(), $hash);
        
        (new ActivityLog())->log('change_password', 'تغییر رمز عبور', 'users', Auth::id());
        
        Auth::logout();
        $_SESSION['success'] = 'رمز عبور با موفقیت تغییر کرد. لطفاً با رمز جدید وارد شوید.';
        return $this->redirect(BASE_URL . '/login');
    }
    
    public function uploadPhoto() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator', 'accountant', 'content_manager']);
        Helpers::verifyCsrf();
        
        if (empty($_FILES['photo']['name'])) {
            $_SESSION['error'] = 'عکسی انتخاب نشده';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        $model = new User();
        $user = $model->findById(Auth::id());
        
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowed)) {
            $_SESSION['error'] = 'فرمت عکس باید jpg یا png باشد';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'حجم عکس نباید بیشتر از ۲ مگابایت باشد';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        // پوشه: uploads/users/{mobile}/
        $mobile = $user['mobile'];
        $dir = __DIR__ . '/../../public/uploads/users/' . $mobile . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar.' . $ext;
        $fullPath = $dir . $filename;
        
        // حذف عکس قبلی (هر فرمتی)
        foreach (glob($dir . 'avatar.*') as $oldFile) {
            @unlink($oldFile);
        }
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $_SESSION['error'] = 'خطا در آپلود عکس';
            return $this->redirect(BASE_URL . '/profile');
        }
        
        $relativePath = 'uploads/users/' . $mobile . '/' . $filename;
        $model->edit(Auth::id(), ['photo' => $relativePath]);
        
        (new ActivityLog())->log('change_photo', 'تغییر عکس پروفایل', 'users', Auth::id());
        
        $_SESSION['success'] = 'عکس پروفایل با موفقیت تغییر کرد';
        return $this->redirect(BASE_URL . '/profile');
    }
    
    public function deletePhoto() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator', 'accountant', 'content_manager']);
        Helpers::verifyCsrf();
        
        $model = new User();
        $user = $model->findById(Auth::id());
        
        if (!empty($user['photo'])) {
            $fullPath = __DIR__ . '/../../public/' . $user['photo'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $model->edit(Auth::id(), ['photo' => null]);
            (new ActivityLog())->log('delete_photo', 'حذف عکس پروفایل', 'users', Auth::id());
        }
        
        $_SESSION['success'] = 'عکس پروفایل حذف شد';
        return $this->redirect(BASE_URL . '/profile');
    }
}