<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Seed gedung kampus (Tabel 3).
     */
    public function run(): void
    {
        Building::firstOrCreate(
            ['kode_gedung' => 'GKB-1'],
            ['nama_gedung' => 'Gedung Kuliah Bersama 1']
        );

        Building::firstOrCreate(
            ['kode_gedung' => 'LAB-TI'],
            ['nama_gedung' => 'Gedung Laboratorium Teknologi Informasi']
        );
    }
}
