<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser; // Pastikan library sudah terinstall

class KnowledgeController extends Controller
{
    public function index() {
        return response()->json(KnowledgeBase::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request) {
        $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'file' => 'required|file|mimes:pdf,txt|max:10240',
        ]);

        try {
            if ($request->hasFile('file')) {
                // 1. Simpan file fisik
                $path = $request->file('file')->store('knowledge_files', 'public');
                
                // 2. Baca teks dari PDF
                $parser = new Parser();
                $pdf = $parser->parseFile($request->file('file')->getPathname());
                $text = $pdf->getText(); 

                // 3. Simpan ke database dengan kolom 'content'
                $kb = KnowledgeBase::create([
                    'title'       => $request->title,
                    'category'    => $request->category,
                    'version'     => $request->version ?? '1.0',
                    'description' => $request->description ?? '-',
                    'file_path'   => $path,
                    'content'     => $text, // <-- INI YANG AKAN DIPAKAI RAG
                    'status'      => 'ready' 
                ]);

                return response()->json(['success' => true, 'data' => $kb], 201);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi RAG untuk mencari jawaban
    public function search(Request $request) {
        $query = $request->input('message');
        
        $results = KnowledgeBase::where('content', 'LIKE', '%' . $query . '%')
                                ->orWhere('title', 'LIKE', '%' . $query . '%')
                                ->limit(3)
                                ->get();

        return response()->json([
            'success' => true, 
            'data' => $results
        ]);
    }

    public function destroy($id) {
        try {
            $kb = KnowledgeBase::findOrFail($id);
            Storage::disk('public')->delete($kb->file_path);
            $kb->delete();
            return response()->json(['success' => true, 'message' => 'Dokumen dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}