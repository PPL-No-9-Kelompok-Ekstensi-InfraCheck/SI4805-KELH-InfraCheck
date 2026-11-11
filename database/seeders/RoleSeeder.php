<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed peran pengguna (Tabel 1).
     */
    public function run(): void
    {
        foreach (['Pelapor', 'Admin', 'Teknisi', 'Pimpinan'] as $namaPeran) {
            Role::firstOrCreate(['nama_peran' => $namaPeran]);
        }
    }
}
