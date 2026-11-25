<?php

namespace Tests\Feature;

use App\Enums\Kondisi;
use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InfrastructureCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_view_categories_counts_and_shared_navigation(): void
    {
        $category = $this->makeCategory();
        DeviceType::create(['id_kategori' => $category->getKey(), 'nama_jenis' => 'Router']);

        $response = $this->actingAs($this->makeUser())->get(route('categories.index'));

        $response->assertOk()->assertSee('Jaringan & Internet');
        $this->assertSame(1, $response->viewData('categories')->first()->device_types_count);
        foreach (['buildings', 'rooms', 'categories', 'device-types', 'devices'] as $module) {
            $response->assertSee(route($module.'.index'));
        }
        $this->get(route('dashboard'))->assertOk()->assertSee(route('categories.index'));
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser())->get(route('categories.create'))
            ->assertOk()->assertSee('Tambah Kategori Infrastruktur')->assertSee('name="nama_kategori"', false);
        $this->get(route('categories.edit', $category))
            ->assertOk()->assertSee('Edit Kategori Infrastruktur')->assertSee('Jaringan & Internet');
    }

    public function test_create_saves_only_validated_fields_and_displays_success(): void
    {
        $response = $this->actingAs($this->makeUser())->post(route('categories.store'), [
            'nama_kategori' => '  Komputer & Periferal  ',
            'id_kategori' => 999,
            'created_at' => '2000-01-01 00:00:00',
        ]);

        $response->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('infrastructure_categories', ['nama_kategori' => 'Komputer & Periferal']);
        $this->assertDatabaseMissing('infrastructure_categories', ['id_kategori' => 999]);
        $this->assertDatabaseMissing('infrastructure_categories', ['created_at' => '2000-01-01 00:00:00']);
        $this->followRedirects($response)->assertOk()->assertSee('Kategori infrastruktur berhasil ditambahkan.');
    }

    public function test_update_keeps_primary_key_and_existing_device_type_relationship(): void
    {
        $category = $this->makeCategory();
        $type = DeviceType::create(['id_kategori' => $category->getKey(), 'nama_jenis' => 'Router']);

        $this->actingAs($this->makeUser())->put(route('categories.update', $category), [
            'nama_kategori' => 'Jaringan Kampus', 'id_kategori' => 999,
        ])->assertRedirect(route('categories.index'))->assertSessionHas('success', 'Kategori infrastruktur berhasil diperbarui.');

        $this->assertSame('Jaringan Kampus', $category->fresh()->nama_kategori);
        $this->assertSame($category->getKey(), $type->fresh()->id_kategori);
        $this->assertSame('Jaringan Kampus', $type->fresh()->category->nama_kategori);
        $this->assertDatabaseMissing('infrastructure_categories', ['id_kategori' => 999]);
    }

    public function test_update_accepts_its_own_unique_name(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser())->patch(route('categories.update', $category), [
            'nama_kategori' => $category->nama_kategori,
        ])->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    #[DataProvider('writeMethods')]
    public function test_duplicate_name_is_rejected_without_changing_existing_data(string $method): void
    {
        $category = $this->makeCategory('Kategori Asli');
        $this->makeCategory();
        $form = $method === 'post' ? route('categories.create') : route('categories.edit', $category);
        $url = $method === 'post' ? route('categories.store') : route('categories.update', $category);

        $response = $this->actingAs($this->makeUser())->from($form)->{$method}($url, ['nama_kategori' => 'Jaringan & Internet']);

        $response->assertRedirect($form);
        $this->followRedirects($response)->assertOk()->assertSee('Nama kategori sudah digunakan.')->assertSee('Jaringan & Internet');
        $this->assertDatabaseCount('infrastructure_categories', 2);
        $this->assertSame('Kategori Asli', $category->fresh()->nama_kategori);
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_name_returns_to_renderable_form_without_mutation(string $method, mixed $value, string $message): void
    {
        $category = $this->makeCategory();
        $form = $method === 'post' ? route('categories.create') : route('categories.edit', $category);
        $url = $method === 'post' ? route('categories.store') : route('categories.update', $category);

        $response = $this->actingAs($this->makeUser())->from($form)->{$method}($url, ['nama_kategori' => $value]);

        $response->assertRedirect($form);
        $this->followRedirects($response)->assertOk()->assertSee($message);
        $this->assertDatabaseCount('infrastructure_categories', 1);
        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    public static function invalidNames(): array
    {
        $cases = [];
        foreach (['post', 'put'] as $method) {
            foreach ([
                'kosong' => ['', 'Nama kategori wajib diisi.'],
                'spasi' => ['   ', 'Nama kategori wajib diisi.'],
                'null' => [null, 'Nama kategori wajib diisi.'],
                'array' => [['invalid'], 'Nama kategori harus berupa teks.'],
                'angka' => [123, 'Nama kategori harus berupa teks.'],
                'terlalu panjang' => [str_repeat('K', 256), 'Nama kategori maksimal 255 karakter.'],
            ] as $label => [$value, $message]) {
                $cases["$method $label"] = [$method, $value, $message];
            }
        }

        return $cases;
    }

    public function test_missing_name_is_rejected(): void
    {
        $this->actingAs($this->makeUser())->post(route('categories.store'), [])
            ->assertSessionHasErrors(['nama_kategori' => 'Nama kategori wajib diisi.']);

        $this->assertDatabaseCount('infrastructure_categories', 0);
    }

    public function test_name_accepts_database_length_limit(): void
    {
        $name = str_repeat('K', 255);

        $this->actingAs($this->makeUser())->post(route('categories.store'), ['nama_kategori' => $name])
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('infrastructure_categories', ['nama_kategori' => $name]);
    }

    #[DataProvider('writeMethods')]
    public function test_duplicate_created_after_validation_returns_validation_error_instead_of_500(string $method): void
    {
        $category = $this->makeCategory();
        // Simulasikan penulisan pesaing setelah validasi, sebelum query penyimpanan.
        InfrastructureCategory::saving(function (InfrastructureCategory $model): void {
            DB::table('infrastructure_categories')->insert(['nama_kategori' => $model->nama_kategori]);
        });
        $url = $method === 'post' ? route('categories.store') : route('categories.update', $category);

        $this->actingAs($this->makeUser())->{$method}($url, ['nama_kategori' => 'Kategori Pesaing'])
            ->assertRedirect()->assertSessionHasErrors(['nama_kategori' => 'Nama kategori sudah digunakan.']);

        $this->assertDatabaseCount('infrastructure_categories', 2);
        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    public static function writeMethods(): array
    {
        return ['tambah' => ['post'], 'ubah' => ['put']];
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser())->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('success', 'Kategori infrastruktur berhasil dihapus.');

        $this->assertModelMissing($category);
    }

    public function test_category_with_device_type_is_protected_from_cascade_deletion(): void
    {
        $category = $this->makeCategory();
        $type = DeviceType::create(['id_kategori' => $category->getKey(), 'nama_jenis' => 'Router']);

        $response = $this->actingAs($this->makeUser())->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'))
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh jenis perangkat.');
        $this->assertModelExists($category);
        $this->assertModelExists($type);
        $this->followRedirects($response)->assertOk()->assertSee('Kategori tidak dapat dihapus karena masih digunakan oleh jenis perangkat.');
    }

    public function test_deletion_preserves_device_type_and_device_chain(): void
    {
        $category = $this->makeCategory();
        $type = DeviceType::create(['id_kategori' => $category->getKey(), 'nama_jenis' => 'Router']);
        $building = Building::create(['kode_gedung' => 'G-1', 'nama_gedung' => 'Gedung Uji']);
        $room = Room::create(['id_gedung' => $building->getKey(), 'kode_ruangan' => 'R-1', 'nama_ruangan' => 'Ruang Uji']);
        $device = Device::create(['id_jenis' => $type->getKey(), 'id_ruangan' => $room->getKey(), 'kode_aset' => 'NET-001', 'nama_perangkat' => 'Router Uji', 'kondisi' => Kondisi::Baik]);

        $this->actingAs($this->makeUser())->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('error');

        $this->assertModelExists($category);
        $this->assertModelExists($type);
        $this->assertModelExists($device);
        $this->assertSame($category->getKey(), $device->fresh()->deviceType->category->getKey());
    }

    public function test_search_paginates_by_name_and_keeps_query_on_second_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeCategory(sprintf('Jaringan %02d', $i));
        }
        $this->makeCategory('Multimedia');
        $this->actingAs($this->makeUser());

        $response = $this->get(route('categories.index', ['q' => 'Jaringan']));

        $response->assertOk()->assertDontSee('Multimedia');
        $categories = $response->viewData('categories');
        $this->assertSame(12, $categories->total());
        $this->assertCount(10, $categories->items());
        $this->assertStringContainsString('q=Jaringan', $categories->nextPageUrl());
        $this->get(route('categories.index', ['q' => 'Jaringan', 'page' => 2]))
            ->assertOk()->assertSee('Jaringan 11')->assertSee('Jaringan 12')->assertDontSee('Jaringan 01');
    }

    public function test_empty_and_unmatched_search_states_are_displayed(): void
    {
        $this->actingAs($this->makeUser())->get(route('categories.index'))
            ->assertOk()->assertSee('Belum ada data kategori.');
        $this->makeCategory();

        $this->get(route('categories.index', ['q' => 'tidak-ada']))
            ->assertOk()->assertSee('Tidak ada kategori yang sesuai dengan pencarian.');
    }

    public function test_invalid_search_is_rejected_and_sql_like_input_is_bound(): void
    {
        $this->makeCategory();
        $this->actingAs($this->makeUser())->from(route('categories.index'))
            ->get(route('categories.index', ['q' => ['invalid']]))
            ->assertSessionHasErrors(['q' => 'Pencarian kategori harus berupa teks.']);
        $this->get(route('categories.index', ['q' => str_repeat('x', 256)]))
            ->assertSessionHasErrors(['q' => 'Pencarian kategori maksimal 255 karakter.']);
        $response = $this->get(route('categories.index', ['q' => "' OR 1=1 --"]));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('categories')->total());
        $this->assertDatabaseCount('infrastructure_categories', 1);
    }

    public function test_category_values_are_escaped_in_list_and_form(): void
    {
        $category = $this->makeCategory('<script>alert(1)</script>');

        $this->actingAs($this->makeUser())->get(route('categories.index'))
            ->assertOk()->assertSee($category->nama_kategori)->assertDontSee($category->nama_kategori, false);
        $this->get(route('categories.edit', $category))
            ->assertOk()->assertSee($category->nama_kategori)->assertDontSee($category->nama_kategori, false);
    }

    #[DataProvider('restrictedEndpoints')]
    public function test_non_admins_even_with_permissions_and_inactive_admin_cannot_access_actions(string $role, bool $active, string $method, string $route, string $permission): void
    {
        $category = $this->makeCategory();
        $user = $this->makeUser($role, $active);
        $user->role->permissions()->syncWithoutDetaching(Permission::where('kode', 'like', 'categories.%')->pluck('id_hak_akses'));

        $this->actingAs($user)->{$method}($this->endpointUrl($route, $category), ['nama_kategori' => 'Diubah'])
            ->assertForbidden();

        $this->assertDatabaseCount('infrastructure_categories', 1);
        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    public static function restrictedEndpoints(): array
    {
        $cases = [];
        foreach (['Pelapor', 'Teknisi', 'Pimpinan', 'Admin'] as $role) {
            foreach (self::endpoints() as $label => $endpoint) {
                $cases["$role $label"] = [$role, $role !== 'Admin', ...$endpoint];
            }
        }

        return $cases;
    }

    #[DataProvider('endpoints')]
    public function test_guest_is_redirected_to_login_for_every_action(string $method, string $route, string $permission): void
    {
        $category = $this->makeCategory();

        $this->{$method}($this->endpointUrl($route, $category), ['nama_kategori' => 'Diubah'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('infrastructure_categories', 1);
        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    #[DataProvider('endpoints')]
    public function test_admin_without_action_permission_is_forbidden(string $method, string $route, string $permission): void
    {
        $category = $this->makeCategory();
        $admin = $this->makeUser();
        $admin->role->permissions()->detach(Permission::where('kode', $permission)->value('id_hak_akses'));

        $this->actingAs($admin)->{$method}($this->endpointUrl($route, $category), ['nama_kategori' => 'Diubah'])
            ->assertForbidden();

        $this->assertDatabaseCount('infrastructure_categories', 1);
        $this->assertSame('Jaringan & Internet', $category->fresh()->nama_kategori);
    }

    public static function endpoints(): array
    {
        return [
            'daftar' => ['get', 'categories.index', 'categories.view'],
            'form tambah' => ['get', 'categories.create', 'categories.create'],
            'simpan' => ['post', 'categories.store', 'categories.create'],
            'form edit' => ['get', 'categories.edit', 'categories.update'],
            'ubah PUT' => ['put', 'categories.update', 'categories.update'],
            'ubah PATCH' => ['patch', 'categories.update', 'categories.update'],
            'hapus' => ['delete', 'categories.destroy', 'categories.delete'],
        ];
    }

    public function test_view_only_admin_sees_no_write_controls(): void
    {
        $category = $this->makeCategory();
        $admin = $this->makeUser();
        $admin->role->permissions()->detach(Permission::whereIn('kode', ['categories.create', 'categories.update', 'categories.delete'])->pluck('id_hak_akses'));

        $this->actingAs($admin)->get(route('categories.index'))->assertOk()
            ->assertDontSee(route('categories.create'))
            ->assertDontSee(route('categories.edit', $category))
            ->assertDontSee('Hapus kategori', false);
    }

    public function test_menu_is_hidden_from_non_admin_even_with_category_permission(): void
    {
        $user = $this->makeUser('Pelapor');
        $user->role->permissions()->syncWithoutDetaching([Permission::where('kode', 'categories.view')->value('id_hak_akses')]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee(route('categories.index'));
    }

    public function test_unknown_category_returns_not_found(): void
    {
        $this->actingAs($this->makeUser())->get(route('categories.edit', 999))->assertNotFound();
        $this->put(route('categories.update', 999), ['nama_kategori' => 'Baru'])->assertNotFound();
        $this->delete(route('categories.destroy', 999))->assertNotFound();
    }

    private function endpointUrl(string $route, InfrastructureCategory $category): string
    {
        return route($route, in_array($route, ['categories.edit', 'categories.update', 'categories.destroy']) ? [$category] : []);
    }

    private function makeCategory(string $name = 'Jaringan & Internet'): InfrastructureCategory
    {
        return InfrastructureCategory::create(['nama_kategori' => $name]);
    }

    private function makeUser(string $roleName = 'Admin', bool $active = true): User
    {
        $role = Role::where('nama_peran', $roleName)->firstOrFail();

        return User::create([
            'id_peran' => $role->getKey(),
            'nama' => 'Pengguna Uji '.$roleName,
            'email' => strtolower($roleName).'@example.test',
            'kata_sandi_hash' => 'category-test-password',
            'akun_aktif' => $active,
        ]);
    }
}
