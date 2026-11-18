<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('login')]
class LoginTest extends DuskTestCase
{
    /**
     * Pengguna dapat membuka halaman login, login dengan akun valid ke dashboard, lalu logout.
     */
    public function test_pengguna_dapat_login_dan_logout_melalui_browser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Masuk')
                ->type('login', 'admin@infracheck.ac.id')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('Dashboard')
                ->press('Logout')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }

    /**
     * Login dengan password salah tetap berada di halaman login dan menampilkan pesan error.
     */
    public function test_login_gagal_dengan_kredensial_salah(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('login', 'admin@infracheck.ac.id')
                ->type('password', 'salah-password')
                ->press('Masuk')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
