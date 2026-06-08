<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk menyuntikkan kolom penunjang radiologi live.
     */
    public function up(): void
    {
        Schema::table('clinical_data', function (Blueprint $blueprint) {
            // Menambahkan kolom baru jika belum terwujud di pgAdmin
            if (!Schema::hasColumn('clinical_data', 'radiology_modality')) {
                $blueprint->string('radiology_modality')->nullable()->after('status');
            }
            if (!Schema::hasColumn('clinical_data', 'radiology_kesan')) {
                $blueprint->text('radiology_kesan')->nullable()->after('radiology_modality');
            }
            if (!Schema::hasColumn('clinical_data', 'radiology_image')) {
                $blueprint->text('radiology_image')->nullable()->after('radiology_kesan');
            }
            if (!Schema::hasColumn('clinical_data', 'radiology_doctor')) {
                $blueprint->string('radiology_doctor')->nullable()->after('radiology_image');
            }
        });
    }

    /**
     * Balikkan struktur tabel (Drop kolom jika rollback).
     */
    public function down(): void
    {
        Schema::table('clinical_data', function (Blueprint $blueprint) {
            // Mengamankan proses rollback struktur kolom di pgAdmin rs_uns_db
            $blueprint->dropColumn(['radiology_modality', 'radiology_kesan', 'radiology_image', 'radiology_doctor']);
        });
    }
};