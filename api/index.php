<?php
// --- JALUR VIP: VERCEL CORS PREFLIGHT BYPASS ---
// Jika browser nanya (OPTIONS), langsung kasih lampu hijau 200 OK!
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE, PATCH');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
    http_response_code(200);
    exit;
}

// Pastikan respon utama juga punya header yang sama
header('Access-Control-Allow-Origin: *');

// --- VERCEL SERVERLESS FIX ---
$tmpCache = '/tmp/laravel/cache';
$tmpStorage = '/tmp/laravel/storage';

if (!is_dir($tmpCache)) { mkdir($tmpCache, 0777, true); }
if (!is_dir($tmpStorage . '/framework/views')) { mkdir($tmpStorage . '/framework/views', 0777, true); }

putenv("APP_SERVICES_CACHE={$tmpCache}/services.php");
putenv("APP_PACKAGES_CACHE={$tmpCache}/packages.php");
putenv("APP_CONFIG_CACHE={$tmpCache}/config.php");
putenv("APP_ROUTES_CACHE={$tmpCache}/routes.php");
putenv("APP_EVENTS_CACHE={$tmpCache}/events.php");
putenv("VIEW_COMPILED_PATH={$tmpStorage}/framework/views");

try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['STATUS' => 'CRASH', 'PESAN' => $e->getMessage()]);
    exit;
}
