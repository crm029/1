<?php
class Invoice extends Model {
    public function all($filters = []) {
        $sql = "SELECT i.*, c.full_name AS client_name, c.mobile AS client_mobile, c.file_number,
                       u.full_name AS creator_name
                FROM invoices i
                JOIN clients c ON c.id = i.client_id
                LEFT JOIN users u ON u.id = i.created_by
                WHERE i.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (i.invoice_number LIKE ? OR c.full_name LIKE ? OR c.file_number LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        $sql .= " ORDER BY i.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne(
            "SELECT i.*, c.full_name AS client_name, c.mobile AS client_mobile, 
                    c.file_number, c.address, c.gender,
                    u.full_name AS creator_name
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.id = ? AND i.deleted_at IS NULL",
            [$id]
        );
    }

    public function getItems($invoice_id) {
        return $this->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id",
            [$invoice_id]
        );
    }

    public function create($data) {
        return $this->insert('invoices', $data);
    }

    public function addItem($data) {
        return $this->insert('invoice_items', $data);
    }

    public function generateNumber() {
        $prefix = 'INV-' . date('Ymd') . '-';
        for ($i = 0; $i < 30; $i++) {
            $num = $prefix . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT id FROM invoices WHERE invoice_number = ?", [$num]);
            if (!$exists) return $num;
        }
        return $prefix . time();
    }

    // گرفتن نوبت‌های قابل فاکتور برای یه کلاینت (پرداخت‌شده یا انجام‌شده)
    public function getClientAppointments($client_id) {
        return $this->fetchAll(
            "SELECT a.id, a.booking_code, a.appointment_date, a.service_price,
                    s.name AS service_name
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.client_id = ? 
               AND a.status IN ('paid','done')
               AND a.deleted_at IS NULL
             ORDER BY a.appointment_date DESC",
            [$client_id]
        );
    }
}