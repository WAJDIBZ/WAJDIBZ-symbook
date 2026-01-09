<?php
// Router script for PHP's built-in server (php -S).
// This implementation is compatible with symfony/runtime.

$uriPath = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$publicDir = __DIR__ . '/public';
$publicReal = realpath($publicDir);

// Serve existing files directly (css/js/images, etc.)
if ($publicReal) {
    $candidate = realpath($publicReal . $uriPath);
    if ($candidate && str_starts_with($candidate, $publicReal) && is_file($candidate)) {
        return false;
    }
}

// Make symfony/runtime think the front controller is the entry script.
$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

chdir($publicDir);

return require $_SERVER['SCRIPT_FILENAME'];
