<?php
require_once __DIR__ . '/appointments.php';

// ============================================
// بخش اپراتور / دکتر
// ============================================

function handleOperator($db, $action, $input) {
    switch ($action) {

        // ============ نوبت‌های امروز ============
        case 'operator_today_appointments':
            $operator_id = (int)($input['operator_id'] ?? 0);
            if (!$operator_id) jsonError('اطلاعات ناقص');

            $stmt = $db->prepare("SELECT a.id, a.booking_code, a.appointment_date, a.start_time, 
                                         a.end_time, a.status, a.service_price, 
                                         a.deposit_amount, a.remaining_amount,
                                         c.full_name, c.mobile, c.file_number,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.operator_id = ?
                                    AND a.appointment_date = CURDATE()
                                    AND a.deleted_at IS NULL
                                    AND a.status IN ('confirmed', 'awaiting_service', 'paid')
                                  ORDER BY a.start_time ASC");
            $stmt->execute([$operator_id]);
            $appointments = $stmt->fetchAll();

            foreach ($appointments as &$a) {
                $a['status_label'] = translateStatus($a['status']);
            }
            unset($a);

            jsonOut(['status' => 'success', 'appointments' => $appointments]);
            break;

        // ============ نوبت‌های آینده ============
        case 'operator_future_appointments':
            $operator_id = (int)($input['operator_id'] ?? 0);
            if (!$operator_id) jsonError('اطلاعات ناقص');

            $stmt = $db->prepare("SELECT a.id, a.booking_code, a.appointment_date, a.start_time, 
                                         a.end_time, a.status,
                                         c.full_name,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.operator_id = ?
                                    AND a.appointment_date > CURDATE()
                                    AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                    AND a.deleted_at IS NULL
                                    AND a.status IN ('confirmed', 'awaiting_service', 'paid')
                                  ORDER BY a.appointment_date ASC, a.start_time ASC
                                  LIMIT 50");
            $stmt->execute([$operator_id]);
            $appointments = $stmt->fetchAll();

            foreach ($appointments as &$a) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($a['appointment_date'])),
                    (int)date('n', strtotime($a['appointment_date'])),
                    (int)date('j', strtotime($a['appointment_date']))
                );
                $a['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
                $a['status_label'] = translateStatus($a['status']);
            }
            unset($a);

            jsonOut(['status' => 'success', 'appointments' => $appointments]);
            break;

        // ============ جزئیات یک نوبت ============
        case 'operator_appointment_detail':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);

            if (!$appt_id || !$operator_id) jsonError('اطلاعات ناقص');

            $stmt = $db->prepare("SELECT a.*, c.full_name, c.mobile, c.file_number, c.gender,
                                         s.name AS service_name, s.duration_minutes
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.operator_id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id, $operator_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');

            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $appt['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            $appt['status_label'] = translateStatus($appt['status']);

            jsonOut(['status' => 'success', 'appointment' => $appt]);
            break;

        // ============ بستن نوبت ============
        case 'operator_close_appointment':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);
            $note = trim($input['operator_note'] ?? '');

            if (!$appt_id || !$operator_id) jsonError('اطلاعات ناقص');
            if (mb_strlen($note) < 5) jsonError('توضیحات خدمات الزامی است (حداقل ۵ حرف)');

            $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code,
                                         c.full_name, c.mobile,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.operator_id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id, $operator_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');
            if (!in_array($appt['status'], ['confirmed', 'awaiting_service', 'paid'])) {
                jsonError('این نوبت قابل بستن نیست');
            }

            $upd = $db->prepare("UPDATE appointments 
                                 SET status = 'done',
                                     operator_note = ?,
                                     done_at = NOW()
                                 WHERE id = ?");
            $upd->execute([$note, $appt_id]);

            sendNotification($db, 'appointment_done', $appt['mobile'], [
                'client_name' => $appt['full_name'],
                'booking_code' => $appt['booking_code'],
                'service_name' => $appt['service_name'],
            ]);

            jsonOut(['status' => 'success', 'message' => 'نوبت با موفقیت بسته شد']);
            break;

        // ============ جستجوی کلاینت ============
        case 'operator_find_client':
            $search = trim($input['search'] ?? '');
            if (empty($search)) jsonError('شماره پرونده یا موبایل را وارد کنید');

            $search = preg_replace('/[^0-9]/', '', $search);

            if (strlen($search) === 6) {
                $stmt = $db->prepare("SELECT id, file_number, full_name, mobile, gender 
                                      FROM clients 
                                      WHERE file_number = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$search]);
            } elseif (strlen($search) === 11) {
                $search = normalizeMobile($search);
                $stmt = $db->prepare("SELECT id, file_number, full_name, mobile, gender 
                                      FROM clients 
                                      WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$search]);
            } else {
                jsonError('شماره پرونده باید ۶ رقم یا موبایل ۱۱ رقم باشد');
            }

            $client = $stmt->fetch();
            if (!$client) jsonError('کلاینتی با این مشخصات یافت نشد');

            $stats = $db->prepare("SELECT 
                COUNT(*) AS total_sessions,
                MAX(appointment_date) AS last_visit,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done_count
                FROM appointments 
                WHERE client_id = ? AND deleted_at IS NULL");
            $stats->execute([$client['id']]);
            $stats_data = $stats->fetch();

            $history = $db->prepare("SELECT 
                s.name AS service_name,
                COUNT(*) AS session_count,
                MAX(a.appointment_date) AS last_date,
                GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ' | ') AS operators
                FROM appointments a
                JOIN services s ON s.id = a.service_id
                LEFT JOIN users u ON u.id = a.operator_id
                WHERE a.client_id = ? 
                  AND a.deleted_at IS NULL 
                  AND a.status = 'done'
                GROUP BY s.id, s.name
                ORDER BY session_count DESC");
            $history->execute([$client['id']]);
            $history_data = $history->fetchAll();

            foreach ($history_data as &$h) {
                if (!empty($h['last_date'])) {
                    [$jy, $jm, $jd] = gregorianToJalali(
                        (int)date('Y', strtotime($h['last_date'])),
                        (int)date('n', strtotime($h['last_date'])),
                        (int)date('j', strtotime($h['last_date']))
                    );
                    $h['last_date_jalali'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
                } else {
                    $h['last_date_jalali'] = null;
                }
            }
            unset($h);

            $last_visit_jalali = null;
            if (!empty($stats_data['last_visit'])) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($stats_data['last_visit'])),
                    (int)date('n', strtotime($stats_data['last_visit'])),
                    (int)date('j', strtotime($stats_data['last_visit']))
                );
                $last_visit_jalali = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            }

            $active = $db->prepare("SELECT a.id, a.booking_code, a.appointment_date, a.start_time,
                                           s.name AS service_name
                                    FROM appointments a
                                    JOIN services s ON s.id = a.service_id
                                    WHERE a.client_id = ? 
                                      AND a.deleted_at IS NULL
                                      AND a.status IN ('pending_receipt', 'confirmed', 'awaiting_service', 'paid')
                                    ORDER BY a.appointment_date ASC");
            $active->execute([$client['id']]);
            $active_appointments = $active->fetchAll();

            foreach ($active_appointments as &$ap) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($ap['appointment_date'])),
                    (int)date('n', strtotime($ap['appointment_date'])),
                    (int)date('j', strtotime($ap['appointment_date']))
                );
                $ap['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            }
            unset($ap);

            jsonOut([
                'status' => 'success',
                'client' => $client,
                'stats' => [
                    'total_sessions' => (int)$stats_data['total_sessions'],
                    'done_count' => (int)$stats_data['done_count'],
                    'last_visit_jalali' => $last_visit_jalali,
                ],
                'history' => $history_data,
                'active_appointments' => $active_appointments,
            ]);
            break;

        // ============ برنامه حضور — لیست ============
        case 'operator_my_schedules':
            $operator_id = (int)($input['operator_id'] ?? 0);
            if (!$operator_id) jsonError('اطلاعات ناقص');

            $stmt = $db->prepare("SELECT id, work_date, start_time, end_time, status, note
                                  FROM operator_schedules
                                  WHERE operator_id = ?
                                    AND work_date >= CURDATE()
                                    AND work_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                  ORDER BY work_date ASC");
            $stmt->execute([$operator_id]);
            $schedules = $stmt->fetchAll();

            // گرفتن تعطیلات ۳۰ روز آینده
            $holidays_stmt = $db->prepare("SELECT holiday_date, title FROM holidays 
                                           WHERE holiday_date >= CURDATE()
                                             AND holiday_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                             AND is_active = 1 AND deleted_at IS NULL");
            $holidays_stmt->execute();
            $holidays_data = $holidays_stmt->fetchAll();
            $holiday_map = [];
            foreach ($holidays_data as $h) {
                $holiday_map[$h['holiday_date']] = $h['title'];
            }

            foreach ($schedules as &$s) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($s['work_date'])),
                    (int)date('n', strtotime($s['work_date'])),
                    (int)date('j', strtotime($s['work_date']))
                );
                $s['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
                $s['is_holiday'] = isset($holiday_map[$s['work_date']]);
                $s['holiday_title'] = $holiday_map[$s['work_date']] ?? null;
            }
            unset($s);

            jsonOut([
                'status' => 'success',
                'schedules' => $schedules,
                'holidays' => $holidays_data,
            ]);
            break;

        // ============ ذخیره برنامه حضور ============
        case 'operator_save_schedule':
            $operator_id = (int)($input['operator_id'] ?? 0);
            $work_date = trim($input['work_date'] ?? '');
            $start_time = trim($input['start_time'] ?? '');
            $end_time = trim($input['end_time'] ?? '');
            $status = trim($input['status'] ?? 'present');

            if (!$operator_id || !$work_date) jsonError('اطلاعات ناقص');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $work_date)) jsonError('فرمت تاریخ نامعتبر');
            if (!in_array($status, ['present', 'absent'])) jsonError('وضعیت نامعتبر');

        // چک تعطیلی
        $holiday = $db->prepare("SELECT id FROM holidays 
            WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
        $holiday->execute([$work_date]);
        if ($holiday->fetch()) {
        jsonError('این روز تعطیل رسمی است و نمی‌توانید برنامه ثبت کنید');
        }

            // چک تداخل با نوبت‌های موجود (فقط اگه غایب می‌شه)
            if ($status === 'absent') {
                $chk = $db->prepare("SELECT COUNT(*) AS c FROM appointments 
                                     WHERE operator_id = ? 
                                       AND appointment_date = ?
                                       AND status IN ('confirmed', 'awaiting_service', 'paid')
                                       AND deleted_at IS NULL");
                $chk->execute([$operator_id, $work_date]);
                if ((int)$chk->fetch()['c'] > 0) {
                    jsonError('برای این روز نوبت فعال دارید. ابتدا نوبت‌ها را لغو یا موکول کنید.');
                }
            }

            // چک تکراری
            $existing = $db->prepare("SELECT id FROM operator_schedules 
                                      WHERE operator_id = ? AND work_date = ? LIMIT 1");
            $existing->execute([$operator_id, $work_date]);
            $exist = $existing->fetch();

            if ($exist) {
                if ($status === 'present') {
                    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
                        jsonError('فرمت ساعت نامعتبر');
                    }
                    $upd = $db->prepare("UPDATE operator_schedules 
                                         SET start_time = ?, end_time = ?, status = 'present', note = NULL
                                         WHERE id = ?");
                    $upd->execute([$start_time, $end_time, $exist['id']]);
                } else {
                    $upd = $db->prepare("UPDATE operator_schedules 
                                         SET status = 'absent', start_time = NULL, end_time = NULL
                                         WHERE id = ?");
                    $upd->execute([$exist['id']]);
                }
            } else {
                if ($status === 'present') {
                    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
                        jsonError('فرمت ساعت نامعتبر');
                    }
                    $ins = $db->prepare("INSERT INTO operator_schedules 
                        (operator_id, work_date, start_time, end_time, status)
                        VALUES (?, ?, ?, ?, 'present')");
                    $ins->execute([$operator_id, $work_date, $start_time, $end_time]);
                } else {
                    $ins = $db->prepare("INSERT INTO operator_schedules 
                        (operator_id, work_date, status)
                        VALUES (?, ?, 'absent')");
                    $ins->execute([$operator_id, $work_date]);
                }
            }

            jsonOut(['status' => 'success', 'message' => 'برنامه حضور ذخیره شد']);
            break;

        // ============ حذف برنامه ============
        case 'operator_delete_schedule':
            $schedule_id = (int)($input['schedule_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);
            if (!$schedule_id || !$operator_id) jsonError('اطلاعات ناقص');

            $del = $db->prepare("DELETE FROM operator_schedules 
                                 WHERE id = ? AND operator_id = ?");
            $del->execute([$schedule_id, $operator_id]);

            jsonOut(['status' => 'success', 'message' => 'برنامه حذف شد']);
            break;

    }
}