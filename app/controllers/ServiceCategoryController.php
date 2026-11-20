<?php
require_once __DIR__ . '/../models/ServiceCategory.php';

class ServiceCategoryController extends Controller {
    
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
        ];
        $model = new ServiceCategory();
        $categories = $model->all($filters);
        
        // تعداد خدمات هر دسته
        foreach ($categories as &$cat) {
            $cat['services_count'] = $model->countServices($cat['id']);
        }
        unset($cat);
        
        $this->view('service_categories/index', [
            'title' => 'مدیریت دسته‌بندی خدمات',
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin']);
        $this->view('service_categories/create', [
            'title' => 'دسته‌بندی جدید',
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/service-categories/create');
        }

        $model = new ServiceCategory();
        $data['category']['created_by'] = Auth::id();
        $model->create($data['category']);

        $_SESSION['success'] = 'دسته‌بندی با موفقیت ایجاد شد';
        $this->redirect(BASE_URL . '/service-categories');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new ServiceCategory();
        $category = $model->findById($id);
        if (!$category) {
            $_SESSION['error'] = 'دسته‌بندی یافت نشد';
            return $this->redirect(BASE_URL . '/service-categories');
        }
        $this->view('service_categories/edit', [
            'title' => 'ویرایش دسته‌بندی',
            'category' => $category,
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new ServiceCategory();
        $category = $model->findById($id);
        if (!$category) {
            $_SESSION['error'] = 'دسته‌بندی یافت نشد';
            return $this->redirect(BASE_URL . '/service-categories');
        }

        $data = $this->validate($id);
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/service-categories/edit/' . $id);
        }

        $model->edit($id, $data['category']);

        $_SESSION['success'] = 'دسته‌بندی با موفقیت ویرایش شد';
        $this->redirect(BASE_URL . '/service-categories');
    }

    public function toggle($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new ServiceCategory();
        $category = $model->findById($id);
        if (!$category) {
            $_SESSION['error'] = 'دسته‌بندی یافت نشد';
            return $this->redirect(BASE_URL . '/service-categories');
        }
        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت دسته‌بندی تغییر کرد';
        $this->redirect(BASE_URL . '/service-categories');
    }

    public function delete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new ServiceCategory();
        $category = $model->findById($id);
        if (!$category) {
            $_SESSION['error'] = 'دسته‌بندی یافت نشد';
            return $this->redirect(BASE_URL . '/service-categories');
        }

        // جلوگیری از حذف اگر خدمتی دارد
        if ($model->countServices($id) > 0) {
            $_SESSION['error'] = 'این دسته دارای خدمت است و قابل حذف نیست. ابتدا خدمات آن را به دسته دیگری منتقل کنید.';
            return $this->redirect(BASE_URL . '/service-categories');
        }

        // Soft Delete
        $model->edit($id, ['deleted_at' => date('Y-m-d H:i:s')]);

        $_SESSION['success'] = 'دسته‌بندی با موفقیت حذف شد';
        $this->redirect(BASE_URL . '/service-categories');
    }

    private function validate($except_id = null) {
        $name = Helpers::clean($_POST['name'] ?? '');
        $description = Helpers::clean($_POST['description'] ?? '');
        $icon = Helpers::clean($_POST['icon'] ?? '');
        $color = $_POST['color'] ?? '#D1B1B0';
        $sort_order = $_POST['sort_order'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (mb_strlen($name) < 2) {
            return ['error' => 'نام دسته‌بندی را وارد کنید (حداقل ۲ کاراکتر)'];
        }

        if (!Helpers::isNumeric($sort_order) || (int)$sort_order < 0) {
            $sort_order = 0;
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#D1B1B0';
        }

        $model = new ServiceCategory();
        if ($model->nameExists($name, $except_id)) {
            return ['error' => 'این نام دسته‌بندی قبلاً استفاده شده است'];
        }

        return ['category' => [
            'name' => $name,
            'description' => $description ?: null,
            'icon' => $icon ?: 'fa-solid fa-spa',
            'color' => $color,
            'sort_order' => (int)$sort_order,
            'is_active' => $is_active,
        ]];
    }
}