<?php
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/ServiceCategory.php';

class BookingController extends Controller {
    public function index() {
        // گام ۱: ورود با پرونده + موبایل
        $captcha = Captcha::generate();
        $this->view('booking/step1', [
            'title' => 'رزرو نوبت',
            'captcha' => $captcha,
        ]);
    }

    public function verifyClient() {
        Helpers::verifyCsrf();

        $file_number = Helpers::clean($_POST['file_number'] ?? '');
        $mobile = Helpers::clean($_POST['mobile'] ?? '');
        $captcha_input = $_POST['captcha'] ?? '';

        if (!preg_match('/^\d{6}$/', $file_number)) {
            $_SESSION['booking_error'] = 'شماره پرونده باید ۶ رقم باشد';
            return $this->redirect(BASE_URL . '/booking');
        }
        if (!Helpers::isMobile($mobile)) {
            $_SESSION['booking_error'] = 'شماره موبایل نامعتبر';
            return $this->redirect(BASE_URL . '/booking');
        }
        if (!Captcha::verify($captcha_input)) {
            $_SESSION['booking_error'] = 'پاسخ کپچا اشتباه است';
            return $this->redirect(BASE_URL . '/booking');
        }

        $clientModel = new Client();
        $client = $clientModel->findByFileNumber($file_number);

        if (!$client || $client['mobile'] !== $mobile) {
            $_SESSION['booking_error'] = 'اطلاعات وارد شده صحیح نیست';
            return $this->redirect(BASE_URL . '/booking');
        }
        if (!$client['is_active']) {
            $_SESSION['booking_error'] = 'پرونده شما غیرفعال است. با پشتیبانی تماس بگیرید.';
            return $this->redirect(BASE_URL . '/booking');
        }

        // ذخیره در سشن
        $_SESSION['booking_client_id'] = $client['id'];
        $_SESSION['booking_client_name'] = $client['full_name'];
        $_SESSION['booking_client_gender'] = $client['gender'];

        $this->redirect(BASE_URL . '/booking/services');
    }

public function services() {
    $this->requireBookingClient();
    $serviceModel = new Service();
    $services = $serviceModel->all(['is_active' => 1]);

    // فیلتر بر اساس جنسیت کلاینت
    $clientGender = $_SESSION['booking_client_gender'];
    $filtered = [];
    foreach ($services as $s) {
        if ($s['gender_restriction'] === 'none' || $s['gender_restriction'] === $clientGender) {
            $filtered[] = $s;
        }
    }

    // فقط دسته‌هایی که خدمت مجاز دارن
    $catModel = new ServiceCategory();
    $allCategories = $catModel->activeList();
    $usedCategoryIds = array_unique(array_column($filtered, 'category_id'));
    $categories = array_filter($allCategories, function($c) use ($usedCategoryIds) {
        return in_array($c['id'], $usedCategoryIds);
    });

    $this->view('booking/step2_services', [
        'title' => 'انتخاب خدمات',
        'services' => $filtered,
        'categories' => array_values($categories),
        'clientName' => $_SESSION['booking_client_name'],
    ]);
}

    public function servicesSubmit() {
        $this->requireBookingClient();
        Helpers::verifyCsrf();

        $ids = $_POST['service_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $_SESSION['booking_error'] = 'حداقل یک خدمت انتخاب کنید';
            return $this->redirect(BASE_URL . '/booking/services');
        }

        // اعتبارسنجی + ذخیره در سشن
        $serviceModel = new Service();
        $selected = [];
        foreach ($ids as $id) {
            $s = $serviceModel->findById((int)$id);
            if ($s && $s['is_active']) $selected[] = [
                'id' => $s['id'],
                'name' => $s['name'],
                'duration' => $s['duration_minutes'],
                'price' => $s['price'],
                'is_doctor' => $s['is_doctor_service'],
                'same_gender' => $s['require_same_gender_operator'],
                'gender_restriction' => $s['gender_restriction'],
            ];
        }

        if (empty($selected)) {
            $_SESSION['booking_error'] = 'خدمات انتخابی نامعتبر';
            return $this->redirect(BASE_URL . '/booking/services');
        }

        // چک محدودیت ۳۰ روز برای هر خدمت
        $apptModel = new Appointment();
        $clientId = $_SESSION['booking_client_id'];
        foreach ($selected as $s) {
            if ($apptModel->hasActiveSameService($clientId, $s['id'], 30)) {
                $_SESSION['booking_error'] = 'شما برای خدمت «' . $s['name'] . '» در ۳۰ روز گذشته نوبت فعال دارید.';
                return $this->redirect(BASE_URL . '/booking/services');
            }
        }

        $_SESSION['booking_services'] = $selected;
        $this->redirect(BASE_URL . '/booking/schedule');
    }
	
