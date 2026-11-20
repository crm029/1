<?php
// ============================================
// تنظیمات عمومی
// ============================================

function handleGetSettings($db) {
    $settings = getSettings($db);
    jsonOut([
        'status' => 'success',
        'settings' => [
            'deposit_amount' => (int)($settings['deposit_amount'] ?? 200000),
            'card_number' => $settings['card_number'] ?? '',
            'card_holder' => $settings['card_holder'] ?? '',
            'clinic_name' => $settings['clinic_name'] ?? 'نایریکا',
        ],
    ]);
}