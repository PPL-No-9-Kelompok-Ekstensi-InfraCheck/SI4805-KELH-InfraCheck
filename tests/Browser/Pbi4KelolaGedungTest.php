<?php

namespace Tests\Browser;

use App\Models\Building;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi4')]
class Pbi4KelolaGedungTest extends DuskTestCase
{
    /**
     * PBI 4: Admin dapat melihat daftar, menambah, dan mengubah data gedung melalui browser.
     */
    public function test_admin_dapat_mengelola_data_gedung(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $kode = 'GD-DSK-'.time();

        try {
            $this->browse(function (Browser $browser) use ($admin, $kode) {
                $browser->loginAs($admin)
                    ->visit('/buildings')
                    ->assertSee('Gedung')
                    ->visit('/buildings/create')
                    ->type('kode_gedung', $kode)
                    ->type('nama_gedung', 'Gedung Uji Dusk')
                    ->press('Simpan')
                    ->waitForLocation('/buildings')
                    ->assertPathIs('/buildings')
                    ->assertSee('Gedung Uji Dusk');

                $building = Building::where('kode_gedung', $kode)->firstOrFail();

                $browser->visit('/buildings/'.$building->id_gedung.'/edit')
                    ->type('nama_gedung', 'Gedung Uji Dusk Diubah')
                    ->press('Simpan')
                    ->waitForLocation('/buildings')
                    ->assertPathIs('/buildings')
                    ->assertSee('Gedung Uji Dusk Diubah');
            });
        } finally {
            Building::where('kode_gedung', $kode)->delete();
        }
    }
}
