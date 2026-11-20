<?php
/**
 * Copy this file to config.local.php and replace the placeholders.
 * Never commit config.local.php or share it publicly.
 */
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'YOUR_DATABASE_NAME',
        'user' => 'YOUR_DATABASE_USER',
        'pass' => 'YOUR_DATABASE_PASSWORD',
    ],
    'secrets' => [
        'bot_api_key' => 'REPLACE_WITH_NEW_BOT_API_KEY',
        'media_secret' => 'REPLACE_WITH_RANDOM_MEDIA_SECRET',
        'cron_secret' => 'REPLACE_WITH_RANDOM_CRON_SECRET',
    ],
    'app' => [
        'name' => 'Nayrica',
        'url' => 'https://nayrica.ir',
        'timezone' => 'Asia/Tehran',
        'debug' => false,
        'brand_color' => '#D1B1B0',
    ],
];
