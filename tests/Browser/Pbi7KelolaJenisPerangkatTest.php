<?php

namespace Tests\Browser;

use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi7')]
class Pbi7KelolaJenisPerangkatTest extends DuskTestCase
{
    /**
     * PBI 7: Admin dapat melihat daftar dan menambah jenis perangkat melalui browser.
     */
    public function test_admin_dapat_mengelola_jenis_perangkat(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $kategori = InfrastructureCategory::firstOrFail();
        $namaJenis = 'Jenis Dusk '.time();

        try {
            $this->browse(function (Browser $browser) use ($admin, $kategori, $namaJenis) {
                $browser->loginAs($admin)
                    ->visit('/device-types')
                    ->assertSee('Jenis Perangkat')
                    ->visit('/device-types/create')
                    ->select('id_kategori', (string) $kategori->id_kategori)
                    ->type('nama_jenis', $namaJenis)
                    ->press('Simpan')
                    ->waitForLocation('/device-types')
                    ->assertPathIs('/device-types')
                    ->assertSee($namaJenis);
            });
        } finally {
            DeviceType::where('nama_jenis', $namaJenis)->delete();
        }
    }
}
