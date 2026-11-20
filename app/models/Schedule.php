<?php
class Schedule extends Model {
    public function getByOperator($operator_id, $from = null, $to = null) {
        $sql = "SELECT * FROM operator_schedules WHERE operator_id = ?";
        $params = [$operator_id];
        if ($from) { $sql .= " AND work_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND work_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY work_date ASC";
        return $this->fetchAll($sql, $params);
    }

    public function findByDate($operator_id, $date) {
        return $this->fetchOne(
            "SELECT * FROM operator_schedules WHERE operator_id = ? AND work_date = ?",
            [$operator_id, $date]
        );
    }

    public function save($operator_id, $date, $status, $start_time = null, $end_time = null, $note = null) {
        $existing = $this->findByDate($operator_id, $date);
        $data = [
            'status' => $status,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'note' => $note,
        ];
        if ($existing) {
            $this->update('operator_schedules', $data, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            $data['operator_id'] = $operator_id;
            $data['work_date'] = $date;
            return $this->insert('operator_schedules', $data);
        }
    }

    public function deleteByDate($operator_id, $date) {
        $this->query("DELETE FROM operator_schedules WHERE operator_id = ? AND work_date = ?", [$operator_id, $date]);
    }

    public function getOperators() {
        return $this->fetchAll(
            "SELECT id, full_name, is_doctor FROM users 
             WHERE role = 'operator' AND is_active = 1 AND deleted_at IS NULL 
             ORDER BY full_name ASC"
        );
    }
}