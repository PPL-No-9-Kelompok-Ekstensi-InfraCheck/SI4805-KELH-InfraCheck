<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('InfraCheck');
        $response->assertSee('Email / NIM / NIP');
    }

    public function test_user_can_login_using_email(): void
    {
        $user = User::where('email', 'admin@infracheck.ac.id')->first();

        $response = $this->post('/login', [
            'login' => 'admin@infracheck.ac.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_login_using_nim_nip(): void
    {
        $user = User::where('nim_nip', 'ADM001')->first();

        $response = $this->post('/login', [
            'login' => 'ADM001',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_can_be_rendered_when_authenticated(): void
    {
        $user = User::where('email', 'admin@infracheck.ac.id')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('MASTER DATA');
        $response->assertSee('Laporan Terbaru');
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'admin@infracheck.ac.id')->first();

        $response = $this->actingAs($user)->post('/logout');
        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
