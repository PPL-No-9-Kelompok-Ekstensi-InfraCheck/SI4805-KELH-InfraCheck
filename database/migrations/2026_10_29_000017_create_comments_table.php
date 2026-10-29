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
        Schema::create('comments', function (Blueprint $table) {
            $table->id('id_tanggapan');
            $table->foreignId('id_laporan')->constrained('reports', 'id_laporan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('id_pengguna')->constrained('users', 'id_pengguna')->onUpdate('cascade')->onDelete('cascade');
            $table->text('isi_tanggapan');
            $table->dateTime('waktu_tanggapan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
