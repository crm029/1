<?php
class User extends Model {
    public function findByUsername($username) {
        return $this->fetchOne("SELECT * FROM users WHERE username = ? AND deleted_at IS NULL", [$username]);
    }

    public function findByMobile($mobile) {
        return $this->fetchOne("SELECT * FROM users WHERE mobile = ? AND deleted_at IS NULL", [$mobile]);
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function updateLastLogin($id) {
        $this->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    public function updatePassword($id, $hash) {
        $this->query("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?", [$hash, $id]);
    }

public function all($filters = [], $exclude_super_admin = false) {
    $sql = "SELECT * FROM users WHERE deleted_at IS NULL";
    $params = [];

    if ($exclude_super_admin) {
        $sql .= " AND role != 'super_admin'";
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (full_name LIKE ? OR mobile LIKE ? OR username LIKE ?)";
        $s = '%' . $filters['search'] . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }
    if (isset($filters['role']) && $filters['role'] !== '') {
        $sql .= " AND role = ?";
        $params[] = $filters['role'];
    }
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $sql .= " AND is_active = ?";
        $params[] = (int)$filters['is_active'];
    }

    $sql .= " ORDER BY id DESC";
    return $this->fetchAll($sql, $params);
}

    public function create($data) {
        return $this->insert('users', $data);
    }

    public function edit($id, $data) {
        $this->update('users', $data, 'id = ?', [$id]);
    }

    public function softDelete($id) {
        $this->query("UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = ?", [$id]);
    }

    public function toggleActive($id) {
        $this->query("UPDATE users SET is_active = 1 - is_active WHERE id = ?", [$id]);
    }

    public function existsUsername($username, $exceptId = null) {
        $sql = "SELECT id FROM users WHERE username = ? AND deleted_at IS NULL";
        $params = [$username];
        if ($exceptId) { $sql .= " AND id != ?"; $params[] = $exceptId; }
        return $this->fetchOne($sql, $params) ? true : false;
    }

    public function existsMobile($mobile, $exceptId = null) {
        $sql = "SELECT id FROM users WHERE mobile = ? AND deleted_at IS NULL";
        $params = [$mobile];
        if ($exceptId) { $sql .= " AND id != ?"; $params[] = $exceptId; }
        return $this->fetchOne($sql, $params) ? true : false;
    }
}