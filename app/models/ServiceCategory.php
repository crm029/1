<?php
class ServiceCategory extends Model {
    
    public function all($filters = []) {
        $sql = "SELECT * FROM service_categories WHERE deleted_at IS NULL";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
        }
        
        $sql .= " ORDER BY sort_order ASC, id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne(
            "SELECT * FROM service_categories WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    public function create($data) {
        return $this->insert('service_categories', $data);
    }

    public function edit($id, $data) {
        $this->update('service_categories', $data, 'id = ?', [$id]);
    }

    public function toggleActive($id) {
        $this->query(
            "UPDATE service_categories SET is_active = 1 - is_active WHERE id = ?",
            [$id]
        );
    }

    // تعداد خدمات فعال این دسته (برای جلوگیری از حذف)
    public function countServices($category_id) {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM services 
             WHERE category_id = ? AND deleted_at IS NULL",
            [$category_id]
        );
        return (int)($row['total'] ?? 0);
    }

    // لیست دسته‌های فعال برای dropdown
    public function activeList() {
        return $this->fetchAll(
            "SELECT id, name, color, icon FROM service_categories 
             WHERE is_active = 1 AND deleted_at IS NULL 
             ORDER BY sort_order ASC, name ASC"
        );
    }

    // چک کردن تکراری بودن نام
    public function nameExists($name, $except_id = null) {
        $sql = "SELECT id FROM service_categories 
                WHERE name = ? AND deleted_at IS NULL";
        $params = [$name];
        if ($except_id) {
            $sql .= " AND id != ?";
            $params[] = $except_id;
        }
        return (bool)$this->fetchOne($sql, $params);
    }

    // چک کردن استفاده شدن sort_order (اختیاری)
    public function maxSortOrder() {
        $row = $this->fetchOne(
            "SELECT MAX(sort_order) AS max_sort FROM service_categories WHERE deleted_at IS NULL"
        );
        return (int)($row['max_sort'] ?? 0);
    }
}