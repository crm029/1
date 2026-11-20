<?php
// ============================================
// درخواست مشاوره
// ============================================

function handleConsultation($db, $input) {
    $client_id = (int)($input['client_id'] ?? 0);
    $full_name = trim($input['full_name'] ?? '');
    $mobile = normalizeMobile($input['mobile'] ?? '');
    $message = trim($input['message'] ?? '');

    if (!$client_id) jsonError('کاربر نامعتبر');
    if (mb_strlen($full_name) < 3) jsonError('نام را وارد کنید');
    if (strlen($mobile) !== 11) jsonError('شماره موبایل نامعتبر');
    if (mb_strlen($message) < 5) jsonError('متن درخواست را کامل وارد کنید');

    $stmt = $db->prepare("INSERT INTO consultation_requests 
        (client_id, full_name, mobile, message, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$client_id, $full_name, $mobile, $message]);

    jsonOut([
        'status' => 'success',
        'message' => 'درخواست شما ثبت شد. به‌زودی کارشناس با شما تماس می‌گیرد.',
    ]);
}