	    public function schedule() {
        $this->requireBookingClient();
        if (empty($_SESSION['booking_services'])) {
            return $this->redirect(BASE_URL . '/booking/services');
        }
        $settingModel = new Setting();
        $this->view('booking/step3_schedule', [
            'title' => 'انتخاب زمان',
            'services' => $_SESSION['booking_services'],
            'clientGender' => $_SESSION['booking_client_gender'],
            'depositPerService' => (int)$settingModel->get('deposit_amount', 200000),
        ]);
    }

    public function getOperators($service_id) {
        $this->requireBookingClient();
        $service_id = (int)$service_id;
        $serviceModel = new Service();
        $service = $serviceModel->findById($service_id);
        if (!$service) return $this->json([]);

        $clientGender = $_SESSION['booking_client_gender'];

        // لیست اپراتورهای این خدمت (از operator_services)
        $rows = $serviceModel->fetchAll(
            "SELECT os.operator_id, u.full_name, u.gender, u.is_doctor
             FROM operator_services os
             JOIN users u ON u.id = os.operator_id
             WHERE os.service_id = ? AND os.deleted_at IS NULL
               AND u.is_active = 1 AND u.deleted_at IS NULL
             ORDER BY u.full_name",
            [$service_id]
        );

        $result = [];
        foreach ($rows as $r) {
            // اگه خدمت اجبار هم‌جنس داره، فیلتر جنسیت اپراتور
            if ($service['require_same_gender_operator'] && $r['gender'] !== $clientGender) {
                continue;
            }
            $result[] = [
                'id' => $r['operator_id'],
                'name' => $r['full_name'],
                'is_doctor' => (int)$r['is_doctor'],
            ];
        }

        return $this->json([
            'operators' => $result,
            'is_doctor_service' => (int)$service['is_doctor_service'],
        ]);
    }

    public function getDates() {
        $this->requireBookingClient();
        $operator_id = (int)($_GET['operator_id'] ?? 0);
        $service_id = (int)($_GET['service_id'] ?? 0);
        if (!$operator_id || !$service_id) return $this->json([]);

        $serviceModel = new Service();
        $service = $serviceModel->findById($service_id);
        if (!$service) return $this->json([]);

        $scheduleModel = new Schedule();
        $apptModel = new Appointment();
        $holidayModel = new Model();

        // بازه: امروز تا ۳۰ روز جلوتر
        $today = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+30 days'));

        $dates = [];
        $cur = $today;
        while ($cur <= $end) {
            $isHoliday = $holidayModel->fetchOne(
                "SELECT id FROM holidays WHERE holiday_date = ? AND is_active = 1 AND deleted_at IS NULL",
                [$cur]
            );
            $schedule = $scheduleModel->findByDate($operator_id, $cur);

            $status = 'gray'; // تعیین نشده / تعطیل
            if ($isHoliday) {
                $status = 'gray';
            } elseif ($schedule && $schedule['status'] === 'present') {
                // چک اینکه اسلات خالی هست یا نه
                $available = $this->hasFreeSlot($apptModel, $service, $operator_id, $cur, $schedule);
                $status = $available ? 'green' : 'red';
            }

            [$jy, $jm, $jd] = Helpers::toJalaliArray($cur);
				$dates[] = [
					'date' => $cur,
					'status' => $status,
					'jalali_day' => $jd,
					'jalali_month' => $jm,
					'jalali_year' => $jy,
					'weekday' => (int)date('w', strtotime($cur)), // 0=Sun, 6=Sat
			];
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }

        return $this->json(['dates' => $dates]);
    }

