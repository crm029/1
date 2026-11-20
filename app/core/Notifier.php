<?php
// اعلان‌رسانی یکپارچه: ابتدا بله (در صورت فعال بودن)، سپس پیامک ۰۰۹۸
class Notifier {
    private static $api_key = 'a4ec8b2bffa64072e3252ba2a42b8b2bb11759e4a52499fb3c08e712e9a34b30';
    private static $platform_url = 'https://balebots.nayrica.ir/projects/nayrica-clinic/receive.php';

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
            $text = str_replace('{' . $key . '}', (string)$value, $text);
        }
        $text = preg_replace('/\\{[a-z_]+\\}/', '', $text);

        return self::sendText($mobile, $text);
    }

    // مسیر مشترک برای تمام اعلان‌های متنی؛ هیچ وابستگی‌ای به کاوه‌نگار ندارد.
    public static function sendText($mobile, $text) {
        if (empty($mobile) || trim((string)$text) === '') return false;

        $settings = new Setting();
        $baleEnabled = $settings->get('bale_enabled', '0') === '1';
        $smsEnabled = $settings->get('sms_enabled', '0') === '1';

        if ($baleEnabled) {
            $baleResult = self::sendViaBale($mobile, $text);
            if (in_array(($baleResult['status'] ?? ''), ['sent', 'success', 'ok'], true)) {
                return $baleResult;
            }
        }

        if ($smsEnabled) {
            return self::sendViaSms($mobile, $text);
        }

        return ['status' => 'no_delivery_channel'];
    }

    private static function sendViaBale($mobile, $text) {
        $ch = curl_init(self::$platform_url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'api_key' => self::$api_key,
                'mobile' => $mobile,
                'text' => $text,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $res = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            return ['status' => 'bale_error', 'message' => $error];
        }
        return json_decode($res, true) ?? ['status' => 'bale_error'];
    }

    private static function sendViaSms($mobile, $text) {
        $model = new Model();
        $enabled = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_enabled' LIMIT 1");
        if (($enabled['setting_value'] ?? '0') !== '1') {
            return ['status' => 'sms_disabled'];
        }

        $sender = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_sender' LIMIT 1");
        $username = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_username' LIMIT 1");
        $password = $model->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'sms_password' LIMIT 1');

        if (empty($sender['setting_value']) || empty($username['setting_value']) || empty($password['setting_value'])) {
            return ['status' => 'error', 'message' => 'تنظیمات پیامک ۰۰۹۸ کامل نیست'];
        }

        $params = http_build_query([
            'FROM' => $sender['setting_value'],
            'TO' => $mobile,
            'TEXT' => $text,
            'USERNAME' => $username['setting_value'],
            'PASSWORD' => $password['setting_value'],
            'DOMAIN' => '0098',
        ]);

        $ch = curl_init('https://www.0098vip.com/sendsmslink.aspx?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            return ['status' => 'sms_error', 'message' => $error];
        }

        $code = (int)trim($res);
        return [
            'status' => ($code === 0) ? 'sms_sent' : 'sms_error',
            'code' => $code,
            'response' => trim($res),
        ];
    }
}
