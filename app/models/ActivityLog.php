<?php
class ActivityLog extends Model {
public function log($action, $description, $table_name = null, $record_id = null, $old = null, $new = null) {
    // اگه کاربر سوپر ادمین هست، لاگ ثبت نکن
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        return;
    }

    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $this->insert('activity_logs', [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'table_name' => $table_name,
        'record_id' => $record_id,
        'old_value' => $old ? (is_array($old) ? json_encode($old, JSON_UNESCAPED_UNICODE) : $old) : null,
        'new_value' => $new ? (is_array($new) ? json_encode($new, JSON_UNESCAPED_UNICODE) : $new) : null,
        'ip_address' => $ip,
    ]);
}

    public function all($filters = [], $exclude_super_admin = false) {
        $sql = "SELECT al.*, u.full_name, u.role 
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1";
        $params = [];

        if ($exclude_super_admin) {
            $sql .= " AND (u.role IS NULL OR u.role != 'super_admin')";
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (al.description LIKE ? OR u.full_name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY al.id DESC LIMIT 500";
        return $this->fetchAll($sql, $params);
    }

    public function stats($days = 7, $exclude_super_admin = false) {
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $sql = "SELECT DATE(al.created_at) AS day, COUNT(*) AS cnt
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE DATE(al.created_at) >= ?";

        if ($exclude_super_admin) {
            $sql .= " AND (u.role IS NULL OR u.role != 'super_admin')";
        }

        $sql .= " GROUP BY DATE(al.created_at)
                  ORDER BY day ASC";

        return $this->fetchAll($sql, [$from]);
    }

    public function actionStats($exclude_super_admin = false) {
        $sql = "SELECT al.action, COUNT(*) AS cnt
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1";

        if ($exclude_super_admin) {
            $sql .= " AND (u.role IS NULL OR u.role != 'super_admin')";
        }

        $sql .= " GROUP BY al.action
                  ORDER BY cnt DESC
                  LIMIT 10";

        return $this->fetchAll($sql);
    }

    public function actions($exclude_super_admin = false) {
        if (!$exclude_super_admin) {
            return $this->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        }
        return $this->fetchAll(
            "SELECT DISTINCT al.action
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE (u.role IS NULL OR u.role != 'super_admin')
             ORDER BY al.action"
        );
    }
}