<?php
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/CashRegister.php';
require_once __DIR__ . '/../models/FinancialLock.php';

class AccountingController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $model = new Transaction();
        $filters = [
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'search' => $_GET['search'] ?? '',
        ];
        $this->view('accounting/index', [
            'title' => 'حسابداری',
            'transactions' => $model->all($filters),
            'filters' => $filters,
            'types' => $this->types(),
        ]);
    }

    public function expenseForm() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $this->view('accounting/expense', ['title' => 'ثبت هزینه']);
    }

    public function addExpense() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده و امکان ثبت هزینه وجود ندارد';
            return $this->redirect(BASE_URL . '/accounting');
        }

        $amount = (int)($_POST['amount'] ?? 0);
        $description = Helpers::clean($_POST['description'] ?? '');
        $method = $_POST['payment_method'] ?? 'cash';

        if ($amount <= 0) {
            $_SESSION['error'] = 'مبلغ باید مثبت باشد';
            return $this->redirect(BASE_URL . '/accounting/expense');
        }
        if (mb_strlen($description) < 3) {
            $_SESSION['error'] = 'توضیحات الزامی است';
            return $this->redirect(BASE_URL . '/accounting/expense');
        }
        if (!in_array($method, ['cash', 'pos', 'card_to_card'])) {
            $method = 'cash';
        }

        $model = new Transaction();
        $model->create([
            'type' => 'expense',
            'amount' => $amount,
            'payment_method' => $method,
            'description' => $description,
            'created_by' => Auth::id(),
        ]);

        $_SESSION['success'] = 'هزینه ثبت شد';
        $this->redirect(BASE_URL . '/accounting');
    }

    public function cashRegister() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $date = $_GET['date'] ?? date('Y-m-d');

        $crModel = new CashRegister();
        $trModel = new Transaction();
        $register = $crModel->getOrCreate($date);

        $stats = $this->calcStats($trModel, $date);

        $this->view('accounting/cash_register', [
            'title' => 'صندوق روزانه',
            'register' => $register,
            'stats' => $stats,
            'date' => $date,
            'recent' => $crModel->recent(15),
        ]);
    }

    public function saveCashRegister() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $date = Helpers::clean($_POST['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $_SESSION['error'] = 'تاریخ نامعتبر';
            return $this->redirect(BASE_URL . '/accounting/cash-register');
        }

        $lock = new FinancialLock();
        if ($lock->isLocked($date)) {
            $_SESSION['error'] = 'دوره مالی این تاریخ قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/cash-register?date=' . $date);
        }

        $crModel = new CashRegister();
        $register = $crModel->findByDate($date);
        if (!$register) {
            $_SESSION['error'] = 'صندوق این روز یافت نشد';
            return $this->redirect(BASE_URL . '/accounting/cash-register?date=' . $date);
        }
        if ($register['is_closed']) {
            $_SESSION['error'] = 'صندوق این روز قبلاً بسته شده است';
            return $this->redirect(BASE_URL . '/accounting/cash-register?date=' . $date);
        }

        $opening = (int)($_POST['opening_balance'] ?? 0);
        $closing = (int)($_POST['closing_balance'] ?? 0);
        $reason = Helpers::clean($_POST['difference_reason'] ?? '');
        $action = $_POST['action'] ?? 'save';

        $trModel = new Transaction();
        $stats = $this->calcStats($trModel, $date);

        $expected = $opening + $stats['net_cash'];
        $difference = $closing - $expected;

        $update = [
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'cash_income' => $stats['cash_income'],
            'pos_income' => $stats['pos_income'],
            'card_to_card_income' => $stats['card_to_card_income'],
            'deposit_income' => $stats['deposit_income'],
            'total_expense' => $stats['total_expense'],
            'expected_balance' => $expected,
            'difference_amount' => $difference,
        ];

        if ($action === 'close') {
            if ($difference != 0 && mb_strlen($reason) < 3) {
                $_SESSION['error'] = 'برای مغایرت باید دلیل وارد کنید';
                return $this->redirect(BASE_URL . '/accounting/cash-register?date=' . $date);
            }
            $update['difference_reason'] = $reason;
            $update['is_closed'] = 1;
            $update['closed_by'] = Auth::id();
            $update['closed_at'] = date('Y-m-d H:i:s');
            $_SESSION['success'] = 'صندوق بسته شد';
        } else {
            $update['difference_reason'] = $reason;
            $_SESSION['success'] = 'اطلاعات صندوق ذخیره شد';
        }

        $crModel->updateStats($register['id'], $update);
        $this->redirect(BASE_URL . '/accounting/cash-register?date=' . $date);
    }

    public function commissions() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $filters = [
            'operator_id' => $_GET['operator_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'paid' => $_GET['paid'] ?? '',
        ];

        $sql = "SELECT a.id, a.booking_code, a.appointment_date, a.done_at, 
                       a.operator_commission, a.commission_paid, a.commission_paid_at,
                       a.service_price,
                       c.full_name AS client_name, c.file_number,
                       s.name AS service_name,
                       u.full_name AS operator_name, u.is_doctor
                FROM appointments a
                JOIN clients c ON c.id = a.client_id
                JOIN services s ON s.id = a.service_id
                LEFT JOIN users u ON u.id = a.operator_id
                WHERE a.status = 'done' AND a.deleted_at IS NULL AND a.operator_id IS NOT NULL";
        $params = [];

        if (!empty($filters['operator_id'])) {
            $sql .= " AND a.operator_id = ?";
            $params[] = $filters['operator_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.appointment_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.appointment_date <= ?";
            $params[] = $filters['date_to'];
        }
        if ($filters['paid'] !== '') {
            $sql .= " AND a.commission_paid = ?";
            $params[] = (int)$filters['paid'];
        }
        $sql .= " ORDER BY a.appointment_date DESC, a.done_at DESC";

        $model = new Model();
        $rows = $model->fetchAll($sql, $params);

        $totalEarned = 0;
        $totalPaid = 0;
        $totalUnpaid = 0;
        foreach ($rows as $r) {
            $totalEarned += (int)$r['operator_commission'];
            if ($r['commission_paid']) $totalPaid += (int)$r['operator_commission'];
            else $totalUnpaid += (int)$r['operator_commission'];
        }

        $operators = $model->fetchAll(
            "SELECT id, full_name, is_doctor FROM users 
             WHERE role = 'operator' AND is_active = 1 AND deleted_at IS NULL 
             ORDER BY full_name"
        );

        $this->view('accounting/commissions', [
            'title' => 'دستمزد اپراتورها',
            'rows' => $rows,
            'filters' => $filters,
            'operators' => $operators,
            'totalEarned' => $totalEarned,
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalUnpaid,
        ]);
    }

    public function payCommission($id) {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $model = new Model();
        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/commissions');
        }

        try {
            $model->beginTransaction();
            $appt = $model->fetchOne(
                "SELECT * FROM appointments WHERE id = ? AND status = 'done' AND deleted_at IS NULL FOR UPDATE",
                [$id]
            );
            if (!$appt || $appt['commission_paid']) {
                throw new RuntimeException('نوبت یافت نشد یا دستمزد آن قبلاً پرداخت شده است.');
            }
            $updated = $model->query(
                "UPDATE appointments SET commission_paid = 1, commission_paid_at = NOW()
                 WHERE id = ? AND commission_paid = 0 AND status = 'done'",
                [$id]
            );
            if ($updated->rowCount() !== 1) {
                throw new RuntimeException('پرداخت دستمزد هم‌زمان انجام شده است.');
            }
            $model->query(
                "INSERT INTO transactions (appointment_id, type, amount, payment_method, description, created_by)
                 VALUES (?, 'commission', ?, 'cash', ?, ?)",
                [$id, $appt['operator_commission'], 'دستمزد نوبت ' . $appt['booking_code'], Auth::id()]
            );
            $model->commit();
        } catch (Exception $e) {
            $model->rollBack();
            $_SESSION['error'] = $e->getMessage() ?: 'ثبت پرداخت دستمزد ناموفق بود';
            return $this->redirect(BASE_URL . '/accounting/commissions');
        }

        $_SESSION['success'] = 'دستمزد پرداخت شد';
        $this->redirect(BASE_URL . '/accounting/commissions');
    }

    public function payAllCommissions($operator_id) {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/commissions');
        }

        $model = new Model();
        $total = 0;
        $count = 0;
        try {
            $model->beginTransaction();
            $rows = $model->fetchAll(
                "SELECT id, booking_code, operator_commission FROM appointments
                 WHERE status = 'done' AND operator_id = ? AND commission_paid = 0 AND deleted_at IS NULL
                 FOR UPDATE",
                [$operator_id]
            );
            if (empty($rows)) {
                throw new RuntimeException('دستمزد پرداخت‌نشده‌ای برای این اپراتور وجود ندارد');
            }

            foreach ($rows as $row) {
                $updated = $model->query(
                    "UPDATE appointments SET commission_paid = 1, commission_paid_at = NOW()
                     WHERE id = ? AND commission_paid = 0 AND status = 'done'",
                    [$row['id']]
                );
                if ($updated->rowCount() !== 1) {
                    throw new RuntimeException('یکی از پرداخت‌ها هم‌زمان توسط کاربر دیگری ثبت شده است.');
                }
                $model->query(
                    "INSERT INTO transactions (appointment_id, type, amount, payment_method, description, created_by)
                     VALUES (?, 'commission', ?, 'cash', ?, ?)",
                    [$row['id'], $row['operator_commission'], 'دستمزد نوبت ' . $row['booking_code'], Auth::id()]
                );
                $total += (int)$row['operator_commission'];
                $count++;
            }
            $model->commit();
        } catch (Exception $e) {
            $model->rollBack();
            $_SESSION['error'] = $e->getMessage() ?: 'پرداخت گروهی دستمزد ناموفق بود';
            return $this->redirect(BASE_URL . '/accounting/commissions');
        }

        $_SESSION['success'] = 'مجموع ' . number_format($total) . ' تومان دستمزد پرداخت شد (' . $count . ' نوبت)';
        $this->redirect(BASE_URL . '/accounting/commissions');
    }

    private function calcStats($trModel, $date) {
        $cash_in = $trModel->sumByTypeAndDate('deposit', $date, $date, 'cash') + $trModel->sumByTypeAndDate('payment', $date, $date, 'cash');
        $pos_in = $trModel->sumByTypeAndDate('deposit', $date, $date, 'pos') + $trModel->sumByTypeAndDate('payment', $date, $date, 'pos');
        $c2c_in = $trModel->sumByTypeAndDate('deposit', $date, $date, 'card_to_card') + $trModel->sumByTypeAndDate('payment', $date, $date, 'card_to_card');
        $deposit_only = $trModel->sumByTypeAndDate('deposit', $date, $date);
        $expense = $trModel->sumByTypeAndDate('expense', $date, $date);

        return [
            'cash_income' => $cash_in,
            'pos_income' => $pos_in,
            'card_to_card_income' => $c2c_in,
            'deposit_income' => $deposit_only,
            'total_expense' => $expense,
            'net_cash' => $cash_in - $expense,
        ];
    }

    public function types() {
        return [
            'deposit' => 'بیعانه',
            'payment' => 'تسویه',
            'refund' => 'برگشت وجه',
            'expense' => 'هزینه',
            'salary' => 'حقوق',
            'commission' => 'دستمزد',
        ];
    }
}