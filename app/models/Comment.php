<?php
class Comment extends Model {
    public function all($filters = []) {
        $sql = "SELECT c.*, u.full_name AS replied_by_name
                FROM comments c
                LEFT JOIN users u ON u.id = c.replied_by
                WHERE c.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.full_name LIKE ? OR c.message LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s;
        }
        $sql .= " ORDER BY c.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function approved() {
        return $this->fetchAll(
            "SELECT * FROM comments 
             WHERE status = 'approved' AND deleted_at IS NULL 
             ORDER BY id DESC LIMIT 20"
        );
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM comments WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function edit($id, $data) {
        $this->update('comments', $data, 'id = ?', [$id]);
    }
}