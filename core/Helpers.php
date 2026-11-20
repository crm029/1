<?php
class Helpers {
    public static function e($v) {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf() {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('درخواست نامعتبر (CSRF)');
        }
    }

    public static function clean($v) {
        return trim(strip_tags($v ?? ''));
    }

    public static function isUsername($v) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $v);
    }

    public static function isMobile($v) {
        return preg_match('/^09[0-9]{9}$/', $v);
    }

    public static function isEmail($v) {
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isPersianName($v) {
        return preg_match('/^[\x{0600}-\x{06FF}\s]{2,100}$/u', $v);
    }

    public static function isNumeric($v) {
        return preg_match('/^[0-9]+$/', $v);
    }

public static function isStrongPassword($v) {
    return strlen($v) >= 8
        && preg_match('/[A-Z]/', $v)   // حرف بزرگ
        && preg_match('/[a-z]/', $v)   // حرف کوچک
        && preg_match('/[0-9]/', $v);  // عدد
}
	
	    // ============ تقویم شمسی ============
    public static function gregorianToJalali($gy, $gm, $gd) {
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

    public static function jalaliToGregorian($jy, $jm, $jd) {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * ((int)($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * ((int)(--$days / 36524));
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0,31,($gy % 4 == 0 && ($gy % 100 != 0 || $gy % 400 == 0)) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
        for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }
        return [$gy, $gm, $gd];
    }

    public static function toJalaliString($date) {
        if (!$date) return '';
        $ts = is_numeric($date) ? $date : strtotime($date);
        [$jy, $jm, $jd] = self::gregorianToJalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    public static function toJalaliArray($date) {
        $ts = is_numeric($date) ? $date : strtotime($date);
        return self::gregorianToJalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    }

    public static function jalaliMonthName($m) {
        $names = ['', 'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        return $names[$m] ?? '';
    }

    public static function jalaliWeekDayName($date) {
        $ts = is_numeric($date) ? $date : strtotime($date);
        $w = (int)date('w', $ts); // 0=Sunday, 6=Saturday
        $map = [1=>'یکشنبه', 2=>'دوشنبه', 3=>'سه‌شنبه', 4=>'چهارشنبه', 5=>'پنجشنبه', 6=>'جمعه', 0=>'شنبه'];
        return $map[$w];
    }

    public static function daysInJalaliMonth($jy, $jm) {
        if ($jm <= 6) return 31;
        if ($jm <= 11) return 30;
        // اسفند
        $isLeap = ((($jy + 12) % 33) % 4) == 1;
        return $isLeap ? 30 : 29;
    }

    // ============ تقویم ماهانه شمسی ============

    /**
     * اولین روز ماه شمسی - کدوم روز هفته؟ (0=شنبه تا 6=جمعه)
     */
    public static function jalaliFirstDayOfWeek($jy, $jm) {
        [$gy, $gm, $gd] = self::jalaliToGregorian($jy, $jm, 1);
        $ts = mktime(0, 0, 0, $gm, $gd, $gy);
        $w = (int)date('w', $ts); // 0=Sunday, 6=Saturday
        $map = [6=>0, 0=>1, 1=>2, 2=>3, 3=>4, 4=>5, 5=>6];
        return $map[$w];
    }

    /**
     * تبدیل تاریخ شمسی به رشته میلادی YYYY-MM-DD
     */
    public static function jalaliToGregorianString($jy, $jm, $jd) {
        [$gy, $gm, $gd] = self::jalaliToGregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    // ============ ترجمه action لاگ‌ها ============
    public static function translateAction($action) {
        $map = [
            'login' => 'ورود',
            'logout' => 'خروج',
            'create' => 'ایجاد',
            'edit' => 'ویرایش',
            'delete' => 'حذف',
            'toggle' => 'تغییر وضعیت',
            'settle' => 'تسویه',
            'confirm' => 'تایید رسید',
            'cancel' => 'لغو',
            'pay' => 'پرداخت',
            'lock' => 'قفل مالی',
            'unlock' => 'باز کردن قفل',
            'change_operator' => 'تغییر اپراتور',
            'change_status' => 'تغییر وضعیت',
            'reschedule' => 'موکول نوبت',
            'reject' => 'رد درخواست',
            'approve' => 'تایید درخواست',
        ];

        // جستجوی دقیق
        if (isset($map[$action])) return $map[$action];

        // جستجوی جزئی (مثلاً change_operator_status)
        foreach ($map as $key => $label) {
            if (strpos($action, $key) !== false) {
                return $label;
            }
        }

        return $action;
    }

}