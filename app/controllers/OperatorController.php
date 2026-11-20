<?php
require_once __DIR__ . '/../models/Appointment.php';

class OperatorController extends Controller {
    // نوبت‌های امروز اپراتور
    public function today() {
        Auth::requireRole(['operator']);
        $model = new Appointment();
        $today = date('Y-m-d');

        $appointments = $model->fetchAll(
            "SELECT a.*, c.full_name AS client_name, c.mobile AS client_mobile, c.file_number, c.gender AS client_gender,
                    s.name AS service_name, s.duration_minutes
             FROM appointments a
             JOIN clients c ON c.id = a.client_id
             JOIN services s ON s.id = a.service_id
             WHERE a.operator_id = ? 
               AND a.appointment_date = ?
               AND a.deleted_at IS NULL
               AND a.status IN ('confirmed','awaiting_service','paid')
             ORDER BY a.start_time ASC",
            [Auth::id(), $today]
        );

        $this->view('operator/today', [
            'title' => 'نوبت‌های امروز من',
            'appointments' => $appointments,
            'today' => $today,
        ]);
    }

    // جستجوی کلاینت با شماره پرونده
    public function searchClient() {
        Auth::requireRole(['operator']);
        $file_number = Helpers::clean($_GET['file_number'] ?? '');
        if (!preg_match('/^\d{6}$/', $file_number)) {
            return $this->json(['error' => 'شماره پرونده ۶ رقمی وارد کنید']);
        }
        $model = new Appointment();
        $client = $model->fetchOne(
            "SELECT * FROM clients WHERE file_number = ? AND deleted_at IS NULL",
            [$file_number]
        );
        if (!$client) return $this->json(['error' => 'کلاینتی با این پرونده یافت نشد']);

        // تاریخچه فقط خدماتی که این اپراتور ارائه داده
        $history = $model->fetchAll(
            "SELECT a.booking_code, a.appointment_date, a.start_time, a.status, a.operator_note,
                    s.name AS service_name
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.client_id = ? 
               AND a.operator_id = ?
               AND a.deleted_at IS NULL
             ORDER BY a.appointment_date DESC, a.start_time DESC
             LIMIT 50",
            [$client['id'], Auth::id()]
        );

        return $this->json([
            'client' => [
                'id' => $client['id'],
                'file_number' => $client['file_number'],
                'full_name' => $client['full_name'],
                'mobile' => $client['mobile'],
                'gender' => $client['gender'],
            ],
            'history' => $history,
        ]);
    }

    // بستن نوبت (ثبت توضیحات + انجام شد)
    public function closeAppointment() {
        Auth::requireRole(['operator']);
        Helpers::verifyCsrf();

        $id = (int)($_POST['appointment_id'] ?? 0);
        $note = Helpers::clean($_POST['operator_note'] ?? '');

        $model = new Appointment();
        $appt = $model->findById($id);

        if (!$appt || $appt['operator_id'] != Auth::id()) {
            return $this->json(['error' => 'نوبت یافت نشد یا دسترسی ندارید']);
        }
        if (!in_array($appt['status'], ['awaiting_service', 'paid', 'confirmed'])) {
            return $this->json(['error' => 'این نوبت قابل بستن نیست']);
        }
        if (mb_strlen($note) < 3) {
            return $this->json(['error' => 'توضیحات خدمات اجباری است']);
        }

        $commission = $model->calculateOperatorCommission($id);
        $model->edit($id, [
            'status' => 'done',
            'operator_note' => $note,
            'done_at' => date('Y-m-d H:i:s'),
            'operator_commission' => $commission,
        ]);

        return $this->json(['success' => true]);
    }
}