<?php

namespace Database\Seeders;

use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use Illuminate\Database\Seeder;

class DeviceTypeSeeder extends Seeder
{
    /**
     * Seed jenis perangkat (Tabel 6). Bergantung pada InfrastructureCategorySeeder.
     */
    public function run(): void
    {
        $jenis = [
            ['Jaringan & Internet', 'Access Point Wi-Fi'],
            ['Komputer & Periferal', 'PC Desktop'],
            ['Audio Visual & Multimedia', 'Proyektor LCD'],
        ];

        foreach ($jenis as [$namaKategori, $namaJenis]) {
            $kategori = InfrastructureCategory::where('nama_kategori', $namaKategori)->firstOrFail();

            DeviceType::firstOrCreate([
                'id_kategori' => $kategori->id_kategori,
                'nama_jenis'  => $namaJenis,
            ]);
        }
    }
}
