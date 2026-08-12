<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Polyfill for mb_split if mbregex/mbstring is not enabled on server
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) {
        return preg_split('/' . str_replace('/', '\/', $pattern) . '/u', $string, $limit);
    }
}

// Register the Composer autoloader with dynamic detection for split folder layout in cPanel
$basePath = __DIR__.'/..';
foreach (['laravel-app', 'hutang-app', 'core', 'laravel'] as $dir) {
    if (file_exists(__DIR__.'/../'.$dir.'/vendor/autoload.php')) {
        $basePath = __DIR__.'/../'.$dir;
        break;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

// Set public path dynamically if deployed in split structure
if ($basePath !== __DIR__.'/..') {
    $app->usePublicPath(__DIR__);
}

$app->handleRequest(Request::capture());
