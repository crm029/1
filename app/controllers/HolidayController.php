<?php
require_once __DIR__ . '/../models/Holiday.php';

class HolidayController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $filters = ['search' => $_GET['search'] ?? ''];
        $model = new Holiday();
        $this->view('holidays/index', [
            'title' => 'مدیریت تعطیلات',
            'holidays' => $model->all($filters),
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin']);
        $this->view('holidays/create', ['title' => 'تعطیلی جدید']);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/holidays/create');
        }

        $model = new Holiday();
        $data['holiday']['created_by'] = Auth::id();
        $model->create($data['holiday']);

        $_SESSION['success'] = 'تعطیلی ثبت شد';
        $this->redirect(BASE_URL . '/holidays');
    }

    public function editForm($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Holiday();
        $holiday = $model->findById($id);
        if (!$holiday) {
            $_SESSION['error'] = 'تعطیلی یافت نشد';
            return $this->redirect(BASE_URL . '/holidays');
        }
        [$hjy, $hjm, $hjd] = Helpers::toJalaliArray($holiday['holiday_date']);
        $this->view('holidays/edit', [
            'title' => 'ویرایش تعطیلی',
            'holiday' => $holiday,
            'initial_jalali' => sprintf('%04d/%02d/%02d', $hjy, $hjm, $hjd),
        ]);
    }

    public function edit($id) {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $model = new Holiday();
        if (!$model->findById($id)) {
            $_SESSION['error'] = 'تعطیلی یافت نشد';
            return $this->redirect(BASE_URL . '/holidays');
        }

        $data = $this->validate($id);
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/holidays/edit/' . $id);
        }

        $model->edit($id, $data['holiday']);
        $_SESSION['success'] = 'تعطیلی ویرایش شد';
        $this->redirect(BASE_URL . '/holidays');
    }

    public function toggle($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Holiday();
        if (!$model->findById($id)) {
            $_SESSION['error'] = 'تعطیلی یافت نشد';
            return $this->redirect(BASE_URL . '/holidays');
        }
        $model->toggleActive($id);
        $_SESSION['success'] = 'وضعیت تغییر کرد';
        $this->redirect(BASE_URL . '/holidays');
    }

    // AJAX: داده‌های تقویم یک ماه
    public function calendarData() {
        Auth::requireRole(['super_admin', 'admin']);

        $jy = (int)($_GET['year'] ?? 0);
        $jm = (int)($_GET['month'] ?? 0);

        if (!$jy || !$jm || $jm < 1 || $jm > 12) {
            [$jy, $jm] = Helpers::toJalaliArray(date('Y-m-d'));
        }

        $model = new Holiday();
        $holidays = $model->getByJalaliMonth($jy, $jm);
        $holiday_dates = array_map(fn($h) => $h['holiday_date'], $holidays);

        return $this->json([
            'jy' => $jy,
            'jm' => $jm,
            'month_name' => Helpers::jalaliMonthName($jm),
            'days' => Helpers::daysInJalaliMonth($jy, $jm),
            'first_weekday' => Helpers::jalaliFirstDayOfWeek($jy, $jm),
            'holidays' => $holiday_dates,
            'today' => date('Y-m-d'),
            'prev' => ['year' => $jm == 1 ? $jy - 1 : $jy, 'month' => $jm == 1 ? 12 : $jm - 1],
            'next' => ['year' => $jm == 12 ? $jy + 1 : $jy, 'month' => $jm == 12 ? 1 : $jm + 1],
        ]);
    }

    private function validate($editId = null) {
        $date = Helpers::clean($_POST['holiday_date'] ?? '');
        $title = Helpers::clean($_POST['title'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['error' => 'تاریخ نامعتبر است'];
        }
        if (mb_strlen($title) < 2) {
            return ['error' => 'عنوان را وارد کنید'];
        }

        $model = new Holiday();
        if ($model->existsDate($date, $editId)) {
            return ['error' => 'این تاریخ قبلاً ثبت شده است'];
        }

        return ['holiday' => [
            'holiday_date' => $date,
            'title' => $title,
            'is_active' => 1,
        ]];
    }
}