<?php

namespace Database\Seeders;

use App\Models\UrgencyLevel;
use Illuminate\Database\Seeder;

class UrgencyLevelSeeder extends Seeder
{
    /**
     * Seed tingkat urgensi (Tabel 9).
     */
    public function run(): void
    {
        foreach (['Rendah', 'Sedang', 'Tinggi', 'Kritis'] as $namaUrgensi) {
            UrgencyLevel::firstOrCreate(['nama_urgensi' => $namaUrgensi]);
        }
    }
}
