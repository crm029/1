<?php
require_once __DIR__ . '/../models/User.php';

class UserController extends Controller {
    private $roles = [
        'super_admin' => 'سوپر ادمین',
        'admin' => 'مدیر کل',
        'secretary' => 'منشی',
        'operator' => 'اپراتور',
        'accountant' => 'حسابدار',
        'content_manager' => 'مدیر محتوا',
    ];

public function index() {
    Auth::requireRole(['super_admin', 'admin']);
    $filters = [
        'search' => $_GET['search'] ?? '',
        'role' => $_GET['role'] ?? '',
        'is_active' => $_GET['is_active'] ?? '',
    ];
    $model = new User();

    // اگه کاربر super_admin نبود، سوپر ادمین‌ها رو نبینه
    $exclude_super = (Auth::role() !== 'super_admin');
    $users = $model->all($filters, $exclude_super);

    $this->view('users/index', [
        'title' => 'مدیریت کاربران',
        'users' => $users,
        'filters' => $filters,
    ]);
}
    public function createForm() {
        Auth::requireRole(['super_admin', 'admin']);
        $this->view('users/create', [
            'title' => 'کاربر جدید',
            'roles' => $this->roles,
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $data = $this->validateAndCollect();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            $_SESSION['old'] = $_POST;
            return $this->redirect(BASE_URL . '/users/create');
        }

        $model = new User();

        // نام کاربری و رمز اولیه = موبایل
        $data['user']['username'] = $data['user']['mobile'];
        $data['user']['password'] = password_hash($data['user']['mobile'], PASSWORD_BCRYPT);
        $data['user']['must_change_password'] = 1;

        $model->create($data['user']);
        $_SESSION['success'] = 'کاربر با موفقیت ایجاد شد. نام کاربری و رمز اولیه: ' . $data['user']['mobile'];
        $this->redirect(BASE_URL . '/users');
    }

public function editForm($id) {
    Auth::requireRole(['super_admin', 'admin']);
    $model = new User();
    $user = $model->findById($id);
    if (!$user) {
        $_SESSION['error'] = 'کاربر یافت نشد';
        return $this->redirect(BASE_URL . '/users');
    }
    
    // ← این چک اضافه بشه
    if ($user['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
        $_SESSION['error'] = 'دسترسی غیرمجاز';
        return $this->redirect(BASE_URL . '/users');
    }
    
    $this->view('users/edit', [
        'title' => 'ویرایش کاربر',
        'user' => $user,
    ]);
}

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new User();
        $user = $model->findById($id);
        if (!$user) {
            $_SESSION['error'] = 'کاربر یافت نشد';
            return $this->redirect(BASE_URL . '/users');
        }

    // اگه admin سعی کرد super_admin رو ویرایش کنه
    if ($user['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
        $_SESSION['error'] = 'دسترسی غیرمجاز';
        return $this->redirect(BASE_URL . '/users');
    }

        $data = $this->validateAndCollect($id);
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/users/edit/' . $id);
        }

        // فقط سوپر ادمین می‌تونه نقش super_admin بده
        if ($data['user']['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
            $_SESSION['error'] = 'فقط سوپر ادمین می‌تواند نقش سوپر ادمین بدهد';
            return $this->redirect(BASE_URL . '/users/edit/' . $id);
        }

        // نام کاربری رو اگر تغییر داد، یکتا بودن چک بشه
        if (!empty($_POST['username']) && $_POST['username'] !== $user['username']) {
            $newUsername = Helpers::clean($_POST['username']);
            if (!Helpers::isUsername($newUsername)) {
                $_SESSION['error'] = 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و _ باشد';
                return $this->redirect(BASE_URL . '/users/edit/' . $id);
            }
            if ($model->existsUsername($newUsername, $id)) {
                $_SESSION['error'] = 'نام کاربری تکراری است';
                return $this->redirect(BASE_URL . '/users/edit/' . $id);
            }
            $data['user']['username'] = $newUsername;
        }

        // اگر رمز جدید خواست عوض بشه
        if (!empty($_POST['new_password'])) {
            if (!Helpers::isStrongPassword($_POST['new_password'])) {
                $_SESSION['error'] = 'رمز جدید باید حداقل ۸ کاراکتر و شامل حرف و عدد باشد';
                return $this->redirect(BASE_URL . '/users/edit/' . $id);
            }
            $data['user']['password'] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $data['user']['must_change_password'] = 1;
        }

        $model->edit($id, $data['user']);
        $_SESSION['success'] = 'کاربر با موفقیت ویرایش شد';
        $this->redirect(BASE_URL . '/users');
    }

    public function toggle($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new User();
        $user = $model->findById($id);
        if (!$user) {
            $_SESSION['error'] = 'کاربر یافت نشد';
            return $this->redirect(BASE_URL . '/users');
        }
        if ($user['id'] == Auth::id()) {
            $_SESSION['error'] = 'نمی‌توانید حساب خودتان را غیرفعال کنید';
            return $this->redirect(BASE_URL . '/users');
        }

    // اگه admin سعی کرد super_admin رو toggle کنه
    if ($user['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
        $_SESSION['error'] = 'دسترسی غیرمجاز';
        return $this->redirect(BASE_URL . '/users');
    }

        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت کاربر تغییر کرد';
        $this->redirect(BASE_URL . '/users');
    }

    private function validateAndCollect($editId = null) {
        $model = new User();

        $full_name = Helpers::clean($_POST['full_name'] ?? '');
        $mobile = Helpers::clean($_POST['mobile'] ?? '');
        $role = $_POST['role'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $is_doctor = isset($_POST['is_doctor']) ? 1 : 0;

        if (!Helpers::isPersianName($full_name)) {
            return ['error' => 'نام و نام خانوادگی باید فقط حروف فارسی باشد'];
        }
        if (!Helpers::isMobile($mobile)) {
            return ['error' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود'];
        }
        if ($model->existsMobile($mobile, $editId)) {
            return ['error' => 'این شماره موبایل قبلاً ثبت شده است'];
        }
        if (!array_key_exists($role, $this->roles)) {
            return ['error' => 'نقش نامعتبر است'];
        }
        if (!in_array($gender, ['male', 'female'])) {
            return ['error' => 'جنسیت را انتخاب کنید'];
        }

        // فقط اپراتور می‌تونه دکتر باشه
        if ($is_doctor && $role !== 'operator') {
            $is_doctor = 0;
        }

        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $photo = $this->uploadPhoto($_FILES['photo']);
            if (!$photo) {
                return ['error' => 'آپلود تصویر ناموفق بود (فرمت jpg/png، حداکثر ۲ مگابایت)'];
            }
        }

        $data = [
            'full_name' => $full_name,
            'mobile' => $mobile,
            'role' => $role,
            'gender' => $gender,
            'is_doctor' => $is_doctor,
            'is_active' => 1,
        ];
        if ($photo) $data['photo'] = $photo;

        return ['user' => $data];
    }

    private function uploadPhoto($file) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($file['type'], $allowed)) return false;
        if ($file['size'] > $maxSize) return false;

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = 'user_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $dir = __DIR__ . '/../../public/uploads/users/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return false;
        return 'uploads/users/' . $name;
    }
}