<?php
// ============================================
// اطلاع‌رسانی به کلاینت از طریق پلتفرم بله
// ============================================

/**
 * ارسال اعلان به کاربر
 * 
 * @param PDO $db
 * @param string $code کد قالب (مثل receipt_confirmed)
 * @param string $mobile موبایل کلاینت
 * @param array $placeholders آرایه‌ای از جایگزین‌ها
 */
function sendNotification($db, $code, $mobile, $placeholders = []) {

    // ۱. گرفتن قالب از دیتابیس
    $stmt = $db->prepare("SELECT text FROM notification_templates 
                          WHERE code = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$code]);
    $template = $stmt->fetch();

    if (!$template) {
        return false; // قالب غیرفعال یا موجود نیست
    }

    $text = $template['text'];

    // ۲. جایگزینی placeholderها
    foreach ($placeholders as $key => $value) {
        $text = str_replace('{' . $key . '}', $value, $text);
    }

    // ۳. حذف placeholderهای باقی‌مانده (اگه مقدار نداشتن)
    $text = preg_replace('/\{[a-z_]+\}/', '', $text);

    // ۴. ارسال به پلتفرم
    return sendToPlatform($mobile, $text);
}

/**
 * ارسال پیام به پلتفرم
 */
function sendToPlatform($mobile, $text) {
    $url = 'https://balebots.nayrica.ir/projects/nayrica-clinic/receive.php';
    
    $payload = [
        'api_key' => BOT_API_KEY,
        'mobile' => $mobile,
        'text' => $text,
    ];

    $ch = curl_init($url . '?api_key=' . BOT_API_KEY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);

    return json_decode($res, true) ?? [];
}