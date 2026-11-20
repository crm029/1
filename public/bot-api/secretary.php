<?php
require_once __DIR__ . '/appointments.php';
require_once __DIR__ . '/operator.php';

// ============================================
// اکشن‌های منشی
// ============================================

function handleSecretary($db, $action, $input) {
    switch ($action) {

        // ============ رسیدهای در انتظار ============
        case 'secretary_pending_receipts':
            $stmt = $db->query("SELECT a.id, a.booking_code, a.appointment_date, a.start_time,
                                       a.service_price, a.deposit_amount, a.remaining_amount,
                                       c.full_name, c.mobile,
                                       s.name AS service_name
                                FROM appointments a
                                JOIN clients c ON c.id = a.client_id
                                JOIN services s ON s.id = a.service_id
                                WHERE a.status = 'pending_receipt' AND a.deleted_at IS NULL
                                ORDER BY a.id DESC
                                LIMIT 30");
            $appointments = $stmt->fetchAll();

            foreach ($appointments as &$a) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($a['appointment_date'])),
                    (int)date('n', strtotime($a['appointment_date'])),
                    (int)date('j', strtotime($a['appointment_date']))
                );
                $a['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            }
            unset($a);

            jsonOut(['status' => 'success', 'appointments' => $appointments]);
            break;

        // ============ جزئیات یک رسید ============
        case 'secretary_receipt_detail':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

            $stmt = $db->prepare("SELECT a.*, c.full_name, c.mobile,
                                         s.name AS service_name,
                                         u.full_name AS operator_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  LEFT JOIN users u ON u.id = a.operator_id
                                  WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');

            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $appt['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

            // آدرس کامل رسید
            if (!empty($appt['receipt_image'])) {
                $appt['receipt_url'] = 'https://nayrica.ir/' . ltrim($appt['receipt_image'], '/');
            } else {
                $appt['receipt_url'] = null;
            }

            jsonOut(['status' => 'success', 'appointment' => $appt]);
            break;

        // ============ تایید رسید ============
case 'secretary_confirm_receipt':
    $appt_id = (int)($input['appointment_id'] ?? 0);
    $staff_id = (int)($input['staff_id'] ?? 0);
    if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

    // اطلاعات کامل نوبت + کلاینت + خدمت
    $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code, a.appointment_date, a.start_time,
                                 c.full_name, c.mobile,
                                 s.name AS service_name
                          FROM appointments a
                          JOIN clients c ON c.id = a.client_id
                          JOIN services s ON s.id = a.service_id
                          WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$appt_id]);
    $appt = $stmt->fetch();

    if (!$appt) jsonError('نوبت یافت نشد');
    if ($appt['status'] !== 'pending_receipt') jsonError('این نوبت در وضعیت در انتظار رسید نیست');

    $upd = $db->prepare("UPDATE appointments 
                         SET status = 'confirmed',
                             confirmed_by = ?,
                             confirmed_at = NOW()
                         WHERE id = ?");
    $upd->execute([$staff_id ?: null, $appt_id]);

    // ============ اطلاع‌رسانی به کلاینت ============
    [$jy, $jm, $jd] = gregorianToJalali(
        (int)date('Y', strtotime($appt['appointment_date'])),
        (int)date('n', strtotime($appt['appointment_date'])),
        (int)date('j', strtotime($appt['appointment_date']))
    );
    $jalali = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

    sendNotification($db, 'receipt_confirmed', $appt['mobile'], [
        'client_name' => $appt['full_name'],
        'booking_code' => $appt['booking_code'],
        'service_name' => $appt['service_name'],
        'appointment_date' => $jalali,
        'start_time' => substr($appt['start_time'], 0, 5),
    ]);

    jsonOut(['status' => 'success', 'message' => 'رسید تایید شد و نوبت قطعی گردید']);
    break;

        // ============ لغو نوبت توسط منشی ============
case 'secretary_cancel_appointment':
    $appt_id = (int)($input['appointment_id'] ?? 0);
    $staff_id = (int)($input['staff_id'] ?? 0);
    if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

    $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code,
                                 c.full_name, c.mobile
                          FROM appointments a
                          JOIN clients c ON c.id = a.client_id
                          WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$appt_id]);
    $appt = $stmt->fetch();
    if (!$appt) jsonError('نوبت یافت نشد');

    $upd = $db->prepare("UPDATE appointments 
                         SET status = 'cancelled',
                             cancel_reason = 'لغو توسط منشی از طریق ربات',
                             closed_by = ?
                         WHERE id = ?");
    $upd->execute([$staff_id ?: null, $appt_id]);

    // ============ اطلاع‌رسانی به کلاینت ============
    sendNotification($db, 'receipt_cancelled', $appt['mobile'], [
        'client_name' => $appt['full_name'],
        'booking_code' => $appt['booking_code'],
        'reason' => 'لغو توسط منشی',
    ]);

    jsonOut(['status' => 'success', 'message' => 'نوبت لغو شد']);
    break;

        // ============ درخواست‌های لغو ============
        case 'secretary_cancel_requests':
            $stmt = $db->query("SELECT a.id, a.booking_code, a.appointment_date, a.start_time,
                                       a.deposit_amount, a.cancel_requested_at,
                                       c.full_name, c.mobile,
                                       s.name AS service_name
                                FROM appointments a
                                JOIN clients c ON c.id = a.client_id
                                JOIN services s ON s.id = a.service_id
                                WHERE a.status = 'cancel_requested' AND a.deleted_at IS NULL
                                ORDER BY a.cancel_requested_at DESC
                                LIMIT 30");
            $appointments = $stmt->fetchAll();

            foreach ($appointments as &$a) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($a['appointment_date'])),
                    (int)date('n', strtotime($a['appointment_date'])),
                    (int)date('j', strtotime($a['appointment_date']))
                );
                $a['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            }
            unset($a);

            jsonOut(['status' => 'success', 'appointments' => $appointments]);
            break;

        // ============ جزئیات درخواست لغو ============
        case 'secretary_cancel_detail':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

            $stmt = $db->prepare("SELECT a.*, c.full_name, c.mobile,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');

            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $appt['jalali_date'] = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

            jsonOut(['status' => 'success', 'appointment' => $appt]);
            break;

        // ============ تایید لغو ============
case 'secretary_approve_cancel':
    $appt_id = (int)($input['appointment_id'] ?? 0);
    $staff_id = (int)($input['staff_id'] ?? 0);
    if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

    $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code, a.appointment_date, 
                                 a.start_time, a.deposit_amount,
                                 c.full_name, c.mobile
                          FROM appointments a
                          JOIN clients c ON c.id = a.client_id
                          WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$appt_id]);
    $appt = $stmt->fetch();
    if (!$appt) jsonError('نوبت یافت نشد');
    if ($appt['status'] !== 'cancel_requested') jsonError('این نوبت درخواست لغو ندارد');

    // محاسبه جریمه
    $apptTs = strtotime($appt['appointment_date'] . ' ' . $appt['start_time']);
    $hoursLeft = ($apptTs - time()) / 3600;
    $penalty = $hoursLeft < 24 ? (int)($appt['deposit_amount'] * 0.30) : 0;
    $refund = (int)$appt['deposit_amount'] - $penalty;

    $upd = $db->prepare("UPDATE appointments 
                         SET status = 'cancelled',
                             cancel_penalty_amount = ?,
                             closed_by = ?
                         WHERE id = ?");
    $upd->execute([$penalty, $staff_id ?: null, $appt_id]);

    // ============ اطلاع‌رسانی به کلاینت ============
    sendNotification($db, 'cancel_approved', $appt['mobile'], [
        'client_name' => $appt['full_name'],
        'booking_code' => $appt['booking_code'],
        'penalty_amount' => number_format($penalty),
        'refund_amount' => number_format($refund),
    ]);

    jsonOut(['status' => 'success', 'message' => 'لغو تایید شد', 'penalty' => $penalty]);
    break;

        // ============ رد درخواست لغو ============
case 'secretary_reject_cancel':
    $appt_id = (int)($input['appointment_id'] ?? 0);
    $staff_id = (int)($input['staff_id'] ?? 0);
    if (!$appt_id) jsonError('شناسه نوبت نامعتبر');

    $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code,
                                 c.full_name, c.mobile
                          FROM appointments a
                          JOIN clients c ON c.id = a.client_id
                          WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
    $stmt->execute([$appt_id]);
    $appt = $stmt->fetch();
    if (!$appt) jsonError('نوبت یافت نشد');
    if ($appt['status'] !== 'cancel_requested') jsonError('این نوبت درخواست لغو ندارد');

    // برگرداندن به وضعیت قطعی
    $upd = $db->prepare("UPDATE appointments 
                         SET status = 'confirmed',
                             cancel_reason = NULL,
                             cancel_card_number = NULL,
                             cancel_card_holder = NULL,
                             cancel_requested_at = NULL
                         WHERE id = ?");
    $upd->execute([$appt_id]);

    // ============ اطلاع‌رسانی به کلاینت ============
    sendNotification($db, 'cancel_rejected', $appt['mobile'], [
        'client_name' => $appt['full_name'],
        'booking_code' => $appt['booking_code'],
    ]);

    jsonOut(['status' => 'success', 'message' => 'درخواست لغو رد شد']);
    break;

            // ============ تشکیل پرونده جدید ============
        case 'secretary_create_client':
            $full_name = trim($input['full_name'] ?? '');
            $mobile = normalizeMobile($input['mobile'] ?? '');
            $emergency_mobile = normalizeMobile($input['emergency_mobile'] ?? '');
            $address = trim($input['address'] ?? '');
            $home_phone = trim($input['home_phone'] ?? '');
            $gender = trim($input['gender'] ?? '');
            $id_card_front = trim($input['id_card_front'] ?? '');
            $id_card_back = trim($input['id_card_back'] ?? '');
            $created_by = (int)($input['created_by'] ?? 0);

            // ============ اعتبارسنجی ============
            if (mb_strlen($full_name) < 3 || !preg_match('/^[\x{0600}-\x{06FF}\s]{2,100}$/u', $full_name)) {
                jsonError('نام و نام خانوادگی باید فقط حروف فارسی باشد');
            }
            if (strlen($mobile) !== 11 || substr($mobile, 0, 2) !== '09') {
                jsonError('شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود');
            }
            if (strlen($emergency_mobile) !== 11 || substr($emergency_mobile, 0, 2) !== '09') {
                jsonError('شماره تماس اضطراری باید ۱۱ رقم و با ۰۹ شروع شود');
            }
            if (mb_strlen($address) < 5) {
                jsonError('آدرس را کامل وارد کنید');
            }
            if (!preg_match('/^[0-9]{8,}$/', $home_phone)) {
                jsonError('شماره تلفن منزل باید حداقل ۸ رقم باشد');
            }
            if (!in_array($gender, ['male', 'female'])) {
                jsonError('جنسیت را انتخاب کنید');
            }
            if (empty($id_card_front) || empty($id_card_back)) {
                jsonError('آپلود تصویر روی و پشت فرم الزامی است');
            }

            // چک تکراری بودن موبایل
            $chk = $db->prepare("SELECT id FROM clients WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $chk->execute([$mobile]);
            if ($chk->fetch()) {
                jsonError('این شماره موبایل قبلاً ثبت شده است');
            }

            // ============ تولید شماره پرونده ۶ رقمی یکتا ============
            do {
                $file_number = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $chk2 = $db->prepare("SELECT id FROM clients WHERE file_number = ? LIMIT 1");
                $chk2->execute([$file_number]);
            } while ($chk2->fetch());

            // ============ ثبت ============
            $stmt = $db->prepare("INSERT INTO clients 
                (file_number, full_name, mobile, emergency_mobile, address, 
                 home_phone, gender, id_card_front, id_card_back, 
                 is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([
                $file_number, $full_name, $mobile, $emergency_mobile, $address,
                $home_phone, $gender, $id_card_front, $id_card_back,
                $created_by ?: null
            ]);

            $client_id = (int)$db->lastInsertId();

            jsonOut([
                'status' => 'success',
                'client_id' => $client_id,
                'file_number' => $file_number,
                'full_name' => $full_name,
                'message' => 'پرونده با موفقیت ایجاد شد',
            ]);
            break;

        // ============ پیدا کردن کلاینت ============
        case 'secretary_find_client':
            $search = trim($input['search'] ?? '');
            if (empty($search)) {
                jsonError('شماره پرونده یا موبایل را وارد کنید');
            }

            $search = preg_replace('/[^0-9]/', '', $search);

            // تشخیص: ۶ رقم = شماره پرونده، ۱۱ رقم = موبایل
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

            if (!$client) {
                jsonError('کلاینتی با این مشخصات یافت نشد');
            }

            jsonOut([
                'status' => 'success',
                'client' => [
                    'id' => (int)$client['id'],
                    'file_number' => $client['file_number'],
                    'full_name' => $client['full_name'],
                    'mobile' => $client['mobile'],
                    'gender' => $client['gender'],
                ],
            ]);
            break;
        // ============ ثبت نوبت توسط منشی ============
        case 'secretary_submit_appointment':
            $client_id = (int)($input['client_id'] ?? 0);
            $service_id = (int)($input['service_id'] ?? 0);
            $operator_id = (int)($input['operator_id'] ?? 0);
            $appointment_date = trim($input['appointment_date'] ?? '');
            $start_time = trim($input['start_time'] ?? '');
            $receipt_path = trim($input['receipt_path'] ?? '');
            $discount_amount = (int)($input['discount_amount'] ?? 0);
            $discount_code_id = (int)($input['discount_code_id'] ?? 0);
            $staff_id = (int)($input['staff_id'] ?? 0);

            if (!$client_id || !$service_id || !$operator_id || !$appointment_date || !$start_time) {
                jsonError('اطلاعات ناقص');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date) 
                || !preg_match('/^\d{2}:\d{2}$/', $start_time)) {
                jsonError('فرمت تاریخ/ساعت نامعتبر');
            }

            // چک ۶۰ روز
            $diff = (strtotime($appointment_date) - strtotime(date('Y-m-d'))) / 86400;
            if ($diff < 0 || $diff > 60) {
                jsonError('تاریخ باید بین امروز و ۶۰ روز آینده باشد');
            }

            // اطلاعات خدمت
            $stmt = $db->prepare("SELECT id, name, price, duration_minutes 
                                  FROM services 
                                  WHERE id = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) jsonError('خدمت یافت نشد');

            // چک تداخل اسلات
            $end_time = date('H:i', strtotime($start_time) + ($service['duration_minutes'] * 60));

            $conflict = $db->prepare("SELECT id FROM appointments 
                                      WHERE operator_id = ? AND appointment_date = ?
                                        AND deleted_at IS NULL 
                                        AND status NOT IN ('cancelled', 'no_show')
                                        AND (start_time < ? AND end_time > ?)
                                      LIMIT 1");
            $conflict->execute([$operator_id, $appointment_date, $end_time, $start_time]);
            if ($conflict->fetch()) {
                jsonError('این ساعت قبلاً رزرو شده است.');
            }

            // بیعانه
            $settings = getSettings($db);
            $deposit = (int)($settings['deposit_amount'] ?? 250000);

            if ($discount_amount > (int)$service['price']) {
                $discount_amount = (int)$service['price'];
            }

            $remaining = max(0, (int)$service['price'] - $deposit - $discount_amount);

            // تولید کد نوبت
            do {
                $booking_code = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $chk2 = $db->prepare("SELECT id FROM appointments WHERE booking_code = ? LIMIT 1");
                $chk2->execute([$booking_code]);
            } while ($chk2->fetch());

            // ثبت با وضعیت confirmed (چون منشی خودش تایید کرده)
            $stmt = $db->prepare("INSERT INTO appointments 
                (booking_code, client_id, service_id, operator_id, appointment_date, 
                 start_time, end_time, status, deposit_amount, service_price, 
                 discount_amount, remaining_amount, receipt_image,
                 confirmed_by, confirmed_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([
                $booking_code, $client_id, $service_id, $operator_id,
                $appointment_date, $start_time, $end_time,
                $deposit, (int)$service['price'], 
                $discount_amount, $remaining,
                $receipt_path ?: null,
                $staff_id ?: null, $staff_id ?: null
            ]);

            $appt_id = (int)$db->lastInsertId();

            // افزایش شمارنده کد تخفیف
            if ($discount_code_id > 0) {
                $db->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE id = ?")
                   ->execute([$discount_code_id]);
            }

            // ============ اطلاع‌رسانی به کلاینت ============
            $client_stmt = $db->prepare("SELECT full_name, mobile FROM clients WHERE id = ? LIMIT 1");
            $client_stmt->execute([$client_id]);
            $client = $client_stmt->fetch();

            if ($client) {
                [$jy, $jm, $jd] = gregorianToJalali(
                    (int)date('Y', strtotime($appointment_date)),
                    (int)date('n', strtotime($appointment_date)),
                    (int)date('j', strtotime($appointment_date))
                );
                $jalali = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

                sendNotification($db, 'receipt_confirmed', $client['mobile'], [
                    'client_name' => $client['full_name'],
                    'booking_code' => $booking_code,
                    'service_name' => $service['name'],
                    'appointment_date' => $jalali,
                    'start_time' => substr($start_time, 0, 5),
                ]);
            }

            jsonOut([
                'status' => 'success',
                'booking_code' => $booking_code,
                'appointment_id' => $appt_id,
                'message' => 'نوبت ثبت شد',
            ]);
            break;

        // ============ لیست نوبت‌ها با فیلتر ============
        case 'secretary_appointments_list':
            $filter = trim($input['filter'] ?? 'today');

            $where = "a.deleted_at IS NULL 
                      AND a.status IN ('confirmed', 'awaiting_service', 'paid')";
            $params = [];

            if ($filter === 'today') {
                $where .= " AND a.appointment_date = CURDATE()";
            } elseif ($filter === '3days') {
                $where .= " AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
            } elseif ($filter === 'week') {
                $where .= " AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($filter === 'undecided') {
                $where .= " AND a.appointment_date < CURDATE()";
            } else {
                $where .= " AND a.appointment_date = CURDATE()";
            }

            $stmt = $db->prepare("SELECT a.id, a.booking_code, a.appointment_date, a.start_time, 
                                         a.end_time, a.status, a.service_price, a.deposit_amount, 
                                         a.remaining_amount,
                                         c.full_name, c.mobile,
                                         s.name AS service_name,
                                         u.full_name AS operator_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  LEFT JOIN users u ON u.id = a.operator_id
                                  WHERE $where
                                  ORDER BY a.appointment_date ASC, a.start_time ASC
                                  LIMIT 50");
            $stmt->execute($params);
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

        // ============ تغییر وضعیت نوبت ============
        case 'secretary_change_status':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            $new_status = trim($input['new_status'] ?? '');
            $staff_id = (int)($input['staff_id'] ?? 0);

            if (!$appt_id || !$new_status) jsonError('اطلاعات ناقص');

            $allowed = ['awaiting_service', 'paid', 'no_show', 'cancelled'];
            if (!in_array($new_status, $allowed)) {
                jsonError('وضعیت نامعتبر');
            }

            // اطلاعات نوبت + کلاینت
            $stmt = $db->prepare("SELECT a.id, a.status, a.booking_code, a.appointment_date, 
                                         a.start_time, a.deposit_amount, a.remaining_amount,
                                         c.full_name, c.mobile,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id]);
            $appt = $stmt->fetch();
            if (!$appt) jsonError('نوبت یافت نشد');

            // آپدیت
            $extra = '';
            if ($new_status === 'done') {
                $extra = ", done_at = NOW()";
            } elseif ($new_status === 'paid') {
                $extra = ", paid_by = " . ($staff_id ?: 'NULL') . ", paid_at = NOW()";
            }

            $upd = $db->prepare("UPDATE appointments 
                                 SET status = ? $extra
                                 WHERE id = ?");
            $upd->execute([$new_status, $appt_id]);

            // ============ اطلاع‌رسانی ============
            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $jalali = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

            if ($new_status === 'done') {
                sendNotification($db, 'appointment_done', $appt['mobile'], [
                    'client_name' => $appt['full_name'],
                    'booking_code' => $appt['booking_code'],
                    'service_name' => $appt['service_name'],
                ]);
            } elseif ($new_status === 'no_show') {
                sendNotification($db, 'no_show', $appt['mobile'], [
                    'client_name' => $appt['full_name'],
                    'booking_code' => $appt['booking_code'],
                    'appointment_date' => $jalali,
                ]);
            } elseif ($new_status === 'cancelled') {
                sendNotification($db, 'receipt_cancelled', $appt['mobile'], [
                    'client_name' => $appt['full_name'],
                    'booking_code' => $appt['booking_code'],
                    'reason' => 'لغو توسط منشی',
                ]);
            } elseif ($new_status === 'paid') {
                sendNotification($db, 'settled', $appt['mobile'], [
                    'client_name' => $appt['full_name'],
                    'booking_code' => $appt['booking_code'],
                    'paid_amount' => number_format((int)$appt['remaining_amount']),
                    'remaining_amount' => '0',
                ]);
            }

            jsonOut([
                'status' => 'success',
                'message' => 'وضعیت تغییر کرد',
                'new_status' => $new_status,
            ]);
            break;

        // ============ جزئیات نوبت منشی ============
        case 'secretary_appointment_detail':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            if (!$appt_id) jsonError('شناسه نامعتبر');

            $stmt = $db->prepare("SELECT a.*, 
                                         c.full_name, c.mobile, c.file_number,
                                         s.name AS service_name,
                                         u.full_name AS operator_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  LEFT JOIN users u ON u.id = a.operator_id
                                  WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id]);
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

        // ============ جستجوی نوبت با کد ============
        case 'secretary_find_appointment':
            $code = trim($input['booking_code'] ?? '');
            if (!preg_match('/^\d{8}$/', $code)) {
                jsonError('کد نوبت باید ۸ رقم باشد');
            }

            $stmt = $db->prepare("SELECT id FROM appointments 
                                  WHERE booking_code = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$code]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبتی با این کد یافت نشد');

            jsonOut([
                'status' => 'success',
                'appointment_id' => (int)$appt['id'],
            ]);
            break;

        // ============ تسویه حساب ============
        case 'secretary_settle_appointment':
            $appt_id = (int)($input['appointment_id'] ?? 0);
            $paid = (int)($input['paid_amount'] ?? 0);
            $method = trim($input['payment_method'] ?? 'cash');
            $staff_id = (int)($input['staff_id'] ?? 0);

            if (!$appt_id) jsonError('شناسه نوبت نامعتبر');
            if (!in_array($method, ['cash', 'pos', 'card_to_card'])) $method = 'cash';
            if ($paid <= 0) jsonError('مبلغ دریافتی باید مثبت باشد');

            // اطلاعات کامل نوبت
            $stmt = $db->prepare("SELECT a.*, c.full_name, c.mobile,
                                         s.name AS service_name
                                  FROM appointments a
                                  JOIN clients c ON c.id = a.client_id
                                  JOIN services s ON s.id = a.service_id
                                  WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1");
            $stmt->execute([$appt_id]);
            $appt = $stmt->fetch();

            if (!$appt) jsonError('نوبت یافت نشد');

            // چک قفل مالی
            $lock = $db->prepare("SELECT id FROM financial_locks 
                                  WHERE lock_year = ? AND lock_month = ? LIMIT 1");
            [$jy, $jm, $jd] = gregorianToJalali(
                (int)date('Y', strtotime($appt['appointment_date'])),
                (int)date('n', strtotime($appt['appointment_date'])),
                (int)date('j', strtotime($appt['appointment_date']))
            );
            $lock->execute([$jy, $jm]);
            if ($lock->fetch()) {
                jsonError('این دوره مالی قفل شده و قابل تغییر نیست');
            }

            if ($appt['status'] !== 'awaiting_service') {
                jsonError('این نوبت در وضعیت قابل تسویه نیست');
            }

            // آپدیت نوبت
            $upd = $db->prepare("UPDATE appointments 
                                 SET status = 'paid',
                                     paid_amount = ?,
                                     payment_method = ?,
                                     paid_by = ?,
                                     paid_at = NOW()
                                 WHERE id = ?");
            $upd->execute([$paid, $method, $staff_id ?: null, $appt_id]);

            // ثبت تراکنش
            $tr = $db->prepare("INSERT INTO transactions 
                (appointment_id, client_id, type, amount, payment_method, description, created_by)
                VALUES (?, ?, 'payment', ?, ?, ?, ?)");
            $tr->execute([
                $appt_id, $appt['client_id'], $paid, $method,
                'تسویه نوبت ' . $appt['booking_code'],
                $staff_id ?: null
            ]);

            // لاگ فعالیت
            $log = $db->prepare("INSERT INTO activity_logs 
                (user_id, action, table_name, record_id, description, created_at)
                VALUES (?, 'settle', 'appointments', ?, ?, NOW())");
            $log->execute([
                $staff_id ?: null, $appt_id,
                'تسویه نوبت ' . $appt['booking_code']
            ]);

            // اطلاع‌رسانی به کلاینت
            sendNotification($db, 'settled', $appt['mobile'], [
                'client_name' => $appt['full_name'],
                'booking_code' => $appt['booking_code'],
                'paid_amount' => number_format($paid),
                'remaining_amount' => '0',
            ]);

            jsonOut([
                'status' => 'success',
                'message' => 'تسویه حساب ثبت شد',
            ]);
            break;


    }

}