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
        Schema::create('repair_progress', function (Blueprint $table) {
            $table->id('id_progres');
            $table->foreignId('id_penugasan')->constrained('assignments', 'id_penugasan')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('persentase');
            $table->text('catatan_progres');
            $table->dateTime('waktu_progres');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_progress');
    }
};
