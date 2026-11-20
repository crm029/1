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

    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
        jsonError('شماره موبایل نامعتبر');
    }

    $maxSize = 3 * 1024 * 1024;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_uploaded_file($file['tmp_name'] ?? '')
        || (int)$file['size'] <= 0
        || (int)$file['size'] > $maxSize) {
        jsonError('فایل نامعتبر یا بزرگ‌تر از ۳ مگابایت است');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($mimeToExt[$mime])) {
        jsonError('فرمت واقعی فایل مجاز نیست (jpg/png/webp)');
    }
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) {
        jsonError('محتوای فایل تصویر معتبر نیست');
    }
    $ext = $mimeToExt[$mime];
    if (!in_array($type, ['receipt', 'client_id_front', 'client_id_back'], true)) {
        jsonError('نوع فایل نامعتبر');
    }

    // پوشه کلاینت
    $clientDir = __DIR__ . '/../uploads/clients/' . $mobile . '/';
    if (!is_dir($clientDir) && !mkdir($clientDir, 0755, true) && !is_dir($clientDir)) {
        jsonError('امکان ایجاد پوشه ذخیره فایل وجود ندارد');
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