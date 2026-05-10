<?php
// SERVERLESS CACHE FIX
$tmp = ['/tmp/storage/framework/views', '/tmp/storage/framework/cache', '/tmp/storage/framework/sessions', '/tmp/bootstrap/cache'];
foreach ($tmp as $dir) { if (!is_dir($dir)) { mkdir($dir, 0777, true); } }

putenv("APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php");
putenv("APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php");
putenv("VIEW_COMPILED_PATH=/tmp/storage/framework/views");

// JALANKAN LARAVEL
require __DIR__ . '/../public/index.php';
