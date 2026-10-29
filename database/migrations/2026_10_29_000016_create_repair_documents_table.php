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
        Schema::create('repair_documents', function (Blueprint $table) {
            $table->id('id_dokumentasi');
            $table->foreignId('id_progres')->constrained('repair_progress', 'id_progres')->onUpdate('cascade')->onDelete('cascade');
            $table->string('lokasi_berkas');
            $table->string('jenis_dokumentasi');
            $table->dateTime('waktu_unggah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_documents');
    }
};
