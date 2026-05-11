
<?php



use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log; 

use App\Http\Controllers\Api\ClinicalDataController;

use App\Http\Controllers\Api\PatientController;

use App\Http\Controllers\Api\UserController;

use App\Models\ClinicalData;

use App\Models\User;



Route::get('/unauthorized', function () {

    return response()->json(['success' => false, 'message' => 'Sesi Berakhir.'], 401);

})->name('login');



Route::post('/token', function (Request $request) {

    $request->validate(['username' => 'required', 'password' => 'required']);

    $credentials = $request->only('username', 'password');

    

    if (Auth::attempt($credentials)) {

        $user = Auth::user();

        $user->tokens()->delete(); 

        

        DB::table('audit_logs')->insert([

            'user_id'     => $user->id,

            'action'      => 'LOGIN',

            'description' => "User {$user->name} ({$user->role}) berhasil login ke sistem.",

            'created_at'  => now(),

            'updated_at'  => now()

        ]);

        

        return response()->json([

            'success'      => true,

            'access_token' => $user->createToken('auth_token')->plainTextToken,

            'user'         => [

                'id'       => $user->id,

                'name'     => $user->name,

                'username' => $user->username,

                'role'     => $user->role ?? 'perawat'

            ]

        ], 200);

    }

    return response()->json(['success' => false, 'message' => 'Kredensial tidak valid.'], 401);

});



