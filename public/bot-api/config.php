<?php
// Shared API configuration for the Nayrica bot.
header('Content-Type: application/json; charset=utf-8');

$botApiKey = getenv('NAYRICA_BOT_API_KEY') ?: '';
if ($botApiKey === '') {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Bot API is not configured']);
    exit;
}
define('BOT_API_KEY', $botApiKey);

$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if (!hash_equals(BOT_API_KEY, (string)$apiKey)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'API Key نامعتبر']);
    exit;
}

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
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال دیتابیس']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($input)) {
    $input = array_merge($_GET, $_POST);
} else {
    $input = array_merge($_GET, $input);
}
$action = $input['action'] ?? $_GET['action'] ?? '';

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/notify.php';
