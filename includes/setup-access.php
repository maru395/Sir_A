<?php
// Used by database/createdb.php and database/populatedb.php to protect setup requests.
declare(strict_types=1);
require_once __DIR__ . '/request-helpers.php';

// These pages change the database, so only allow direct local requests.
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header(
    "Content-Security-Policy: default-src 'self'; style-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'"
);
$host = strtolower(parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?: '');
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'none';
$mode = $_SERVER['HTTP_SEC_FETCH_MODE'] ?? 'navigate';
$allowed = in_array($remote, ['127.0.0.1', '::1'], true)
&& in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
&& in_array($site, ['none', 'same-origin'], true)
&& $mode === 'navigate';
foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
    if (isset($_SERVER[$header])) {
        $origin = parse_url($_SERVER[$header], PHP_URL_HOST);
        $port = parse_url($_SERVER[$header], PHP_URL_PORT) ?: (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 443 : 80);
        $allowed = $allowed
        && $origin === $host
        && $port === (int) ($_SERVER['SERVER_PORT'] ?? 80);
    }
}
if (!$allowed || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(403);
    die('Setup can only be opened directly on this computer using localhost or 127.0.0.1.');
}

// Keep two setup requests from changing the database at the same time.
$setupKey = substr(hash('sha256', App::STORAGE_ID), 0, 20);
$setupLock = fopen(App::temporaryDirectory() . '/aviton-setup-' . $setupKey . '-setup.lock', 'c+');
if (!$setupLock || !flock($setupLock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    die('Setup is already running. Try again shortly.');
}
