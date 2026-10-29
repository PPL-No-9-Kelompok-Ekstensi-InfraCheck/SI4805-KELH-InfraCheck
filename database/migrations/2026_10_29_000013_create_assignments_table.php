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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id('id_penugasan');
            $table->foreignId('id_laporan')->constrained('reports', 'id_laporan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('id_teknisi')->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_admin_penugas')->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('restrict');
            $table->dateTime('waktu_penugasan');
            $table->string('status_penugasan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
