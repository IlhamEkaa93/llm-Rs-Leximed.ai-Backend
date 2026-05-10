<?php
// --- VERCEL ENTRY POINT ---

// Pindahkan cache ke /tmp (Wajib untuk Vercel)
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

// Jalankan Laravel (CORS akan dihandle oleh Middleware Laravel)
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['STATUS' => 'CRASH', 'PESAN' => $e->getMessage()]);
    exit;
}
