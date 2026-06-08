<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ClinicalData
 *
 * Tabel ini bertindak sebagai "Pusat Data Klinis" yang menyimpan:
 * 1. Data Vital Sign (TTV) yang diinput oleh asisten/perawat.
 * 2. Narasi/Keluhan mentah pasien (Raw Content).
 * 3. Hasil ringkasan medis berbasis AI (Llama 3.3 / Gemini Vision).
 * 4. Berkas penunjang radiologi PACS (gambar, kesan, nama radiolog).
 */
class ClinicalData extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database PostgreSQL.
     */
    protected $table = 'clinical_data';

    /**
     * Kolom yang dapat diisi melalui Mass Assignment.
     *
     * FIX KRITIS (v2): Tambahkan semua kolom radiologi ke $fillable.
     * Sebelumnya kolom radiology_* tidak ada di sini, sehingga Laravel
     * diam-diam mengabaikan nilainya saat create() / update(),
     * menyebabkan foto dan data radiologi selalu tersimpan sebagai null.
     */
    protected $fillable = [
        // ── Data Identitas & Sumber ──
        'patient_id',           // NORM Pasien (Foreign Key/Link)
        'source',               // Sumber data: manual, voice, whatsapp, radiologi

        // ── Data Vital Sign (TTV) ──
        'blood_pressure',       // Tensi / Tekanan Darah (Contoh: 120/80)
        'heart_rate',           // Nadi / Detak Jantung
        'temperature',          // Suhu Tubuh
        'oxygen_saturation',    // SpO2 / Saturasi Oksigen

        // ── Data Konten Medis ──
        'raw_content',          // Narasi keluhan mentah atau transkrip voice
        'ai_summary',           // Output ringkasan SOAP / rekam medis dari AI
        'status',               // draft (proses asisten) atau verified (disahkan dokter)

        // ── Data Penunjang Radiologi PACS ──
        'radiology_modality',   // Jenis pemeriksaan: Toraks X-Ray, MRI, CT Scan, USG
        'radiology_kesan',      // Kesan/impresi klinis dari laporan radiolog
        'radiology_image',      // URL path gambar PACS tersimpan di public/storage/radiology/
        'radiology_doctor',     // Nama dokter / ahli madya radiologi pemeriksa
    ];

    /**
     * Casting otomatis untuk memastikan tipe data konsisten saat dikirim ke Frontend.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status'     => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    | Scopes mempermudah query di Controller (Logic Reusable).
    */

    /**
     * Mengambil data yang sudah divalidasi oleh dokter.
     * Digunakan untuk Histori Rekam Medis di DataRekamMedis.jsx.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Mengambil draf terbaru (Input asisten yang belum diringkas AI/Dokter).
     */
    public function scopeLatestDraft($query, $patientId)
    {
        return $query->where('patient_id', $patientId)
                     ->where('status', 'draft')
                     ->latest();
    }

    /**
     * Mendapatkan profil klinis lengkap (TTV + Narasi + Radiologi) berdasarkan NORM.
     */
    public function scopeFullProfile($query, $patientId)
    {
        return $query->where('patient_id', $patientId)
                     ->latest();
    }

    /**
     * Scope khusus: Mengambil histori kunjungan terverifikasi beserta data radiologi.
     * Digunakan oleh route GET /patients/{rm}/history.
     */
    public function scopeVerifiedHistory($query, $patientId)
    {
        return $query->where('patient_id', $patientId)
                     ->where('status', 'verified')
                     ->orderBy('created_at', 'desc');
    }
}