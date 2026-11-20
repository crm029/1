<?php
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Client.php';

class DashboardController extends Controller {
    public function index() {
        Auth::requireLogin();

        $role = Auth::role();
        $userId = Auth::id();

        $data = [
            'title' => 'داشبورد',
            'role' => $role,
            'stats' => [],
            'notifications' => [],
        ];

        $model = new Model();

        if ($role === 'secretary') {
            $data['stats'] = $this->secretaryStats($model);
        } elseif (in_array($role, ['super_admin', 'admin'])) {
            $data['stats'] = $this->adminStats($model);
        } elseif ($role === 'operator') {
            $data['stats'] = $this->operatorStats($model, $userId);
        } elseif ($role === 'accountant') {
            $data['stats'] = $this->accountantStats($model);
        } elseif ($role === 'content_manager') {
            $data['stats'] = $this->contentStats($model);
        }

        $data['notifications'] = $this->getNotifications($model, $role, $userId);

        $this->view('dashboard', $data);
    }

    // ============ منشی ============
    private function secretaryStats($model) {
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        $todayAppts = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE appointment_date = ? AND deleted_at IS NULL 
               AND status NOT IN ('cancelled', 'no_show')",
            [$today]
        )['c'] ?? 0;

        $totalClients = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM clients WHERE is_active = 1 AND deleted_at IS NULL"
        )['c'] ?? 0;

        $weekAppts = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE appointment_date BETWEEN ? AND ?
               AND deleted_at IS NULL 
               AND status NOT IN ('cancelled', 'no_show')",
            [$today, $weekEnd]
        )['c'] ?? 0;

        $pendingReceipts = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE status = 'pending_receipt' AND deleted_at IS NULL"
        )['c'] ?? 0;

        return [
            'today_appointments' => (int)$todayAppts,
            'total_clients' => (int)$totalClients,
            'week_appointments' => (int)$weekAppts,
            'pending_receipts' => (int)$pendingReceipts,
        ];
    }

    // ============ مدیر / سوپر ادمین ============
    private function adminStats($model) {
        $today = date('Y-m-d');
        
        $todayAppointments = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE appointment_date = ? AND deleted_at IS NULL 
               AND status NOT IN ('cancelled', 'no_show')",
            [$today]
        )['c'] ?? 0;

        $totalClients = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM clients WHERE is_active = 1 AND deleted_at IS NULL"
        )['c'] ?? 0;

        $todayIncome = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE DATE(created_at) = ? 
               AND type IN ('deposit', 'payment')
               AND deleted_at IS NULL",
            [$today]
        )['s'] ?? 0;

        $monthIncome = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE YEAR(created_at) = YEAR(CURDATE()) 
               AND MONTH(created_at) = MONTH(CURDATE())
               AND type IN ('deposit', 'payment')
               AND deleted_at IS NULL"
        )['s'] ?? 0;

        return [
            'today_appointments' => (int)$todayAppointments,
            'total_clients' => (int)$totalClients,
            'today_income' => (int)$todayIncome,
            'month_income' => (int)$monthIncome,
        ];
    }

    // ============ اپراتور ============
    private function operatorStats($model, $operatorId) {
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        $todayAppts = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE operator_id = ? AND appointment_date = ? 
               AND deleted_at IS NULL 
               AND status IN ('confirmed', 'awaiting_service', 'paid')",
            [$operatorId, $today]
        )['c'] ?? 0;

        $weekAppts = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE operator_id = ? 
               AND appointment_date BETWEEN ? AND ?
               AND deleted_at IS NULL 
               AND status IN ('confirmed', 'awaiting_service', 'paid')",
            [$operatorId, $today, $weekEnd]
        )['c'] ?? 0;

        $unpaidCommission = $model->fetchOne(
            "SELECT COALESCE(SUM(operator_commission), 0) AS s FROM appointments 
             WHERE operator_id = ? 
               AND status = 'done' 
               AND commission_paid = 0
               AND deleted_at IS NULL",
            [$operatorId]
        )['s'] ?? 0;

        $monthDone = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM appointments 
             WHERE operator_id = ? 
               AND status = 'done' 
               AND YEAR(done_at) = YEAR(CURDATE()) 
               AND MONTH(done_at) = MONTH(CURDATE())
               AND deleted_at IS NULL",
            [$operatorId]
        )['c'] ?? 0;

        return [
            'today_appointments' => (int)$todayAppts,
            'week_appointments' => (int)$weekAppts,
            'unpaid_commission' => (int)$unpaidCommission,
            'month_done' => (int)$monthDone,
        ];
    }

    // ============ حسابدار ============
    private function accountantStats($model) {
        $today = date('Y-m-d');

        $todayIncome = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE DATE(created_at) = ? 
               AND type IN ('deposit', 'payment')
               AND deleted_at IS NULL",
            [$today]
        )['s'] ?? 0;

        $monthIncome = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE YEAR(created_at) = YEAR(CURDATE()) 
               AND MONTH(created_at) = MONTH(CURDATE())
               AND type IN ('deposit', 'payment')
               AND deleted_at IS NULL"
        )['s'] ?? 0;

        $todayExpense = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE DATE(created_at) = ? 
               AND type = 'expense'
               AND deleted_at IS NULL",
            [$today]
        )['s'] ?? 0;

        $monthExpense = $model->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS s FROM transactions 
             WHERE YEAR(created_at) = YEAR(CURDATE()) 
               AND MONTH(created_at) = MONTH(CURDATE())
               AND type = 'expense'
               AND deleted_at IS NULL"
        )['s'] ?? 0;

        $openRegister = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM cash_register 
             WHERE register_date = ? AND is_closed = 0",
            [$today]
        )['c'] ?? 0;

        $unpaidCommissions = $model->fetchOne(
            "SELECT COALESCE(SUM(operator_commission), 0) AS s FROM appointments 
             WHERE status = 'done' AND commission_paid = 0 AND deleted_at IS NULL"
        )['s'] ?? 0;

        return [
            'today_income' => (int)$todayIncome,
            'month_income' => (int)$monthIncome,
            'today_expense' => (int)$todayExpense,
            'month_expense' => (int)$monthExpense,
            'open_register' => (int)$openRegister,
            'unpaid_commissions' => (int)$unpaidCommissions,
        ];
    }

    // ============ مدیر محتوا ============
    private function contentStats($model) {
        $pendingComments = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM comments 
             WHERE status = 'pending' AND deleted_at IS NULL"
        )['c'] ?? 0;

        $totalGallery = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM gallery WHERE deleted_at IS NULL"
        )['c'] ?? 0;

        $approvedComments = $model->fetchOne(
            "SELECT COUNT(*) AS c FROM comments 
             WHERE status = 'approved' AND deleted_at IS NULL"
        )['c'] ?? 0;

        return [
            'pending_comments' => (int)$pendingComments,
            'total_gallery' => (int)$totalGallery,
            'approved_comments' => (int)$approvedComments,
        ];
    }

    // ============ اعلانات ============
    private function getNotifications($model, $role, $userId) {
        $notifs = [];

        if (in_array($role, ['super_admin', 'admin', 'secretary'])) {
            $pendingReceipts = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE status = 'pending_receipt' AND deleted_at IS NULL"
            )['c'] ?? 0;
            if ($pendingReceipts > 0) {
                $notifs[] = [
                    'icon' => 'bi-receipt',
                    'label' => $pendingReceipts . ' رسید در انتظار تایید',
                    'url' => BASE_URL . '/appointments?status=pending_receipt',
                    'color' => 'warning',
                ];
            }

            $cancelRequests = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE status = 'cancel_requested' AND deleted_at IS NULL"
            )['c'] ?? 0;
            if ($cancelRequests > 0) {
                $notifs[] = [
                    'icon' => 'bi-exclamation-triangle-fill',
                    'label' => $cancelRequests . ' درخواست لغو',
                    'url' => BASE_URL . '/appointments?status=cancel_requested',
                    'color' => 'danger',
                ];
            }

