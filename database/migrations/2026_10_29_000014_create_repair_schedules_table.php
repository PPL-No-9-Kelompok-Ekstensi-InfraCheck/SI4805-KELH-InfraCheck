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
        Schema::create('repair_schedules', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->foreignId('id_penugasan')->unique()->constrained('assignments', 'id_penugasan')->onUpdate('cascade')->onDelete('cascade');
            $table->dateTime('waktu_mulai');
            $table->dateTime('target_selesai');
            $table->text('catatan_jadwal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_schedules');
    }
};
