<?php

namespace Tests\Feature;

use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tests\TestCase;

class DeviceTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin()
    {
        return User::where('email', 'admin@infracheck.ac.id')->first();
    }

    public function test_index_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get(route('device-types.index'));

        $response->assertStatus(200);
        $response->assertSee('Access Point Wi-Fi');
    }

    public function test_store_creates_device_type(): void
    {
        $category = InfrastructureCategory::where('nama_kategori', 'Komputer & Periferal')->first();

        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'Laptop',
        ]);

        $response->assertRedirect(route('device-types.index'));
        $this->assertDatabaseHas('device_types', [
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'Laptop',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => '',
            'nama_jenis' => '',
        ]);

        $response->assertSessionHasErrors(['id_kategori', 'nama_jenis']);
    }

    public function test_store_validates_unique_name(): void
    {
        $category = InfrastructureCategory::first();

        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'Access Point Wi-Fi',
        ]);

        $response->assertSessionHasErrors('nama_jenis');
    }

    public function test_store_validates_category_exists(): void
    {
        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => 99999,
            'nama_jenis' => 'Tablet',
        ]);

        $response->assertSessionHasErrors('id_kategori');
    }

    public function test_update_changes_device_type(): void
    {
        $type = DeviceType::first();

        $response = $this->actingAs($this->admin())->put(route('device-types.update', $type), [
            'id_kategori' => $type->id_kategori,
            'nama_jenis' => 'Renamed Jenis',
        ]);

        $response->assertRedirect(route('device-types.index'));
        $this->assertDatabaseHas('device_types', [
            'id_jenis' => $type->id_jenis,
            'nama_jenis' => 'Renamed Jenis',
        ]);
    }

    public function test_destroy_deletes_unused_type(): void
    {
        $category = InfrastructureCategory::first();
        $type = DeviceType::create([
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'To Delete',
        ]);

        $response = $this->actingAs($this->admin())->delete(route('device-types.destroy', $type));

        $response->assertRedirect(route('device-types.index'));
        $this->assertDatabaseMissing('device_types', ['id_jenis' => $type->id_jenis]);
    }

    public function test_destroy_is_blocked_when_devices_exist(): void
    {
        $type = DeviceType::where('nama_jenis', 'Access Point Wi-Fi')->first();

        $response = $this->actingAs($this->admin())->delete(route('device-types.destroy', $type));

        $response->assertRedirect(route('device-types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('device_types', ['id_jenis' => $type->id_jenis]);
    }

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('device-types.index'));
        $response->assertRedirect(route('login'));
    }

    public static function nonAdminEmails(): array
    {
        return [
            'teknisi' => ['teknisi@infracheck.ac.id'],
            'pelapor' => ['pelapor@infracheck.ac.id'],
            'pimpinan' => ['pimpinan@infracheck.ac.id'],
        ];
    }

    #[DataProvider('nonAdminEmails')]
    public function test_non_admin_tidak_boleh_membuka_halaman_kelola(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();
        $type = DeviceType::firstOrFail();

        $this->actingAs($user)->get(route('device-types.index'))->assertForbidden();
        $this->actingAs($user)->get(route('device-types.create'))->assertForbidden();
        $this->actingAs($user)->get(route('device-types.edit', $type))->assertForbidden();
    }

    #[DataProvider('nonAdminEmails')]
    public function test_non_admin_tidak_boleh_mengubah_data(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();
        $category = InfrastructureCategory::firstOrFail();
        $type = DeviceType::firstOrFail();

        $this->actingAs($user)->post(route('device-types.store'), [
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'Dibuat Non Admin',
        ])->assertForbidden();
        $this->assertDatabaseMissing('device_types', ['nama_jenis' => 'Dibuat Non Admin']);

        $this->actingAs($user)->put(route('device-types.update', $type), [
            'id_kategori' => $type->id_kategori,
            'nama_jenis' => 'Diubah Non Admin',
        ])->assertForbidden();
        $this->assertDatabaseMissing('device_types', ['nama_jenis' => 'Diubah Non Admin']);

        $unused = DeviceType::create([
            'id_kategori' => $category->id_kategori,
            'nama_jenis' => 'Tidak Boleh Dihapus',
        ]);

        $this->actingAs($user)->delete(route('device-types.destroy', $unused))->assertForbidden();
        $this->assertDatabaseHas('device_types', ['id_jenis' => $unused->id_jenis]);
    }

    public function test_admin_dapat_membuka_form_tambah_dan_edit(): void
    {
        $type = DeviceType::firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('device-types.create'))
            ->assertOk()
            ->assertSee('Tambah Jenis Perangkat');

        $this->actingAs($this->admin())
            ->get(route('device-types.edit', $type))
            ->assertOk()
            ->assertSee('Edit Jenis Perangkat');
    }

    public function test_menu_jenis_perangkat_hanya_tampil_untuk_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertSee('Jenis Perangkat');

        $this->actingAs(User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail())
            ->get(route('dashboard'))
            ->assertDontSee('Jenis Perangkat');
    }

    public function test_route_show_tidak_tersedia(): void
    {
        $type = DeviceType::firstOrFail();

        // Route show dihapus; URI yang sama hanya melayani PUT/PATCH.
        $this->withoutExceptionHandling();
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->actingAs($this->admin())
            ->get("/device-types/{$type->id_jenis}");
    }

    public function test_database_menolak_nama_jenis_duplikat(): void
    {
        $type = DeviceType::firstOrFail();

        $this->expectException(QueryException::class);

        DeviceType::create([
            'id_kategori' => $type->id_kategori,
            'nama_jenis' => $type->nama_jenis,
        ]);
    }
}
