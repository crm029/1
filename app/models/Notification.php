<?php
require_once __DIR__ . '/Setting.php';

class Notification extends Model {
    public function send($clientId, $message, $context = '') {
        $client = $this->fetchOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
        if (!$client) return false;

        $settingModel = new Setting();
        $baleEnabled = $settingModel->get('bale_enabled', '0') === '1';
        $smsEnabled  = $settingModel->get('sms_enabled', '0') === '1';

        $viaBale = ($baleEnabled && !empty($client['bale_chat_id'])) ? 1 : 0;
        $viaSms  = ($smsEnabled && empty($client['bale_chat_id'])) ? 1 : 0;

        // ذخیره لاگ
        $this->insert('notification_logs', [
            'client_id' => $clientId,
            'mobile' => $client['mobile'],
            'message' => $message,
            'context' => $context,
            'sent_via_bale' => $viaBale,
            'sent_via_sms' => $viaSms,
        ]);

        if ($viaBale) {
            return $this->sendBale($client['bale_chat_id'], $message);
        }
        if ($viaSms) {
            return $this->sendSms($client['mobile'], $message);
        }
        return false;
    }

    private function sendSms($mobile, $message) {
        $settingModel = new Setting();
        $apiKey = $settingModel->get('sms_api_key', '');
        $sender = $settingModel->get('sms_sender', '');
        if (!$apiKey || !$sender) return false;

        $url = 'https://api.kavenegar.com/v1/' . $apiKey . '/sms/send.json';
        $data = ['receptor' => $mobile, 'message' => $message, 'sender' => $sender];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res !== false;
    }

    private function sendBale($chatId, $message) {
        $settingModel = new Setting();
        $token = $settingModel->get('bale_token', '');
        if (!$token) return false;

        $url = "https://tapi.bale.ai/bot{$token}/sendMessage";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => $chatId,
                'text' => $message,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res !== false;
    }
}