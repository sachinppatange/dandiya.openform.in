<?php
/**
 * Local XAMPP + live register.openform.in database.
 * Both db.php and config/wa_config.php load this file.
 *
 * Local  → http://localhost/ganesh/     database: ganesh / root
 * Live   → https://register.openform.in/  database: fill LIVE block below
 *          (on cPanel the MySQL host is still "localhost")
 *
 * Optional: copy db.live.php.example to db.live.php on the server
 * to keep the live password out of this file.
 */
if (defined('DB_HOST')) {
    return;
}

function app_db_http_host(): string
{
    $h = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $h = strtolower(preg_replace('/:\d+$/', '', $h) ?: '');
    return $h;
}

function app_db_is_local(): bool
{
    $env = strtolower((string) (getenv('APP_ENV') ?: getenv('APP_ENVIRONMENT') ?: ''));
    if ($env === 'production' || $env === 'live') {
        return false;
    }
    if ($env === 'local' || $env === 'development') {
        return true;
    }
    $h = app_db_http_host();
    if ($h === '' || $h === 'localhost' || $h === '127.0.0.1' || $h === '::1') {
        return true;
    }
    if (str_contains($h, 'localhost') || str_ends_with($h, '.local') || str_ends_with($h, '.test')) {
        return true;
    }
    if (preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $h)) {
        return true;
    }
    return false;
}

$localDb = [
    'host' => 'localhost',
    'name' => 'dandiyanew',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];

$liveDb = [
    'host' => 'localhost',
    'name' => 'u750208840_dandiyadb',
    'user' => 'u750208840_dandiyauser',
    'password' => 'Sachin@1078#',
    'charset' => 'utf8mb4',
];

$liveFile = __DIR__ . '/db.live.php';
if (is_file($liveFile)) {
    $fromFile = require $liveFile;
    if (is_array($fromFile)) {
        $liveDb = array_merge($liveDb, $fromFile);
    }
}

$cfg = app_db_is_local() ? $localDb : $liveDb;

define('DB_HOST', (string) $cfg['host']);
define('DB_NAME', (string) $cfg['name']);
define('DB_USER', (string) $cfg['user']);
define('DB_PASSWORD', (string) $cfg['password']);
define('DB_CHARSET', (string) ($cfg['charset'] ?? 'utf8mb4'));
define('DB_IS_LOCAL', app_db_is_local());
