<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // 1. GET: Mengambil semua data user
    public function index()
    {
        try {
            $users = User::query()->orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true, 
                'data'    => $users
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error GET Users: " . $e->getMessage());
            return response()->json([
                'success'      => false, 
                'message'      => 'Gagal mengambil data staf.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    // 2. POST: Membuat user baru
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'name'     => 'required|string',
            'password' => 'required|string|min:6',
            'role'     => 'required|string'
        ]);

        try {
            $user = User::create([
                'username' => $request->username,
                'name'     => $request->name,
                'email'    => $request->email ?? $request->username . '@leximed.com',
                'role'     => $request->role,
                // Kolom 'unit' dan 'status' DIABAIKAN agar tidak error dengan PostgreSQL
                'password' => Hash::make($request->password), 
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Staf berhasil didaftarkan.', 
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error POST User: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal mendaftar staf: ' . $e->getMessage()
            ], 500);
        }
    }

    // 3. PUT: Mengupdate user yang ada berdasarkan ID
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $dataToUpdate = [
                'username' => $request->username ?? $user->username,
                'name'     => $request->name ?? $user->name,
                'email'    => $request->email ?? $user->email,
                'role'     => $request->role ?? $user->role,
            ];

            if ($request->filled('password')) {
                $dataToUpdate['password'] = Hash::make($request->password);
            }

            $user->update($dataToUpdate);

            return response()->json([
                'success' => true, 
                'message' => 'Data staf berhasil diperbarui.', 
                'data'    => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error PUT User ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal memperbarui staf: ' . $e->getMessage()
            ], 500);
        }
    }

    // 4. DELETE: Menghapus user berdasarkan ID
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true, 
                'message' => 'Staf berhasil dihapus dari sistem.'
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error DELETE User ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menghapus staf: ' . $e->getMessage()
            ], 500);
        }
    }
}