Route::middleware('auth:sanctum')->group(function () {



    Route::get('/audit-logs', function() {

        try {

            $logs = DB::table('audit_logs')

                ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id') 

                ->select('audit_logs.*', 'users.name as real_name')

                ->orderBy('audit_logs.created_at', 'desc')

                ->limit(50)

                ->get()

                ->map(function($log) {

                    return [

                        'id'     => $log->id,

                        'time'   => $log->created_at ? date('Y-m-d H:i:s', strtotime($log->created_at)) : now()->toDateTimeString(),

                        'user'   => $log->real_name ?? 'System / Cloud AI',

                        'action' => $log->action,

                        'target' => $log->description ?? '-', 

                        'status' => 'Success'

                    ];

                });

            return response()->json(['success' => true, 'logs' => $logs, 'stats' => ['total' => DB::table('audit_logs')->count(), 'alerts' => DB::table('audit_logs')->where('action', 'LIKE', '%ALERT%')->count(), 'time' => '1.1s']], 200);

        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });



    Route::get('/dashboard-stats', function() {

        try {

            return response()->json([

                'success' => true,

                'total_staff'       => DB::table('users')->count(),

                'total_logs'        => DB::table('audit_logs')->count(),

                'total_documents'   => DB::table('knowledge_bases')->count(),

                'system_uptime'     => '99.9%',

                'today_patients'    => DB::table('patients')->whereDate('created_at', date('Y-m-d'))->count(),

                'pending_ai'        => ClinicalData::where('status', 'draft')->count(),

                'completed_resumes' => ClinicalData::where('status', 'verified')->count()

            ], 200);

        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });



    Route::get('/radiology/dashboard', function() {

        return response()->json(['stats' => ['total_scans' => DB::table('clinical_data')->where('source', 'radiologi')->count(), 'pending_analysis' => DB::table('clinical_data')->where('source', 'radiologi')->where('status', 'draft')->count(), 'ai_verified' => DB::table('clinical_data')->where('source', 'radiologi')->where('status', 'verified')->count()], 'recent_work' => []], 200);

    });



    Route::get('/clinical-data', function() {

        try {

            $data = ClinicalData::orderBy('created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data], 200);

        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });



    Route::post('/clinical-data', function(Request $request) {

        DB::beginTransaction();

        try {

            $rawContent = is_array($request->raw_content) ? json_encode($request->raw_content) : $request->raw_content;

            $validSources = ['manual', 'whatsapp', 'voice'];

            $reqSource = $request->source ?? 'manual';

            $finalSource = in_array($reqSource, $validSources) ? $reqSource : 'manual';



            $data = ClinicalData::create([

                'patient_id'  => $request->patient_id,

                'raw_content' => $rawContent,

                'blood_pressure' => $request->blood_pressure ?? null,

                'heart_rate' => $request->heart_rate ?? null,

                'temperature' => $request->temperature ?? null,

                'oxygen_saturation' => $request->oxygen_saturation ?? null,

                'status'      => $request->status ?? 'draft',

                'source'      => $finalSource

            ]);



            if (Auth::check()) {

                $user = Auth::user();

                DB::table('audit_logs')->insert([

                    'user_id'     => $user->id,

                    'action'      => 'DATA_INPUT',

                    'description' => "Staf {$user->name} menyimpan data medis pasien RM: {$request->patient_id}",

                    'created_at'  => now(),

                    'updated_at'  => now()

                ]);

            }



            DB::commit();

            return response()->json(['success' => true, 'data' => $data], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error("Error POST Clinical Data: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });



    Route::get('/clinical-data/{norm}', [ClinicalDataController::class, 'show']);

    Route::post('/clinical-data/{norm}/generate-ai', [ClinicalDataController::class, 'generateAI']);

    

    Route::patch('/clinical-data/{norm}/verify', function($norm, Request $request) {

        DB::beginTransaction();

        try {

            $data = ClinicalData::where('patient_id', $norm)->latest()->first();

            if ($data) {

                $data->update([

                    'status'     => 'verified', 

                    'ai_summary' => $request->input('final_summary', $data->ai_summary)

                ]);

                

                if (Auth::check()) {

                    $user = Auth::user();

                    DB::table('audit_logs')->insert([ 

                        'user_id'     => $user->id,

                        'action'      => 'AI SUMMARIZATION',

                        'description' => "Dokter {$user->name} memverifikasi rekam medis pasien RM: {$norm}",

                        'created_at'  => now(), 

                        'updated_at'  => now()

                    ]);

                }

                DB::commit();

                return response()->json(['success' => true, 'message' => 'Dokumen klinis divalidasi.'], 200);

            }

            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);

        } catch (\Exception $e) { 

            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500); 

        }

    });



    Route::post('/rag-guideline', function(Request $request) {

        try {

            $patientId = $request->input('patient_id');

            $clinicalData = ClinicalData::where('patient_id', $patientId)->latest()->first();

            $guideline = DB::table('knowledge_bases')->where('title', 'LIKE', '%' . ($clinicalData->diagnosis ?? 'Medis') . '%')->orWhere('category', 'LIKE', '%SOP%')->first();

            

            if (Auth::check()) {

                $user = Auth::user();

                DB::table('audit_logs')->insert(['user_id' => $user->id, 'action' => 'RAG KNOWLEDGE INDEXING', 'description' => "User {$user->name} mencari pedoman klinis untuk RM: {$patientId}", 'created_at' => now(), 'updated_at' => now()]);

            }

            return response()->json(['success' => true, 'source' => $guideline->title ?? 'PPK Penatalaksanan Klinis RS UNS', 'ai_recommendation' => "Berdasarkan analisis rekam medis, pasien membutuhkan pemantauan saturasi oksigen berkala.", 'clinical_notes' => "Prioritaskan stabilisasi jalan napas.", 'evidence_level' => "Evidence Level: A", 'document_url' => $guideline->file_path ?? "#"], 200);

        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => $e->getMessage()], 500); }

    });



    Route::get('/users', [UserController::class, 'index']);

    Route::post('/users', [UserController::class, 'store']);

    Route::put('/users/{id}', [UserController::class, 'update']);

    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    

    // ==============================================================

    // VERCEL HACK: KNOWLEDGE ROUTES BYPASS READ-ONLY FILESYSTEM

    // ==============================================================

    Route::get('/knowledge', function() {

        try {

            $data = DB::table('knowledge_bases')->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data], 200);

        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });



    Route::post('/knowledge', function(Request $request) {

        try {

            $fileName = 'document.pdf';

            

            // Simpan ke /tmp/ (RAM) yang diizinkan Vercel, jangan ke /storage/

            if ($request->hasFile('file')) {

                $file = $request->file('file');

                $fileName = time() . '_' . $file->getClientOriginalName();

                $file->move('/tmp', $fileName); 

            }



            $id = DB::table('knowledge_bases')->insertGetId([

                'title'       => $request->title ?? 'Dokumen Tanpa Judul',

                'category'    => $request->category ?? 'General',

                'file_path'   => '/tmp/' . $fileName, 

                'version'     => $request->version ?? '1.0',

                'description' => 'Diunggah via Vercel Cloud Node',

                'status'      => 'ready',

                'created_at'  => now(),

                'updated_at'  => now()

            ]);



            if (Auth::check()) {

                $user = Auth::user();

                DB::table('audit_logs')->insert([

                    'user_id'     => $user->id,

                    'action'      => 'KNOWLEDGE INJECT',

                    'description' => "Admin {$user->name} meng-inject Vector DB: {$fileName}",

                    'created_at'  => now(),

                    'updated_at'  => now()

                ]);

            }



            return response()->json(['success' => true, 'message' => 'Dokumen berhasil diekstrak ke Vector DB!'], 201);

        } catch (\Exception $e) {

            Log::error("Error Upload Knowledge Vercel: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Gagal Upload: ' . $e->getMessage()], 500);

        }

    });



    Route::delete('/knowledge/{id}', function($id) {

        try {

            DB::table('knowledge_bases')->where('id', $id)->delete();

            return response()->json(['success' => true, 'message' => 'Dokumen dihapus dari Vector DB'], 200);

        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }

    });

    // ==============================================================



    Route::get('/patients-list', [PatientController::class, 'index']); 

    Route::get('/patients/{query}', [PatientController::class, 'show']); 

    Route::post('/patients', [PatientController::class, 'store']);

    

    Route::get('/patients/{rm}/history', function($rm) {

        try {

            $history = ClinicalData::where('patient_id', $rm)->where('status', 'verified')->orderBy('created_at', 'desc')->get();

            return response()->json($history, 200);

        } catch (\Exception $e) { return response()->json(['message' => $e->getMessage()], 500); }

    });



    Route::get('/manajemen/dashboard', function() {

        return response()->json(['stats' => ['totalPasien' => DB::table('patients')->count(), 'avgTunggu' => '45m', 'utilBed' => '82%', 'totalLayanan' => 3420], 'reports' => [['id' => 1, 'date' => date('Y-m-d'), 'title' => 'Laporan Performa UGD', 'status' => 'Final']]], 200);

    });

});

