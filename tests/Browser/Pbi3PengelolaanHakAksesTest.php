<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi3')]
class Pbi3PengelolaanHakAksesTest extends DuskTestCase
{
    /**
     * PBI 3: Admin dapat membuka dan menyimpan matriks hak akses, sedangkan non-Admin ditolak.
     */
    public function test_admin_dapat_mengelola_hak_akses_dan_non_admin_ditolak(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin, $pelapor) {
            $browser->loginAs($admin)
                ->visit('/access')
                ->assertSee('Hak Akses')
                ->press('Simpan Perubahan')
                ->assertPathIs('/access')
                ->assertSee('Hak Akses');

            $browser->loginAs($pelapor)
                ->visit('/access')
                ->assertSee('403');
        });
    }
}
