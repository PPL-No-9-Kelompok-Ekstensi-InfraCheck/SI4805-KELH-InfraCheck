<?php

namespace Tests\Browser;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Report;
use App\Models\Room;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi10')]
class Pbi10PembuatanLaporanTest extends DuskTestCase
{
    /**
     * PBI 10: Pelapor dapat membuat laporan kerusakan melalui browser.
     */
    public function test_pelapor_dapat_membuat_laporan_kerusakan(): void
    {
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();
        $device = Device::create([
            'id_jenis' => DeviceType::firstOrFail()->id_jenis,
            'id_ruangan' => Room::firstOrFail()->id_ruangan,
            'kode_aset' => 'LP-DSK-'.time(),
            'nama_perangkat' => 'Proyektor Dusk Uji',
            'kondisi' => 'Rusak Ringan',
        ]);
        $deskripsi = 'Kerusakan uji Dusk '.time();

        try {
            $this->browse(function (Browser $browser) use ($pelapor, $device, $urgensi, $deskripsi) {
                $browser->loginAs($pelapor)
                    ->visit('/laporan/buat')
                    ->select('id_perangkat', (string) $device->id_perangkat)
                    ->click("label:has(input[name='id_urgensi'][value='{$urgensi->id_urgensi}'])")
                    ->type('deskripsi_kerusakan', $deskripsi)
                    ->press('Kirim Laporan')
                    ->waitForLocation('/laporan/buat')
                    ->assertPathIs('/laporan/buat')
                    ->assertSee('Laporan');
            });

            $this->assertDatabaseHas('reports', [
                'id_perangkat' => $device->id_perangkat,
                'deskripsi_kerusakan' => $deskripsi,
            ]);
        } finally {
            foreach (Report::where('id_perangkat', $device->id_perangkat)->get() as $r) {
                $r->statusHistories()->delete();
                $r->delete();
            }
            $device->delete();
        }
    }
}
