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
        Schema::create('devices', function (Blueprint $table) {
            $table->id('id_perangkat');
            $table->foreignId('id_jenis')->constrained('device_types', 'id_jenis')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('id_ruangan')->constrained('rooms', 'id_ruangan')->onUpdate('cascade')->onDelete('restrict');
            $table->string('kode_aset')->unique();
            $table->string('nama_perangkat');
            $table->string('kondisi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
