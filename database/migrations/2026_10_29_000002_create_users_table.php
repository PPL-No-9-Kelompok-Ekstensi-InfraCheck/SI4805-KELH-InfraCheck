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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->foreignId('id_peran')->constrained('roles', 'id_peran')->onUpdate('cascade')->onDelete('restrict');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('kata_sandi_hash');
            $table->string('nim_nip')->nullable();
            $table->string('unit')->nullable();
            $table->string('kontak')->nullable();
            $table->boolean('akun_aktif')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
