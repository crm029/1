<?php
require_once __DIR__ . '/../models/Setting.php';

class SettingController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Setting();
        $settings = $model->all();
        $this->view('settings/index', [
            'title' => 'تنظیمات',
            'settings' => $settings,
            'is_super' => Auth::role() === 'super_admin',
        ]);
    }

    public function save() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new Setting();
        $isSuper = Auth::role() === 'super_admin';

        // فیلدهای عمومی
        $general = ['deposit_amount', 'work_start_time', 'work_end_time', 'card_number', 'card_holder', 'clinic_name', 'clinic_phone', 'clinic_address'];
        foreach ($general as $k) {
            if (isset($_POST[$k])) {
                $val = Helpers::clean($_POST[$k]);
                $model->set($k, $val, Auth::id());
            }
        }

        // فقط سوپر ادمین
        if ($isSuper) {
            $model->set('sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0', Auth::id());
            $model->set('bale_enabled', isset($_POST['bale_enabled']) ? '1' : '0', Auth::id());
            $model->set('sms_api_key', Helpers::clean($_POST['sms_api_key'] ?? ''), Auth::id());
            $model->set('sms_sender', Helpers::clean($_POST['sms_sender'] ?? ''), Auth::id());
            $model->set('sms_username', Helpers::clean($_POST['sms_username'] ?? ''), Auth::id());
            $model->set('sms_password', Helpers::clean($_POST['sms_password'] ?? ''), Auth::id());
            $model->set('bale_token', Helpers::clean($_POST['bale_token'] ?? ''), Auth::id());

            // آپلود لوگو
            if (!empty($_FILES['clinic_logo']['name'])) {
                $logo = $this->uploadLogo($_FILES['clinic_logo']);
                if ($logo) {
                    $model->set('clinic_logo', $logo, Auth::id());
                } else {
                    $_SESSION['error'] = 'آپلود لوگو ناموفق بود (فرمت jpg/png/svg، حداکثر ۲ مگابایت)';
                }
            }
        }

        $_SESSION['success'] = 'تنظیمات ذخیره شد';
        $this->redirect(BASE_URL . '/settings');
    }

    private function uploadLogo($file) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/svg+xml'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($file['type'], $allowed)) return null;
        if ($file['size'] > $maxSize) return null;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return null;
        return 'uploads/' . $name;
    }
}