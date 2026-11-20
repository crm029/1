<?php
// ============================================
// احراز هویت (شماره موبایل، پرونده، رمز، نقش)
// ============================================

function handleAuth($db, $action, $input) {
    switch ($action) {

        // ============ بررسی موبایل ============
        case 'check_phone':
            $mobile = normalizeMobile($input['mobile'] ?? '');
            if (strlen($mobile) !== 11) {
                jsonError('شماره موبایل نامعتبر');
            }

            $stmt = $db->prepare("SELECT id, file_number, full_name, gender 
                                  FROM clients 
                                  WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$mobile]);
            $client = $stmt->fetch();
            if ($client) {
                jsonOut([
                    'status' => 'success',
                    'type' => 'client',
                    'user_id' => (int)$client['id'],
                    'full_name' => $client['full_name'],
                    'gender' => $client['gender'],
                ]);
            }

            $stmt = $db->prepare("SELECT id, full_name, role, gender, is_doctor, is_active 
                                  FROM users 
                                  WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$mobile]);
            $staff = $stmt->fetch();
            if ($staff) {
                if (!$staff['is_active']) {
                    jsonError('حساب کاربری شما غیرفعال است');
                }
                jsonOut([
                    'status' => 'success',
                    'type' => 'staff',
                    'user_id' => (int)$staff['id'],
                    'full_name' => $staff['full_name'],
                    'role' => $staff['role'],
                    'gender' => $staff['gender'],
                    'is_doctor' => (int)$staff['is_doctor'],
                ]);
            }

            jsonOut(['status' => 'success', 'type' => 'none']);
            break;

        // ============ بررسی شماره پرونده ============
        case 'check_file_number':
            $mobile = normalizeMobile($input['mobile'] ?? '');
            $file_number = trim($input['file_number'] ?? '');

            if (!preg_match('/^\d{6}$/', $file_number)) {
                jsonError('شماره پرونده باید ۶ رقم باشد');
            }

            $stmt = $db->prepare("SELECT id, file_number, full_name, gender, is_active 
                                  FROM clients 
                                  WHERE file_number = ? AND mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$file_number, $mobile]);
            $client = $stmt->fetch();

            if (!$client) {
                jsonError('اطلاعات ورودی اشتباه است یا شما ثبت‌نام نشده‌اید');
            }
            if (!$client['is_active']) {
                jsonError('پرونده شما غیرفعال است');
            }

            jsonOut([
                'status' => 'success',
                'user_id' => (int)$client['id'],
                'full_name' => $client['full_name'],
                'file_number' => $client['file_number'],
                'gender' => $client['gender'],
            ]);
            break;

        // ============ بررسی رمز پرسنل ============
        case 'verify_password':
            $mobile = normalizeMobile($input['mobile'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($password)) {
                jsonError('رمز عبور را وارد کنید');
            }

            $stmt = $db->prepare("SELECT id, full_name, role, gender, is_doctor, password, is_active 
                                  FROM users 
                                  WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$mobile]);
            $staff = $stmt->fetch();

            if (!$staff || !password_verify($password, $staff['password'])) {
                jsonError('اطلاعات ورودی اشتباه است');
            }
            if (!$staff['is_active']) {
                jsonError('حساب کاربری شما غیرفعال است');
            }

            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
               ->execute([$staff['id']]);

            jsonOut([
                'status' => 'success',
                'user_id' => (int)$staff['id'],
                'full_name' => $staff['full_name'],
                'role' => $staff['role'],
                'gender' => $staff['gender'],
                'is_doctor' => (int)$staff['is_doctor'],
            ]);
            break;

        // ============ آپدیت نقش ============
        case 'refresh_role':
            $mobile = normalizeMobile($input['mobile'] ?? '');
            if (strlen($mobile) !== 11) {
                jsonError('شماره موبایل نامعتبر');
            }

            // چک کلاینت
            $stmt = $db->prepare("SELECT id, full_name, gender FROM clients 
                                  WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$mobile]);
            $client = $stmt->fetch();
            if ($client) {
                jsonOut([
                    'status' => 'success',
                    'type' => 'client',
                    'user_id' => (int)$client['id'],
                    'full_name' => $client['full_name'],
                    'gender' => $client['gender'],
                ]);
            }

            // چک پرسنل
            $stmt = $db->prepare("SELECT id, full_name, role, gender, is_doctor, is_active 
                                  FROM users 
                                  WHERE mobile = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$mobile]);
            $staff = $stmt->fetch();
            if ($staff) {
                if (!$staff['is_active']) {
                    jsonOut(['status' => 'success', 'type' => 'none']);
                }
                jsonOut([
                    'status' => 'success',
                    'type' => 'staff',
                    'user_id' => (int)$staff['id'],
                    'full_name' => $staff['full_name'],
                    'role' => $staff['role'],
                    'gender' => $staff['gender'],
                    'is_doctor' => (int)$staff['is_doctor'],
                ]);
            }

            jsonOut(['status' => 'success', 'type' => 'none']);
            break;
    }
}