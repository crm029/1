<?php
require_once __DIR__ . '/../models/FinancialLock.php';

class FinancialLockController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $model = new FinancialLock();
        $this->view('accounting/locks', [
            'title' => 'قفل دوره مالی',
            'locks' => $model->all(),
        ]);
    }

    public function lock() {
        Auth::requireRole(['super_admin', 'admin']);
        Helpers::verifyCsrf();

        $year = (int)($_POST['year'] ?? 0);
        $month = (int)($_POST['month'] ?? 0);
        $notes = Helpers::clean($_POST['notes'] ?? '');

        if ($year < 1400 || $year > 1450 || $month < 1 || $month > 12) {
            $_SESSION['error'] = 'سال یا ماه نامعتبر است';
            return $this->redirect(BASE_URL . '/accounting/locks');
        }

        $model = new FinancialLock();
        if (!$model->lock($year, $month, $notes ?: null)) {
            $_SESSION['error'] = 'این ماه قبلاً قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/locks');
        }

        $_SESSION['success'] = 'دوره مالی قفل شد';
        $this->redirect(BASE_URL . '/accounting/locks');
    }

    public function unlock($id) {
        Auth::requireRole(['super_admin']);
        $model = new FinancialLock();
        $model->unlock($id);
        $_SESSION['success'] = 'قفل دوره مالی برداشته شد';
        $this->redirect(BASE_URL . '/accounting/locks');
    }
}