<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InfrastructureCategory;
use App\Models\DeviceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

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
            'nama_jenis'  => 'Laptop',
        ]);

        $response->assertRedirect(route('device-types.index'));
        $this->assertDatabaseHas('device_types', [
            'id_kategori' => $category->id_kategori,
            'nama_jenis'  => 'Laptop',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => '',
            'nama_jenis'  => '',
        ]);

        $response->assertSessionHasErrors(['id_kategori', 'nama_jenis']);
    }

    public function test_store_validates_unique_name(): void
    {
        $category = InfrastructureCategory::first();

        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => $category->id_kategori,
            'nama_jenis'  => 'Access Point Wi-Fi',
        ]);

        $response->assertSessionHasErrors('nama_jenis');
    }

    public function test_store_validates_category_exists(): void
    {
        $response = $this->actingAs($this->admin())->post(route('device-types.store'), [
            'id_kategori' => 99999,
            'nama_jenis'  => 'Tablet',
        ]);

        $response->assertSessionHasErrors('id_kategori');
    }

    public function test_update_changes_device_type(): void
    {
        $type = DeviceType::first();

        $response = $this->actingAs($this->admin())->put(route('device-types.update', $type), [
            'id_kategori' => $type->id_kategori,
            'nama_jenis'  => 'Renamed Jenis',
        ]);

        $response->assertRedirect(route('device-types.index'));
        $this->assertDatabaseHas('device_types', [
            'id_jenis'   => $type->id_jenis,
            'nama_jenis' => 'Renamed Jenis',
        ]);
    }

    public function test_destroy_deletes_unused_type(): void
    {
        $category = InfrastructureCategory::first();
        $type = DeviceType::create([
            'id_kategori' => $category->id_kategori,
            'nama_jenis'  => 'To Delete',
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
}
