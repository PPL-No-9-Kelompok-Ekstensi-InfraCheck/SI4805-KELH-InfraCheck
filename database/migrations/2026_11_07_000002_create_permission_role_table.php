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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('id_peran')
                ->constrained('roles', 'id_peran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_hak_akses')
                ->constrained('permissions', 'id_hak_akses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['id_peran', 'id_hak_akses']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
