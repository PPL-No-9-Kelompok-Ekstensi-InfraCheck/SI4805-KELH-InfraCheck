<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
    }

    private function pelapor(): User
    {
        return User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
    }

    private function teknisi(): User
    {
        return User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
    }

    private function role(string $name): Role
    {
        return Role::where('nama_peran', $name)->firstOrFail();
    }

    private function permission(string $code): Permission
    {
        return Permission::where('kode', $code)->firstOrFail();
    }

    /**
     * Daftar kode permission yang dimiliki sebuah role.
     *
     * @return list<string>
     */
    private function permissionsOf(string $roleName): array
    {
        $codes = $this->role($roleName)
            ->permissions()
            ->pluck('kode')
            ->all();

        sort($codes);

        return $codes;
    }

    /**
     * Membangun payload lengkap untuk seluruh role.
     *
     * @param  array<string, list<string>>  $overrides
     * @return array{permissions: array<int, list<int>>}
     */
    private function payload(array $overrides = []): array
    {
        $payload = [];

        foreach (Role::with('permissions')->get() as $role) {
            $payload[$role->id_peran] = $role->permissions
                ->pluck('id_hak_akses')
                ->all();
        }

        foreach ($overrides as $roleName => $codes) {
            $roleId = $this->role($roleName)->id_peran;

            $payload[$roleId] = array_map(
                fn (string $code) => $this->permission($code)->id_hak_akses,
                $codes
            );
        }

        return ['permissions' => $payload];
    }

    /**
     * Memberikan daftar permission tertentu kepada sebuah role.
     *
     * @param  list<string>  $codes
     */
    private function grant(string $roleName, array $codes): void
    {
        $this->actingAs($this->admin())
            ->put('/access', $this->payload([$roleName => $codes]))
            ->assertRedirect(route('access.index'))
            ->assertSessionHasNoErrors();
    }

    private function validUserPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Dewi Lestari',
            'email' => 'dewi@infracheck.ac.id',
            'nim_nip' => '1202220999',
            'id_peran' => $this->role('Teknisi')->id_peran,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'unit' => 'Sistem Informasi',
            'kontak' => '081200000000',
            'akun_aktif' => 1,
        ], $overrides);
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'nama' => $user->nama,
            'email' => $user->email,
            'nim_nip' => $user->nim_nip,
            'id_peran' => $user->id_peran,
            'password' => '',
            'password_confirmation' => '',
            'unit' => $user->unit,
            'kontak' => $user->kontak,
            'akun_aktif' => $user->akun_aktif ? 1 : 0,
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Halaman Hak Akses
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_view_access_management_page(): void
    {
        $response = $this->actingAs($this->admin())->get('/access');

        $response->assertOk();
        $response->assertSee('Matriks Hak Akses');
        $response->assertSee('Lihat');
        $response->assertSee('Tambah');
        $response->assertSee('Ubah');
        $response->assertSee('Hapus');
        $response->assertSee('Admin');
        $response->assertSee('Pelapor');
        $response->assertSee('Teknisi');
        $response->assertSee('Pimpinan');
        $response->assertSee('Simpan Perubahan');
    }

    public function test_non_admin_cannot_view_access_management_page(): void
    {
        $this->actingAs($this->pelapor())
            ->get('/access')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_update_access_management(): void
    {
        $this->actingAs($this->pelapor())
            ->put('/access', $this->payload())
            ->assertForbidden();
    }

    public function test_non_admin_with_access_permissions_still_cannot_manage_access(): void
    {
        $this->role('Teknisi')->permissions()->syncWithoutDetaching([
            $this->permission('access.view')->id_hak_akses,
            $this->permission('access.update')->id_hak_akses,
        ]);

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)->get('/access')->assertForbidden();
        $this->actingAs($teknisi)->put('/access', $this->payload())->assertForbidden();

        $this->assertDatabaseHas('permission_role', [
            'id_peran' => $this->role('Admin')->id_peran,
            'id_hak_akses' => $this->permission('access.update')->id_hak_akses,
        ]);
    }

    public function test_inactive_admin_cannot_access_access_management(): void
    {
        $admin = $this->admin();
        $admin->update(['akun_aktif' => false]);

        $this->actingAs($admin)
            ->get('/access')
            ->assertForbidden();
    }

    public function test_admin_without_update_permission_cannot_save_matrix(): void
    {
        $this->role('Admin')->permissions()->detach(
            $this->permission('access.update')->id_hak_akses
        );

        $admin = User::findOrFail($this->admin()->id_pengguna);

        $this->actingAs($admin)->get('/access')->assertOk();
        $this->actingAs($admin)->put('/access', $this->payload())->assertForbidden();
    }

    public function test_access_page_hides_save_button_without_update_permission(): void
    {
        $this->role('Admin')->permissions()->detach(
            $this->permission('access.update')->id_hak_akses
        );

        $admin = User::findOrFail($this->admin()->id_pengguna);

        $this->actingAs($admin)
            ->get('/access')
            ->assertOk()
            ->assertDontSee('Simpan Perubahan');
    }

    /*
    |--------------------------------------------------------------------------
    | Perubahan Permission
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_revoke_permission_from_role(): void
    {
        $codes = array_values(array_diff(
            $this->permissionsOf('Pelapor'),
            ['reports.create']
        ));

        $this->actingAs($this->admin())
            ->put('/access', $this->payload(['Pelapor' => $codes]))
            ->assertRedirect(route('access.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('permission_role', [
            'id_peran' => $this->role('Pelapor')->id_peran,
            'id_hak_akses' => $this->permission('reports.create')->id_hak_akses,
        ]);
    }

    public function test_admin_can_grant_permission_to_role(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->get('/users')
            ->assertOk();
    }

    public function test_granted_user_permissions_follow_action_scope(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $admin = $this->admin();

        $this->actingAs($teknisi)->get('/users')->assertOk();
        $this->actingAs($teknisi)->get('/users/create')->assertOk();
        $this->actingAs($teknisi)
            ->get('/users/'.$admin->id_pengguna.'/edit')
            ->assertForbidden();
        $this->actingAs($teknisi)
            ->delete('/users/'.$this->pelapor()->id_pengguna)
            ->assertForbidden();
    }

    public function test_revoked_permission_applies_immediately(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $this->actingAs($teknisi)->get('/users')->assertOk();

        $codes = array_values(array_diff(
            $this->permissionsOf('Teknisi'),
            ['users.view']
        ));

        $this->actingAs($this->admin())
            ->put('/access', $this->payload(['Teknisi' => $codes]))
            ->assertRedirect(route('access.index'));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $this->actingAs($teknisi)->get('/users')->assertForbidden();
    }

    public function test_invalid_permission_id_is_rejected(): void
    {
        $payload = $this->payload();
        $roleId = $this->role('Pelapor')->id_peran;
        $invalidIndex = count($payload['permissions'][$roleId]);

        $payload['permissions'][$roleId][] = 9999;

        $this->actingAs($this->admin())
            ->put('/access', $payload)
            ->assertSessionHasErrors('permissions.'.$roleId.'.'.$invalidIndex);
    }

    public function test_admin_role_cannot_lose_access_permissions(): void
    {
        $codes = array_values(array_diff(
            $this->permissionsOf('Admin'),
            ['access.update']
        ));

        $this->actingAs($this->admin())
            ->from('/access')
            ->put('/access', $this->payload(['Admin' => $codes]))
            ->assertRedirect('/access')
            ->assertSessionHasErrors('permissions');

        $this->assertDatabaseHas('permission_role', [
            'id_peran' => $this->role('Admin')->id_peran,
            'id_hak_akses' => $this->permission('access.update')->id_hak_akses,
        ]);
    }

    public function test_put_without_permissions_field_is_rejected(): void
    {
        $beforePelapor = $this->permissionsOf('Pelapor');
        $beforeAdmin = $this->permissionsOf('Admin');

        $this->actingAs($this->admin())
            ->put('/access', [])
            ->assertSessionHasErrors('permissions');

        $this->assertSame($beforePelapor, $this->permissionsOf('Pelapor'));
        $this->assertSame($beforeAdmin, $this->permissionsOf('Admin'));
    }

    public function test_admin_can_empty_non_mandatory_permissions(): void
    {
        $this->actingAs($this->admin())
            ->put('/access', $this->payload(['Pelapor' => ['dashboard.view']]))
            ->assertRedirect(route('access.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(['dashboard.view'], $this->permissionsOf('Pelapor'));
    }

    public function test_admin_cannot_empty_role_permissions_entirely(): void
    {
        $payload = $this->payload();
        unset($payload['permissions'][$this->role('Pelapor')->id_peran]);

        $this->actingAs($this->admin())
            ->from('/access')
            ->put('/access', $payload)
            ->assertRedirect('/access')
            ->assertSessionHasErrors('permissions');

        $this->assertContains('dashboard.view', $this->permissionsOf('Pelapor'));
    }

    public function test_dashboard_view_cannot_be_revoked_from_any_role(): void
    {
        foreach (['Admin', 'Pelapor', 'Teknisi', 'Pimpinan'] as $roleName) {
            $codes = array_values(array_diff(
                $this->permissionsOf($roleName),
                ['dashboard.view']
            ));

            $this->actingAs($this->admin())
                ->from('/access')
                ->put('/access', $this->payload([$roleName => $codes]))
                ->assertRedirect('/access')
                ->assertSessionHasErrors('permissions');

            $this->assertDatabaseHas('permission_role', [
                'id_peran' => $this->role($roleName)->id_peran,
                'id_hak_akses' => $this->permission('dashboard.view')->id_hak_akses,
            ]);
        }
    }

    public function test_access_permissions_cannot_be_delegated_to_non_admin(): void
    {
        foreach (['access.view', 'access.update'] as $code) {
            $codes = array_merge($this->permissionsOf('Teknisi'), [$code]);

            $this->actingAs($this->admin())
                ->from('/access')
                ->put('/access', $this->payload(['Teknisi' => $codes]))
                ->assertRedirect('/access')
                ->assertSessionHasErrors('permissions');

            $this->assertDatabaseMissing('permission_role', [
                'id_peran' => $this->role('Teknisi')->id_peran,
                'id_hak_akses' => $this->permission($code)->id_hak_akses,
            ]);
        }
    }

    public function test_access_page_locks_dashboard_and_blocks_access_permissions(): void
    {
        $response = $this->actingAs($this->admin())->get('/access');

        $response->assertOk()
            ->assertSee('title="Wajib dimiliki role ini"', false)
            ->assertSee('title="Hanya dapat dimiliki role Admin"', false);

        $html = $response->getContent();
        $dashboardId = (int) $this->permission('dashboard.view')->id_hak_akses;

        preg_match_all('/name="permissions\[(\d+)\]\[\]"\s*value="(\d+)"/', $html, $matches, PREG_SET_ORDER);

        $lockedByRole = [];

        foreach ($matches as $match) {
            $lockedByRole[(int) $match[1]][] = (int) $match[2];
        }

        foreach (Role::all() as $role) {
            $this->assertContains(
                $dashboardId,
                $lockedByRole[(int) $role->id_peran] ?? [],
                'dashboard.view harus terkunci untuk role '.$role->nama_peran.'.'
            );
        }
    }

    public function test_permission_sync_rolls_back_when_failure_occurs(): void
    {
        $beforePelapor = $this->permissionsOf('Pelapor');

        $payload = $this->payload([
            'Pelapor' => array_merge($beforePelapor, ['users.view']),
            'Teknisi' => array_merge($this->permissionsOf('Teknisi'), ['users.view']),
        ]);

        $inserts = 0;
        $thrown = false;

        DB::listen(function ($query) use (&$inserts, &$thrown): void {
            if ($thrown) {
                return;
            }

            $sql = strtolower(ltrim($query->sql));

            if (str_starts_with($sql, 'insert') && str_contains($sql, 'permission_role')) {
                $inserts++;

                if ($inserts >= 2) {
                    $thrown = true;

                    throw new RuntimeException('Simulasi kegagalan sinkronisasi.');
                }
            }
        });

        $this->withoutExceptionHandling();

        $caught = null;

        try {
            $this->actingAs($this->admin())->put('/access', $payload);
        } catch (RuntimeException $exception) {
            $caught = $exception;
        }

        $this->assertNotNull($caught, 'Kegagalan sinkronisasi seharusnya dilempar.');
        $this->assertSame('Simulasi kegagalan sinkronisasi.', $caught->getMessage());
        $this->assertSame($beforePelapor, $this->permissionsOf('Pelapor'));
        $this->assertTrue($thrown);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard, Sidebar, dan Status Akun
    |--------------------------------------------------------------------------
    */

    public function test_admin_dashboard_shows_access_menu(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-menu="access"', false);
    }

    public function test_pelapor_dashboard_shows_report_menu_and_hides_settings(): void
    {
        $this->actingAs($this->pelapor())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-menu="reports"', false)
            ->assertDontSee('data-menu="access"', false)
            ->assertDontSee('data-menu="users"', false);
    }

    public function test_teknisi_with_users_view_sees_user_menu(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-menu="users"', false)
            ->assertDontSee('data-menu="access"', false);
    }

    public function test_teknisi_with_only_users_create_does_not_see_user_menu(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-menu="users"', false);
    }

    public function test_non_admin_with_access_permissions_does_not_see_access_menu(): void
    {
        $this->role('Teknisi')->permissions()->syncWithoutDetaching([
            $this->permission('access.view')->id_hak_akses,
            $this->permission('access.update')->id_hak_akses,
        ]);

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-menu="access"', false);
    }

    public function test_inactive_user_with_existing_session_cannot_open_dashboard(): void
    {
        $admin = $this->admin();
        $admin->update(['akun_aktif' => false]);

        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/users')->assertForbidden();
    }

    public function test_user_without_dashboard_view_cannot_open_dashboard(): void
    {
        $this->role('Teknisi')->permissions()->detach(
            $this->permission('dashboard.view')->id_hak_akses
        );

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)->get('/dashboard')->assertForbidden();
        $this->actingAs($this->admin())->get('/dashboard')->assertOk();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Visibilitas Tombol
    |--------------------------------------------------------------------------
    */

    public function test_user_index_hides_create_edit_delete_buttons_without_permissions(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $admin = $this->admin();

        $this->actingAs($teknisi)
            ->get('/users')
            ->assertOk()
            ->assertDontSee('+ Tambah Pengguna')
            ->assertDontSee('/users/'.$admin->id_pengguna.'/edit', false)
            ->assertDontSee('Hapus');
    }

    public function test_user_index_shows_buttons_for_granted_permissions(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create', 'users.update', 'users.delete']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $admin = $this->admin();
        $pelapor = $this->pelapor();

        $this->actingAs($teknisi)
            ->get('/users')
            ->assertOk()
            ->assertSee('+ Tambah Pengguna')
            ->assertSee('/users/'.$pelapor->id_pengguna.'/edit', false)
            ->assertDontSee('/users/'.$admin->id_pengguna.'/edit', false)
            ->assertSee('Hapus');
    }

    /*
    |--------------------------------------------------------------------------
    | Pencegahan Privilege Escalation (F1)
    |--------------------------------------------------------------------------
    */

    public function test_non_admin_with_users_create_cannot_create_admin_user(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->post('/users', $this->validUserPayload([
                'id_peran' => $this->role('Admin')->id_peran,
            ]))
            ->assertSessionHasErrors('id_peran');

        $this->assertDatabaseMissing('users', ['email' => 'dewi@infracheck.ac.id']);
    }

    public function test_non_admin_can_create_non_admin_user(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->post('/users', $this->validUserPayload())
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'dewi@infracheck.ac.id',
            'id_peran' => $this->role('Teknisi')->id_peran,
        ]);
    }

    public function test_non_admin_create_form_only_shows_assignable_roles(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $response = $this->actingAs($teknisi)->get('/users/create');
        $response->assertOk();

        preg_match_all('/<option[^>]*value="(\d+)"/', $response->getContent(), $matches);

        $roleIds = array_map('intval', $matches[1]);
        sort($roleIds);

        $this->assertSame([(int) $this->role('Teknisi')->id_peran], $roleIds);
    }

    public function test_non_admin_cannot_create_user_with_wider_role(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->from('/users/create')
            ->post('/users', $this->validUserPayload([
                'id_peran' => $this->role('Pimpinan')->id_peran,
            ]))
            ->assertRedirect('/users/create')
            ->assertSessionHasErrors('id_peran');

        $this->assertDatabaseMissing('users', ['email' => 'dewi@infracheck.ac.id']);
    }

    public function test_non_admin_cannot_change_user_role_to_wider_role(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.update']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $pelapor = $this->pelapor();

        $this->actingAs($teknisi)
            ->from('/users/'.$pelapor->id_pengguna.'/edit')
            ->put('/users/'.$pelapor->id_pengguna, $this->updatePayload($pelapor, [
                'id_peran' => $this->role('Pimpinan')->id_peran,
            ]))
            ->assertRedirect('/users/'.$pelapor->id_pengguna.'/edit')
            ->assertSessionHasErrors('id_peran');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $pelapor->id_pengguna,
            'id_peran' => $this->role('Pelapor')->id_peran,
        ]);
    }

    public function test_non_admin_can_assign_role_with_subset_permissions(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->post('/users', $this->validUserPayload([
                'id_peran' => $this->role('Teknisi')->id_peran,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'dewi@infracheck.ac.id',
            'id_peran' => $this->role('Teknisi')->id_peran,
        ]);
    }

    public function test_admin_can_assign_any_role(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validUserPayload([
                'id_peran' => $this->role('Pimpinan')->id_peran,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $created = User::where('email', 'dewi@infracheck.ac.id')->firstOrFail();
        $this->assertSame($this->role('Pimpinan')->id_peran, $created->id_peran);

        $this->actingAs($this->admin())
            ->put('/users/'.$created->id_pengguna, $this->updatePayload($created, [
                'id_peran' => $this->role('Admin')->id_peran,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $created->refresh();
        $this->assertSame($this->role('Admin')->id_peran, $created->id_peran);

        $adminEdit = $this->actingAs($this->admin())->get('/users/create');
        $adminEdit->assertOk()
            ->assertSee('value="'.$this->role('Admin')->id_peran.'"', false);
    }

    public function test_non_admin_with_users_update_cannot_promote_user_to_admin(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.update']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $pelapor = $this->pelapor();

        $this->actingAs($teknisi)
            ->from('/users/'.$pelapor->id_pengguna.'/edit')
            ->put('/users/'.$pelapor->id_pengguna, $this->updatePayload($pelapor, [
                'id_peran' => $this->role('Admin')->id_peran,
            ]))
            ->assertRedirect('/users/'.$pelapor->id_pengguna.'/edit')
            ->assertSessionHasErrors('id_peran');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $pelapor->id_pengguna,
            'id_peran' => $this->role('Pelapor')->id_peran,
        ]);
    }

    public function test_non_admin_cannot_edit_or_update_admin_account(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.update']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $admin = $this->admin();
        $originalHash = $admin->kata_sandi_hash;

        $this->actingAs($teknisi)
            ->get('/users/'.$admin->id_pengguna.'/edit')
            ->assertForbidden();

        $this->actingAs($teknisi)
            ->put('/users/'.$admin->id_pengguna, $this->updatePayload($admin, [
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ]))
            ->assertForbidden();

        $admin->refresh();

        $this->assertSame($originalHash, $admin->kata_sandi_hash);
        $this->assertSame($this->role('Admin')->id_peran, $admin->id_peran);
    }

    public function test_non_admin_cannot_delete_admin_account(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.delete']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);
        $admin = $this->admin();

        $this->actingAs($teknisi)
            ->delete('/users/'.$admin->id_pengguna)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id_pengguna' => $admin->id_pengguna]);
    }

    public function test_non_admin_cannot_escalate_own_role(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.update']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->from('/users/'.$teknisi->id_pengguna.'/edit')
            ->put('/users/'.$teknisi->id_pengguna, $this->updatePayload($teknisi, [
                'id_peran' => $this->role('Pimpinan')->id_peran,
            ]))
            ->assertRedirect('/users/'.$teknisi->id_pengguna.'/edit')
            ->assertSessionHasErrors('id_peran');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $teknisi->id_pengguna,
            'id_peran' => $this->role('Teknisi')->id_peran,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Integrasi PBI 1
    |--------------------------------------------------------------------------
    */

    public function test_granted_user_permissions_can_perform_pbi1_crud(): void
    {
        $this->grant('Teknisi', array_merge(
            $this->permissionsOf('Teknisi'),
            ['users.view', 'users.create', 'users.update', 'users.delete']
        ));

        $teknisi = User::findOrFail($this->teknisi()->id_pengguna);

        $this->actingAs($teknisi)
            ->post('/users', $this->validUserPayload())
            ->assertRedirect(route('users.index'));

        $created = User::where('email', 'dewi@infracheck.ac.id')->firstOrFail();

        $this->actingAs($teknisi)
            ->put('/users/'.$created->id_pengguna, $this->updatePayload($created, [
                'nama' => 'Dewi Lestari Diperbarui',
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $created->refresh();
        $this->assertSame('Dewi Lestari Diperbarui', $created->nama);

        $this->actingAs($teknisi)
            ->delete('/users/'.$created->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', [
            'id_pengguna' => $created->id_pengguna,
        ]);
    }
}
