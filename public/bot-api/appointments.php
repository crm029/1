<?php
// ============================================
// نوبت‌ها (لیست، جزئیات، ثبت، لغو، موکول)
// ============================================

function handleAppointments($db, $action, $input) {
    switch ($action) {

        // ============ نوبت‌های باز کاربر ============
        case 'get_my_appointments':
            $client_id = (int)($input['client_id'] ?? 0);
            if (!$client_id) jsonError('کاربر نامعتبر');

            $stmt = $db->prepare("SELECT a.id, a.booking_code, a.appointment_date, a.start_time, a.end_time, 
                                         a.status, a.service_price, a.deposit_amount, a.remaining_amount,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.client_id = ? AND a.deleted_at IS NULL
                                    AND a.status NOT IN ('done', 'cancelled', 'no_show')
                                  ORDER BY a.appointment_date ASC, a.start_time ASC");
            $stmt->execute([$client_id]);
            $appointments = $stmt->fetchAll();

            // تبدیل تاریخ به شمسی
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
        case 'get_appointment_detail':
            $appointment_id = (int)($input['appointment_id'] ?? 0);
            $client_id = (int)($input['client_id'] ?? 0);

            if (!$appointment_id || !$client_id) jsonError('اطلاعات ناقص');

            $stmt = $db->prepare("SELECT a.*, s.name AS service_name, s.duration_minutes,
                                         u.full_name AS operator_name
                                  FROM appointments a
                                  JOIN services s ON s.id = a.service_id
                                  LEFT JOIN users u ON u.id = a.operator_id
                                  WHERE a.id = ? AND a.client_id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appointment_id, $client_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');

            // محاسبه ساعت مانده
            $apptTs = strtotime($appt['appointment_date'] . ' ' . $appt['start_time']);
            $hoursLeft = ($apptTs - time()) / 3600;

            // تاریخ شمسی
            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $appt['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            $appt['status_label'] = translateStatus($appt['status']);
            $appt['hours_left'] = round($hoursLeft, 1);
            $appt['can_reschedule'] = $hoursLeft >= 48;
            $appt['can_cancel'] = in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid']);

            jsonOut(['status' => 'success', 'appointment' => $appt]);
            break;

        // ============ ثبت نوبت ============
        case 'submit_appointment':
            $client_id = (int)($input['client_id'] ?? 0);
            $service_id = (int)($input['service_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);
            $appointment_date = trim($input['appointment_date'] ?? '');
            $start_time = trim($input['start_time'] ?? '');
            
            $receipt_path = trim($input['receipt_path'] ?? '');
            $discount_amount = (int)($input['discount_amount'] ?? 0);
            $discount_code_id = (int)($input['discount_code_id'] ?? 0);

            if (!$client_id || !$service_id || !$operator_id || !$appointment_date || !$start_time) {
                jsonError('اطلاعات ناقص');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date) 
                || !preg_match('/^\d{2}:\d{2}$/', $start_time)) {
                jsonError('فرمت تاریخ/ساعت نامعتبر');
            }

            // چک ۳۰ روز
            $diff = (strtotime($appointment_date) - strtotime(date('Y-m-d'))) / 86400;
            if ($diff < 0 || $diff > 30) {
                jsonError('تاریخ باید بین امروز و ۳۰ روز آینده باشد');
            }

            // اطلاعات خدمت
            $stmt = $db->prepare("SELECT id, name, price, duration_minutes 
                                  FROM services 
                                  WHERE id = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) jsonError('خدمت یافت نشد');

            // چک محدودیت ۳۰ روزه
            $chk = $db->prepare("SELECT id FROM appointments 
                                 WHERE client_id = ? AND service_id = ? 
                                   AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                   AND deleted_at IS NULL
                                   AND status NOT IN ('cancelled', 'no_show')
                                 LIMIT 1");
            $chk->execute([$client_id, $service_id]);
            if ($chk->fetch()) {
                jsonError('شما در ۳۰ روز گذشته برای این خدمت نوبت فعال دارید.');
            }

            // چک خالی بودن اسلات
            $end_time = date('H:i', strtotime($start_time) + ($service['duration_minutes'] * 60));

            $conflict = $db->prepare("SELECT id FROM appointments 
                                      WHERE operator_id = ? AND appointment_date = ?
                                        AND deleted_at IS NULL 
                                        AND status NOT IN ('cancelled', 'no_show')
                                        AND (start_time < ? AND end_time > ?)
                                      LIMIT 1");
            $conflict->execute([$operator_id, $appointment_date, $end_time, $start_time]);
            if ($conflict->fetch()) {
                jsonError('این ساعت قبلاً رزرو شده است. ساعت دیگری انتخاب کنید.');
            }

            // بیعانه
            $settings = getSettings($db);
            $deposit = (int)($settings['deposit_amount'] ?? 200000);

            // اگه تخفیف بیشتر از قیمت خدمت باشه، کپ کن
            if ($discount_amount > (int)$service['price']) {
                $discount_amount = (int)$service['price'];
            }

            $remaining = max(0, (int)$service['price'] - $deposit - $discount_amount);

            // تولید کد نوبت یکتا
            do {
                $booking_code = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $chk2 = $db->prepare("SELECT id FROM appointments WHERE booking_code = ? LIMIT 1");
                $chk2->execute([$booking_code]);
            } while ($chk2->fetch());

            // ثبت
            $stmt = $db->prepare("INSERT INTO appointments 
                (booking_code, client_id, service_id, operator_id, appointment_date, 
                 start_time, end_time, status, deposit_amount, service_price, 
                 discount_amount, remaining_amount, receipt_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_receipt', ?, ?, ?, ?, ?)");
            $stmt->execute([
                 $booking_code, $client_id, $service_id, $operator_id,
                 $appointment_date, $start_time, $end_time,
                 $deposit, (int)$service['price'], 
                 $discount_amount, $remaining,
                 $receipt_path ?: null
            ]);

$appt_id = (int)$db->lastInsertId();

// افزایش شمارنده کد تخفیف
if ($discount_code_id > 0) {
    $db->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE id = ?")
       ->execute([$discount_code_id]);
}

// ============ اطلاع‌رسانی به کلاینت ============
// گرفتن موبایل و نام کلاینت
$client_stmt = $db->prepare("SELECT full_name, mobile FROM clients WHERE id = ? LIMIT 1");
$client_stmt->execute([$client_id]);
$client = $client_stmt->fetch();

if ($client) {
    // تاریخ شمسی
    [$jy, $jm, $jd] = gregorianToJalali(
        (int)date('Y', strtotime($appointment_date)),
        (int)date('n', strtotime($appointment_date)),
        (int)date('j', strtotime($appointment_date))
    );
    $jalali = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

    sendNotification($db, 'booking_created', $client['mobile'], [
        'client_name' => $client['full_name'],
        'booking_code' => $booking_code,
        'service_name' => $service['name'],
        'appointment_date' => $jalali,
        'start_time' => $start_time,
    ]);
}

jsonOut([
    'status' => 'success',
    'booking_code' => $booking_code,
    'appointment_id' => $appt_id,
    'message' => 'نوبت ثبت شد',
]);

        // ============ درخواست لغو ============
        case 'request_cancel':
            $appointment_id = (int)($input['appointment_id'] ?? 0);
            $client_id = (int)($input['client_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            $card_number = trim($input['card_number'] ?? '');
            $card_holder = trim($input['card_holder'] ?? '');

            if (!$appointment_id || !$client_id) jsonError('اطلاعات ناقص');
            if (mb_strlen($reason) < 3) jsonError('دلیل لغو را وارد کنید');
            if (!preg_match('/^\d{16}$/', $card_number)) jsonError('شماره کارت باید ۱۶ رقم باشد');
            if (mb_strlen($card_holder) < 3) jsonError('نام صاحب کارت را وارد کنید');

            // چک نوبت
            $stmt = $db->prepare("SELECT id, status, appointment_date, start_time 
                                  FROM appointments 
                                  WHERE id = ? AND client_id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appointment_id, $client_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');
            if (!in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid'])) {
                jsonError('این نوبت قابل لغو نیست');
            }

            // ثبت درخواست
            $upd = $db->prepare("UPDATE appointments 
                                 SET status = 'cancel_requested',
                                     cancel_reason = ?,
                                     cancel_card_number = ?,
                                     cancel_card_holder = ?,
                                     cancel_requested_at = NOW()
                                 WHERE id = ?");
            $upd->execute([$reason, $card_number, $card_holder, $appointment_id]);

            jsonOut(['status' => 'success', 'message' => 'درخواست لغو ثبت شد و در انتظار بررسی منشی است.']);
            break;

        // ============ درخواست موکول ============
case 'request_reschedule':
    $appointment_id = (int)($input['appointment_id'] ?? 0);
    $client_id = (int)($input['client_id'] ?? 0);
    $new_date = trim($input['new_date'] ?? '');
    $new_time = trim($input['new_time'] ?? '');

    if (!$appointment_id || !$client_id || !$new_date || !$new_time) {
        jsonError('اطلاعات ناقص');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date) 
        || !preg_match('/^\d{2}:\d{2}$/', $new_time)) {
        jsonError('فرمت تاریخ/ساعت نامعتبر');
    }

    // چک نوبت + اطلاعات کلاینت
    $stmt = $db->prepare("SELECT a.*, s.duration_minutes,
                                 c.full_name, c.mobile
                          FROM appointments a
                          JOIN services s ON s.id = a.service_id
                          JOIN clients c ON c.id = a.client_id
                          WHERE a.id = ? AND a.client_id = ? AND a.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$appointment_id, $client_id]);
    $appt = $stmt->fetch();

    if (!$appt) jsonError('نوبت یافت نشد');
    if (!in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid'])) {
        jsonError('این نوبت قابل موکول نیست');
    }

    // چک ۴۸ ساعت
    $apptTs = strtotime($appt['appointment_date'] . ' ' . $appt['start_time']);
    $hoursLeft = ($apptTs - time()) / 3600;
    if ($hoursLeft < 48) {
        jsonError('کمتر از ۴۸ ساعت مانده به نوبت. امکان موکول در دسترس شما نیست. با منشی تماس بگیرید.');
    }

    // چک خالی بودن اسلات جدید
    $new_end = date('H:i', strtotime($new_time) + ($appt['duration_minutes'] * 60));

    $conflict = $db->prepare("SELECT id FROM appointments 
                              WHERE operator_id = ? AND appointment_date = ?
                                AND id != ?
                                AND deleted_at IS NULL 
                                AND status NOT IN ('cancelled', 'no_show')
                                AND (start_time < ? AND end_time > ?)
                              LIMIT 1");
    $conflict->execute([
        $appt['operator_id'], $new_date, $appointment_id, $new_end, $new_time
    ]);
    if ($conflict->fetch()) {
        jsonError('این ساعت قبلاً رزرو شده است. ساعت دیگری انتخاب کنید.');
    }

    // ذخیره تاریخ قدیمی برای اطلاع‌رسانی
    $old_date = $appt['appointment_date'];
    $old_time = $appt['start_time'];

    // ثبت موکول
    $upd = $db->prepare("UPDATE appointments 
                         SET appointment_date = ?,
                             start_time = ?,
                             end_time = ?,
                             rescheduled_at = NOW()
                         WHERE id = ?");
    $upd->execute([$new_date, $new_time, $new_end, $appointment_id]);

    // ============ اطلاع‌رسانی به کلاینت ============
    [$ojy, $ojm, $ojd] = gregorianToJalali(
        (int)date('Y', strtotime($old_date)),
        (int)date('n', strtotime($old_date)),
        (int)date('j', strtotime($old_date))
    );
    $old_jalali = sprintf('%04d/%02d/%02d', $ojy, $ojm, $ojd);

    [$njy, $njm, $njd] = gregorianToJalali(
        (int)date('Y', strtotime($new_date)),
        (int)date('n', strtotime($new_date)),
        (int)date('j', strtotime($new_date))
    );
    $new_jalali = sprintf('%04d/%02d/%02d', $njy, $njm, $njd);

    sendNotification($db, 'rescheduled', $appt['mobile'], [
        'client_name' => $appt['full_name'],
        'booking_code' => $appt['booking_code'],
        'old_date' => $old_jalali . ' — ' . substr($old_time, 0, 5),
        'new_date' => $new_jalali,
        'new_time' => $new_time,
    ]);

    jsonOut(['status' => 'success', 'message' => 'نوبت با موفقیت موکول شد.']);
    break;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date) 
                || !preg_match('/^\d{2}:\d{2}$/', $new_time)) {
                jsonError('فرمت تاریخ/ساعت نامعتبر');
            }

            // چک نوبت
            $stmt = $db->prepare("SELECT a.*, s.duration_minutes 
                                  FROM appointments a
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.client_id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appointment_id, $client_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');
            if (!in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid'])) {
                jsonError('این نوبت قابل موکول نیست');
            }

            // چک ۴۸ ساعت
            $apptTs = strtotime($appt['appointment_date'] . ' ' . $appt['start_time']);
            $hoursLeft = ($apptTs - time()) / 3600;
            if ($hoursLeft < 48) {
                jsonError('کمتر از ۴۸ ساعت مانده به نوبت. امکان موکول در دسترس شما نیست. با منشی تماس بگیرید.');
            }

            // چک خالی بودن اسلات جدید
            $new_end = date('H:i', strtotime($new_time) + ($appt['duration_minutes'] * 60));

            $conflict = $db->prepare("SELECT id FROM appointments 
                                      WHERE operator_id = ? AND appointment_date = ?
                                        AND id != ?
                                        AND deleted_at IS NULL 
                                        AND status NOT IN ('cancelled', 'no_show')
                                        AND (start_time < ? AND end_time > ?)
                                      LIMIT 1");
            $conflict->execute([
                $appt['operator_id'], $new_date, $appointment_id, $new_end, $new_time
            ]);
            if ($conflict->fetch()) {
                jsonError('این ساعت قبلاً رزرو شده است. ساعت دیگری انتخاب کنید.');
            }

            // ثبت موکول
            $upd = $db->prepare("UPDATE appointments 
                                 SET appointment_date = ?,
                                     start_time = ?,
                                     end_time = ?,
                                     rescheduled_at = NOW()
                                 WHERE id = ?");
            $upd->execute([$new_date, $new_time, $new_end, $appointment_id]);

            jsonOut(['status' => 'success', 'message' => 'نوبت با موفقیت موکول شد.']);
            break;
    }
}

// ============================================
// ترجمه وضعیت نوبت
// ============================================
function translateStatus($status) {
    $map = [
        'pending_receipt'  => 'در انتظار تایید رسید',
        'confirmed'        => 'قطعی',
        'suspended'        => 'تعلیق',
        'awaiting_service' => 'در انتظار اجرا',
        'paid'             => 'تسویه شده',
        'done'             => 'انجام شده',
        'cancelled'        => 'لغو شده',
        'no_show'          => 'غیبت',
        'cancel_requested' => 'درخواست لغو',
    ];
    return $map[$status] ?? $status;
}