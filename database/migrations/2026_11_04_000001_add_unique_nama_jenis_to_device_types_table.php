<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan unique index pada nama jenis perangkat sebagai pertahanan
     * terakhir di level database (validasi aplikasi tetap digunakan).
     */
    public function up(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            $table->unique('nama_jenis', 'device_types_nama_jenis_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            $table->dropUnique('device_types_nama_jenis_unique');
        });
    }
};