    public function getSlots() {
        $this->requireBookingClient();
        $operator_id = (int)($_GET['operator_id'] ?? 0);
        $service_id = (int)($_GET['service_id'] ?? 0);
        $date = Helpers::clean($_GET['date'] ?? '');
        if (!$operator_id || !$service_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['slots' => []]);
        }

        $serviceModel = new Service();
        $service = $serviceModel->findById($service_id);
        if (!$service) return $this->json(['slots' => []]);

        $scheduleModel = new Schedule();
        $schedule = $scheduleModel->findByDate($operator_id, $date);
        if (!$schedule || $schedule['status'] !== 'present') {
            return $this->json(['slots' => [], 'message' => 'اپراتور در این روز حضور ندارد']);
        }

        $settingModel = new Setting();
        $workStart = $schedule['start_time'] ?: $settingModel->get('work_start_time', '09:00');
        $workEnd = $schedule['end_time'] ?: $settingModel->get('work_end_time', '21:00');

        $duration = (int)$service['duration_minutes'];
        $slots = [];
        $t = strtotime($date . ' ' . $workStart);
        $endTs = strtotime($date . ' ' . $workEnd);
        while ($t + ($duration * 60) <= $endTs) {
            $s = date('H:i', $t);
            $e = date('H:i', $t + ($duration * 60));
            $slots[] = ['start' => $s, 'end' => $e];
            $t += $duration * 60;
        }

