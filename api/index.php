<?php
// --- 1. TANGKAP CRASH PHP DAN JADIKAN JSON ---
ini_set('display_errors', '0');
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal Error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit;
    }
});

// --- 2. VERCEL CORS FIX ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

// --- 3. VERCEL AUTHORIZATION HEADER FIX ---
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (isset($_SERVER['Authorization'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['Authorization'];
    } elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        // TYPO FIXED: Menggunakan CASE_UPPER bukan CASE_CAPITAL
        $requestHeaders = array_change_key_case($requestHeaders, CASE_UPPER);
        if (isset($requestHeaders['AUTHORIZATION'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $requestHeaders['AUTHORIZATION'];
        }
    }
}

// --- 4. VERCEL SERVERLESS CACHE FIX ---
$storagePath = '/tmp/storage';
if (!is_dir($storagePath . '/framework/views')) {
    mkdir($storagePath . '/framework/views', 0777, true);
}
putenv("APP_SERVICES_CACHE=/tmp/services.php");
putenv("APP_PACKAGES_CACHE=/tmp/packages.php");
putenv("VIEW_COMPILED_PATH=$storagePath/framework/views");

// --- 5. JALANKAN LARAVEL ---
require __DIR__ . '/../public/index.php';
