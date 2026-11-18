<?php

namespace Database\Seeders;

use App\Models\InfrastructureCategory;
use Illuminate\Database\Seeder;

class InfrastructureCategorySeeder extends Seeder
{
    /**
     * Seed kategori infrastruktur (Tabel 5).
     */
    public function run(): void
    {
        foreach ([
            'Jaringan & Internet',
            'Komputer & Periferal',
            'Audio Visual & Multimedia',
        ] as $namaKategori) {
            InfrastructureCategory::firstOrCreate(['nama_kategori' => $namaKategori]);
        }
    }
}