        $apptModel = new Appointment();
        $booked = $apptModel->getByOperatorDate($operator_id, $date);
        $result = [];
        foreach ($slots as $slot) {
            $conflict = false;
            foreach ($booked as $b) {
                if ($slot['start'] < $b['end_time'] && $slot['end'] > $b['start_time']) {
                    $conflict = true;
                    break;
                }
            }
            $result[] = ['start' => $slot['start'], 'end' => $slot['end'], 'available' => !$conflict];
        }
        return $this->json(['slots' => $result]);
    }

    private function hasFreeSlot($apptModel, $service, $operator_id, $date, $schedule) {
        $settingModel = new Setting();
        $workStart = $schedule['start_time'] ?: $settingModel->get('work_start_time', '09:00');
        $workEnd = $schedule['end_time'] ?: $settingModel->get('work_end_time', '21:00');
        $duration = (int)$service['duration_minutes'];
        $t = strtotime($date . ' ' . $workStart);
        $endTs = strtotime($date . ' ' . $workEnd);
        $booked = $apptModel->getByOperatorDate($operator_id, $date);
        while ($t + ($duration * 60) <= $endTs) {
            $s = date('H:i', $t);
            $e = date('H:i', $t + ($duration * 60));
            $conflict = false;
            foreach ($booked as $b) {
                if ($s < $b['end_time'] && $e > $b['start_time']) { $conflict = true; break; }
            }
            if (!$conflict) return true;
            $t += $duration * 60;
        }
        return false;
    }

    public function scheduleSubmit() {
        $this->requireBookingClient();
        Helpers::verifyCsrf();

        $services = $_SESSION['booking_services'];
        $booking = [];

        foreach ($services as $idx => $s) {
            $operator_id = (int)($_POST['operator_' . $s['id']] ?? 0);
            $date = Helpers::clean($_POST['date_' . $s['id']] ?? '');
            $time = Helpers::clean($_POST['time_' . $s['id']] ?? '');

            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $_SESSION['booking_error'] = 'برای «' . $s['name'] . '» تاریخ انتخاب کنید';
                return $this->redirect(BASE_URL . '/booking/schedule');
            }
            if (!$time || !preg_match('/^\d{2}:\d{2}$/', $time)) {
                $_SESSION['booking_error'] = 'برای «' . $s['name'] . '» ساعت انتخاب کنید';
                return $this->redirect(BASE_URL . '/booking/schedule');
            }

            $startTs = strtotime($date . ' ' . $time);
            $end = date('H:i', $startTs + ((int)$s['duration'] * 60));

            $booking[] = [
                'service_id' => $s['id'],
                'service_name' => $s['name'],
                'duration' => $s['duration'],
                'price' => $s['price'],
                'operator_id' => $operator_id ?: null,
                'is_random' => $operator_id ? 0 : 1,
                'date' => $date,
                'start' => $time,
                'end' => $end,
            ];
        }

        $_SESSION['booking_schedule'] = $booking;
        $this->redirect(BASE_URL . '/booking/preview');
    }
	
    public function preview() {
        $this->requireBookingClient();
        if (empty($_SESSION['booking_schedule'])) {
            return $this->redirect(BASE_URL . '/booking/schedule');
        }
        $settingModel = new Setting();
        $settings = $settingModel->all();

        $services = $_SESSION['booking_services'];
        $schedule = $_SESSION['booking_schedule'];

        $depositPerService = (int)($settings['deposit_amount'] ?? 200000);
        $totalDeposit = $depositPerService * count($schedule);

        $totalPrice = 0;
        foreach ($services as $s) $totalPrice += (int)$s['price'];

        $rows = [];
        foreach ($schedule as $i => $sc) {
            $rows[] = [
                'service_name' => $sc['service_name'],
                'duration' => $sc['duration'],
                'price' => $sc['price'],
                'date' => $sc['date'],
                'start' => $sc['start'],
                'end' => $sc['end'],
                'operator_id' => $sc['operator_id'],
            ];
        }

        $discount = $_SESSION['booking_discount'] ?? null;
        $discountAmount = $discount['amount'] ?? 0;

        $finalDeposit = $totalDeposit;
        $finalTotal = max(0, $totalPrice - $discountAmount);
        $finalRemaining = max(0, $finalTotal - $totalDeposit);

        $this->view('booking/step4_preview', [
            'title' => 'پیش‌نمایش و پرداخت',
            'rows' => $rows,
            'totalDeposit' => $totalDeposit,
            'totalPrice' => $totalPrice,
            'finalDeposit' => $finalDeposit,
            'finalTotal' => $finalTotal,
            'finalRemaining' => $finalRemaining,
            'discountAmount' => $discountAmount,
            'discount' => $discount,
            'settings' => $settings,
            'clientName' => $_SESSION['booking_client_name'],
        ]);
    }

    public function submit() {
        $this->requireBookingClient();
        Helpers::verifyCsrf();

        if (empty($_SESSION['booking_schedule'])) {
            return $this->redirect(BASE_URL . '/booking/schedule');
        }
        if (!isset($_POST['accept_terms'])) {
            $_SESSION['booking_error'] = 'پذیرش قوانین الزامی است';
            return $this->redirect(BASE_URL . '/booking/preview');
        }

        $receipt = $this->uploadReceipt();
        if (!$receipt) {
            $_SESSION['booking_error'] = 'آپلود رسید الزامی است (jpg/png، حداکثر ۳ مگابایت)';
            return $this->redirect(BASE_URL . '/booking/preview');
        }

        $clientId = $_SESSION['booking_client_id'];
        $services = $_SESSION['booking_services'];
        $schedule = $_SESSION['booking_schedule'];
        $discount = $_SESSION['booking_discount'] ?? null;

        $settingModel = new Setting();
        $depositPerService = (int)$settingModel->get('deposit_amount', 200000);
        $totalDeposit = $depositPerService * count($schedule);

        $discountTotal = $discount['amount'] ?? 0;
        $discountPerAppt = count($schedule) > 0 ? (int)floor($discountTotal / count($schedule)) : 0;

        $apptModel = new Appointment();
        $bookingCodes = [];

        $bookingLocks = [];
        try {
            $apptModel->beginTransaction();

            // Serialize reservations for the same operator and calendar day.
            foreach ($schedule as $slot) {
                $operatorId = (int)($slot['operator_id'] ?? 0);
                if (!$operatorId) continue; // Random operator is assigned later.
                $lockName = 'appt_' . substr(hash('sha256', $operatorId . '|' . $slot['date']), 0, 48);
                if (!isset($bookingLocks[$lockName])) {
                    $lockResult = $apptModel->fetchOne("SELECT GET_LOCK(?, 10) AS acquired", [$lockName]);
                    if ((int)($lockResult['acquired'] ?? 0) !== 1) {
                        throw new RuntimeException('زمان انتخابی در حال بررسی است؛ دوباره تلاش کنید.');
                    }
                    $bookingLocks[$lockName] = true;
                }
            }

            // Consume the discount atomically with the booking transaction.
            if ($discount) {
                $serviceIds = array_values(array_unique(array_map('intval', array_column($schedule, 'service_id'))));
                $servicePlaceholders = implode(',', array_fill(0, count($serviceIds), '?'));
                $discountSql = "UPDATE discount_codes
                    SET used_count = used_count + 1
                    WHERE id = ? AND is_active = 1 AND deleted_at IS NULL
                      AND (start_date IS NULL OR start_date <= CURDATE())
                      AND (end_date IS NULL OR end_date >= CURDATE())
                      AND (max_uses IS NULL OR used_count < max_uses)
                      AND (service_id IS NULL OR service_id IN ({$servicePlaceholders}))";
                $discountParams = array_merge([(int)$discount['code_id']], $serviceIds);
                $discountStmt = $apptModel->query($discountSql, $discountParams);
                if ($discountStmt->rowCount() !== 1) {
                    throw new RuntimeException('کد تخفیف دیگر معتبر نیست یا ظرفیت آن تکمیل شده است');
                }
            }

            foreach ($schedule as $i => $sc) {
                $serviceId = $sc['service_id'];
                $service = null;
                foreach ($services as $s) if ($s['id'] === $serviceId) { $service = $s; break; }
                if (!$service) continue;

                if (!empty($sc['operator_id']) && $apptModel->hasConflict(
                    (int)$sc['operator_id'],
                    $sc['date'],
                    $sc['start'],
                    $sc['end']
                )) {
                    throw new RuntimeException('این زمان در همین لحظه رزرو شده است. لطفاً زمان دیگری انتخاب کنید.');
                }

                $code = $apptModel->generateBookingCode();
                $bookingCodes[] = $code;

                $apptDiscount = $discountPerAppt;
                if ($i === count($schedule) - 1) {
                    $apptDiscount = $discountTotal - ($discountPerAppt * (count($schedule) - 1));
                }
                if ($apptDiscount > (int)$service['price']) $apptDiscount = (int)$service['price'];

                $servicePrice = (int)$service['price'];
                $remaining = max(0, $servicePrice - $depositPerService - $apptDiscount);

                $apptModel->create([
                    'booking_code' => $code,
                    'client_id' => $clientId,
                    'service_id' => $serviceId,
                    'operator_id' => $sc['operator_id'] ?: null,
                    'is_random_operator' => $sc['is_random'],
                    'appointment_date' => $sc['date'],
                    'start_time' => $sc['start'],
                    'end_time' => $sc['end'],
                    'status' => 'pending_receipt',
                    'deposit_amount' => $depositPerService,
                    'service_price' => $servicePrice,
                    'remaining_amount' => $remaining,
                    'discount_amount' => $apptDiscount,
                    'receipt_image' => $receipt,
                ]);
            }

            $apptModel->commit();
            foreach (array_keys($bookingLocks) as $lockName) {
                $apptModel->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$lockName]);
            }
        } catch (Exception $e) {
            $apptModel->rollBack();
            foreach (array_keys($bookingLocks) as $lockName) {
                try { $apptModel->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$lockName]); } catch (Exception $ignored) {}
            }
            $_SESSION['booking_error'] = $e->getMessage() ?: 'خطا در ثبت نوبت. دوباره تلاش کنید.';
            return $this->redirect(BASE_URL . '/booking/preview');
        }

