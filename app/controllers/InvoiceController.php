<?php
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/FinancialLock.php';
require_once __DIR__ . '/../models/Setting.php';

class InvoiceController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $filters = [
            'search' => $_GET['search'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];
        $model = new Invoice();
        $this->view('accounting/invoices', [
            'title' => 'فاکتورها',
            'invoices' => $model->all($filters),
            'filters' => $filters,
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $this->view('accounting/invoice_create', ['title' => 'فاکتور جدید']);
    }

    public function searchClient() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $q = Helpers::clean($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) return $this->json([]);
        $model = new Client();
        $results = $model->fetchAll(
            "SELECT id, file_number, full_name, mobile FROM clients 
             WHERE deleted_at IS NULL AND is_active = 1 
               AND (file_number LIKE ? OR mobile LIKE ? OR full_name LIKE ?)
             LIMIT 10",
            ["%$q%", "%$q%", "%$q%"]
        );
        return $this->json($results);
    }

    public function getClientAppointments($client_id) {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $model = new Invoice();
        $rows = $model->getClientAppointments($client_id);
        return $this->json($rows);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        Helpers::verifyCsrf();

        $client_id = (int)($_POST['client_id'] ?? 0);
        $appt_ids = $_POST['appointment_ids'] ?? [];
        $notes = Helpers::clean($_POST['notes'] ?? '');
        $discount = (int)($_POST['discount_amount'] ?? 0);

        if (!$client_id) {
            $_SESSION['error'] = 'کلاینت را انتخاب کنید';
            return $this->redirect(BASE_URL . '/accounting/invoices/create');
        }
        if (!is_array($appt_ids) || empty($appt_ids)) {
            $_SESSION['error'] = 'حداقل یک نوبت انتخاب کنید';
            return $this->redirect(BASE_URL . '/accounting/invoices/create');
        }

        $lock = new FinancialLock();
        if ($lock->isLocked(date('Y-m-d'))) {
            $_SESSION['error'] = 'دوره مالی جاری قفل شده است';
            return $this->redirect(BASE_URL . '/accounting/invoices');
        }

        $model = new Invoice();
        $clientAppts = $model->getClientAppointments($client_id);
        $validAppts = [];
        foreach ($clientAppts as $ca) $validAppts[$ca['id']] = $ca;

        $items = [];
        $total = 0;
        foreach ($appt_ids as $aid) {
            $aid = (int)$aid;
            if (!isset($validAppts[$aid])) continue;
            $items[] = $validAppts[$aid];
            $total += (int)$validAppts[$aid]['service_price'];
        }

        if (empty($items)) {
            $_SESSION['error'] = 'نوبت‌های انتخابی معتبر نیستند';
            return $this->redirect(BASE_URL . '/accounting/invoices/create');
        }

        if ($discount < 0 || $discount > $total) $discount = 0;
        $payable = $total - $discount;

        $model->beginTransaction();
        try {
            $invoice_id = $model->create([
                'invoice_number' => $model->generateNumber(),
                'client_id' => $client_id,
                'total_amount' => $total,
                'discount_amount' => $discount,
                'payable_amount' => $payable,
                'notes' => $notes ?: null,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $it) {
                $model->addItem([
                    'invoice_id' => $invoice_id,
                    'appointment_id' => $it['id'],
                    'service_name' => $it['service_name'],
                    'service_price' => $it['service_price'],
                ]);
            }
            $model->commit();
        } catch (Exception $e) {
            $model->rollBack();
            $_SESSION['error'] = 'خطا در ایجاد فاکتور';
            return $this->redirect(BASE_URL . '/accounting/invoices/create');
        }

        $_SESSION['success'] = 'فاکتور با موفقیت ایجاد شد';
        $this->redirect(BASE_URL . '/accounting/invoices/show/' . $invoice_id);
    }

    public function show($id) {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $model = new Invoice();
        $invoice = $model->findById($id);
        if (!$invoice) {
            $_SESSION['error'] = 'فاکتور یافت نشد';
            return $this->redirect(BASE_URL . '/accounting/invoices');
        }
        $items = $model->getItems($id);

        $settingModel = new Setting();
        $settings = $settingModel->all();

        $this->view('accounting/invoice_show', [
            'title' => 'مشاهده فاکتور',
            'invoice' => $invoice,
            'items' => $items,
            'settings' => $settings,
        ]);
    }

    public function delete($id) {
        Auth::requireRole(['super_admin', 'admin']);
        $model = new Invoice();
        $invoice = $model->findById($id);
        if (!$invoice) {
            $_SESSION['error'] = 'فاکتور یافت نشد';
            return $this->redirect(BASE_URL . '/accounting/invoices');
        }
        $model->update('invoices', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        $_SESSION['success'] = 'فاکتور حذف شد';
        $this->redirect(BASE_URL . '/accounting/invoices');
    }
}