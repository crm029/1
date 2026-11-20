<?php
// ============================================
// تنظیمات API ربات نایریکا
// ============================================
header('Content-Type: application/json; charset=utf-8');

// کلید API پلتفرم
define('BOT_API_KEY', 'a4ec8b2bffa64072e3252ba2a42b8b2bb11759e4a52499fb3c08e712e9a34b30');

// ============================================
// بررسی کلید API
// ============================================
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== BOT_API_KEY) {
    echo json_encode(['status' => 'error', 'message' => 'API Key نامعتبر']);
    exit;
}

// ============================================
// اتصال به دیتابیس
// ============================================
$config = require __DIR__ . '/../../config/config.php';
date_default_timezone_set($config['app']['timezone']);

try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال دیتابیس']);
    exit;
}

// ============================================
// دریافت action و input
// ============================================
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// ادغام $_GET و $_POST در $input (برای تست از مرورگر)
if (empty($input)) {
    $input = array_merge($_GET, $_POST);
} else {
    $input = array_merge($_GET, $input);
}

$action = $input['action'] ?? $_GET['action'] ?? '';

// ============================================
// توابع مشترک
// ============================================
require_once __DIR__ . '/helpers.php';

// ============================================
// توابع اطلاع‌رسانی
// ============================================
require_once __DIR__ . '/notify.php';