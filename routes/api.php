<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\ClinicalData;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rute ini HARUS BISA diakses (Jangan masukkan dalam middleware auth dulu untuk testing)
Route::get('/dashboard-stats', function() {
    return response()->json([
        'success' => true,
        'total_staff' => DB::table('users')->count(),
        'total_logs' => DB::table('audit_logs')->count(),
        'total_documents' => DB::table('knowledge_bases')->count(),
        'system_uptime' => '99.9%'
    ], 200);
});

// Rute token kamu
Route::post('/token', function (Request $request) {
    // ... kode login kamu yang lama ...
    return response()->json(['message' => 'Login route active']); 
});

// Masukkan rute lainnya di bawah sini...
