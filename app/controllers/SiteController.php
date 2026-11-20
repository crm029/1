<?php
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/ServiceCategory.php';

class SiteController extends Controller {
    public function home() {
        $serviceModel = new Service();
        $settingModel = new Setting();
        $settings = $settingModel->all();
        $services = $serviceModel->all(['is_active' => 1]);

        $this->view('site/home', [
            'title' => 'خانه',
            'meta_title' => 'کلینیک لیزر و زیبایی نایریکا | رزرو آنلاین نوبت',
            'meta_description' => 'در کلینیک نایریکا با خدمات لیزر و زیبایی آشنا شوید و برای دریافت خدمات، نوبت آنلاین رزرو کنید.',
            'services' => $services,
            'settings' => $settings,
        ]);
    }

    public function services() {
        $serviceModel = new Service();
        $settingModel = new Setting();
        $services = $serviceModel->all(['is_active' => 1]);
        $catModel = new ServiceCategory();
        $allCategories = $catModel->activeList();
        $usedCategoryIds = array_unique(array_column($services, 'category_id'));
        $categories = array_filter($allCategories, function($c) use ($usedCategoryIds) {
            return in_array($c['id'], $usedCategoryIds);
        });

        $this->view('site/services', [
            'title' => 'خدمات',
            'meta_title' => 'خدمات لیزر و زیبایی نایریکا',
            'meta_description' => 'فهرست خدمات فعال کلینیک نایریکا را مشاهده کنید و برای انتخاب خدمات موردنظر، نوبت آنلاین بگیرید.',
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
            'title' => 'گالری نمونه‌کارها',
            'meta_title' => 'گالری نمونه‌کارهای کلینیک نایریکا',
            'meta_description' => 'نمونه‌کارها و تصاویر قبل و بعد خدمات زیبایی کلینیک نایریکا را مشاهده کنید.',
            'settings' => $settingModel->all(),
            'gallery' => $galleryModel->active(),
            'comments' => $commentModel->approved(),
        ]);
    }

    public function contact() {
        $settingModel = new Setting();
        $this->view('site/contact', [
            'title' => 'تماس با ما',
            'meta_title' => 'تماس با کلینیک نایریکا | آدرس و شماره تماس',
            'meta_description' => 'اطلاعات تماس و نشانی کلینیک نایریکا را مشاهده کنید و برای دریافت راهنمایی با ما در ارتباط باشید.',
            'settings' => $settingModel->all(),
        ]);
    }

    public function consultationForm() {
        $settingModel = new Setting();
        $this->view('site/consultation', [
            'title' => 'درخواست مشاوره',
            'meta_title' => 'درخواست مشاوره زیبایی | کلینیک نایریکا',
            'meta_description' => 'برای دریافت راهنمایی درباره خدمات لیزر و زیبایی، درخواست مشاوره خود را در کلینیک نایریکا ثبت کنید.',
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
            'meta_title' => 'درباره کلینیک نایریکا',
            'meta_description' => 'با کلینیک نایریکا، رویکرد ما و خدمات لیزر و زیبایی آشنا شوید.',
            'settings' => $settingModel->all(),
        ]);
    }
}