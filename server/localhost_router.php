<?php
// router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve the file if it exists (e.g. CSS, JS, images)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything through index.php
require_once __DIR__ . '/index.php';
