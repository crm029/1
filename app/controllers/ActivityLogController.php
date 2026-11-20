<?php
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/User.php';

class ActivityLogController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin']);
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'action' => $_GET['action'] ?? '',
            'search' => $_GET['search'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];
        $model = new ActivityLog();
        $userModel = new User();

        // اگه کاربر admin هست، لاگ‌های super_admin رو نبینه
        $exclude_super = (Auth::role() !== 'super_admin');

        // لیست کاربران برای فیلتر (بدون super_admin برای admin)
        $users_sql = "SELECT id, full_name, role FROM users WHERE deleted_at IS NULL";
        if ($exclude_super) {
            $users_sql .= " AND role != 'super_admin'";
        }
        $users_sql .= " ORDER BY full_name";
        $users = $model->fetchAll($users_sql);

        $this->view('logs/index', [
            'title' => 'لاگ فعالیت‌ها',
            'logs' => $model->all($filters, $exclude_super),
            'filters' => $filters,
            'users' => $users,
            'actions' => $model->actions($exclude_super),
            'stats' => $model->stats(7, $exclude_super),
            'actionStats' => $model->actionStats($exclude_super),
        ]);
    }
}