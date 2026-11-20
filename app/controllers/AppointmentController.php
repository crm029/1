<?php
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Schedule.php';

class AppointmentController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $filters = [
            'status' => $_GET['status'] ?? '',
            'operator_id' => $_GET['operator_id'] ?? '',
            'service_id' => $_GET['service_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $model = new Appointment();
        $serviceModel = new Service();

        $this->view('appointments/index', [
            'title' => 'مدیریت نوبت‌ها',
            'appointments' => $model->all($filters),
            'filters' => $filters,
            'services' => $serviceModel->all(),
            'operators' => $serviceModel->getOperators(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function createForm() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $serviceModel = new Service();
        $settingModel = new Setting();

        $this->view('appointments/create', [
            'title' => 'نوبت جدید',
            'services' => $serviceModel->all(['is_active' => 1]),
            'operators' => $serviceModel->getOperators(),
            'deposit' => (int)$settingModel->get('deposit_amount', 200000),
        ]);
    }

    public function searchClient() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $q = Helpers::clean($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) return $this->json([]);
        $model = new Client();
        $results = $model->fetchAll(
            "SELECT id, file_number, full_name, mobile, gender FROM clients 
             WHERE deleted_at IS NULL AND is_active = 1 
               AND (file_number LIKE ? OR mobile LIKE ? OR full_name LIKE ?)
             LIMIT 10",
            ["%$q%", "%$q%", "%$q%"]
        );
        return $this->json($results);
    }

    public function getServiceOperators($service_id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $serviceModel = new Service();
        $service = $serviceModel->findById($service_id);
        if (!$service) return $this->json([]);

        $operators = $serviceModel->getOperators();
        $result = [];
        foreach ($operators as $op) {
            $commission = $serviceModel->fetchOne(
                "SELECT id FROM operator_services WHERE service_id = ? AND operator_id = ? AND deleted_at IS NULL",
                [$service_id, $op['id']]
            );
            if (!$commission) continue;

            $user = $serviceModel->fetchOne("SELECT gender FROM users WHERE id = ?", [$op['id']]);
            $result[] = [
                'id' => $op['id'],
                'name' => $op['full_name'],
                'is_doctor' => $op['is_doctor'],
                'gender' => $user['gender'],
            ];
        }
        return $this->json($result);
    }

    public function getAvailableSlots() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $operator_id = (int)($_GET['operator_id'] ?? 0);
        $service_id = (int)($_GET['service_id'] ?? 0);
        $date = Helpers::clean($_GET['date'] ?? '');

        if (!$operator_id || !$service_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['error' => 'پارامتر نامعتبر']);
        }

        $serviceModel = new Service();
        $service = $serviceModel->findById($service_id);
        if (!$service) return $this->json(['error' => 'خدمت یافت نشد']);

        $scheduleModel = new Schedule();
        $schedule = $scheduleModel->findByDate($operator_id, $date);
        if (!$schedule || $schedule['status'] !== 'present') {
            return $this->json(['slots' => [], 'message' => 'اپراتور در این روز حضور ندارد']);
        }

        $settingModel = new Setting();
        $workStart = $schedule['start_time'] ?: $settingModel->get('work_start_time', '09:00');
        $workEnd = $schedule['end_time'] ?: $settingModel->get('work_end_time', '21:00');

        $duration = (int)$service['duration_minutes'];

        $slots = [];
        $t = strtotime($date . ' ' . $workStart);
        $endTs = strtotime($date . ' ' . $workEnd);
        while ($t + ($duration * 60) <= $endTs) {
            $slots[] = ['start' => date('H:i', $t), 'end' => date('H:i', $t + ($duration * 60))];
            $t += $duration * 60;
        }

        $apptModel = new Appointment();
        $booked = $apptModel->getByOperatorDate($operator_id, $date);
        $available = [];
        foreach ($slots as $slot) {
            $conflict = false;
            foreach ($booked as $b) {
                if ($slot['start'] < $b['end_time'] && $slot['end'] > $b['start_time']) {
                    $conflict = true;
                    break;
                }
            }
            $available[] = [
                'start' => $slot['start'],
                'end' => $slot['end'],
                'available' => !$conflict,
            ];
        }

        return $this->json(['slots' => $available]);
    }

    public function create() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $data = $this->validate();
        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            return $this->redirect(BASE_URL . '/appointments/create');
        }

        $model = new Appointment();
        $data['appointment']['booking_code'] = $model->generateBookingCode();
        $data['appointment']['created_by'] = Auth::id();
        $data['appointment']['status'] = 'confirmed';
        $data['appointment']['remaining_amount'] = $data['appointment']['service_price'] - $data['appointment']['deposit_amount'];

        $slot = $data['appointment'];
        $slotLock = null;
        try {
            if (!empty($slot['operator_id'])) {
                $slotLock = 'appt_' . substr(hash('sha256', (int)$slot['operator_id'] . '|' . $slot['appointment_date']), 0, 48);
                $lockResult = $model->fetchOne("SELECT GET_LOCK(?, 10) AS acquired", [$slotLock]);
                if ((int)($lockResult['acquired'] ?? 0) !== 1) {
                    throw new RuntimeException('زمان انتخابی در حال بررسی است؛ دوباره تلاش کنید.');
                }
                if ($model->hasConflict($slot['operator_id'], $slot['appointment_date'], $slot['start_time'], $slot['end_time'])) {
                    throw new RuntimeException('این زمان هم‌زمان رزرو شده است.');
                }
            }
            $id = $model->create($data['appointment']);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage() ?: 'ثبت نوبت ناموفق بود';
            return $this->redirect(BASE_URL . '/appointments/create');
        } finally {
            if ($slotLock !== null) {
                $model->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$slotLock]);
            }
        }
        $_SESSION['success'] = 'نوبت با کد ' . $data['appointment']['booking_code'] . ' ثبت شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    public function show($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator']);
        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }
        if (Auth::role() === 'operator' && $appt['operator_id'] != Auth::id()) {
            http_response_code(403);
            die('دسترسی غیرمجاز');
        }

        // اپراتورهای این خدمت (برای انتخاب اپراتور جدید)
        $serviceModel = new Service();
        $service_operators = [];
        foreach ($serviceModel->getOperators() as $op) {
            $commission = $serviceModel->fetchOne(
                "SELECT id FROM operator_services WHERE service_id = ? AND operator_id = ? AND deleted_at IS NULL",
                [$appt['service_id'], $op['id']]
            );
            if ($commission && $op['id'] != $appt['operator_id']) {
                $service_operators[] = $op;
            }
        }

        $this->view('appointments/show', [
            'title' => 'جزئیات نوبت',
            'appt' => $appt,
            'statuses' => $this->statuses(),
            'service_operators' => $service_operators,
        ]);
    }

    public function changeStatus($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }
        if (Auth::role() === 'operator' && $appt['operator_id'] != Auth::id()) {
            http_response_code(403);
            die('دسترسی غیرمجاز');
        }

        $newStatus = $_POST['status'] ?? '';
        if (!array_key_exists($newStatus, $this->statuses())) {
            $_SESSION['error'] = 'وضعیت نامعتبر';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        if (Auth::role() === 'operator') {
            if ($newStatus !== 'done') {
                $_SESSION['error'] = 'اپراتور فقط می‌تواند نوبت را ببندد';
                return $this->redirect(BASE_URL . '/appointments/show/' . $id);
            }
            $note = Helpers::clean($_POST['operator_note'] ?? '');
            if (mb_strlen($note) < 3) {
                $_SESSION['error'] = 'برای بستن نوبت، توضیحات خدمات الزامی است';
                return $this->redirect(BASE_URL . '/appointments/show/' . $id);
            }
            $commission = $model->calculateOperatorCommission($id);
            $model->edit($id, [
                'status' => 'done',
                'operator_note' => $note,
                'done_at' => date('Y-m-d H:i:s'),
                'operator_commission' => $commission,
            ]);

            // ============ نوتیف: بستن نوبت توسط اپراتور ============
            try {
                Notifier::send('appointment_done', $appt['client_mobile'], [
                    'client_name' => $appt['client_name'],
                    'booking_code' => $appt['booking_code'],
                    'service_name' => $appt['service_name'] ?? '',
                ]);
            } catch (Exception $e) {}

        } else {
            $updateData = ['status' => $newStatus];
            $now = date('Y-m-d H:i:s');
            if ($newStatus === 'confirmed') $updateData['confirmed_at'] = $now;
            if ($newStatus === 'awaiting_service') $updateData['confirmed_at'] = $appt['confirmed_at'] ?: $now;
            if ($newStatus === 'done') $updateData['done_at'] = $now;
            $model->edit($id, $updateData);

            // ============ نوتیف بر اساس وضعیت جدید ============
            try {
                if ($newStatus === 'no_show') {
                    Notifier::send('no_show', $appt['client_mobile'], [
                        'client_name' => $appt['client_name'],
                        'booking_code' => $appt['booking_code'],
                        'appointment_date' => Helpers::toJalaliString($appt['appointment_date']),
                    ]);
                } elseif ($newStatus === 'done') {
                    Notifier::send('appointment_done', $appt['client_mobile'], [
                        'client_name' => $appt['client_name'],
                        'booking_code' => $appt['booking_code'],
                        'service_name' => $appt['service_name'] ?? '',
                    ]);
                } elseif ($newStatus === 'cancelled') {
                    Notifier::send('receipt_cancelled', $appt['client_mobile'], [
                        'client_name' => $appt['client_name'],
                        'booking_code' => $appt['booking_code'],
                        'reason' => 'لغو توسط مدیر',
                    ]);
                }
            } catch (Exception $e) {}
        }

        $_SESSION['success'] = 'وضعیت نوبت به‌روزرسانی شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // تایید رسید بیعانه توسط منشی
    public function confirmReceipt($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }
        if ($appt['status'] !== 'pending_receipt') {
            $_SESSION['error'] = 'این نوبت در وضعیت تایید رسید نیست';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked($appt['appointment_date'])) {
            $_SESSION['error'] = 'این دوره مالی قفل شده و قابل تغییر نیست';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        $model->edit($id, [
            'status' => 'confirmed',
            'confirmed_by' => Auth::id(),
            'confirmed_at' => date('Y-m-d H:i:s'),
        ]);

        (new ActivityLog())->log('confirm', 'تایید رسید نوبت ' . $appt['booking_code'], 'appointments', $id);

        // ثبت خودکار تراکنش بیعانه
        require_once __DIR__ . '/../models/Transaction.php';
        $trModel = new Transaction();
        $trModel->create([
            'appointment_id' => $id,
            'client_id' => $appt['client_id'],
            'type' => 'deposit',
            'amount' => $appt['deposit_amount'],
            'payment_method' => 'card_to_card',
            'description' => 'بیعانه نوبت ' . $appt['booking_code'],
            'created_by' => Auth::id(),
        ]);

        // ============ نوتیف: تایید رسید ============
        try {
            Notifier::send('receipt_confirmed', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'service_name' => $appt['service_name'] ?? '',
                'appointment_date' => Helpers::toJalaliString($appt['appointment_date']),
                'start_time' => substr($appt['start_time'], 0, 5),
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'رسید تایید و نوبت قطعی شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // لغو / تعلیق نوبت
    public function suspendOrCancel($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }

        $action = $_POST['action'] ?? '';
        $reason = Helpers::clean($_POST['reason'] ?? '');

        if (!in_array($action, ['suspended', 'cancelled'])) {
            $_SESSION['error'] = 'عملیات نامعتبر';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }
        if (mb_strlen($reason) < 3) {
            $_SESSION['error'] = 'دلیل الزامی است';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        $model->edit($id, [
            'status' => $action,
            'cancel_reason' => $reason,
        ]);

        // ============ نوتیف: لغو/تعلیق ============
        try {
            Notifier::send('receipt_cancelled', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'reason' => $reason,
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = $action === 'cancelled' ? 'نوبت لغو شد' : 'نوبت به تعلیق درآمد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // تغییر وضعیت به در انتظار اجرا
    public function markAwaiting($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }
        $model->edit($id, ['status' => 'awaiting_service']);
        $_SESSION['success'] = 'نوبت به در انتظار اجرا تغییر کرد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // تسویه حساب
    public function settle($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }

        require_once __DIR__ . '/../models/FinancialLock.php';
        $financialLock = new FinancialLock();
        if ($financialLock->isLocked($appt['appointment_date'])) {
            $_SESSION['error'] = 'این دوره مالی قفل شده و قابل تغییر نیست';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }
        if ($appt['status'] !== 'awaiting_service') {
            $_SESSION['error'] = 'این نوبت در وضعیت قابل تسویه نیست';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        $paid = (int)($_POST['paid_amount'] ?? 0);
        $expected = (int)($appt['remaining_amount'] ?? 0);
        $method = $_POST['payment_method'] ?? 'cash';
        if (!in_array($method, ['cash', 'pos', 'card_to_card'], true)) $method = 'cash';
        if ($expected <= 0 || $paid !== $expected) {
            $_SESSION['error'] = 'مبلغ تسویه باید دقیقاً برابر مانده قابل پرداخت باشد (' . number_format($expected) . ' تومان)';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        try {
            $model->beginTransaction();
            $updated = $model->query(
                "UPDATE appointments
                 SET status = 'paid', paid_amount = ?, payment_method = ?, paid_by = ?, paid_at = NOW()
                 WHERE id = ? AND status = 'awaiting_service' AND deleted_at IS NULL",
                [$paid, $method, Auth::id(), $id]
            );
            if ($updated->rowCount() !== 1) {
                throw new RuntimeException('این نوبت هم‌زمان توسط کاربر دیگری تغییر کرده است.');
            }

            $model->query(
                "INSERT INTO transactions (appointment_id, client_id, type, amount, payment_method, description, created_by)
                 VALUES (?, ?, 'payment', ?, ?, ?, ?)",
                [$id, $appt['client_id'], $paid, $method, 'تسویه نوبت ' . $appt['booking_code'], Auth::id()]
            );
            $model->commit();
        } catch (Exception $e) {
            $model->rollBack();
            $_SESSION['error'] = $e->getMessage() ?: 'ثبت تسویه ناموفق بود';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        (new ActivityLog())->log('settle', 'تسویه نوبت ' . $appt['booking_code'], 'appointments', $id);

        try {
            Notifier::send('settled', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'paid_amount' => number_format($paid),
                'remaining_amount' => '0',
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'تسویه حساب ثبت شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // تایید لغو درخواست کلاینت
    public function approveCancel($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt || $appt['status'] !== 'cancel_requested') {
            $_SESSION['error'] = 'این نوبت درخواست لغو فعالی ندارد';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        require_once __DIR__ . '/../models/FinancialLock.php';
        $lock = new FinancialLock();
        if ($lock->isLocked($appt['appointment_date'])) {
            $_SESSION['error'] = 'این دوره مالی قفل شده و قابل تغییر نیست';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        // محاسبه جریمه
        $hoursLeft = (strtotime($appt['appointment_date'] . ' ' . $appt['start_time']) - time()) / 3600;
        $penalty = $hoursLeft < 24 ? (int)($appt['deposit_amount'] * 0.30) : 0;
        $refund = (int)$appt['deposit_amount'] - $penalty;

        $model->edit($id, [
            'status' => 'cancelled',
            'cancel_penalty_amount' => $penalty,
        ]);

        (new ActivityLog())->log('cancel', 'تایید لغو نوبت ' . $appt['booking_code'], 'appointments', $id);

        // ============ نوتیف: تایید لغو ============
        try {
            Notifier::send('cancel_approved', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'refund_amount' => number_format($refund),
                'penalty_amount' => number_format($penalty),
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'لغو نوبت تایید شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // رد درخواست لغو
    public function rejectCancel($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt || $appt['status'] !== 'cancel_requested') {
            $_SESSION['error'] = 'این نوبت درخواست لغو فعالی ندارد';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        $model->edit($id, [
            'status' => 'confirmed',
            'cancel_reason' => null,
            'cancel_card_number' => null,
            'cancel_card_holder' => null,
            'cancel_requested_at' => null,
        ]);

        // ============ نوتیف: رد درخواست لغو ============
        try {
            Notifier::send('cancel_rejected', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'درخواست لغو رد شد و نوبت به حالت قطعی برگشت';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // فرم موکول کردن به تاریخ جدید
    public function rescheduleForm($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt || $appt['status'] !== 'cancel_requested') {
            $_SESSION['error'] = 'این نوبت قابل موکول کردن نیست';
            return $this->redirect(BASE_URL . '/appointments');
        }
        $this->view('appointments/reschedule', [
            'title' => 'موکول به تاریخ جدید',
            'appt' => $appt,
        ]);
    }

    // ثبت موکول کردن
    public function rescheduleSubmit($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt || $appt['status'] !== 'cancel_requested') {
            $_SESSION['error'] = 'این نوبت قابل موکول کردن نیست';
            return $this->redirect(BASE_URL . '/appointments');
        }

        $date = Helpers::clean($_POST['new_date'] ?? '');
        $start = Helpers::clean($_POST['new_start'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $_SESSION['error'] = 'تاریخ نامعتبر است';
            return $this->redirect(BASE_URL . '/appointments/reschedule/' . $id);
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $start)) {
            $_SESSION['error'] = 'ساعت نامعتبر است';
            return $this->redirect(BASE_URL . '/appointments/reschedule/' . $id);
        }

        $startTs = strtotime($date . ' ' . $start);
        $end = date('H:i', $startTs + ((int)$appt['duration_minutes'] * 60));

        $slotLock = null;
        try {
            if (!empty($appt['operator_id'])) {
                $slotLock = 'appt_' . substr(hash('sha256', (int)$appt['operator_id'] . '|' . $date), 0, 48);
                $lockResult = $model->fetchOne("SELECT GET_LOCK(?, 10) AS acquired", [$slotLock]);
                if ((int)($lockResult['acquired'] ?? 0) !== 1) {
                    throw new RuntimeException('زمان انتخابی در حال بررسی است؛ دوباره تلاش کنید.');
                }
                if ($model->hasConflict($appt['operator_id'], $date, $start, $end, $id)) {
                    throw new RuntimeException('این ساعت برای این اپراتور رزرو شده است');
                }
            }

            $oldDate = $appt['appointment_date'];
            $oldStart = $appt['start_time'];

            $model->edit($id, [
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'confirmed',
            'cancel_reason' => null,
            'cancel_card_number' => null,
            'cancel_card_holder' => null,
            'cancel_requested_at' => null,
            'rescheduled_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage() ?: 'تغییر زمان نوبت ناموفق بود';
            return $this->redirect(BASE_URL . '/appointments/reschedule/' . $id);
        } finally {
            if ($slotLock !== null) {
                $model->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$slotLock]);
            }
        }

        // ============ نوتیف: موکول ============
        try {
            Notifier::send('rescheduled', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'old_date' => Helpers::toJalaliString($oldDate) . ' — ' . substr($oldStart, 0, 5),
                'new_date' => Helpers::toJalaliString($date),
                'new_time' => $start,
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'نوبت با موفقیت به تاریخ جدید موکول شد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    // AJAX: تاریخ‌های قابل انتخاب برای اپراتور این نوبت
    public function rescheduleDates($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) return $this->json([]);

        $scheduleModel = new Schedule();
        $holidayModel = new Model();

        $today = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+90 days'));

        $dates = [];
        $cur = $today;
        while ($cur <= $end) {
            $isHoliday = $holidayModel->fetchOne(
                "SELECT id FROM holidays WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL",
                [$cur]
            );
            $schedule = $scheduleModel->findByDate($appt['operator_id'], $cur);

            $status = 'gray';
            if (!$isHoliday && $schedule && $schedule['status'] === 'present') {
                $available = $this->hasFreeSlotForReschedule($model, $appt, $cur, $schedule, $id);
                $status = $available ? 'green' : 'red';
            }

            [$jy, $jm, $jd] = Helpers::toJalaliArray($cur);
            $dates[] = [
                'date' => $cur,
                'status' => $status,
                'jalali_day' => $jd,
                'jalali_month' => $jm,
                'jalali_year' => $jy,
                'weekday' => (int)date('w', strtotime($cur)),
            ];
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }
        return $this->json(['dates' => $dates]);
    }

    // AJAX: اسلات‌های ساعت برای تاریخ مشخص
    public function rescheduleSlots($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        $date = Helpers::clean($_GET['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $this->json(['slots' => []]);

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) return $this->json(['slots' => []]);

        $scheduleModel = new Schedule();
        $schedule = $scheduleModel->findByDate($appt['operator_id'], $date);
        if (!$schedule || $schedule['status'] !== 'present') {
            return $this->json(['slots' => [], 'message' => 'اپراتور در این روز حضور ندارد']);
        }

        $settingModel = new Setting();
        $workStart = $schedule['start_time'] ?: $settingModel->get('work_start_time', '09:00');
        $workEnd = $schedule['end_time'] ?: $settingModel->get('work_end_time', '21:00');
        $duration = (int)$appt['duration_minutes'];

        $slots = [];
        $t = strtotime($date . ' ' . $workStart);
        $endTs = strtotime($date . ' ' . $workEnd);
        while ($t + ($duration * 60) <= $endTs) {
            $slots[] = ['start' => date('H:i', $t), 'end' => date('H:i', $t + ($duration * 60))];
            $t += $duration * 60;
        }

        $booked = $model->getByOperatorDate($appt['operator_id'], $date);
        $result = [];
        foreach ($slots as $slot) {
            $conflict = false;
            foreach ($booked as $b) {
                if ($slot['start'] < $b['end_time'] && $slot['end'] > $b['start_time']) {
                    $conflict = true;
                    break;
                }
            }
            $result[] = ['start' => $slot['start'], 'end' => $slot['end'], 'available' => !$conflict];
        }
        return $this->json(['slots' => $result]);
    }

    private function hasFreeSlotForReschedule($model, $appt, $date, $schedule, $exceptId) {
        $settingModel = new Setting();
        $workStart = $schedule['start_time'] ?: $settingModel->get('work_start_time', '09:00');
        $workEnd = $schedule['end_time'] ?: $settingModel->get('work_end_time', '21:00');
        $duration = (int)$appt['duration_minutes'];
        $t = strtotime($date . ' ' . $workStart);
        $endTs = strtotime($date . ' ' . $workEnd);
        $booked = $model->getByOperatorDate($appt['operator_id'], $date);
        while ($t + ($duration * 60) <= $endTs) {
            $s = date('H:i', $t);
            $e = date('H:i', $t + ($duration * 60));
            $conflict = false;
            foreach ($booked as $b) {
                if ($s < $b['end_time'] && $e > $b['start_time']) { $conflict = true; break; }
            }
            if (!$conflict) return true;
            $t += $duration * 60;
        }
        return false;
    }

    // تغییر اپراتور نوبت
    public function changeOperator($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Appointment();
        $appt = $model->findById($id);
        if (!$appt) {
            $_SESSION['error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/appointments');
        }

        $new_operator_id = (int)($_POST['new_operator_id'] ?? 0);
        $reason = Helpers::clean($_POST['reason'] ?? '');

        if (!$new_operator_id) {
            $_SESSION['error'] = 'اپراتور جدید را انتخاب کنید';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }
        if (mb_strlen($reason) < 3) {
            $_SESSION['error'] = 'علت تغییر الزامی است';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }
        if ($new_operator_id == $appt['operator_id']) {
            $_SESSION['error'] = 'اپراتور جدید با اپراتور فعلی یکسان است';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        // گرفتن نام اپراتور قدیم و جدید
        $userModel = new User();
        $old_operator = !empty($appt['operator_id']) ? $userModel->findById($appt['operator_id']) : null;
        $new_operator = $userModel->findById($new_operator_id);

        if (!$new_operator) {
            $_SESSION['error'] = 'اپراتور جدید یافت نشد';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        }

        // Use the same operator/day advisory lock as public and staff bookings.
        // The conflict check must happen only after acquiring the lock.
        $slotLock = 'appt_' . substr(hash('sha256', (int)$new_operator_id . '|' . $appt['appointment_date']), 0, 48);
        try {
            $lockResult = $model->fetchOne("SELECT GET_LOCK(?, 10) AS acquired", [$slotLock]);
            if ((int)($lockResult['acquired'] ?? 0) !== 1) {
                throw new RuntimeException('زمان انتخابی در حال بررسی است؛ دوباره تلاش کنید.');
            }

            if ($model->hasConflict(
                $new_operator_id,
                $appt['appointment_date'],
                $appt['start_time'],
                $appt['end_time'],
                $id
            )) {
                throw new RuntimeException('اپراتور جدید در این ساعت نوبت دیگری دارد');
            }

            $model->edit($id, ['operator_id' => $new_operator_id]);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage() ?: 'تغییر اپراتور ناموفق بود';
            return $this->redirect(BASE_URL . '/appointments/show/' . $id);
        } finally {
            if (isset($lockResult) && (int)($lockResult['acquired'] ?? 0) === 1) {
                $model->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$slotLock]);
            }
        }

        (new ActivityLog())->log(
            'change_operator', 
            'تغییر اپراتور نوبت ' . $appt['booking_code'] . ' از ' . ($old_operator['full_name'] ?? '—') . ' به ' . $new_operator['full_name'], 
            'appointments', 
            $id
        );

        // ============ نوتیف: تغییر اپراتور ============
        try {
            Notifier::send('operator_changed', $appt['client_mobile'], [
                'client_name' => $appt['client_name'],
                'booking_code' => $appt['booking_code'],
                'old_operator' => $old_operator['full_name'] ?? '—',
                'new_operator' => $new_operator['full_name'] ?? '—',
            ]);
        } catch (Exception $e) {}

        $_SESSION['success'] = 'اپراتور نوبت با موفقیت تغییر کرد';
        $this->redirect(BASE_URL . '/appointments/show/' . $id);
    }

    private function validate() {
        $client_id = (int)($_POST['client_id'] ?? 0);
        $service_id = (int)($_POST['service_id'] ?? 0);
        $operator_id = (int)($_POST['operator_id'] ?? 0);
        $date = Helpers::clean($_POST['appointment_date'] ?? '');
        $start = Helpers::clean($_POST['start_time'] ?? '');
        $deposit = (int)($_POST['deposit_amount'] ?? 0);

        if (!$client_id) return ['error' => 'کلاینت را انتخاب کنید'];
        if (!$service_id) return ['error' => 'خدمت را انتخاب کنید'];
        if (!$operator_id) return ['error' => 'اپراتور را انتخاب کنید'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return ['error' => 'تاریخ نامعتبر'];
        if (!preg_match('/^\d{2}:\d{2}$/', $start)) return ['error' => 'ساعت نامعتبر'];

        $clientModel = new Client();
        $serviceModel = new Service();
        $userModel = new User();

        $client = $clientModel->findById($client_id);
        $service = $serviceModel->findById($service_id);
        $operator = $userModel->findById($operator_id);

        if (!$client || !$service || !$operator) return ['error' => 'اطلاعات نامعتبر'];

        $startTs = strtotime($start);
        $end = date('H:i', $startTs + ((int)$service['duration_minutes'] * 60));

        $apptModel = new Appointment();
        if ($apptModel->hasConflict($operator_id, $date, $start, $end)) {
            return ['error' => 'این ساعت برای این اپراتور رزرو شده است'];
        }

        return ['appointment' => [
            'client_id' => $client_id,
            'service_id' => $service_id,
            'operator_id' => $operator_id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'deposit_amount' => $deposit,
            'service_price' => (int)$service['price'],
            'remaining_amount' => (int)$service['price'] - $deposit,
            'is_random_operator' => 0,
        ]];
    }

    public function statuses() {
        return [
            'pending_receipt' => 'در انتظار تایید رسید',
            'confirmed' => 'قطعی',
            'suspended' => 'تعلیق',
            'cancel_requested' => 'درخواست لغو',
            'cancelled' => 'لغو شده',
            'awaiting_service' => 'در انتظار اجرا',
            'paid' => 'تسویه شده',
            'done' => 'انجام شده',
            'no_show' => 'غیبت',
        ];
    }
}