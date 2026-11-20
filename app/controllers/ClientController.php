<?php
require_once __DIR__ . '/../models/Client.php';

class ClientController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'gender' => $_GET['gender'] ?? '',
        ];
        $model = new Client();
        $clients = $model->all($filters);
        $this->view('clients/index', [
            'title' => 'مدیریت کلاینت‌ها',
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $this->view('clients/create', ['title' => 'پرونده جدید']);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/clients/create');
        }

        $model = new Client();
        $data['client']['file_number'] = $model->generateFileNumber();
        $data['client']['created_by'] = Auth::id();
        $id = $model->create($data['client']);

        $_SESSION['success'] = 'پرونده با موفقیت ایجاد شد. شماره پرونده: ' . $data['client']['file_number'];
        $this->redirect(BASE_URL . '/clients');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $model = new Client();
        $client = $model->findById($id);
        if (!$client) {
            $_SESSION['error'] = 'کلاینت یافت نشد';
            return $this->redirect(BASE_URL . '/clients');
        }
        $this->view('clients/edit', [
            'title' => 'ویرایش پرونده',
            'client' => $client,
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Client();
        $client = $model->findById($id);
        if (!$client) {
            $_SESSION['error'] = 'کلاینت یافت نشد';
            return $this->redirect(BASE_URL . '/clients');
        }

        $data = $this->validate($id);
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/clients/edit/' . $id);
        }

        $model->edit($id, $data['client']);
        $_SESSION['success'] = 'پرونده با موفقیت ویرایش شد';
        $this->redirect(BASE_URL . '/clients');
    }

    private function validate($editId = null) {
        $full_name = Helpers::clean($_POST['full_name'] ?? '');
        $mobile = Helpers::clean($_POST['mobile'] ?? '');
        $emergency_mobile = Helpers::clean($_POST['emergency_mobile'] ?? '');
        $address = Helpers::clean($_POST['address'] ?? '');
        $home_phone = Helpers::clean($_POST['home_phone'] ?? '');
        $gender = $_POST['gender'] ?? '';

        if (!Helpers::isPersianName($full_name)) {
            return ['error' => 'نام و نام خانوادگی باید فقط حروف فارسی باشد'];
        }
        if (!Helpers::isMobile($mobile)) {
            return ['error' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود'];
        }
        if (!Helpers::isMobile($emergency_mobile)) {
            return ['error' => 'شماره تماس اضطراری باید ۱۱ رقم و با ۰۹ شروع شود'];
        }
        if (mb_strlen($address) < 5) {
            return ['error' => 'آدرس را کامل وارد کنید'];
        }
        if (!Helpers::isNumeric($home_phone) || strlen($home_phone) < 8) {
            return ['error' => 'شماره تلفن منزل باید حداقل ۸ رقم باشد'];
        }
        if (!in_array($gender, ['male', 'female'])) {
            return ['error' => 'جنسیت را انتخاب کنید'];
        }

        $model = new Client();
        if ($model->existsMobile($mobile, $editId)) {
            return ['error' => 'این شماره موبایل قبلاً ثبت شده است'];
        }

        $front = $this->uploadImage('id_card_front', 'clients/');
        $back = $this->uploadImage('id_card_back', 'clients/');

        if (!$editId && (!$front || !$back)) {
            return ['error' => 'آپلود تصویر روی و پشت فرم اطلاعات الزامی است (jpg/png، حداکثر ۲MB)'];
        }

        $data = [
            'full_name' => $full_name,
            'mobile' => $mobile,
            'emergency_mobile' => $emergency_mobile,
            'address' => $address,
            'home_phone' => $home_phone,
            'gender' => $gender,
            'is_active' => 1,
        ];
        if ($front) $data['id_card_front'] = $front;
        if ($back) $data['id_card_back'] = $back;
        if ($editId && !$front) unset($data['id_card_front']);
        if ($editId && !$back) unset($data['id_card_back']);

        return ['client' => $data];
    }

    private function uploadImage($field, $subdir) {
        if (empty($_FILES[$field]['name']) || !isset($_FILES[$field]['tmp_name'])) return null;
        $file = $_FILES[$field];
        $maxSize = 2 * 1024 * 1024;

        // Only allow the intended destination; never trust the browser-provided MIME or extension.
        if ($subdir !== 'clients/' ||
            ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_uploaded_file($file['tmp_name']) ||
            (int)$file['size'] <= 0 ||
            (int)$file['size'] > $maxSize) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($mimeToExt[$mime])) return null;

        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) return null;

        $ext = $mimeToExt[$mime];
        $name = 'client_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = __DIR__ . '/../../public/uploads/clients/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return null;

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return null;
        return 'uploads/clients/' . $name;
    }
}