// ============ اطلاع‌رسانی به کلاینت (از طریق بله) ============
$clientModel = new Client();
$client = $clientModel->findById($clientId);

if ($client) {
    foreach ($schedule as $i => $sc) {
        $service = null;
        foreach ($services as $s) {
            if ($s['id'] === $sc['service_id']) { $service = $s; break; }
        }
        if (!$service) continue;

        try {
            Notifier::send('booking_created', $client['mobile'], [
                'client_name' => $client['full_name'],
                'booking_code' => $bookingCodes[$i] ?? '',
                'service_name' => $service['name'],
                'appointment_date' => Helpers::toJalaliString($sc['date']),
                'start_time' => substr($sc['start'], 0, 5),
            ]);
        } catch (Exception $e) {
            // در صورت خطا، رزرو نباید fail بشه
        }
    }
}

$_SESSION['booking_codes'] = $bookingCodes;
$_SESSION['booking_total_deposit'] = $totalDeposit;
unset($_SESSION['booking_client_id'], $_SESSION['booking_client_name'], $_SESSION['booking_client_gender']);
unset($_SESSION['booking_services'], $_SESSION['booking_schedule'], $_SESSION['booking_discount']);

$this->redirect(BASE_URL . '/booking/success');
    }

    public function success() {
        if (empty($_SESSION['booking_codes'])) {
            return $this->redirect(BASE_URL . '/');
        }
        $codes = $_SESSION['booking_codes'];
        $total = $_SESSION['booking_total_deposit'] ?? 0;
        unset($_SESSION['booking_codes'], $_SESSION['booking_total_deposit']);

        $this->view('booking/success', [
            'title' => 'رزرو موفق',
            'codes' => $codes,
            'total' => $total,
        ]);
    }
	
	// اعتبارسنجی کد تخفیف (AJAX)
    public function validateDiscount() {
    $this->requireBookingClient();
    Helpers::verifyCsrf();

    $code = strtoupper(Helpers::clean($_POST['code'] ?? ''));
    if (empty($code)) {
        return $this->json(['error' => 'کد را وارد کنید']);
    }
    if (empty($_SESSION['booking_services'])) {
        return $this->json(['error' => 'سشن منقضی شده']);
    }

    require_once __DIR__ . '/../models/DiscountCode.php';
    $model = new DiscountCode();

    $totalPrice = 0;
    foreach ($_SESSION['booking_services'] as $s) $totalPrice += (int)$s['price'];

    $result = $model->validate($code);
    if (isset($result['error'])) {
        return $this->json(['error' => $result['error']]);
    }

    $row = $result['code'];

    if (!empty($row['service_id'])) {
        $found = false;
        $matchedPrice = 0;
        foreach ($_SESSION['booking_services'] as $s) {
            if ($s['id'] == $row['service_id']) {
                $found = true;
                $matchedPrice = (int)$s['price'];
                break;
            }
        }
        if (!$found) {
            return $this->json(['error' => 'این کد برای خدمات انتخابی شما معتبر نیست']);
        }
        $discount = $model->calcDiscount($row, $matchedPrice);
    } else {
        $discount = $model->calcDiscount($row, $totalPrice);
    }

    $_SESSION['booking_discount'] = [
        'code_id' => $row['id'],
        'code' => $row['code'],
        'amount' => $discount,
        'type' => $row['discount_type'],
        'value' => (int)$row['discount_value'],
    ];

    return $this->json([
        'success' => true,
        'amount' => $discount,
        'message' => 'کد تخفیف اعمال شد',
    ]);
}

    // حذف کد تخفیف
    public function removeDiscount() {
        $this->requireBookingClient();
        Helpers::verifyCsrf();
        unset($_SESSION['booking_discount']);
        return $this->json(['success' => true]);
    }
	
    private function uploadReceipt() {
        if (empty($_FILES['receipt']['name'])) return null;
        $file = $_FILES['receipt'];
        $maxSize = 3 * 1024 * 1024;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file($file['tmp_name'] ?? '')
            || (int)$file['size'] <= 0
            || (int)$file['size'] > $maxSize) return null;

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($mimeToExt[$mime])) return null;
        $ext = $mimeToExt[$mime];
        $name = 'receipt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dir = __DIR__ . '/../../public/uploads/receipts/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return null;
        return 'uploads/receipts/' . $name;
    }
	
	    // فرم پیگیری / لغو نوبت
    public function trackForm() {
        $this->view('booking/track', ['title' => 'پیگیری نوبت']);
    }

    public function track() {
        Helpers::verifyCsrf();
        $code = Helpers::clean($_POST['booking_code'] ?? '');
        $mobile = Helpers::clean($_POST['mobile'] ?? '');

        if (!preg_match('/^\d{8}$/', $code)) {
            $_SESSION['track_error'] = 'کد نوبت باید ۸ رقم باشد';
            return $this->redirect(BASE_URL . '/booking/track');
        }
        if (!Helpers::isMobile($mobile)) {
            $_SESSION['track_error'] = 'شماره موبایل نامعتبر';
            return $this->redirect(BASE_URL . '/booking/track');
        }

        if (!$this->allowTrackAttempt()) {
            $_SESSION['track_error'] = 'تعداد تلاش‌های شما بیش از حد مجاز است. ۱۵ دقیقه دیگر دوباره تلاش کنید.';
            return $this->redirect(BASE_URL . '/booking/track');
        }

        $apptModel = new Appointment();
        $appt = $apptModel->findByBookingCode($code);
        if (!$appt || $appt['client_mobile'] !== $mobile) {
            $_SESSION['track_error'] = 'اطلاعات وارد شده صحیح نیست';
            return $this->redirect(BASE_URL . '/booking/track');
        }

        $this->clearTrackAttempts();
        $_SESSION['track_code'] = $code;
        $_SESSION['track_mobile'] = $mobile;
        $this->redirect(BASE_URL . '/booking/track/detail');
    }

    public function trackDetail() {
        if (empty($_SESSION['track_code']) || empty($_SESSION['track_mobile'])) {
            return $this->redirect(BASE_URL . '/booking/track');
        }
        $apptModel = new Appointment();
        $appt = $apptModel->findByBookingCode($_SESSION['track_code']);
        if (!$appt || $appt['client_mobile'] !== $_SESSION['track_mobile']) {
            unset($_SESSION['track_code'], $_SESSION['track_mobile']);
            return $this->redirect(BASE_URL . '/booking/track');
        }

        // چک امکان لغو
        $canCancel = in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid']);
        $hoursLeft = (strtotime($appt['appointment_date'] . ' ' . $appt['start_time']) - time()) / 3600;
        $isLessThan24 = $hoursLeft < 24;

        $this->view('booking/track_detail', [
            'title' => 'جزئیات نوبت',
            'appt' => $appt,
            'canCancel' => $canCancel,
            'hoursLeft' => $hoursLeft,
            'isLessThan24' => $isLessThan24,
            'penalty' => $isLessThan24 ? (int)($appt['deposit_amount'] * 0.30) : 0,
        ]);
    }

    // لغو نوبت توسط کلاینت
    public function cancelByClient() {
        if (empty($_SESSION['track_code'])) {
            return $this->redirect(BASE_URL . '/booking/track');
        }
        Helpers::verifyCsrf();

        $apptModel = new Appointment();
        $appt = $apptModel->findByBookingCode($_SESSION['track_code']);
        if (!$appt) {
            $_SESSION['track_error'] = 'نوبت یافت نشد';
            return $this->redirect(BASE_URL . '/booking/track');
        }
        if (!in_array($appt['status'], ['pending_receipt', 'confirmed', 'awaiting_service', 'paid'])) {
            $_SESSION['track_error'] = 'این نوبت قابل لغو نیست';
            return $this->redirect(BASE_URL . '/booking/track/detail');
        }

        $reason = Helpers::clean($_POST['reason'] ?? '');
        $card_number = Helpers::clean($_POST['card_number'] ?? '');
        $card_holder = Helpers::clean($_POST['card_holder'] ?? '');

        if (mb_strlen($reason) < 3) {
            $_SESSION['track_error'] = 'دلیل لغو الزامی است';
            return $this->redirect(BASE_URL . '/booking/track/detail');
        }
        if (!preg_match('/^\d{16}$/', $card_number)) {
            $_SESSION['track_error'] = 'شماره کارت باید ۱۶ رقم باشد';
            return $this->redirect(BASE_URL . '/booking/track/detail');
        }
        if (!Helpers::isPersianName($card_holder)) {
            $_SESSION['track_error'] = 'نام صاحب کارت باید فقط حروف فارسی باشد';
            return $this->redirect(BASE_URL . '/booking/track/detail');
        }

        // ثبت درخواست لغو (نه لغو مستقیم)
        $apptModel->edit($appt['id'], [
            'status' => 'cancel_requested',
            'cancel_reason' => $reason,
            'cancel_card_number' => $card_number,
            'cancel_card_holder' => $card_holder,
            'cancel_requested_at' => date('Y-m-d H:i:s'),
        ]);

        require_once __DIR__ . '/../models/Notification.php';
        $notif = new Notification();
        $notif->send(
            $appt['client_id'],
            "درخواست لغو نوبت شما با کد {$appt['booking_code']} ثبت شد و در انتظار بررسی منشی است.",
            'cancel_requested'
        );

        $_SESSION['track_success'] = 'درخواست لغو شما ثبت شد و پس از بررسی منشی به شما اطلاع داده می‌شود.';
        $_SESSION['track_refund'] = 0;
        $_SESSION['track_penalty'] = 0;
        $this->redirect(BASE_URL . '/booking/track/result');
    }

    public function trackResult() {
        if (empty($_SESSION['track_success'])) {
            return $this->redirect(BASE_URL . '/');
        }
        $success = $_SESSION['track_success'];
        $refund = $_SESSION['track_refund'] ?? 0;
        $penalty = $_SESSION['track_penalty'] ?? 0;
        unset($_SESSION['track_success'], $_SESSION['track_refund'], $_SESSION['track_penalty'], $_SESSION['track_code'], $_SESSION['track_mobile']);

        $this->view('booking/track_result', [
            'title' => 'نتیجه لغو',
            'success' => $success,
            'refund' => $refund,
            'penalty' => $penalty,
        ]);
    }

    public function trackLogout() {
        unset($_SESSION['track_code'], $_SESSION['track_mobile']);
        $this->redirect(BASE_URL . '/booking/track');
    }
	
    /**
     * Rate-limit booking lookups by remote IP. Five failed lookups per 15 minutes.
     * Only hashed identifiers are persisted; file locking makes increments atomic.
     */
    private function trackRateLimitFile() {
        $dir = __DIR__ . '/../../storage/rate-limits';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $dir . '/booking-track-' . hash('sha256', $ip) . '.json';
    }

    private function allowTrackAttempt() {
        $file = $this->trackRateLimitFile();
        $fp = @fopen($file, 'c+');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $state = json_decode($raw ?: '{}', true) ?: [];
        $now = time();
        if (empty($state['started']) || ($now - (int)$state['started']) >= 900) {
            $state = ['started' => $now, 'count' => 0];
        }
        if ((int)($state['count'] ?? 0) >= 5) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }
        $state['count'] = (int)$state['count'] + 1;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    private function clearTrackAttempts() {
        $file = $this->trackRateLimitFile();
        if (is_file($file)) @unlink($file);
    }

    private function requireBookingClient() {
        if (empty($_SESSION['booking_client_id'])) {
            $this->redirect(BASE_URL . '/booking');
        }
    }
}