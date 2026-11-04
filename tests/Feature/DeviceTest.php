<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\Priority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

class DeviceTest extends TestCase
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

    private function payload(array $override = []): array
    {
        return array_merge([
            'id_jenis'       => DeviceType::first()->id_jenis,
            'id_ruangan'     => Room::first()->id_ruangan,
            'kode_aset'      => 'AST-NEW-001',
            'nama_perangkat' => 'Perangkat Uji',
            'kondisi'        => 'Baik',
        ], $override);
    }

    public function test_index_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get(route('devices.index'));

        $response->assertStatus(200);
        $response->assertSee('Access Point Cisco AP-01');
        $response->assertSee('AST-AP-001');
    }

    public function test_create_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get(route('devices.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Perangkat');
    }

    public function test_edit_page_can_be_rendered(): void
    {
        $device = Device::first();

        $response = $this->actingAs($this->admin())->get(route('devices.edit', $device));

        $response->assertStatus(200);
        $response->assertSee($device->kode_aset);
    }

    public function test_store_creates_device(): void
    {
        $response = $this->actingAs($this->admin())->post(route('devices.store'), $this->payload());

        $response->assertRedirect(route('devices.index'));
        $this->assertDatabaseHas('devices', [
            'kode_aset'      => 'AST-NEW-001',
            'nama_perangkat' => 'Perangkat Uji',
            'kondisi'        => 'Baik',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('devices.store'), [
            'id_jenis'       => '',
            'id_ruangan'     => '',
            'kode_aset'      => '',
            'nama_perangkat' => '',
            'kondisi'        => '',
        ]);

        $response->assertSessionHasErrors(['id_jenis', 'id_ruangan', 'kode_aset', 'nama_perangkat', 'kondisi']);
    }

    public function test_store_validates_unique_kode_aset(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('devices.store'), $this->payload(['kode_aset' => 'AST-AP-001']));

        $response->assertSessionHasErrors('kode_aset');
    }

    public function test_store_validates_invalid_kondisi(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('devices.store'), $this->payload(['kondisi' => 'Hancur']));

        $response->assertSessionHasErrors('kondisi');
    }

    public function test_store_validates_relations_exist(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('devices.store'), $this->payload(['id_jenis' => 99999, 'id_ruangan' => 99999]));

        $response->assertSessionHasErrors(['id_jenis', 'id_ruangan']);
    }

    public function test_update_changes_device(): void
    {
        $device = Device::first();

        $response = $this->actingAs($this->admin())->put(route('devices.update', $device), $this->payload([
            'kode_aset'      => $device->kode_aset,
            'nama_perangkat' => 'Nama Diperbarui',
            'kondisi'        => 'Rusak Ringan',
        ]));

        $response->assertRedirect(route('devices.index'));
        $this->assertDatabaseHas('devices', [
            'id_perangkat'   => $device->id_perangkat,
            'nama_perangkat' => 'Nama Diperbarui',
            'kondisi'        => 'Rusak Ringan',
        ]);
    }

    public function test_update_allows_keeping_own_kode_aset(): void
    {
        $device = Device::first();

        $response = $this->actingAs($this->admin())->put(route('devices.update', $device), $this->payload([
            'kode_aset' => $device->kode_aset,
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('devices.index'));
    }

    public function test_destroy_deletes_unused_device(): void
    {
        $device = Device::create($this->payload(['kode_aset' => 'AST-DEL-001']));

        $response = $this->actingAs($this->admin())->delete(route('devices.destroy', $device));

        $response->assertRedirect(route('devices.index'));
        $this->assertDatabaseMissing('devices', ['id_perangkat' => $device->id_perangkat]);
    }

    public function test_destroy_is_blocked_when_reports_exist(): void
    {
        $device = Device::first();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->first();

        Report::create([
            'id_pelapor'           => $pelapor->id_pengguna,
            'id_perangkat'         => $device->id_perangkat,
            'id_ruangan'           => $device->id_ruangan,
            'id_status'            => ReportStatus::first()->id_status,
            'id_urgensi'           => UrgencyLevel::first()->id_urgensi,
            'id_prioritas'         => Priority::first()->id_prioritas,
            'deskripsi_kerusakan'  => 'Lampu indikator mati',
            'waktu_laporan'        => now(),
        ]);

        $response = $this->actingAs($this->admin())->delete(route('devices.destroy', $device));

        $response->assertRedirect(route('devices.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('devices', ['id_perangkat' => $device->id_perangkat]);
    }

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('devices.index'));
        $response->assertRedirect(route('login'));
    }
}
