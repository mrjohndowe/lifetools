<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (preg_match('~^/assets/[a-z0-9-]+\.(?:css|js|svg)$~D',$path) && is_file(__DIR__ . $path)) {
    return false;
}
if ($path === '/release.php') {
    return false;
}
if ($path !== '/' && $path !== '/index.php') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/index.php';

