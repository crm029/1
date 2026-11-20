<?php
class Salary extends Model {
    public function all($filters = []) {
        $sql = "SELECT s.*, u.full_name, u.role, u.is_doctor,
                       p.full_name AS paid_by_name
                FROM salaries s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN users p ON p.id = s.paid_by
                WHERE s.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND s.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND s.period_start >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND s.period_end <= ?";
            $params[] = $filters['date_to'];
        }
        if ($filters['paid'] !== '') {
            $sql .= " AND s.is_paid = ?";
            $params[] = (int)$filters['paid'];
        }
        $sql .= " ORDER BY s.period_end DESC, s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne(
            "SELECT s.*, u.full_name, u.role FROM salaries s 
             JOIN users u ON u.id = s.user_id 
             WHERE s.id = ? AND s.deleted_at IS NULL",
            [$id]
        );
    }

    public function create($data) {
        return $this->insert('salaries', $data);
    }

    public function edit($id, $data) {
        $this->update('salaries', $data, 'id = ?', [$id]);
    }

    public function staff() {
        return $this->fetchAll(
            "SELECT id, full_name, role, is_doctor FROM users 
             WHERE role IN ('secretary','accountant','operator') 
               AND is_active = 1 AND deleted_at IS NULL 
             ORDER BY role, full_name"
        );
    }
}