<?php
require_once __DIR__ . '/../models/Salary.php';
require_once __DIR__ . '/../models/Transaction.php';

class SalaryController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'paid' => $_GET['paid'] ?? '',
        ];
        $model = new Salary();
        $rows = $model->all($filters);

        $totalPaid = 0;
        $totalUnpaid = 0;
        foreach ($rows as $r) {
            if ($r['is_paid']) $totalPaid += (int)$r['amount'];
            else $totalUnpaid += (int)$r['amount'];
        }

        $this->view('accounting/salaries', [
            'title' => 'حقوق پرسنل',
            'rows' => $rows,
            'filters' => $filters,
            'staff' => $model->staff(),
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalUnpaid,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $model = new Salary();
        $this->view('accounting/salary_create', [
            'title' => 'ثبت حقوق جدید',
            'staff' => $model->staff(),
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $user_id = (int)($_POST['user_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $description = Helpers::clean($_POST['description'] ?? '');

        $ps_y = (int)($_POST['ps_year'] ?? 0);
        $ps_m = (int)($_POST['ps_month'] ?? 0);
        $ps_d = (int)($_POST['ps_day'] ?? 0);
        $pe_y = (int)($_POST['pe_year'] ?? 0);
        $pe_m = (int)($_POST['pe_month'] ?? 0);
        $pe_d = (int)($_POST['pe_day'] ?? 0);

        if (!$user_id) {
            $_SESSION['error'] = 'کارمند را انتخاب کنید';
            return $this->redirect(BASE_URL . '/accounting/salaries/create');
        }
        if ($amount <= 0) {
            $_SESSION['error'] = 'مبلغ باید مثبت باشد';
            return $this->redirect(BASE_URL . '/accounting/salaries/create');
        }
        if (!$ps_y || !$ps_m || !$ps_d || !$pe_y || !$pe_m || !$pe_d) {
            $_SESSION['error'] = 'تاریخ شروع و پایان را کامل انتخاب کنید';
            return $this->redirect(BASE_URL . '/accounting/salaries/create');
        }

        [$g1, $gm1, $gd1] = Helpers::jalaliToGregorian($ps_y, $ps_m, $ps_d);
        [$g2, $gm2, $gd2] = Helpers::jalaliToGregorian($pe_y, $pe_m, $pe_d);

        $period_start = sprintf('%04d-%02d-%02d', $g1, $gm1, $gd1);
        $period_end = sprintf('%04d-%02d-%02d', $g2, $gm2, $gd2);

        if (strtotime($period_start) > strtotime($period_end)) {
            $_SESSION['error'] = 'تاریخ شروع نباید بعد از پایان باشد';
            return $this->redirect(BASE_URL . '/accounting/salaries/create');
        }
		
		        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
		
        $model = new Salary();
        $model->create([
            'user_id' => $user_id,
            'amount' => $amount,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'description' => $description ?: null,
            'created_by' => Auth::id(),
        ]);

        $_SESSION['success'] = 'حقوق ثبت شد';
        $this->redirect(BASE_URL . '/accounting/salaries');
    }
	
    public function pay($id) {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $model = new Salary();
        $salary = $model->findById($id);
        if (!$salary) {
            $_SESSION['error'] = 'رکورد یافت نشد';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
        if ($salary['is_paid']) {
            $_SESSION['error'] = 'این حقوق قبلاً پرداخت شده';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
		
		        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
		
        $model->edit($id, [
            'is_paid' => 1,
            'paid_at' => date('Y-m-d H:i:s'),
            'paid_by' => Auth::id(),
        ]);

        $trModel = new Transaction();
        $trModel->create([
            'type' => 'salary',
            'amount' => $salary['amount'],
            'payment_method' => 'cash',
            'description' => 'حقوق ' . $salary['full_name'],
            'created_by' => Auth::id(),
        ]);

        $_SESSION['success'] = 'حقوق پرداخت شد';
        $this->redirect(BASE_URL . '/accounting/salaries');
    }

    public function delete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Salary();
        $salary = $model->findById($id);
        if (!$salary) {
            $_SESSION['error'] = 'رکورد یافت نشد';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
        if ($salary['is_paid']) {
            $_SESSION['error'] = 'حقوق پرداخت‌شده قابل حذف نیست';
            return $this->redirect(BASE_URL . '/accounting/salaries');
        }
        $model->edit($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        $_SESSION['success'] = 'رکورد حذف شد';
        $this->redirect(BASE_URL . '/accounting/salaries');
    }
}