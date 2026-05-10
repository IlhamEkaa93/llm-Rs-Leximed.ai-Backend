<?php
// Jembatan Vercel dengan Penangkap Error Darurat
try {
    // Jalankan Mesin Laravel
    require __DIR__ . '/../public/index.php';
    
} catch (\Throwable $e) {
    // JIKA LARAVEL CRASH (500), TANGKAP DAN PAKSA TAMPILKAN!
    http_response_code(500);
    
    // Paksa kirim header CORS agar browser tidak memblokir pesan error ini
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    // Cetak error aslinya
    echo json_encode([
        'STATUS_DARURAT' => 'LARAVEL_CRASH',
        'PESAN_ERROR_ASLI' => $e->getMessage(),
        'FILE_PENYEBAB' => $e->getFile(),
        'BARIS' => $e->getLine()
    ]);
    exit;
}
