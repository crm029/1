<?php
class Transaction extends Model {
    public function all($filters = []) {
        $sql = "SELECT t.*, u.full_name AS creator_name, c.full_name AS client_name, a.booking_code
                FROM transactions t
                LEFT JOIN users u ON u.id = t.created_by
                LEFT JOIN clients c ON c.id = t.client_id
                LEFT JOIN appointments a ON a.id = t.appointment_id
                WHERE t.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (t.description LIKE ? OR c.full_name LIKE ? OR a.booking_code LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        $sql .= " ORDER BY t.created_at DESC";
        return $this->fetchAll($sql, $params);
    }

    public function create($data) {
        return $this->insert('transactions', $data);
    }

    public function sumByTypeAndDate($type, $from, $to, $payment_method = null) {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM transactions 
                WHERE type = ? AND DATE(created_at) BETWEEN ? AND ? AND deleted_at IS NULL";
        $params = [$type, $from, $to];
        if ($payment_method) {
            $sql .= " AND payment_method = ?";
            $params[] = $payment_method;
        }
        $row = $this->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }
}