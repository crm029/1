<?php
/**
 * Runtime configuration loader.
 * Keep credentials in config.local.php (not tracked) or server environment variables.
 */
$localConfigPath = __DIR__ . '/config.local.php';
$local = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($local)) {
    throw new RuntimeException('Invalid local configuration file.');
}

$dbLocal = $local['db'] ?? [];
$appLocal = $local['app'] ?? [];

$getEnv = static function ($name, $fallback = null) {
    $value = getenv($name);
    return ($value === false || $value === '') ? $fallback : $value;
};

$db = [
    'host' => $getEnv('DB_HOST', $dbLocal['host'] ?? 'localhost'),
    'name' => $getEnv('DB_NAME', $dbLocal['name'] ?? ''),
    'user' => $getEnv('DB_USER', $dbLocal['user'] ?? ''),
    'pass' => $getEnv('DB_PASS', $dbLocal['pass'] ?? ''),
];

if ($db['name'] === '' || $db['user'] === '' || $db['pass'] === '') {
    throw new RuntimeException('Database credentials are missing. Configure config/config.local.php or DB_HOST, DB_NAME, DB_USER and DB_PASS environment variables.');
}

$secrets = [
    'bot_api_key' => $getEnv('NAYRICA_BOT_API_KEY', $local['secrets']['bot_api_key'] ?? ''),
    'media_secret' => $getEnv('NAYRICA_MEDIA_SECRET', $local['secrets']['media_secret'] ?? ''),
    'cron_secret' => $getEnv('NAYRICA_CRON_SECRET', $local['secrets']['cron_secret'] ?? ''),
];

if (!defined('NAYRICA_BOT_API_KEY')) define('NAYRICA_BOT_API_KEY', (string)$secrets['bot_api_key']);
if (!defined('NAYRICA_MEDIA_SECRET')) define('NAYRICA_MEDIA_SECRET', (string)$secrets['media_secret']);
if (!defined('NAYRICA_CRON_SECRET')) define('NAYRICA_CRON_SECRET', (string)$secrets['cron_secret']);

$debugValue = $getEnv('APP_DEBUG', $appLocal['debug'] ?? false);
$debug = filter_var($debugValue, FILTER_VALIDATE_BOOLEAN);

return [
    'db' => $db,
    'secrets' => $secrets,
    'app' => [
        'name' => $getEnv('APP_NAME', $appLocal['name'] ?? 'Nayrica'),
        'url' => rtrim($getEnv('APP_URL', $appLocal['url'] ?? 'https://nayrica.ir'), '/'),
        'timezone' => $getEnv('APP_TIMEZONE', $appLocal['timezone'] ?? 'Asia/Tehran'),
        'debug' => $debug,
        'brand_color' => $getEnv('APP_BRAND_COLOR', $appLocal['brand_color'] ?? '#D1B1B0'),
    ],
];
