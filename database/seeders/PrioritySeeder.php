<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Seed prioritas penanganan (Tabel 10).
     */
    public function run(): void
    {
        foreach (['Rendah', 'Sedang', 'Tinggi', 'Mendesak'] as $namaPrioritas) {
            Priority::firstOrCreate(['nama_prioritas' => $namaPrioritas]);
        }
    }
}
