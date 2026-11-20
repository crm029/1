<?php
class DiscountCode extends Model {
    public function all($filters = []) {
        $sql = "SELECT d.*, s.name AS service_name, u.full_name AS creator_name
                FROM discount_codes d
                LEFT JOIN services s ON s.id = d.service_id
                LEFT JOIN users u ON u.id = d.created_by
                WHERE d.deleted_at IS NULL";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND d.code LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND d.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }
        $sql .= " ORDER BY d.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM discount_codes WHERE id = ? AND deleted_at IS NULL", [$id]);
    }

    public function findByCode($code) {
        return $this->fetchOne(
            "SELECT * FROM discount_codes WHERE code = ? AND deleted_at IS NULL",
            [$code]
        );
    }

    public function create($data) {
        return $this->insert('discount_codes', $data);
    }

    public function edit($id, $data) {
        $this->update('discount_codes', $data, 'id = ?', [$id]);
    }

    public function toggleActive($id) {
        $this->query("UPDATE discount_codes SET is_active = 1 - is_active WHERE id = ?", [$id]);
    }

    public function softDelete($id) {
        $this->query("UPDATE discount_codes SET deleted_at = NOW(), is_active = 0 WHERE id = ?", [$id]);
    }

    public function incrementUse($id) {
        $this->query("UPDATE discount_codes SET used_count = used_count + 1 WHERE id = ?", [$id]);
    }

    // اعتبارسنجی کد برای استفاده در رزرو
    public function validate($code, $service_id = null) {
        $row = $this->findByCode($code);
        if (!$row) return ['error' => 'کد تخفیف یافت نشد'];
        if (!$row['is_active']) return ['error' => 'کد تخفیف غیرفعال است'];

        $today = date('Y-m-d');
        if (!empty($row['start_date']) && $today < $row['start_date']) {
            return ['error' => 'این کد هنوز فعال نشده است'];
        }
        if (!empty($row['end_date']) && $today > $row['end_date']) {
            return ['error' => 'این کد منقضی شده است'];
        }
        if ($row['max_uses'] !== null && $row['used_count'] >= $row['max_uses']) {
            return ['error' => 'ظرفیت استفاده از این کد تکمیل شده است'];
        }
        if (!empty($row['service_id']) && $service_id && $row['service_id'] != $service_id) {
            return ['error' => 'این کد برای خدمت انتخابی معتبر نیست'];
        }

        return ['code' => $row];
    }

    // محاسبه مبلغ تخفیف
    public function calcDiscount($row, $amount) {
        if ($row['discount_type'] === 'percent') {
            return (int)round(($amount * $row['discount_value']) / 100);
        }
        return min((int)$row['discount_value'], $amount);
    }
}