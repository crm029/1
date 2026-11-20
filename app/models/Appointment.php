<?php
class Appointment extends Model {
    public function all($filters = []) {
        $sql = "SELECT a.*, c.full_name AS client_name, c.mobile AS client_mobile, c.file_number,
                       s.name AS service_name, s.price AS service_price,
                       u.full_name AS operator_name
                FROM appointments a
                JOIN clients c ON c.id = a.client_id
                JOIN services s ON s.id = a.service_id
                LEFT JOIN users u ON u.id = a.operator_id
                WHERE a.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['operator_id'])) {
            $sql .= " AND a.operator_id = ?";
            $params[] = $filters['operator_id'];
        }
        if (!empty($filters['service_id'])) {
            $sql .= " AND a.service_id = ?";
            $params[] = $filters['service_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.appointment_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.appointment_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.full_name LIKE ? OR c.mobile LIKE ? OR c.file_number LIKE ? OR a.booking_code LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
        return $this->fetchAll($sql, $params);
    }

    public function findById($id) {
        return $this->fetchOne(
            "SELECT a.*, c.full_name AS client_name, c.mobile AS client_mobile, c.file_number, c.gender AS client_gender,
                    s.name AS service_name, s.duration_minutes, s.price AS service_price, s.is_doctor_service,
                    u.full_name AS operator_name, u.is_doctor
             FROM appointments a
             JOIN clients c ON c.id = a.client_id
             JOIN services s ON s.id = a.service_id
             LEFT JOIN users u ON u.id = a.operator_id
             WHERE a.id = ? AND a.deleted_at IS NULL",
            [$id]
        );
    }

    public function findByBookingCode($code) {
        return $this->fetchOne(
            "SELECT a.*, c.full_name AS client_name, c.mobile AS client_mobile, c.file_number,
                    s.name AS service_name, s.duration_minutes, u.full_name AS operator_name
             FROM appointments a
             JOIN clients c ON c.id = a.client_id
             JOIN services s ON s.id = a.service_id
             LEFT JOIN users u ON u.id = a.operator_id
             WHERE a.booking_code = ? AND a.deleted_at IS NULL",
            [$code]
        );
    }

    public function create($data) {
        return $this->insert('appointments', $data);
    }

    public function edit($id, $data) {
        $this->update('appointments', $data, 'id = ?', [$id]);
    }

    public function generateBookingCode() {
        for ($i = 0; $i < 30; $i++) {
            $code = str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $exists = $this->fetchOne("SELECT id FROM appointments WHERE booking_code = ?", [$code]);
            if (!$exists) return $code;
        }
        return str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    }

    // چک تداخل زمانی برای یه اپراتور در تاریخ مشخص
    public function hasConflict($operator_id, $date, $start_time, $end_time, $except_id = null) {
        $sql = "SELECT id FROM appointments 
                WHERE operator_id = ? 
                  AND appointment_date = ? 
                  AND deleted_at IS NULL 
                  AND status NOT IN ('cancelled', 'no_show')
                  AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))";
        $params = [$operator_id, $date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time];
        if ($except_id) {
            $sql .= " AND id != ?";
            $params[] = $except_id;
        }
        return $this->fetchOne($sql, $params) ? true : false;
    }

    // نوبت‌های یک روز یک اپراتور
    public function getByOperatorDate($operator_id, $date) {
        return $this->fetchAll(
            "SELECT start_time, end_time FROM appointments 
             WHERE operator_id = ? AND appointment_date = ? 
               AND deleted_at IS NULL 
               AND status NOT IN ('cancelled', 'no_show')",
            [$operator_id, $date]
        );
    }

    // نوبت‌های فعال یه کلاینت برای یه خدمت در ۳۰ روز گذشته
    public function hasActiveSameService($client_id, $service_id, $days = 30) {
        $from = date('Y-m-d', strtotime("-{$days} days"));
        return $this->fetchOne(
            "SELECT id FROM appointments 
             WHERE client_id = ? AND service_id = ? 
               AND appointment_date >= ? 
               AND deleted_at IS NULL 
               AND status NOT IN ('cancelled', 'no_show')",
            [$client_id, $service_id, $from]
        ) ? true : false;
    }
	
	    // محاسبه دستمزد اپراتور برای یک نوبت
    public function calculateOperatorCommission($appointment_id) {
        $appt = $this->fetchOne(
            "SELECT a.operator_id, a.service_price, s.id AS service_id 
             FROM appointments a 
             JOIN services s ON s.id = a.service_id 
             WHERE a.id = ?",
            [$appointment_id]
        );
        if (!$appt || !$appt['operator_id']) return 0;

        $rule = $this->fetchOne(
            "SELECT commission_percent, fixed_amount 
             FROM operator_services 
             WHERE operator_id = ? AND service_id = ? AND deleted_at IS NULL",
            [$appt['operator_id'], $appt['service_id']]
        );
        if (!$rule) return 0;

        // اولویت: درصد
        if ($rule['commission_percent'] !== null && $rule['commission_percent'] > 0) {
            return (int)round(($appt['service_price'] * $rule['commission_percent']) / 100);
        }
        if ($rule['fixed_amount'] !== null && $rule['fixed_amount'] > 0) {
            return (int)$rule['fixed_amount'];
        }
        return 0;
    }
	
}