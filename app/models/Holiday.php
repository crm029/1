<?php
class Holiday extends Model {
    public function all($filters = []) {
        $sql = "SELECT * FROM holidays WHERE deleted_at IS NULL";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        $sql .= " ORDER BY holiday_date DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM holidays WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function create($data) {
        return $this->insert('holidays', $data);
    }

    public function edit($id, $data) {
        $this->update('holidays', $data, 'id = ?', [$id]);
    }

    public function toggleActive($id) {
        $this->query("UPDATE holidays SET is_active = 1 - is_active WHERE id = ?", [$id]);
    }

    public function existsDate($date, $exceptId = null) {
        $sql = "SELECT id FROM holidays WHERE holiday_date = ? AND deleted_at IS NULL";
        $params = [$date];
        if ($exceptId) { $sql .= " AND id != ?"; $params[] = $exceptId; }
        return $this->fetchOne($sql, $params) ? true : false;
    }

    public function getByJalaliMonth($jy, $jm) {
        $days = Helpers::daysInJalaliMonth($jy, $jm);
        $start = Helpers::jalaliToGregorianString($jy, $jm, 1);
        $end = Helpers::jalaliToGregorianString($jy, $jm, $days);

        return $this->fetchAll(
            "SELECT * FROM holidays WHERE holiday_date BETWEEN ? AND ? AND deleted_at IS NULL",
            [$start, $end]
        );
    }
}