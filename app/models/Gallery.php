<?php
class Gallery extends Model {
    public function all($filters = []) {
        $sql = "SELECT g.*, s.name AS service_name
                FROM gallery g
                LEFT JOIN services s ON s.id = g.service_id
                WHERE g.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND g.title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND g.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }
        $sql .= " ORDER BY g.sort_order ASC, g.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function active() {
        return $this->fetchAll(
            "SELECT g.*, s.name AS service_name
             FROM gallery g
             LEFT JOIN services s ON s.id = g.service_id
             WHERE g.deleted_at IS NULL AND g.is_active = 1
             ORDER BY g.sort_order ASC, g.id DESC"
        );
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM gallery WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function create($data) {
        return $this->insert('gallery', $data);
    }

    public function edit($id, $data) {
        $this->update('gallery', $data, 'id = ?', [$id]);
    }

    public function toggleActive($id) {
        $this->query("UPDATE gallery SET is_active = 1 - is_active WHERE id = ?", [$id]);
    }

    public function softDelete($id) {
        $this->query("UPDATE gallery SET deleted_at = NOW(), is_active = 0 WHERE id = ?", [$id]);
    }
}