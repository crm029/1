<?php
require_once __DIR__ . '/../models/DiscountCode.php';
require_once __DIR__ . '/../models/Service.php';

class DiscountController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
        ];
        $model = new DiscountCode();
        $this->view('discounts/index', [
            'title' => 'کدهای تخفیف',
            'codes' => $model->all($filters),
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin']);
        $serviceModel = new Service();
        $this->view('discounts/create', [
            'title' => 'کد تخفیف جدید',
            'services' => $serviceModel->all(['is_active' => 1]),
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/discounts/create');
        }

        $model = new DiscountCode();
        $data['code']['created_by'] = Auth::id();
        $model->create($data['code']);

        $_SESSION['success'] = 'کد تخفیف ایجاد شد';
        $this->redirect(BASE_URL . '/discounts');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new DiscountCode();
        $code = $model->findById($id);
        if (!$code) {
            $_SESSION['error'] = 'کد یافت نشد';
            return $this->redirect(BASE_URL . '/discounts');
        }
        $serviceModel = new Service();
        $this->view('discounts/edit', [
            'title' => 'ویرایش کد تخفیف',
            'code' => $code,
            'services' => $serviceModel->all(['is_active' => 1]),
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new DiscountCode();
        $existing = $model->findById($id);
        if (!$existing) {
            $_SESSION['error'] = 'کد یافت نشد';
            return $this->redirect(BASE_URL . '/discounts');
        }

        $data = $this->validate($id);
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/discounts/edit/' . $id);
        }

        $model->edit($id, $data['code']);
        $_SESSION['success'] = 'کد تخفیف ویرایش شد';
        $this->redirect(BASE_URL . '/discounts');
    }

    public function toggle($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new DiscountCode();
        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت کد تغییر کرد';
        $this->redirect(BASE_URL . '/discounts');
    }

    public function delete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new DiscountCode();
        $model->softDelete($id);
        $_SESSION['success'] = 'کد تخفیف غیرفعال شد';
        $this->redirect(BASE_URL . '/discounts');
    }

    private function validate($editId = null) {
        $code = strtoupper(Helpers::clean($_POST['code'] ?? ''));
        $service_id = (int)($_POST['service_id'] ?? 0);
        $type = $_POST['discount_type'] ?? 'percent';
        $value = (int)($_POST['discount_value'] ?? 0);
        $max_uses = (isset($_POST['max_uses']) && $_POST['max_uses'] !== '') ? (int)$_POST['max_uses'] : null;

        $sy = (int)($_POST['start_year'] ?? 0);
        $sm = (int)($_POST['start_month'] ?? 0);
        $sd = (int)($_POST['start_day'] ?? 0);
        $ey = (int)($_POST['end_year'] ?? 0);
        $em = (int)($_POST['end_month'] ?? 0);
        $ed = (int)($_POST['end_day'] ?? 0);

        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
            return ['error' => 'کد باید ۳ تا ۳۰ کاراکتر و فقط شامل حروف انگلیسی، عدد، _ و - باشد'];
        }
        if (!in_array($type, ['percent', 'fixed'])) {
            return ['error' => 'نوع تخفیف نامعتبر'];
        }
        if ($value <= 0) {
            return ['error' => 'مقدار تخفیف باید مثبت باشد'];
        }
        if ($type === 'percent' && $value > 100) {
            return ['error' => 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد'];
        }
        if ($max_uses !== null && $max_uses < 1) $max_uses = null;

        $model = new DiscountCode();
        $dup = $model->fetchOne(
            "SELECT id FROM discount_codes WHERE code = ? AND deleted_at IS NULL" . ($editId ? " AND id != ?" : ""),
            $editId ? [$code, $editId] : [$code]
        );
        if ($dup) return ['error' => 'این کد قبلاً ثبت شده است'];

        $start_date = null;
        $end_date = null;
        if ($sy && $sm && $sd) {
            [$gy, $gm, $gd] = Helpers::jalaliToGregorian($sy, $sm, $sd);
            $start_date = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }
        if ($ey && $em && $ed) {
            [$gy, $gm, $gd] = Helpers::jalaliToGregorian($ey, $em, $ed);
            $end_date = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }
        if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
            return ['error' => 'تاریخ شروع نباید بعد از پایان باشد'];
        }

        return ['code' => [
            'code' => $code,
            'service_id' => $service_id ?: null,
            'discount_type' => $type,
            'discount_value' => $value,
            'max_uses' => $max_uses,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_active' => 1,
        ]];
    }
}