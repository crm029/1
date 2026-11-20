<?php
class NotificationTemplateController extends Controller {
    
    private function getPlaceholders() {
        return [
            'booking_created' => ['client_name', 'booking_code', 'service_name', 'appointment_date', 'start_time'],
            'receipt_confirmed' => ['client_name', 'booking_code', 'service_name', 'appointment_date', 'start_time'],
            'receipt_cancelled' => ['client_name', 'booking_code', 'reason'],
            'appointment_done' => ['client_name', 'booking_code', 'service_name'],
            'settled' => ['client_name', 'booking_code', 'paid_amount', 'remaining_amount'],
            'rescheduled' => ['client_name', 'booking_code', 'old_date', 'new_date', 'new_time'],
            'operator_changed' => ['client_name', 'booking_code', 'old_operator', 'new_operator'],
            'cancel_approved' => ['client_name', 'booking_code', 'refund_amount', 'penalty_amount'],
            'cancel_rejected' => ['client_name', 'booking_code'],
            'no_show' => ['client_name', 'booking_code', 'appointment_date'],
        ];
    }

    public function index() {
        Auth::requireRole(['super_admin']);
        $model = new Model();
        $templates = $model->fetchAll("SELECT * FROM notification_templates ORDER BY id ASC");
        
        $this->view('notification_templates/index', [
            'title' => 'مدیریت متن پیام‌ها',
            'templates' => $templates,
            'placeholders' => $this->getPlaceholders(),
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin']);
        $this->view('notification_templates/create', [
            'title' => 'قالب جدید',
            'placeholders' => $this->getPlaceholders(),
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin']);
        Helpers::verifyCsrf();

        $code = Helpers::clean($_POST['code'] ?? '');
        $title = Helpers::clean($_POST['title'] ?? '');
        $description = Helpers::clean($_POST['description'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!preg_match('/^[a-z_]+$/', $code)) {
            $_SESSION['error'] = 'کد باید فقط حروف کوچک انگلیسی و _ باشد';
            return $this->redirect(BASE_URL . '/notification-templates/create');
        }
        if (mb_strlen($title) < 2) {
            $_SESSION['error'] = 'عنوان را وارد کنید';
            return $this->redirect(BASE_URL . '/notification-templates/create');
        }
        if (mb_strlen($text) < 5) {
            $_SESSION['error'] = 'متن پیام را وارد کنید';
            return $this->redirect(BASE_URL . '/notification-templates/create');
        }

        $model = new Model();
        $existing = $model->fetchOne("SELECT id FROM notification_templates WHERE code = ? LIMIT 1", [$code]);
        if ($existing) {
            $_SESSION['error'] = 'این کد قبلاً وجود دارد';
            return $this->redirect(BASE_URL . '/notification-templates/create');
        }

        $model->insert('notification_templates', [
            'code' => $code,
            'title' => $title,
            'description' => $description ?: null,
            'text' => $text,
            'is_active' => $is_active,
            'updated_by' => Auth::id(),
        ]);

        (new ActivityLog())->log('create', 'ایجاد قالب پیام: ' . $title, 'notification_templates', null);

        $_SESSION['success'] = 'قالب با موفقیت ایجاد شد';
        $this->redirect(BASE_URL . '/notification-templates');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin']);
        $model = new Model();
        $template = $model->fetchOne("SELECT * FROM notification_templates WHERE id = ? LIMIT 1", [$id]);
        if (!$template) {
            $_SESSION['error'] = 'قالب یافت نشد';
            return $this->redirect(BASE_URL . '/notification-templates');
        }
        $this->view('notification_templates/edit', [
            'title' => 'ویرایش قالب',
            'template' => $template,
            'placeholders' => $this->getPlaceholders(),
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin']);
        Helpers::verifyCsrf();

        $model = new Model();
        $template = $model->fetchOne("SELECT * FROM notification_templates WHERE id = ? LIMIT 1", [$id]);
        if (!$template) {
            $_SESSION['error'] = 'قالب یافت نشد';
            return $this->redirect(BASE_URL . '/notification-templates');
        }

        $title = Helpers::clean($_POST['title'] ?? '');
        $description = Helpers::clean($_POST['description'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (mb_strlen($title) < 2) {
            $_SESSION['error'] = 'عنوان را وارد کنید';
            return $this->redirect(BASE_URL . '/notification-templates/edit/' . $id);
        }
        if (mb_strlen($text) < 5) {
            $_SESSION['error'] = 'متن پیام را وارد کنید';
            return $this->redirect(BASE_URL . '/notification-templates/edit/' . $id);
        }

        $model->update('notification_templates', [
            'title' => $title,
            'description' => $description ?: null,
            'text' => $text,
            'is_active' => $is_active,
            'updated_by' => Auth::id(),
        ], 'id = ?', [$id]);

        (new ActivityLog())->log('edit', 'ویرایش قالب پیام: ' . $title, 'notification_templates', $id);

        $_SESSION['success'] = 'قالب با موفقیت ویرایش شد';
        $this->redirect(BASE_URL . '/notification-templates');
    }

    public function delete($id) {
        Auth::requireRole(['super_admin']);
        Helpers::verifyCsrf();

        $model = new Model();
        $template = $model->fetchOne("SELECT * FROM notification_templates WHERE id = ? LIMIT 1", [$id]);
        if (!$template) {
            $_SESSION['error'] = 'قالب یافت نشد';
            return $this->redirect(BASE_URL . '/notification-templates');
        }

        $model->query("DELETE FROM notification_templates WHERE id = ?", [$id]);

        (new ActivityLog())->log('delete', 'حذف قالب پیام: ' . $template['title'], 'notification_templates', $id);

        $_SESSION['success'] = 'قالب با موفقیت حذف شد';
        $this->redirect(BASE_URL . '/notification-templates');
    }
}