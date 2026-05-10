<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Rute Standar
Route::get('/dashboard-stats', function() {
    return response()->json([
        'success' => true,
        'total_staff' => DB::table('users')->count(),
        'total_logs' => DB::table('audit_logs')->count(),
        'total_documents' => DB::table('knowledge_bases')->count(),
        'system_uptime' => '99.9%'
    ], 200);
});

// Rute Cadangan (Tanpa Prefix /api jika Vercel memotongnya)
Route::get('dashboard-stats', function() {
    return response()->json(['success' => true, 'message' => 'Fallback Route Active']);
});

Route::post('/token', function (Request $request) {
    return response()->json(['status' => 'Auth active']);
});
