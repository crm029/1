<?php
// ============================================
// توابع کمکی مشترک
// ============================================

/**
 * نرمال‌سازی شماره موبایل به فرمت 09xxxxxxxxx
 */
function normalizeMobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (substr($mobile, 0, 2) === '98') {
        $mobile = '0' . substr($mobile, 2);
    }
    if (strlen($mobile) === 10 && substr($mobile, 0, 1) !== '0') {
        $mobile = '0' . $mobile;
    }
    return $mobile;
}

/**
 * دریافت تنظیمات سایت
 */
function getSettings($db) {
    $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['setting_key']] = $r['setting_value'];
    }
    return $out;
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function gregorianToJalali($gy, $gm, $gd) {
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

/**
 * خروجی JSON و پایان
 */
function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * خروجی خطا
 */
function jsonError($message) {
    jsonOut(['status' => 'error', 'message' => $message]);
}