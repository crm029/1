<?php
require_once __DIR__ . '/../models/Gallery.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Service.php';

class ContentController extends Controller {
    // ==================== گالری ====================
    public function gallery() {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'is_active' => $_GET['is_active'] ?? '',
        ];
        $model = new Gallery();
        $this->view('content/gallery', [
            'title' => 'مدیریت گالری',
            'items' => $model->all($filters),
            'filters' => $filters,
        ]);
    }

    public function galleryCreateForm() {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        $serviceModel = new Service();
        $this->view('content/gallery_create', [
            'title' => 'تصویر جدید',
            'services' => $serviceModel->all(['is_active' => 1]),
        ]);
    }

    public function galleryCreate() {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        Helpers::verifyCsrf();

        $title = Helpers::clean($_POST['title'] ?? '');
        $service_id = (int)($_POST['service_id'] ?? 0);
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        $before = $this->uploadImage('before_image');
        $after = $this->uploadImage('after_image');

        if (!$before || !$after) {
            $_SESSION['error'] = 'آپلود تصویر قبل و بعد الزامی است (jpg/png، حداکثر ۳ مگابایت)';
            return $this->redirect(BASE_URL . '/content/gallery/create');
        }

        $model = new Gallery();
        $model->create([
            'title' => $title ?: null,
            'before_image' => $before,
            'after_image' => $after,
            'service_id' => $service_id ?: null,
            'sort_order' => $sort_order,
            'is_active' => 1,
            'created_by' => Auth::id(),
        ]);

        $_SESSION['success'] = 'تصویر با موفقیت اضافه شد';
        $this->redirect(BASE_URL . '/content/gallery');
    }

    public function galleryEditForm($id) {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        $model = new Gallery();
        $item = $model->findById($id);
        if (!$item) {
            $_SESSION['error'] = 'تصویر یافت نشد';
            return $this->redirect(BASE_URL . '/content/gallery');
        }
        $serviceModel = new Service();
        $this->view('content/gallery_edit', [
            'title' => 'ویرایش تصویر',
            'item' => $item,
            'services' => $serviceModel->all(['is_active' => 1]),
        ]);
    }

    public function galleryEdit($id) {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        Helpers::verifyCsrf();

        $model = new Gallery();
        $item = $model->findById($id);
        if (!$item) {
            $_SESSION['error'] = 'تصویر یافت نشد';
            return $this->redirect(BASE_URL . '/content/gallery');
        }

        $title = Helpers::clean($_POST['title'] ?? '');
        $service_id = (int)($_POST['service_id'] ?? 0);
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        $data = [
            'title' => $title ?: null,
            'service_id' => $service_id ?: null,
            'sort_order' => $sort_order,
        ];

        $before = $this->uploadImage('before_image');
        $after = $this->uploadImage('after_image');
        if ($before) $data['before_image'] = $before;
        if ($after) $data['after_image'] = $after;

        $model->edit($id, $data);
        $_SESSION['success'] = 'تصویر ویرایش شد';
        $this->redirect(BASE_URL . '/content/gallery');
    }

    public function galleryToggle($id) {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        $model = new Gallery();
        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت تصویر تغییر کرد';
        $this->redirect(BASE_URL . '/content/gallery');
    }

    public function galleryDelete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Gallery();
        $model->softDelete($id);
        $_SESSION['success'] = 'تصویر حذف شد';
        $this->redirect(BASE_URL . '/content/gallery');
    }

    // ==================== نظرات ====================
    public function comments() {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $model = new Comment();
        $this->view('content/comments', [
            'title' => 'مدیریت نظرات',
            'comments' => $model->all($filters),
            'filters' => $filters,
        ]);
    }

    public function commentReply($id) {
        Auth::requireRole(['super_admin', 'admin', 'content_manager']);
        Helpers::verifyCsrf();

        $model = new Comment();
        $comment = $model->findById($id);
        if (!$comment) {
            $_SESSION['error'] = 'نظر یافت نشد';
            return $this->redirect(BASE_URL . '/content/comments');
        }

        $reply = Helpers::clean($_POST['reply'] ?? '');
        $status = $_POST['status'] ?? 'approved';

        if (!in_array($status, ['pending','approved','rejected'])) $status = 'approved';

        $data = ['status' => $status];
        if (!empty($reply)) {
            $data['reply'] = $reply;
            $data['replied_by'] = Auth::id();
            $data['replied_at'] = date('Y-m-d H:i:s');
        }

        $model->edit($id, $data);
        $_SESSION['success'] = 'نظر به‌روزرسانی شد';
        $this->redirect(BASE_URL . '/content/comments');
    }

    public function commentDelete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Comment();
        $model->edit($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        $_SESSION['success'] = 'نظر حذف شد';
        $this->redirect(BASE_URL . '/content/comments');
    }

    // ==================== کمکی ====================
    private function uploadImage($field) {
        if (empty($_FILES[$field]['name']) || !isset($_FILES[$field]['tmp_name'])) return null;
        $file = $_FILES[$field];
        $maxSize = 3 * 1024 * 1024;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_uploaded_file($file['tmp_name']) ||
            (int)$file['size'] <= 0 ||
            (int)$file['size'] > $maxSize) return null;

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($mimeToExt[$mime])) return null;
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) return null;

        $name = 'gallery_' . bin2hex(random_bytes(16)) . '.' . $mimeToExt[$mime];
        $dir = __DIR__ . '/../../public/uploads/gallery/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return null;

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return null;
        return 'uploads/gallery/' . $name;
    }
}