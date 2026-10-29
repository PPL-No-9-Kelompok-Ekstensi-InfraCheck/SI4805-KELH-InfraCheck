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
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->foreignId('id_laporan')->constrained('reports', 'id_laporan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('id_status')->constrained('report_statuses', 'id_status')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_pengubah')->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('restrict');
            $table->dateTime('waktu_perubahan');
            $table->text('catatan_perubahan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
