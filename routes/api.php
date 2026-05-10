<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ClinicalDataController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\KnowledgeController;
use App\Models\ClinicalData;

/* --- 1. AUTHENTICATION (Public) --- */
Route::post('/token', function (Request $request) {
    $credentials = $request->only('username', 'password');
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ], 200);
    }
    return response()->json(['success' => false, 'message' => 'Kredensial tidak valid.'], 401);
});

/* --- 2. PROTECTED ROUTES --- */
Route::middleware('auth:sanctum')->group(function () {

    // Stats Dashboard
    Route::get('/dashboard-stats', function() {
        return response()->json([
            'success' => true,
            'total_staff' => DB::table('users')->count(),
            'total_logs' => DB::table('clinical_data')->count(),
            'total_documents' => DB::table('knowledge_bases')->count(),
            'system_uptime' => '99.9%',
            'today_patients' => DB::table('patients')->whereDate('created_at', date('Y-m-d'))->count(),
            'pending_ai' => ClinicalData::where('status', 'draft')->count(),
            'completed_resumes' => ClinicalData::where('status', 'verified')->count()
        ]);
    });

    // Audit Logs
    Route::get('/audit-logs', function() {
        $logs = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.name as real_name')
            ->orderBy('audit_logs.created_at', 'desc')->limit(50)->get();
        return response()->json(['success' => true, 'logs' => $logs]);
    });

    // Master Data Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Patients & Clinical Data
    Route::get('/patients-list', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/clinical-data', function() { return response()->json(['success' => true, 'data' => ClinicalData::latest()->get()]); });
    
    // RAG Engine (AI Recommendation)
    Route::post('/rag-guideline', function(Request $request) {
        return response()->json([
            'success' => true,
            'ai_recommendation' => "Gunakan protokol standar RS UNS untuk stabilisasi pasien.",
            'clinical_notes' => "Pantau vital sign secara berkala."
        ]);
    });
});
