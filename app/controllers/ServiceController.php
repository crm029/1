<?php
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/ServiceCategory.php';

class ServiceController extends Controller {
    
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
        ];
        $model = new Service();
        $services = $model->all($filters);
        
        $catModel = new ServiceCategory();
        $categories = $catModel->activeList();
        
        $this->view('services/index', [
            'title' => 'مدیریت خدمات',
            'services' => $services,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Service();
        $catModel = new ServiceCategory();
        
        $this->view('services/create', [
            'title' => 'خدمت جدید',
            'operators' => $model->getOperators(),
            'categories' => $catModel->activeList(),
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/services/create');
        }

        $model = new Service();
        $service_id = $model->create($data['service']);
        $this->saveCommissions($model, $service_id);

        $_SESSION['success'] = 'خدمت با موفقیت ایجاد شد';
        $this->redirect(BASE_URL . '/services');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Service();
        $service = $model->findById($id);
        if (!$service) {
            $_SESSION['error'] = 'خدمت یافت نشد';
            return $this->redirect(BASE_URL . '/services');
        }
        
        $catModel = new ServiceCategory();
        
        $this->view('services/edit', [
            'title' => 'ویرایش خدمت',
            'service' => $service,
            'operators' => $model->getOperators(),
            'commissions' => $model->getOperatorCommissions($id),
            'categories' => $catModel->activeList(),
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new Service();
        $service = $model->findById($id);
        if (!$service) {
            $_SESSION['error'] = 'خدمت یافت نشد';
            return $this->redirect(BASE_URL . '/services');
        }

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/services/edit/' . $id);
        }

        $model->edit($id, $data['service']);
        $this->saveCommissions($model, $id);

        $_SESSION['success'] = 'خدمت با موفقیت ویرایش شد';
        $this->redirect(BASE_URL . '/services');
    }

    public function toggle($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Service();
        $service = $model->findById($id);
        if (!$service) {
            $_SESSION['error'] = 'خدمت یافت نشد';
            return $this->redirect(BASE_URL . '/services');
        }
        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت خدمت تغییر کرد';
        $this->redirect(BASE_URL . '/services');
    }

    private function validate() {
        $name = Helpers::clean($_POST['name'] ?? '');
        $description = Helpers::clean($_POST['description'] ?? '');
        $duration = $_POST['duration_minutes'] ?? '';
        $price = $_POST['price'] ?? '';
        $color = $_POST['color'] ?? '#D1B1B0';
        $gender = $_POST['gender_restriction'] ?? 'none';
        $show_price = isset($_POST['show_price_public']) ? 1 : 0;
        $is_doctor = isset($_POST['is_doctor_service']) ? 1 : 0;
        $same_gender = isset($_POST['require_same_gender_operator']) ? 1 : 0;
        $category_id = $_POST['category_id'] ?? '';

        if (mb_strlen($name) < 2) {
            return ['error' => 'نام خدمت را وارد کنید (حداقل ۲ کاراکتر)'];
        }
        if (empty($category_id)) {
            return ['error' => 'دسته‌بندی خدمت را انتخاب کنید'];
        }
        if (!Helpers::isNumeric($duration) || (int)$duration < 1) {
            return ['error' => 'مدت زمان باید عددی مثبت و به دقیقه باشد'];
        }
        if (!Helpers::isNumeric($price) || (int)$price < 0) {
            return ['error' => 'قیمت باید عددی مثبت باشد'];
        }
        if (!in_array($gender, ['none', 'male', 'female'])) {
            return ['error' => 'جنسیت نامعتبر است'];
        }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#D1B1B0';
        }

        return ['service' => [
            'category_id' => (int)$category_id,
            'name' => $name,
            'description' => $description ?: null,
            'duration_minutes' => (int)$duration,
            'price' => (int)$price,
            'show_price_public' => $show_price,
            'is_doctor_service' => $is_doctor,
            'gender_restriction' => $gender,
            'require_same_gender_operator' => $same_gender,
            'color' => $color,
            'is_active' => 1,
        ]];
    }

    private function saveCommissions($model, $service_id) {
        $operators = $model->getOperators();
        foreach ($operators as $op) {
            $pid = $_POST['commission_percent_' . $op['id']] ?? '';
            $fix = $_POST['commission_fixed_' . $op['id']] ?? '';

            if ($pid === '' && $fix === '') continue;

            if ($pid !== '') {
                $pid = (float)$pid;
                if ($pid < 0 || $pid > 100) continue;
                $fix = null;
            } else {
                $fix = (int)$fix;
                if ($fix < 0) continue;
                $pid = null;
            }

            $model->saveOperatorCommission($service_id, $op['id'], $pid, $fix);
        }
    }
}