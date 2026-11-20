<?php
require_once __DIR__ . '/Setting.php';

class Notification extends Model {
    public function send($clientId, $message, $context = '') {
        $client = $this->fetchOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
        if (!$client) return false;

        $settingModel = new Setting();
        $baleEnabled = $settingModel->get('bale_enabled', '0') === '1';
        $smsEnabled = $settingModel->get('sms_enabled', '0') === '1';

        // ارسال همه اعلان‌ها از مسیر واحد Notifier؛ پیامک فقط از ۰۰۹۸ است.
        $result = Notifier::sendText($client['mobile'], $message);

        $viaBale = ($baleEnabled && in_array(($result['status'] ?? ''), ['sent', 'success', 'ok'], true)) ? 1 : 0;
        $viaSms = (($result['status'] ?? '') === 'sms_sent') ? 1 : 0;

        $this->insert('notification_logs', [
            'client_id' => $clientId,
            'mobile' => $client['mobile'],
            'message' => $message,
            'context' => $context,
            'sent_via_bale' => $viaBale,
            'sent_via_sms' => $viaSms,
        ]);

        return $result;
    }
}
