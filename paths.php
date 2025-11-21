<?php
// Application root (filesystem path)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

// Base URL for the application (relative to document root)
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
    $docRoot = rtrim(str_replace('\\', '/', (string)$docRoot), '/');
    $appRoot = rtrim(str_replace('\\', '/', APP_ROOT), '/');

    $baseUrl = '';
    if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
        $baseUrl = substr($appRoot, strlen($docRoot));
    }

    $baseUrl = rtrim($baseUrl, '/');
    define('BASE_URL', $baseUrl);
}

if (!function_exists('url_for')) {
    function url_for(string $path): string
    {
        $path = ltrim($path, '/');
        $prefix = BASE_URL === '' ? '' : BASE_URL;
        return $prefix . '/' . $path;
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path): void
    {
        header('Location: ' . url_for($path));
        exit;
    }
}
