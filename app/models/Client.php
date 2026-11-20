<?php
class Client extends Model {
    public function all($filters = []) {
        $sql = "SELECT * FROM clients WHERE deleted_at IS NULL";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (full_name LIKE ? OR mobile LIKE ? OR file_number LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['gender'])) {
            $sql .= " AND gender = ?";
            $params[] = $filters['gender'];
        }
        $sql .= " ORDER BY id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function findByFileNumber($file_number) {
        return $this->fetchOne("SELECT * FROM clients WHERE file_number = ? AND deleted_at IS NULL", [$file_number]);
    }

    public function findByMobile($mobile) {
        return $this->fetchOne("SELECT * FROM clients WHERE mobile = ? AND deleted_at IS NULL", [$mobile]);
    }

    public function create($data) {
        return $this->insert('clients', $data);
    }

    public function edit($id, $data) {
        $this->update('clients', $data, 'id = ?', [$id]);
    }

    public function existsMobile($mobile, $exceptId = null) {
        $sql = "SELECT id FROM clients WHERE mobile = ? AND deleted_at IS NULL";
        $params = [$mobile];
        if ($exceptId) { $sql .= " AND id != ?"; $params[] = $exceptId; }
        return $this->fetchOne($sql, $params) ? true : false;
    }

    public function generateFileNumber() {
        for ($i = 0; $i < 20; $i++) {
            $num = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT id FROM clients WHERE file_number = ?", [$num]);
            if (!$exists) return $num;
        }
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}