<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function account(string $email = 'pelapor@infracheck.ac.id'): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function profilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'nama' => $user->nama,
            'email' => $user->email,
            'nim_nip' => $user->nim_nip,
            'unit' => $user->unit,
            'kontak' => $user->kontak,
        ], $overrides);
    }

    private function passwordPayload(array $overrides = []): array
    {
        return array_merge([
            'current_password' => 'password',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ], $overrides);
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->put('/profil', [
            'nama' => 'Penyusup',
            'email' => 'penyusup@example.com',
        ])->assertRedirect('/login');
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->put('/profil/password', $this->passwordPayload())
            ->assertRedirect('/login');
    }

    public function test_inactive_user_cannot_access_profile_page(): void
    {
        $user = $this->account();
        $user->update(['akun_aktif' => false]);

        $this->actingAs($user)->get('/profil')->assertForbidden();
    }

    public function test_inactive_user_cannot_update_profile(): void
    {
        $user = $this->account();
        $user->update(['akun_aktif' => false]);

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user))
            ->assertForbidden();
    }

    public function test_every_role_can_view_own_profile(): void
    {
        $emails = [
            'admin@infracheck.ac.id',
            'teknisi@infracheck.ac.id',
            'pelapor@infracheck.ac.id',
            'pimpinan@infracheck.ac.id',
        ];

        foreach ($emails as $email) {
            $user = $this->account($email);

            $this->actingAs($user)
                ->get('/profil')
                ->assertOk()
                ->assertSee($user->nama)
                ->assertSee($user->email)
                ->assertSee($user->role->nama_peran)
                ->assertSee('Profil Saya');
        }
    }

    public function test_profile_page_displays_own_data(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Andi Saputra (Mahasiswa)')
            ->assertSee('pelapor@infracheck.ac.id')
            ->assertSee('1202220001')
            ->assertSee('Sistem Informasi')
            ->assertSee('081345678901')
            ->assertSee('Data Profil')
            ->assertSee('Ubah Password');
    }

    public function test_profile_page_does_not_expose_role_or_status_inputs(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertDontSee('name="id_peran"', false)
            ->assertDontSee('name="akun_aktif"', false)
            ->assertSee('Role dan status akun hanya dapat diubah oleh Admin');
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = $this->account();

        $response = $this->actingAs($user)->put('/profil', [
            'nama' => 'Andi Saputra Baru',
            'email' => 'andi.baru@infracheck.ac.id',
            'nim_nip' => '1202220002',
            'unit' => 'Teknologi Informasi',
            'kontak' => '081399999999',
        ]);

        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $user->id_pengguna,
            'nama' => 'Andi Saputra Baru',
            'email' => 'andi.baru@infracheck.ac.id',
            'nim_nip' => '1202220002',
            'unit' => 'Teknologi Informasi',
            'kontak' => '081399999999',
        ]);
    }

    public function test_user_may_keep_own_email_when_updating_profile(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, ['nama' => 'Nama Tetap Sama']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $user->id_pengguna,
            'nama' => 'Nama Tetap Sama',
            'email' => $user->email,
        ]);
    }

    public function test_email_must_be_unique_when_updating_profile(): void
    {
        $user = $this->account();
        $other = $this->account('teknisi@infracheck.ac.id');

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, ['email' => $other->email]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $user->id_pengguna,
            'email' => 'pelapor@infracheck.ac.id',
        ]);
    }

    public function test_nim_nip_must_be_unique_when_updating_profile(): void
    {
        $user = $this->account();
        $other = $this->account('teknisi@infracheck.ac.id');

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, ['nim_nip' => $other->nim_nip]))
            ->assertSessionHasErrors('nim_nip');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $user->id_pengguna,
            'nim_nip' => '1202220001',
        ]);
    }

    public function test_optional_profile_fields_may_be_empty(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, [
                'nim_nip' => '',
                'unit' => '',
                'kontak' => '',
            ]))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->nim_nip);
        $this->assertNull($user->unit);
        $this->assertNull($user->kontak);
    }

    public function test_user_cannot_change_own_role_or_account_status_from_profile(): void
    {
        $user = $this->account();
        $adminRole = Role::where('nama_peran', 'Admin')->firstOrFail();
        $originalRoleId = $user->id_peran;

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, [
                'id_peran' => $adminRole->id_peran,
                'akun_aktif' => 0,
            ]))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame($originalRoleId, $user->id_peran);
        $this->assertTrue($user->akun_aktif);
    }

    public function test_user_cannot_change_password_from_profile_update_endpoint(): void
    {
        $user = $this->account();
        $originalHash = $user->kata_sandi_hash;

        $this->actingAs($user)
            ->put('/profil', $this->profilePayload($user, ['password' => 'passwordbaru123']))
            ->assertSessionHasNoErrors();

        $this->assertSame($originalHash, $user->refresh()->kata_sandi_hash);
    }

    public function test_profile_update_requires_valid_data(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil', [
                'nama' => '',
                'email' => 'bukan-email',
                'nim_nip' => null,
                'unit' => null,
                'kontak' => null,
            ])
            ->assertSessionHasErrors(['nama', 'email']);
    }

    public function test_profile_route_does_not_accept_a_user_id(): void
    {
        $user = $this->account();
        $other = $this->account('teknisi@infracheck.ac.id');

        $this->actingAs($user)
            ->get('/profil/'.$other->id_pengguna)
            ->assertNotFound();

        $this->actingAs($user)
            ->put('/profil/'.$other->id_pengguna, $this->profilePayload($user))
            ->assertNotFound();
    }

    public function test_profile_update_does_not_change_other_users(): void
    {
        $user = $this->account();
        $other = $this->account('teknisi@infracheck.ac.id');
        $otherName = $other->nama;

        $this->actingAs($user)->put('/profil', [
            'nama' => 'Hanya Milik Sendiri',
            'email' => $user->email,
            'nim_nip' => $user->nim_nip,
            'unit' => $user->unit,
            'kontak' => $user->kontak,
        ])->assertRedirect(route('profile.index'));

        $this->assertSame($otherName, $other->refresh()->nama);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->account();
        $originalHash = $user->kata_sandi_hash;

        $this->actingAs($user)
            ->put('/profil/password', $this->passwordPayload([
                'current_password' => 'password-salah',
            ]))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalHash, $user->refresh()->kata_sandi_hash);
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->account();
        $originalHash = $user->kata_sandi_hash;

        $response = $this->actingAs($user)
            ->put('/profil/password', $this->passwordPayload());

        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('status', 'Password berhasil diubah.');

        $user->refresh();

        $this->assertNotSame($originalHash, $user->kata_sandi_hash);
        $this->assertTrue(Hash::check('passwordbaru123', $user->kata_sandi_hash));
        $this->assertFalse(Hash::check('password', $user->kata_sandi_hash));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil/password', $this->passwordPayload([
                'password_confirmation' => 'berbeda123',
            ]))
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_be_at_least_eight_characters(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil/password', $this->passwordPayload([
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ]))
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->put('/profil/password', $this->passwordPayload([
                'password' => 'password',
                'password_confirmation' => 'password',
            ]))
            ->assertSessionHasErrors('password');
    }

    public function test_dashboard_links_to_profile_page(): void
    {
        $user = $this->account('admin@infracheck.ac.id');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('profile.index'), false)
            ->assertSee('Profil Saya');
    }

    public function test_profile_page_links_back_to_dashboard(): void
    {
        $user = $this->account();

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee(route('dashboard'), false);
    }

    public function test_password_fields_have_independent_visibility_toggles(): void
    {
        $user = $this->account();

        $response = $this->actingAs($user)->get('/profil');

        $response->assertOk();

        $labels = [
            'current_password' => 'Tampilkan password lama',
            'password' => 'Tampilkan password baru',
            'password_confirmation' => 'Tampilkan konfirmasi password baru',
        ];

        foreach ($labels as $field => $label) {
            $response->assertSee('data-password-toggle="'.$field.'"', false);
            $response->assertSee('aria-label="'.$label.'"', false);
        }

        $content = $response->getContent();

        $this->assertSame(3, substr_count($content, 'data-password-toggle="'));
        $this->assertSame(3, substr_count($content, 'type="button"'));
        $this->assertSame(3, substr_count($content, 'aria-pressed="false"'));
    }
}
