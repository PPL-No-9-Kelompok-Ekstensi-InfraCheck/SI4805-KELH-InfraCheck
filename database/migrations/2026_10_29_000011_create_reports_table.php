<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id('id_laporan');
            $table->foreignId('id_pelapor')->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_perangkat')->constrained('devices', 'id_perangkat')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_ruangan')->constrained('rooms', 'id_ruangan')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_status')->constrained('report_statuses', 'id_status')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_urgensi')->constrained('urgency_levels', 'id_urgensi')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_prioritas')->nullable()->constrained('priorities', 'id_prioritas')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('id_admin_verifikator')->nullable()->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('set null');
            $table->text('deskripsi_kerusakan');
            $table->dateTime('waktu_laporan');
            $table->dateTime('waktu_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
