<?php
// ============================================
// اطلاع‌رسانی (اول بله، اگر عضو نبود پیامک)
// ============================================

class Notifier {
    private static $api_key = 'a4ec8b2bffa64072e3252ba2a42b8b2bb11759e4a52499fb3c08e712e9a34b30';
    private static $platform_url = 'https://balebots.nayrica.ir/projects/nayrica-clinic/receive.php';

    // ============================================
    // ارسال اعلان: اول بله، اگر نبود پیامک
    // ============================================
    public static function send($code, $mobile, $placeholders = []) {
        if (empty($mobile)) return false;

        $model = new Model();

        $template = $model->fetchOne(
            "SELECT text FROM notification_templates WHERE code = ? AND is_active = 1 LIMIT 1",
            [$code]
        );

        if (!$template) return false;

        $text = $template['text'];
        foreach ($placeholders as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        $text = preg_replace('/\{[a-z_]+\}/', '', $text);

        // ۱. تلاش با بله
        $bale_result = self::sendViaBale($mobile, $text);

        // اگه کاربر توی ربات نبود → پیامک
        if (($bale_result['status'] ?? '') === 'skipped') {
            return self::sendViaSms($mobile, $text);
        }

        return $bale_result;
    }

    // ============================================
    // ارسال از طریق بله
    // ============================================
    private static function sendViaBale($mobile, $text) {
        $ch = curl_init(self::$platform_url . '?api_key=' . self::$api_key);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'api_key' => self::$api_key,
            'mobile' => $mobile,
            'text' => $text,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? [];
    }

    // ============================================
    // ارسال از طریق پیامک (0098sms)
    // ============================================
    private static function sendViaSms($mobile, $text) {
        $model = new Model();

        // چک فعال بودن پیامک
        $enabled = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_enabled' LIMIT 1");
        if (empty($enabled['setting_value'])) {
            return ['status' => 'sms_disabled'];
        }

        $sender = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_sender' LIMIT 1");
        $username = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_username' LIMIT 1");
        $password = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_password' LIMIT 1");

        if (empty($sender['setting_value']) || empty($username['setting_value']) || empty($password['setting_value'])) {
            return ['status' => 'error', 'message' => 'تنظیمات پیامک کامل نیست'];
        }

        $params = http_build_query([
            'FROM' => $sender['setting_value'],
            'TO' => $mobile,
            'TEXT' => $text,
            'USERNAME' => $username['setting_value'],
            'PASSWORD' => $password['setting_value'],
            'DOMAIN' => '0098',
        ]);

        $url = 'https://www.0098vip.com/sendsmslink.aspx?' . $params;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);

        $code = (int)trim($res);

        return [
            'status' => ($code === 0) ? 'sms_sent' : 'sms_error',
            'code' => $code,
            'response' => trim($res),
        ];
    }
}