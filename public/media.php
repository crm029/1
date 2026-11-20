<?php
// Serve private client documents, receipts and staff photos through expiring signed URLs.
$relativePath = ltrim((string)($_GET['file'] ?? ''), '/');
$expires = (int)($_GET['expires'] ?? 0);
$signature = (string)($_GET['signature'] ?? '');
$config = require __DIR__ . '/../config/config.php';
$secret = $config['secrets']['media_secret'] ?? '';

$deny = static function () {
    http_response_code(404);
    exit('Not found');
};

if ($secret === '' || $expires < time() || $expires > time() + 3600
    || !preg_match('#^uploads/(clients|users)/[A-Za-z0-9._/-]+$#', $relativePath)
    || strpos($relativePath, '..') !== false
    || strpos($relativePath, '\\') !== false) {
    $deny();
}

$expected = hash_hmac('sha256', $relativePath . '|' . $expires, $secret);
if ($signature === '' || !hash_equals($expected, $signature)) {
    $deny();
}

$uploadsRoot = realpath(__DIR__ . '/uploads');
$filePath = realpath(__DIR__ . '/' . $relativePath);
if (!$uploadsRoot || !$filePath || !is_file($filePath)
    || strpos($filePath, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0) {
    $deny();
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filePath);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    $deny();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
