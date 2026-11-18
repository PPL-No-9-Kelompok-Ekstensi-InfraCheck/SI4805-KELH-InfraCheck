<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Seed ruangan (Tabel 4). Bergantung pada gedung yang dihasilkan BuildingSeeder.
     */
    public function run(): void
    {
        $gedungUtama = Building::where('kode_gedung', 'GKB-1')->firstOrFail();
        $gedungLab = Building::where('kode_gedung', 'LAB-TI')->firstOrFail();

        Room::firstOrCreate(
            ['id_gedung' => $gedungUtama->id_gedung, 'kode_ruangan' => 'R.101'],
            ['nama_ruangan' => 'Ruang Kuliah 101']
        );

        Room::firstOrCreate(
            ['id_gedung' => $gedungLab->id_gedung, 'kode_ruangan' => 'LAB.01'],
            ['nama_ruangan' => 'Laboratorium Komputer Jaringan']
        );
    }
}
