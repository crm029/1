<?php
class Service extends Model {
    
    public function all($filters = []) {
        $sql = "SELECT s.*, 
                       c.name AS category_name, 
                       c.color AS category_color, 
                       c.icon AS category_icon
                FROM services s
                LEFT JOIN service_categories c ON c.id = s.category_id
                WHERE s.deleted_at IS NULL";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND s.name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND s.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND s.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        
        $sql .= " ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne(
            "SELECT * FROM services WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    public function create($data) {
        return $this->insert('services', $data);
    }

    public function edit($id, $data) {
        $this->update('services', $data, 'id = ?', [$id]);
    }

    public function toggleActive($id) {
        $this->query("UPDATE services SET is_active = 1 - is_active WHERE id = ?", [$id]);
    }

    public function getOperatorCommissions($service_id) {
        return $this->fetchAll(
            "SELECT os.*, u.full_name, u.is_doctor 
             FROM operator_services os
             JOIN users u ON u.id = os.operator_id
             WHERE os.service_id = ? AND os.deleted_at IS NULL",
            [$service_id]
        );
    }

    public function saveOperatorCommission($service_id, $operator_id, $percent, $fixed) {
        $existing = $this->fetchOne(
            "SELECT id FROM operator_services WHERE service_id = ? AND operator_id = ?",
            [$service_id, $operator_id]
        );

        $data = [
            'commission_percent' => $percent !== '' && $percent !== null ? $percent : null,
            'fixed_amount' => $fixed !== '' && $fixed !== null ? $fixed : null,
        ];

        if ($existing) {
            $this->update('operator_services', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['service_id'] = $service_id;
            $data['operator_id'] = $operator_id;
            $this->insert('operator_services', $data);
        }
    }

    public function getOperators() {
        return $this->fetchAll(
            "SELECT id, full_name, is_doctor FROM users 
             WHERE role = 'operator' AND is_active = 1 AND deleted_at IS NULL 
             ORDER BY full_name ASC"
        );
    }
}