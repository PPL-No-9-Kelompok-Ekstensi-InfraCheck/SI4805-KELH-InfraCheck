<?php

namespace Tests\Browser;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi8')]
class Pbi8KelolaPerangkatTest extends DuskTestCase
{
    /**
     * PBI 8: Admin dapat melihat daftar dan menambah data perangkat melalui browser.
     */
    public function test_admin_dapat_mengelola_data_perangkat(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $jenis = DeviceType::firstOrFail();
        $ruangan = Room::firstOrFail();
        $kodeAset = 'AST-DSK-'.time();

        try {
            $this->browse(function (Browser $browser) use ($admin, $jenis, $ruangan, $kodeAset) {
                $browser->loginAs($admin)
                    ->visit('/devices')
                    ->assertSee('Perangkat')
                    ->visit('/devices/create')
                    ->type('kode_aset', $kodeAset)
                    ->type('nama_perangkat', 'Perangkat Uji Dusk')
                    ->select('id_jenis', (string) $jenis->id_jenis)
                    ->select('id_ruangan', (string) $ruangan->id_ruangan)
                    ->select('kondisi', 'Baik')
                    ->press('Simpan')
                    ->waitForLocation('/devices')
                    ->assertPathIs('/devices')
                    ->assertSee('Perangkat Uji Dusk');
            });
        } finally {
            Device::where('kode_aset', $kodeAset)->delete();
        }
    }
}
