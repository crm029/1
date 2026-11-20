<?php
// ============================================
// نقطه ورود API ربات نایریکا
// ============================================
require_once __DIR__ . '/config.php';

// ============================================
// مسیریابی اکشن‌ها
// ============================================
switch ($action) {

    // ---------- عمومی ----------
    case 'ping':
        jsonOut([
            'status' => 'success',
            'message' => 'اتصال برقرار است',
            'time' => date('Y-m-d H:i:s'),
        ]);
        break;

    case 'get_settings':
        require_once __DIR__ . '/settings.php';
        handleGetSettings($db);
        break;

    // ---------- احراز هویت ----------
    case 'check_phone':
    case 'check_file_number':
    case 'verify_password':
    case 'refresh_role':
        require_once __DIR__ . '/auth.php';
        handleAuth($db, $action, $input);
        break;

    // ---------- خدمات ----------
    case 'get_categories':
    case 'get_services':
    case 'get_operators':
        require_once __DIR__ . '/services.php';
        handleServices($db, $action, $input);
        break;

    // ---------- تاریخ و ساعت ----------
    case 'get_available_dates':
    case 'get_available_slots':
        require_once __DIR__ . '/schedule.php';
        handleSchedule($db, $action, $input);
        break;

    // ---------- نوبت‌ها ----------
    case 'get_my_appointments':
    case 'get_appointment_detail':
    case 'submit_appointment':
    case 'request_cancel':
    case 'request_reschedule':
        require_once __DIR__ . '/appointments.php';
        handleAppointments($db, $action, $input);
        break;

    // ---------- آپلود فایل ----------
    case 'upload_receipt':
    case 'upload_client_id':
        require_once __DIR__ . '/upload.php';
        handleUpload($db);
        break;

    // ---------- مشاوره ----------
    case 'submit_consultation':
        require_once __DIR__ . '/consultation.php';
        handleConsultation($db, $input);
        break;

    // ---------- کد تخفیف ----------
    case 'apply_discount_code':
    case 'increment_discount_use':
        require_once __DIR__ . '/discount.php';
        handleDiscount($db, $action, $input);
        break;

    // ---------- منشی ----------
    case 'secretary_pending_receipts':
    case 'secretary_receipt_detail':
    case 'secretary_confirm_receipt':
    case 'secretary_cancel_appointment':
    case 'secretary_cancel_requests':
    case 'secretary_cancel_detail':
    case 'secretary_approve_cancel':
    case 'secretary_reject_cancel':
    case 'secretary_create_client':
    case 'secretary_find_client':
    case 'secretary_submit_appointment':
    case 'secretary_appointments_list':
    case 'secretary_change_status':
    case 'secretary_appointment_detail':
    case 'secretary_find_appointment':
    case 'secretary_settle_appointment':
        require_once __DIR__ . '/secretary.php';
        handleSecretary($db, $action, $input);
        break;

    // ---------- اپراتور ----------
    case 'operator_today_appointments':
    case 'operator_future_appointments':
    case 'operator_appointment_detail':
    case 'operator_close_appointment':
    case 'operator_find_client':
    case 'operator_my_schedules':
    case 'operator_save_schedule':
    case 'operator_delete_schedule':
        require_once __DIR__ . '/operator.php';
        handleOperator($db, $action, $input);
        break;

    // ---------- حسابدار (هزینه) ----------
    case 'get_expense_categories':
    case 'get_expense_category':
    case 'add_expense_category':
    case 'update_expense_category':
    case 'delete_expense_category':
    case 'submit_expense':
        require_once __DIR__ . '/expense.php';
        handleExpense($db, $action, $input);
        break;

    // ---------- تست اعلان ----------
    case 'test_notify':
        $mobile = $input['mobile'] ?? '';
        $res = sendNotification($db, 'receipt_confirmed', $mobile, [
            'client_name' => 'کاربر تستی',
            'booking_code' => '12345678',
            'service_name' => 'لیزر',
            'appointment_date' => '1405/07/25',
            'start_time' => '14:30',
        ]);
        jsonOut(['status' => 'success', 'platform_response' => $res]);
        break;

    default:
        jsonError('اکشن نامشخص');
}