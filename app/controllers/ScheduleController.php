<?php
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Service.php';

class ScheduleController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator']);
        
        [$jy, $jm, $jd] = Helpers::toJalaliArray(date('Y-m-d'));
        $year = (int)($_GET['jy'] ?? $jy);
        $month = (int)($_GET['jm'] ?? $jm);

        $model = new Schedule();
        $serviceModel = new Service();
        $operators = $serviceModel->getOperators();

        $operator_id = (int)($_GET['operator_id'] ?? 0);
        $is_self = false;

        if (Auth::role() === 'operator') {
            $operator_id = Auth::id();
            $is_self = true;
        } elseif (!$operator_id && !empty($operators)) {
            $operator_id = $operators[0]['id'];
        }

        // ============ گرفتن تعطیلات این ماه ============
        $daysInMonth = Helpers::daysInJalaliMonth($year, $month);
        $startGreg = Helpers::jalaliToGregorianString($year, $month, 1);
        $endGreg = Helpers::jalaliToGregorianString($year, $month, $daysInMonth);

        $holidayModel = new Model();
        $holidays_raw = $holidayModel->fetchAll(
            "SELECT holiday_date, title FROM holidays 
             WHERE holiday_date BETWEEN ? AND ? 
               AND is_active = 1 AND deleted_at IS NULL",
            [$startGreg, $endGreg]
        );
        $holiday_map = [];
        foreach ($holidays_raw as $h) {
            $holiday_map[$h['holiday_date']] = $h['title'];
        }
        // ================================================

        // ساخت روزها
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $greg = Helpers::jalaliToGregorianString($year, $month, $d);
            $days[] = [
                'gregorian' => $greg,
                'jalali_day' => $d,
                'schedule' => $model->findByDate($operator_id, $greg) ?: [],
                'is_holiday' => isset($holiday_map[$greg]),
                'holiday_title' => $holiday_map[$greg] ?? null,
            ];
        }

        $startOffset = Helpers::jalaliFirstDayOfWeek($year, $month);

        $this->view('schedules/index', [
            'title' => 'برنامه حضور',
            'year' => $year,
            'month' => $month,
            'monthName' => Helpers::jalaliMonthName($month),
            'operators' => $operators,
            'operator_id' => $operator_id,
            'is_self' => $is_self,
            'days' => $days,
            'startOffset' => $startOffset,
            'today' => date('Y-m-d'),
        ]);
    }

    public function save() {
        Auth::requireRole(['super_admin', 'admin', 'secretary', 'operator']);
        Helpers::verifyCsrf();

        $operator_id = (int)($_POST['operator_id'] ?? 0);
        $date = Helpers::clean($_POST['date'] ?? '');
        $status = $_POST['status'] ?? 'present';
        $start = Helpers::clean($_POST['start_time'] ?? '');
        $end = Helpers::clean($_POST['end_time'] ?? '');
        $note = Helpers::clean($_POST['note'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['success' => false, 'error' => 'تاریخ نامعتبر']);
        }
        if (Auth::role() === 'operator') {
            $operator_id = Auth::id();
        }

        // ============ چک تعطیلی ============
        $holiday = (new Model())->fetchOne(
            "SELECT id FROM holidays WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1",
            [$date]
        );
        if ($holiday) {
            return $this->json(['success' => false, 'error' => 'این روز تعطیل رسمی است']);
        }
        // ====================================

        $model = new Schedule();

        if ($status === 'clear') {
            $model->query("DELETE FROM operator_schedules WHERE operator_id = ? AND work_date = ?", [$operator_id, $date]);
            return $this->json(['success' => true]);
        }

        if ($status === 'absent') {
            $existing = $model->findByDate($operator_id, $date);
            $data = ['operator_id' => $operator_id, 'work_date' => $date, 'status' => 'absent', 'start_time' => null, 'end_time' => null, 'note' => $note];
            if ($existing) {
                $model->update('operator_schedules', $data, 'id = ?', [$existing['id']]);
            } else {
                $model->insert('operator_schedules', $data);
            }
            return $this->json(['success' => true]);
        }

        if ($status === 'present') {
            if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
                return $this->json(['success' => false, 'error' => 'ساعت نامعتبر']);
            }
            $existing = $model->findByDate($operator_id, $date);
            $data = ['operator_id' => $operator_id, 'work_date' => $date, 'status' => 'present', 'start_time' => $start, 'end_time' => $end, 'note' => $note];
            if ($existing) {
                $model->update('operator_schedules', $data, 'id = ?', [$existing['id']]);
            } else {
                $model->insert('operator_schedules', $data);
            }
            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'error' => 'وضعیت نامعتبر']);
    }
}