$consultations = $model->fetchOne(
    "SELECT COUNT(*) AS c FROM consultation_requests 
     WHERE status = 'pending' AND deleted_at IS NULL"
)['c'] ?? 0;
if ($consultations > 0) {
    $notifs[] = [
        'icon' => 'bi-chat-dots',
        'label' => $consultations . ' درخواست مشاوره',
        'url' => BASE_URL . '/consultations?status=pending',
        'color' => 'info',
    ];
}

            $todayAwaiting = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE appointment_date = CURDATE() 
                   AND status IN ('confirmed', 'awaiting_service') 
                   AND deleted_at IS NULL"
            )['c'] ?? 0;
            if ($todayAwaiting > 0) {
                $notifs[] = [
                    'icon' => 'bi-calendar-check',
                    'label' => $todayAwaiting . ' نوبت امروز در انتظار',
                    'url' => BASE_URL . '/appointments?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d'),
                    'color' => 'success',
                ];
            }
        }

        if ($role === 'operator') {
            $todayAppts = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE operator_id = ? 
                   AND appointment_date = CURDATE()
                   AND status IN ('confirmed', 'awaiting_service', 'paid')
                   AND deleted_at IS NULL",
                [$userId]
            )['c'] ?? 0;
            if ($todayAppts > 0) {
                $notifs[] = [
                    'icon' => 'bi-calendar-check',
                    'label' => $todayAppts . ' نوبت امروز',
                    'url' => BASE_URL . '/operator/today',
                    'color' => 'success',
                ];
            }

            $toClose = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE operator_id = ? 
                   AND status = 'paid'
                   AND deleted_at IS NULL",
                [$userId]
            )['c'] ?? 0;
            if ($toClose > 0) {
                $notifs[] = [
                    'icon' => 'bi-check2-circle',
                    'label' => $toClose . ' نوبت آماده بستن',
                    'url' => BASE_URL . '/operator/today',
                    'color' => 'warning',
                ];
            }
        }

        if (in_array($role, ['super_admin', 'admin', 'accountant'])) {
            $openRegister = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM cash_register 
                 WHERE register_date = CURDATE() AND is_closed = 0"
            )['c'] ?? 0;
            if ($openRegister > 0) {
                $notifs[] = [
                    'icon' => 'bi-safe',
                    'label' => 'صندوق امروز بسته نشده',
                    'url' => BASE_URL . '/accounting/cash-register',
                    'color' => 'warning',
                ];
            }

            $unpaidCommissions = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM appointments 
                 WHERE status = 'done' AND commission_paid = 0 AND deleted_at IS NULL"
            )['c'] ?? 0;
            if ($unpaidCommissions > 0) {
                $notifs[] = [
                    'icon' => 'bi-cash-stack',
                    'label' => $unpaidCommissions . ' دستمزد پرداخت‌نشده',
                    'url' => BASE_URL . '/accounting/commissions',
                    'color' => 'info',
                ];
            }
        }

        if ($role === 'content_manager') {
            $pendingComments = $model->fetchOne(
                "SELECT COUNT(*) AS c FROM comments 
                 WHERE status = 'pending' AND deleted_at IS NULL"
            )['c'] ?? 0;
            if ($pendingComments > 0) {
                $notifs[] = [
                    'icon' => 'bi-chat-dots',
                    'label' => $pendingComments . ' نظر در انتظار تایید',
                    'url' => BASE_URL . '/content/comments',
                    'color' => 'warning',
                ];
            }
        }

        return $notifs;
    }
}