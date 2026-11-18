<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BuildingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // This key exists only in the test process; no .env is required or modified.
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
    }

    public function test_admin_can_view_buildings_and_the_dashboard_link(): void
    {
        $admin = $this->makeUser();
        $building = $this->makeBuilding();
        Room::create(['id_gedung' => $building->id_gedung, 'kode_ruangan' => '101', 'nama_ruangan' => 'Ruang 101']);

        $response = $this->actingAs($admin)->get(route('buildings.index'));

        $response->assertOk()->assertSee('GKB-1')->assertSee('Gedung Kuliah Bersama');
        $this->assertSame(1, $response->viewData('buildings')->first()->rooms_count);
        $this->get(route('dashboard'))->assertOk()->assertSee(route('buildings.index'));
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $building = $this->makeBuilding();

        $this->actingAs($this->makeUser())->get(route('buildings.create'))
            ->assertOk()->assertSee('Tambah Gedung')->assertSee('name="kode_gedung"', false);
        $this->get(route('buildings.edit', $building))
            ->assertOk()->assertSee('Edit Gedung')->assertSee('value="GKB-1"', false);
    }

    public function test_admin_can_create_a_building_using_only_validated_fields(): void
    {
        $this->actingAs($this->makeUser())->post(route('buildings.store'), [
            'kode_gedung' => 'LAB-TI',
            'nama_gedung' => 'Laboratorium TI',
            'id_gedung' => 999,
        ])->assertRedirect(route('buildings.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('buildings', ['kode_gedung' => 'LAB-TI', 'nama_gedung' => 'Laboratorium TI']);
        $this->assertDatabaseMissing('buildings', ['id_gedung' => 999]);
        $this->get(route('buildings.index'))->assertSee('Gedung berhasil ditambahkan.');
    }

    public function test_admin_can_update_a_building_and_change_its_code(): void
    {
        $building = $this->makeBuilding();

        $this->actingAs($this->makeUser())->put(route('buildings.update', $building), [
            'kode_gedung' => 'GKB-2', 'nama_gedung' => 'Gedung Baru',
        ])->assertRedirect(route('buildings.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('buildings', [
            'id_gedung' => $building->id_gedung, 'kode_gedung' => 'GKB-2', 'nama_gedung' => 'Gedung Baru',
        ]);
    }

    public function test_update_can_keep_its_own_unique_code(): void
    {
        $building = $this->makeBuilding();

        $this->actingAs($this->makeUser())->patch(route('buildings.update', $building), [
            'kode_gedung' => 'GKB-1', 'nama_gedung' => 'Nama Diperbarui',
        ])->assertRedirect(route('buildings.index'))->assertSessionHasNoErrors();

        $this->assertSame('Nama Diperbarui', $building->fresh()->nama_gedung);
    }

    public function test_admin_can_delete_a_building_without_rooms(): void
    {
        $building = $this->makeBuilding();

        $this->actingAs($this->makeUser())->delete(route('buildings.destroy', $building))
            ->assertRedirect(route('buildings.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('buildings', ['id_gedung' => $building->id_gedung]);
    }

    public function test_building_with_rooms_cannot_be_deleted_and_rooms_are_preserved(): void
    {
        $building = $this->makeBuilding();
        $room = Room::create(['id_gedung' => $building->id_gedung, 'kode_ruangan' => '101', 'nama_ruangan' => 'Ruang 101']);

        $this->actingAs($this->makeUser())->delete(route('buildings.destroy', $building))
            ->assertRedirect(route('buildings.index'))
            ->assertSessionHas('error', 'Gedung tidak dapat dihapus karena masih memiliki ruangan.');

        $this->assertDatabaseHas('buildings', ['id_gedung' => $building->id_gedung]);
        $this->assertDatabaseHas('rooms', ['id_ruangan' => $room->id_ruangan, 'id_gedung' => $building->id_gedung]);
        $this->get(route('buildings.index'))->assertSee('Gedung tidak dapat dihapus karena masih memiliki ruangan.');
    }

    public function test_duplicate_code_is_rejected_on_create(): void
    {
        $this->makeBuilding();

        $response = $this->actingAs($this->makeUser())->from(route('buildings.create'))->post(route('buildings.store'), [
            'kode_gedung' => 'GKB-1', 'nama_gedung' => 'Duplikat',
        ]);
        $response->assertRedirect(route('buildings.create'));

        $this->assertDatabaseCount('buildings', 1);
        $this->followRedirects($response)->assertOk()->assertSee('Kode gedung sudah digunakan.')->assertSee('value="Duplikat"', false);
    }

    public function test_update_cannot_use_another_buildings_code(): void
    {
        $building = $this->makeBuilding();
        $this->makeBuilding(['kode_gedung' => 'OTHER']);

        $this->actingAs($this->makeUser())->put(route('buildings.update', $building), [
            'kode_gedung' => 'OTHER', 'nama_gedung' => 'Duplikat',
        ])->assertSessionHasErrors('kode_gedung');

        $this->assertSame('GKB-1', $building->fresh()->kode_gedung);
        $this->assertSame('Gedung Kuliah Bersama', $building->fresh()->nama_gedung);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_building_input_is_rejected(string $method, string $field, mixed $value): void
    {
        $building = $this->makeBuilding();
        $url = $method === 'post' ? route('buildings.store') : route('buildings.update', $building);
        $data = ['kode_gedung' => 'NEW', 'nama_gedung' => 'Gedung Baru'];
        $data[$field] = $value;

        $this->actingAs($this->makeUser())->{$method}($url, $data)->assertSessionHasErrors($field);

        $this->assertDatabaseCount('buildings', 1);
        $this->assertSame('GKB-1', $building->fresh()->kode_gedung);
        $this->assertSame('Gedung Kuliah Bersama', $building->fresh()->nama_gedung);
    }

    public static function invalidInputs(): array
    {
        $cases = [];

        foreach (['post', 'put'] as $method) {
            foreach (['kode_gedung', 'nama_gedung'] as $field) {
                foreach (['empty' => '', 'whitespace' => '   ', 'too long' => str_repeat('x', 256), 'array' => ['invalid']] as $label => $value) {
                    $cases["$method $field $label"] = [$method, $field, $value];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('arrayFormInputs')]
    public function test_form_renders_after_array_input_fails_validation(string $method, string $field): void
    {
        $building = $this->makeBuilding();
        $form = $method === 'post' ? route('buildings.create') : route('buildings.edit', $building);
        $url = $method === 'post' ? route('buildings.store') : route('buildings.update', $building);
        $data = ['kode_gedung' => 'NEW', 'nama_gedung' => 'Nama Tetap Ditampilkan'];
        $data[$field] = ['invalid'];

        $response = $this->actingAs($this->makeUser())->from($form)->{$method}($url, $data);
        $response->assertRedirect($form);
        $response = $this->followRedirects($response);

        $response->assertOk()->assertSee($method === 'post' ? 'Tambah Gedung' : 'Edit Gedung')
            ->assertSee($field === 'kode_gedung' ? 'Kode gedung harus berupa teks.' : 'Nama gedung harus berupa teks.')
            ->assertSee('value="'.($field === 'kode_gedung' ? 'Nama Tetap Ditampilkan' : 'NEW').'"', false);
        $this->assertDatabaseCount('buildings', 1);
        $this->assertSame('GKB-1', $building->fresh()->kode_gedung);
        $this->assertSame('Gedung Kuliah Bersama', $building->fresh()->nama_gedung);
    }

    public static function arrayFormInputs(): array
    {
        return [
            'create code' => ['post', 'kode_gedung'],
            'create name' => ['post', 'nama_gedung'],
            'edit code' => ['put', 'kode_gedung'],
            'edit name' => ['put', 'nama_gedung'],
        ];
    }

    public function test_fields_accept_the_migration_length_limit(): void
    {
        $data = ['kode_gedung' => str_repeat('K', 255), 'nama_gedung' => str_repeat('N', 255)];

        $this->actingAs($this->makeUser())->post(route('buildings.store'), $data)
            ->assertRedirect(route('buildings.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('buildings', $data);
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_code_or_name_and_pagination_preserves_the_query(string $search): void
    {
        for ($index = 1; $index <= 12; $index++) {
            $this->makeBuilding(['kode_gedung' => sprintf('LAB-%02d', $index), 'nama_gedung' => "Laboratorium $index"]);
        }

        $this->makeBuilding(['kode_gedung' => 'OTHER', 'nama_gedung' => 'Gedung Lain']);
        $this->actingAs($this->makeUser());
        $response = $this->get(route('buildings.index', ['q' => $search]));
        $response->assertOk()->assertDontSee('Gedung Lain');
        $buildings = $response->viewData('buildings');
        $this->assertSame(12, $buildings->total());
        $this->assertCount(10, $buildings->items());
        $this->assertStringContainsString('q='.urlencode($search), $buildings->nextPageUrl());

        $response = $this->get(route('buildings.index', ['q' => $search, 'page' => 2]));
        $response->assertOk()->assertSee('LAB-11')->assertSee('LAB-12');
        $this->assertCount(2, $response->viewData('buildings')->items());
    }

    public static function searchTerms(): array
    {
        return ['by code' => ['LAB-'], 'by name' => ['Laboratorium']];
    }

    public function test_empty_and_no_search_results_states_are_displayed(): void
    {
        $this->actingAs($this->makeUser())->get(route('buildings.index'))
            ->assertOk()->assertSee('Belum ada data gedung.');

        $this->makeBuilding();
        $this->get(route('buildings.index', ['q' => 'tidak-ada']))
            ->assertOk()->assertSee('Tidak ada gedung yang sesuai dengan pencarian.');
    }

    public function test_building_values_are_escaped_in_views(): void
    {
        $building = $this->makeBuilding(['nama_gedung' => '<script>alert(1)</script>']);

        $this->actingAs($this->makeUser())->get(route('buildings.index'))
            ->assertOk()->assertSee($building->nama_gedung)->assertDontSee($building->nama_gedung, false);
        $this->get(route('buildings.edit', $building))
            ->assertOk()->assertSee($building->nama_gedung)->assertDontSee($building->nama_gedung, false);
    }

    #[DataProvider('restrictedEndpoints')]
    public function test_non_admins_and_inactive_admin_cannot_access_any_building_action(string $role, bool $active, string $method, string $route): void
    {
        $building = $this->makeBuilding();
        $url = route($route, in_array($route, ['buildings.edit', 'buildings.update', 'buildings.destroy']) ? [$building] : []);

        $this->actingAs($this->makeUser($role, $active))->{$method}($url, ['kode_gedung' => 'NEW', 'nama_gedung' => 'Diubah'])
            ->assertForbidden();

        $this->assertDatabaseCount('buildings', 1);
        $this->assertSame('GKB-1', $building->fresh()->kode_gedung);
        $this->assertSame('Gedung Kuliah Bersama', $building->fresh()->nama_gedung);
    }

    public static function restrictedEndpoints(): array
    {
        $cases = [];

        foreach (['Pelapor', 'Teknisi', 'Pimpinan', 'Admin'] as $role) {
            foreach (self::endpoints() as $label => [$method, $route]) {
                $cases["$role $label"] = [$role, $role !== 'Admin', $method, $route];
            }
        }

        return $cases;
    }

    #[DataProvider('endpoints')]
    public function test_guest_is_redirected_to_login_for_every_building_action(string $method, string $route): void
    {
        $building = $this->makeBuilding();
        $url = route($route, in_array($route, ['buildings.edit', 'buildings.update', 'buildings.destroy']) ? [$building] : []);

        $this->{$method}($url, ['kode_gedung' => 'NEW', 'nama_gedung' => 'Diubah'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('buildings', 1);
        $this->assertSame('GKB-1', $building->fresh()->kode_gedung);
    }

    public static function endpoints(): array
    {
        return [
            'index' => ['get', 'buildings.index'],
            'create' => ['get', 'buildings.create'],
            'store' => ['post', 'buildings.store'],
            'edit' => ['get', 'buildings.edit'],
            'update PUT' => ['put', 'buildings.update'],
            'update PATCH' => ['patch', 'buildings.update'],
            'destroy' => ['delete', 'buildings.destroy'],
        ];
    }

    public function test_unknown_building_returns_not_found(): void
    {
        $this->actingAs($this->makeUser())->get(route('buildings.edit', 999))->assertNotFound();
        $this->put(route('buildings.update', 999), ['kode_gedung' => 'NEW', 'nama_gedung' => 'Baru'])->assertNotFound();
        $this->delete(route('buildings.destroy', 999))->assertNotFound();
    }

    private function makeBuilding(array $attributes = []): Building
    {
        return Building::create(array_merge([
            'kode_gedung' => 'GKB-1',
            'nama_gedung' => 'Gedung Kuliah Bersama',
        ], $attributes));
    }

    private function makeUser(string $roleName = 'Admin', bool $active = true): User
    {
        $role = Role::firstOrCreate(['nama_peran' => $roleName]);

        return User::create([
            'id_peran' => $role->id_peran,
            'nama' => 'Pengguna Uji '.$roleName,
            'email' => strtolower($roleName).'@example.test',
            'kata_sandi_hash' => 'building-test-password',
            'akun_aktif' => $active,
        ]);
    }
}
