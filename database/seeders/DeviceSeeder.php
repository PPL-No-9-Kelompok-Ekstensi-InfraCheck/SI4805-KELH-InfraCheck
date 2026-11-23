<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Seed perangkat inventaris (Tabel 7).
     * Bergantung pada RoomSeeder dan DeviceTypeSeeder.
     */
    public function run(): void
    {
        $perangkat = [
            ['AST-AP-001',   'Access Point Wi-Fi', 'R.101',   'Access Point Cisco AP-01'],
            ['AST-PRJ-001',  'Proyektor LCD',      'R.101',   'Proyektor Epson EB-X400'],
            ['AST-PC-LAB-01', 'PC Desktop',        'LAB.01',  'PC Workstation Lab 01'],
        ];

        foreach ($perangkat as [$kodeAset, $namaJenis, $kodeRuangan, $namaPerangkat]) {
            $jenis = DeviceType::where('nama_jenis', $namaJenis)->firstOrFail();
            $ruangan = Room::where('kode_ruangan', $kodeRuangan)->firstOrFail();

            Device::firstOrCreate(
                ['kode_aset' => $kodeAset],
                [
                    'id_jenis'       => $jenis->id_jenis,
                    'id_ruangan'     => $ruangan->id_ruangan,
                    'nama_perangkat' => $namaPerangkat,
                    'kondisi'        => 'Baik',
                ]
            );
        }
    }
}
