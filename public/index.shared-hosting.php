<?php

/**
 * هذا الملف مخصص للـ Shared Hosting فقط.
 * بعد رفع الملفات، انسخ هذا الملف إلى public_html/index.php
 * وعدّل المسار APP_PATH ليطابق مسار مشروعك على السيرفر.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ← عدّل هذا المسار ليطابق مكان المشروع على السيرفر
define('APP_PATH', __DIR__ . '/../ministry-app');

// Maintenance mode
if (file_exists($maintenance = APP_PATH . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require APP_PATH . '/vendor/autoload.php';

// Bootstrap Laravel
/** @var Application $app */
$app = require_once APP_PATH . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
