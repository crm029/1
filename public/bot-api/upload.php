<?php
// ============================================
// دریافت و ذخیره فایل (رسید یا فرم کلاینت)
// ============================================

function handleUpload($db) {
    // بررسی API Key
    $api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
    if ($api_key !== BOT_API_KEY) {
        jsonError('API Key نامعتبر');
    }

    if (empty($_FILES['file']['name'])) {
        jsonError('فایلی ارسال نشده');
    }

    $file = $_FILES['file'];
    $type = $_POST['type'] ?? 'receipt'; // receipt | client_id_front | client_id_back
    $mobile = normalizeMobile($_POST['mobile'] ?? '');

    if (strlen($mobile) !== 11) {
        jsonError('شماره موبایل نامعتبر');
    }

    $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $maxSize = 3 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) {
        jsonError('فرمت فایل مجاز نیست (jpg/png/webp)');
    }
    if ($file['size'] > $maxSize) {
        jsonError('حجم فایل بیشتر از ۳ مگابایت است');
    }

    // پوشه کلاینت
    $clientDir = __DIR__ . '/../uploads/clients/' . $mobile . '/';
    if (!is_dir($clientDir)) {
        mkdir($clientDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $ext = 'jpg';
    }

    if ($type === 'receipt') {
        // نام فایل: تاریخ شمسی yyyy-mm-dd
        [$jy, $jm, $jd] = gregorianToJalali(date('Y'), date('n'), date('j'));
        $baseName = sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
        $filename = $baseName . '.' . $ext;
        $counter = 2;
        while (file_exists($clientDir . $filename)) {
            $filename = $baseName . '_' . $counter . '.' . $ext;
            $counter++;
        }
    } elseif ($type === 'client_id_front') {
        $filename = 'id_card_front.' . $ext;
    } elseif ($type === 'client_id_back') {
        $filename = 'id_card_back.' . $ext;
    } else {
        jsonError('نوع فایل نامعتبر');
    }

    $fullPath = $clientDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        jsonError('خطا در ذخیره فایل');
    }

    $relativePath = 'uploads/clients/' . $mobile . '/' . $filename;

    jsonOut([
        'status' => 'success',
        'path' => $relativePath,
        'filename' => $filename,
    ]);
}