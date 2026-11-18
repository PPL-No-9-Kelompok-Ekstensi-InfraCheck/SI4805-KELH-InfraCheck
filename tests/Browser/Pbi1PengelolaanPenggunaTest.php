<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi1')]
class Pbi1PengelolaanPenggunaTest extends DuskTestCase
{
    /**
     * PBI 1: Admin dapat melihat daftar, menambah, dan mengubah data pengguna melalui browser.
     */
    public function test_admin_dapat_mengelola_data_pengguna(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelaporRole = Role::where('nama_peran', 'Pelapor')->firstOrFail();
        $testEmail = 'dusk.user.'.time().'@infracheck.ac.id';

        try {
            $this->browse(function (Browser $browser) use ($admin, $pelaporRole, $testEmail) {
                $browser->loginAs($admin)
                    ->visit('/users')
                    ->assertSee('Pengguna')
                    ->visit('/users/create')
                    ->type('nama', 'Pengguna Dusk Uji')
                    ->type('email', $testEmail)
                    ->select('id_peran', (string) $pelaporRole->id_peran)
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->press('Tambah Pengguna')
                    ->waitForLocation('/users')
                    ->assertPathIs('/users')
                    ->assertSee('Pengguna Dusk Uji');

                $createdUser = User::where('email', $testEmail)->firstOrFail();

                $browser->visit('/users/'.$createdUser->id_pengguna.'/edit')
                    ->type('nama', 'Pengguna Dusk Diubah')
                    ->press('Simpan Perubahan')
                    ->waitForLocation('/users')
                    ->assertPathIs('/users')
                    ->assertSee('Pengguna Dusk Diubah');
            });
        } finally {
            User::where('email', $testEmail)->delete();
        }
    }
}
