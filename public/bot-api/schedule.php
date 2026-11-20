<?php
// ============================================
// تاریخ‌ها و ساعت‌های قابل رزرو
// ============================================

function handleSchedule($db, $action, $input) {
    switch ($action) {

        // ============ تاریخ‌های قابل رزرو ۳۰ روز ============
        case 'get_available_dates':
            $service_id = (int)($input['service_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);

            if (!$service_id || !$operator_id) {
                jsonError('اطلاعات ناقص');
            }

            // اطلاعات خدمت
            $stmt = $db->prepare("SELECT duration_minutes 
                                  FROM services 
                                  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) {
                jsonError('خدمت یافت نشد');
            }

            $days = (int)($input['days'] ?? 30);
            if ($days < 1 || $days > 60) $days = 30;

            $today = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+{$days} days"));

            $dates = [];
            $cur = $today;
            while ($cur <= $endDate) {

                // چک تعطیلی
                $h = $db->prepare("SELECT id FROM holidays 
                                   WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
                $h->execute([$cur]);
                $isHoliday = (bool)$h->fetch();

                // چک برنامه حضور اپراتور
                $s = $db->prepare("SELECT status, start_time, end_time 
                                   FROM operator_schedules 
                                   WHERE operator_id = ? AND work_date = ? LIMIT 1");
                $s->execute([$operator_id, $cur]);
                $sched = $s->fetch();

                if ($isHoliday) {
                    $status = 'red';  // تعطیلی رسمی → قرمز
                } elseif ($sched && $sched['status'] === 'present') {
                    $hasFree = checkHasFreeSlot($db, $service, $operator_id, $cur, $sched);
                    $status = $hasFree ? 'green' : 'red';
                } else {
                    $status = 'gray';  // اپراتور حضور نداره
                }

                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($cur)),
                    (int)date('n', strtotime($cur)),
                    (int)date('j', strtotime($cur))
                );

                $dates[] = [
                    'date' => $cur,
                    'status' => $status,
                    'jalali_day' => $jd,
                    'jalali_month' => $jm,
                    'jalali_year' => $jy,
                    'weekday' => (int)date('w', strtotime($cur)),
                ];

                $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
            }

            jsonOut(['status' => 'success', 'dates' => $dates]);
            break;

        // ============ ساعت‌های خالی ============
        case 'get_available_slots':
            $service_id = (int)($input['service_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);
            $date = trim($input['date'] ?? '');

            if (!$service_id || !$operator_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                jsonError('اطلاعات ناقص');
            }

            // اطلاعات خدمت
            $stmt = $db->prepare("SELECT duration_minutes 
                                  FROM services 
                                  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) {
                jsonError('خدمت یافت نشد');
            }

            // چک تعطیلی
            $h = $db->prepare("SELECT id FROM holidays 
                               WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
            $h->execute([$date]);
            if ($h->fetch()) {
                jsonOut(['status' => 'success', 'slots' => [], 'message' => 'این روز تعطیل رسمی است']);
            }

            // حضور اپراتور
            $s = $db->prepare("SELECT status, start_time, end_time 
                               FROM operator_schedules 
                               WHERE operator_id = ? AND work_date = ? LIMIT 1");
            $s->execute([$operator_id, $date]);
            $sched = $s->fetch();

            if (!$sched || $sched['status'] !== 'present') {
                jsonOut(['status' => 'success', 'slots' => []]);
            }

            // تنظیمات کاری
            $settings = getSettings($db);
            $workStart = $sched['start_time'] ?: ($settings['work_start_time'] ?? '09:00');
            $workEnd = $sched['end_time'] ?: ($settings['work_end_time'] ?? '21:00');

            // تولید اسلات‌ها
            $duration = (int)$service['duration_minutes'];
            $slots = [];
            $t = strtotime($date . ' ' . $workStart);
            $endTs = strtotime($date . ' ' . $workEnd);
            while ($t + ($duration * 60) <= $endTs) {
                $slots[] = [
                    'start' => date('H:i', $t),
                    'end' => date('H:i', $t + ($duration * 60)),
                ];
                $t += $duration * 60;
            }

            // نوبت‌های گرفته‌شده
            $b = $db->prepare("SELECT start_time, end_time 
                               FROM appointments 
                               WHERE operator_id = ? AND appointment_date = ? 
                                 AND deleted_at IS NULL 
                                 AND status NOT IN ('cancelled', 'no_show')");
            $b->execute([$operator_id, $date]);
            $booked = $b->fetchAll();

            $result = [];
            foreach ($slots as $slot) {
                $conflict = false;
                foreach ($booked as $bk) {
                    if ($slot['start'] < $bk['end_time'] && $slot['end'] > $bk['start_time']) {
                        $conflict = true;
                        break;
                    }
                }
                $result[] = [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'available' => !$conflict,
                ];
            }

            jsonOut(['status' => 'success', 'slots' => $result]);
            break;
    }
}

// ============================================
// چک می‌کنه اپراتور در این روز حداقل یه اسلات خالی داشته باشه
// ============================================
function checkHasFreeSlot($db, $service, $operator_id, $date, $sched) {
    $settings = getSettings($db);
    $workStart = $sched['start_time'] ?: ($settings['work_start_time'] ?? '09:00');
    $workEnd = $sched['end_time'] ?: ($settings['work_end_time'] ?? '21:00');

    $duration = (int)$service['duration_minutes'];
    $t = strtotime($date . ' ' . $workStart);
    $endTs = strtotime($date . ' ' . $workEnd);

    $b = $db->prepare("SELECT start_time, end_time 
                       FROM appointments 
                       WHERE operator_id = ? AND appointment_date = ? 
                         AND deleted_at IS NULL 
                         AND status NOT IN ('cancelled', 'no_show')");
    $b->execute([$operator_id, $date]);
    $booked = $b->fetchAll();

    while ($t + ($duration * 60) <= $endTs) {
        $s = date('H:i', $t);
        $e = date('H:i', $t + ($duration * 60));
        $conflict = false;
        foreach ($booked as $bk) {
            if ($s < $bk['end_time'] && $e > $bk['start_time']) {
                $conflict = true;
                break;
            }
        }
        if (!$conflict) return true;
        $t += $duration * 60;
    }
    return false;
}