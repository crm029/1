<?php
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/ServiceCategory.php';

class SiteController extends Controller {
    public function home() {
        $serviceModel = new Service();
        $settingModel = new Setting();
        $settings = $settingModel->all();

        // خدمات عمومی
        $services = $serviceModel->all(['is_active' => 1]);

        $this->view('site/home', [
            'title' => 'خانه',
            'services' => $services,
            'settings' => $settings,
        ]);
    }

    public function services() {
    $serviceModel = new Service();
    $settingModel = new Setting();
    $services = $serviceModel->all(['is_active' => 1]);

    // فقط دسته‌هایی که خدمت فعال دارن
    $catModel = new ServiceCategory();
    $allCategories = $catModel->activeList();
    $usedCategoryIds = array_unique(array_column($services, 'category_id'));
    $categories = array_filter($allCategories, function($c) use ($usedCategoryIds) {
        return in_array($c['id'], $usedCategoryIds);
    });

    $this->view('site/services', [
        'title' => 'خدمات',
        'services' => $services,
        'categories' => array_values($categories),
        'settings' => $settingModel->all(),
    ]);
}

    public function gallery() {
        require_once __DIR__ . '/../models/Gallery.php';
        require_once __DIR__ . '/../models/Comment.php';
        $settingModel = new Setting();
        $galleryModel = new Gallery();
        $commentModel = new Comment();

        $this->view('site/gallery', [
            'title' => 'گالری',
            'settings' => $settingModel->all(),
            'gallery' => $galleryModel->active(),
            'comments' => $commentModel->approved(),
        ]);
    }

    public function contact() {
        $settingModel = new Setting();
        $this->view('site/contact', [
            'title' => 'تماس با ما',
            'settings' => $settingModel->all(),
        ]);
    }

    public function consultationForm() {
        $settingModel = new Setting();
        $this->view('site/consultation', [
            'title' => 'درخواست مشاوره',
            'settings' => $settingModel->all(),
        ]);
    }

    public function consultationSubmit() {
        Helpers::verifyCsrf();
        $full_name = Helpers::clean($_POST['full_name'] ?? '');
        $mobile = Helpers::clean($_POST['mobile'] ?? '');
        $message = Helpers::clean($_POST['message'] ?? '');

        if (!Helpers::isPersianName($full_name)) {
            $_SESSION['error'] = 'نام را فارسی وارد کنید';
            return $this->redirect(BASE_URL . '/site/consultation');
        }
        if (!Helpers::isMobile($mobile)) {
            $_SESSION['error'] = 'شماره موبایل نامعتبر';
            return $this->redirect(BASE_URL . '/site/consultation');
        }
        if (mb_strlen($message) < 5) {
            $_SESSION['error'] = 'متن پیام را کامل وارد کنید';
            return $this->redirect(BASE_URL . '/site/consultation');
        }

        $model = new Model();
        $model->insert('consultation_requests', [
            'full_name' => $full_name,
            'mobile' => $mobile,
            'message' => $message,
            'status' => 'pending',
        ]);

        $_SESSION['success'] = 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.';
        $this->redirect(BASE_URL . '/site/consultation');
    }
	
	    public function about() {
        $settingModel = new Setting();
        $this->view('site/about', [
            'title' => 'درباره ما',
            'settings' => $settingModel->all(),
        ]);
    }
}