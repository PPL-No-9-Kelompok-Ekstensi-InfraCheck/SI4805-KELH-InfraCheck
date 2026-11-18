<?php

namespace Tests\Browser;

use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi5')]
class Pbi5KelolaRuanganTest extends DuskTestCase
{
    /**
     * PBI 5: Admin dapat melihat daftar, menambah, dan mengubah data ruangan melalui browser.
     */
    public function test_admin_dapat_mengelola_data_ruangan(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $building = Building::firstOrFail();
        $kode = 'R-DSK-'.time();

        try {
            $this->browse(function (Browser $browser) use ($admin, $building, $kode) {
                $browser->loginAs($admin)
                    ->visit('/rooms')
                    ->assertSee('Ruangan')
                    ->visit('/rooms/create')
                    ->select('id_gedung', (string) $building->id_gedung)
                    ->type('kode_ruangan', $kode)
                    ->type('nama_ruangan', 'Ruang Uji Dusk')
                    ->press('Simpan')
                    ->waitForLocation('/rooms')
                    ->assertPathIs('/rooms')
                    ->assertSee('Ruang Uji Dusk');

                $room = Room::where('kode_ruangan', $kode)->firstOrFail();

                $browser->visit('/rooms/'.$room->id_ruangan.'/edit')
                    ->type('nama_ruangan', 'Ruang Uji Dusk Diubah')
                    ->press('Simpan')
                    ->waitForLocation('/rooms')
                    ->assertPathIs('/rooms')
                    ->assertSee('Ruang Uji Dusk Diubah');
            });
        } finally {
            Room::where('kode_ruangan', $kode)->delete();
        }
    }
}
