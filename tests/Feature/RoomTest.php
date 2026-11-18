<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\UrgencyLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
    }

    public function test_admin_can_view_rooms_and_both_dashboard_links(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->makeUser())->get(route('rooms.index'));

        $response->assertOk()->assertSee('R.101')->assertSee('Ruang Kuliah 101')
            ->assertSee('GKB-1')->assertSee('Gedung Kuliah Bersama')
            ->assertSee(route('rooms.edit', $room))->assertSee(route('buildings.index'))
            ->assertSee("return confirm('Hapus ruangan ini? Tindakan ini tidak dapat dibatalkan.');", false);
        $this->get(route('dashboard'))->assertOk()
            ->assertSee(route('rooms.index'))->assertSee(route('buildings.index'));
    }

    public function test_create_and_edit_forms_show_building_options_and_current_selection(): void
    {
        $building = $this->makeBuilding();
        $other = $this->makeBuilding(['kode_gedung' => 'LAB-TI', 'nama_gedung' => 'Gedung Laboratorium']);
        $room = $this->makeRoom($other);

        $this->actingAs($this->makeUser())->get(route('rooms.create'))->assertOk()
            ->assertSee('Tambah Ruangan')->assertSee('name="id_gedung"', false)
            ->assertSee('GKB-1')->assertSee('LAB-TI');
        $response = $this->get(route('rooms.edit', $room));

        $response->assertOk()->assertSee('Edit Ruangan')->assertSee('value="R.101"', false)
            ->assertSee('value="'.$other->id_gedung.'" selected', false);
        $this->assertSame([$building->id_gedung, $other->id_gedung], $response->viewData('buildings')->modelKeys());
    }

    public function test_create_form_explains_when_no_buildings_are_available(): void
    {
        $this->actingAs($this->makeUser())->get(route('rooms.create'))->assertOk()
            ->assertSee('Belum ada data gedung.')->assertSee(route('buildings.create'))
            ->assertSee('type="submit" disabled', false);
    }

    public function test_admin_can_create_room_using_only_validated_fields(): void
    {
        $building = $this->makeBuilding();
        $data = ['id_gedung' => $building->id_gedung, 'kode_ruangan' => 'LAB.01', 'nama_ruangan' => 'Laboratorium Komputer'];

        $this->actingAs($this->makeUser())->post(route('rooms.store'), $data + ['id_ruangan' => 999])
            ->assertRedirect(route('rooms.index'))->assertSessionHas('success', 'Ruangan berhasil ditambahkan.');

        $this->assertDatabaseHas('rooms', $data);
        $this->assertDatabaseMissing('rooms', ['id_ruangan' => 999]);
        $this->get(route('rooms.index'))->assertOk()->assertSee('Ruangan berhasil ditambahkan.');
    }

    public function test_admin_can_update_room_and_move_it_to_another_building(): void
    {
        $room = $this->makeRoom();
        $building = $this->makeBuilding(['kode_gedung' => 'LAB-TI']);
        $data = ['id_gedung' => $building->id_gedung, 'kode_ruangan' => 'LAB.02', 'nama_ruangan' => 'Laboratorium Baru'];

        $this->actingAs($this->makeUser())->put(route('rooms.update', $room), $data + ['id_ruangan' => 999])
            ->assertRedirect(route('rooms.index'))->assertSessionHas('success', 'Ruangan berhasil diperbarui.');

        $this->assertDatabaseHas('rooms', ['id_ruangan' => $room->id_ruangan] + $data);
        $this->assertDatabaseMissing('rooms', ['id_ruangan' => 999]);
        $this->get(route('rooms.index'))->assertOk()->assertSee('Ruangan berhasil diperbarui.');
    }

    public function test_patch_can_keep_the_room_code_and_building(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->makeUser())->patch(route('rooms.update', $room), [
            'id_gedung' => $room->id_gedung, 'kode_ruangan' => 'R.101', 'nama_ruangan' => 'Nama Diperbarui',
        ])->assertRedirect(route('rooms.index'))->assertSessionHasNoErrors();

        $this->assertSame('Nama Diperbarui', $room->fresh()->nama_ruangan);
    }

    public function test_room_code_can_be_reused_in_another_building_without_changing_the_schema(): void
    {
        $this->makeRoom();
        $building = $this->makeBuilding(['kode_gedung' => 'OTHER']);
        $data = ['id_gedung' => $building->id_gedung, 'kode_ruangan' => 'R.101', 'nama_ruangan' => 'Ruangan Gedung Lain'];

        $this->actingAs($this->makeUser())->post(route('rooms.store'), $data)
            ->assertRedirect(route('rooms.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rooms', $data);
        $this->assertDatabaseCount('rooms', 2);
    }

    public function test_admin_can_delete_room_without_references_and_preserve_building(): void
    {
        $room = $this->makeRoom();
        $building = $room->building;

        $this->actingAs($this->makeUser())->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.index'))->assertSessionHas('success', 'Ruangan berhasil dihapus.');

        $this->assertModelMissing($room);
        $this->assertModelExists($building);
        $this->get(route('rooms.index'))->assertOk()->assertSee('Ruangan berhasil dihapus.');
    }

    public function test_room_referenced_by_device_cannot_be_deleted(): void
    {
        $room = $this->makeRoom();
        $device = $this->makeDevice($room);

        $this->actingAs($this->makeUser())->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.index'))
            ->assertSessionHas('error', 'Ruangan tidak dapat dihapus karena masih direferensikan perangkat atau laporan.');

        $this->assertModelExists($room);
        $this->assertModelExists($device);
        $this->get(route('rooms.index'))->assertOk()
            ->assertSee('Ruangan tidak dapat dihapus karena masih direferensikan perangkat atau laporan.');
    }

    public function test_room_referenced_only_by_report_cannot_be_deleted(): void
    {
        $room = $this->makeRoom();
        // A report has its own room FK, independent of the device's current room.
        $deviceRoom = $this->makeRoom($room->building, ['kode_ruangan' => 'R.102']);
        $device = $this->makeDevice($deviceRoom);
        $admin = $this->makeUser();
        $status = ReportStatus::create(['nama_status' => 'Diajukan']);
        $urgency = UrgencyLevel::create(['nama_urgensi' => 'Rendah']);
        $report = Report::create([
            'id_pelapor' => $admin->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $room->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgency->id_urgensi,
            'deskripsi_kerusakan' => 'Perangkat bermasalah.',
            'waktu_laporan' => '2026-11-08 09:00:00',
        ]);

        $this->actingAs($admin)->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.index'))->assertSessionHas('error');

        $this->assertModelExists($room);
        $this->assertModelExists($report);
        $this->assertModelExists($device);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected_without_mutating_rooms(string $method, string $field, mixed $value, string $message): void
    {
        $room = $this->makeRoom();
        $url = $method === 'post' ? route('rooms.store') : route('rooms.update', $room);
        $data = ['id_gedung' => $room->id_gedung, 'kode_ruangan' => 'NEW', 'nama_ruangan' => 'Ruangan Baru'];
        $data[$field] = $value;

        $this->actingAs($this->makeUser())->{$method}($url, $data)->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseHas('rooms', [
            'id_ruangan' => $room->id_ruangan, 'id_gedung' => $room->id_gedung,
            'kode_ruangan' => 'R.101', 'nama_ruangan' => 'Ruang Kuliah 101',
        ]);
    }

    public static function invalidInputs(): array
    {
        $cases = [];

        foreach (['post', 'put'] as $method) {
            foreach (['kode_ruangan' => 'Kode ruangan', 'nama_ruangan' => 'Nama ruangan'] as $field => $label) {
                foreach ([
                    'empty' => ['', "$label wajib diisi."],
                    'whitespace' => ['   ', "$label wajib diisi."],
                    'array' => [['invalid'], "$label harus berupa teks."],
                    'too long' => [str_repeat('x', 256), "$label maksimal 255 karakter."],
                ] as $case => [$value, $message]) {
                    $cases["$method $field $case"] = [$method, $field, $value, $message];
                }
            }

            foreach ([
                'empty' => ['', 'Gedung wajib dipilih.'],
                'array' => [['invalid'], 'Pilihan gedung tidak valid.'],
                'text' => ['invalid', 'Pilihan gedung tidak valid.'],
                'fraction' => [1.5, 'Pilihan gedung tidak valid.'],
                'missing' => [999, 'Gedung yang dipilih tidak tersedia.'],
            ] as $case => [$value, $message]) {
                $cases["$method id_gedung $case"] = [$method, 'id_gedung', $value, $message];
            }
        }

        return $cases;
    }

    public function test_room_fields_accept_the_migration_length_limit(): void
    {
        $building = $this->makeBuilding();
        $data = ['id_gedung' => $building->id_gedung, 'kode_ruangan' => str_repeat('K', 255), 'nama_ruangan' => str_repeat('N', 255)];

        $this->actingAs($this->makeUser())->post(route('rooms.store'), $data)
            ->assertRedirect(route('rooms.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rooms', $data);
    }

    #[DataProvider('arrayFormInputs')]
    public function test_form_survives_array_input_and_preserves_other_values(string $method, string $field): void
    {
        $room = $this->makeRoom();
        $form = $method === 'post' ? route('rooms.create') : route('rooms.edit', $room);
        $url = $method === 'post' ? route('rooms.store') : route('rooms.update', $room);
        $data = ['id_gedung' => (string) $room->id_gedung, 'kode_ruangan' => 'NEW', 'nama_ruangan' => 'Nama Tetap Ditampilkan'];
        $data[$field] = ['invalid'];

        $response = $this->actingAs($this->makeUser())->from($form)->{$method}($url, $data);

        $response->assertRedirect($form);
        $response = $this->followRedirects($response);
        $response->assertOk()->assertSee('Periksa kembali data yang Anda masukkan.');
        if ($field !== 'id_gedung') {
            $response->assertSee('value="'.$room->id_gedung.'" selected', false);
        }
        if ($field !== 'kode_ruangan') {
            $response->assertSee('value="NEW"', false);
        }
        if ($field !== 'nama_ruangan') {
            $response->assertSee('value="Nama Tetap Ditampilkan"', false);
        }
        $this->assertSame('Ruang Kuliah 101', $room->fresh()->nama_ruangan);
    }

    public static function arrayFormInputs(): array
    {
        $cases = [];

        foreach (['post', 'put'] as $method) {
            foreach (['id_gedung', 'kode_ruangan', 'nama_ruangan'] as $field) {
                $cases["$method $field"] = [$method, $field];
            }
        }

        return $cases;
    }

    #[DataProvider('searchTerms')]
    public function test_search_matches_room_or_building_and_pagination_preserves_query(string $search): void
    {
        $building = $this->makeBuilding();
        for ($index = 1; $index <= 12; $index++) {
            $this->makeRoom($building, ['kode_ruangan' => sprintf('LAB-%02d', $index), 'nama_ruangan' => "Laboratorium $index"]);
        }
        $other = $this->makeBuilding(['kode_gedung' => 'OTHER', 'nama_gedung' => 'Gedung Lain']);
        $this->makeRoom($other, ['kode_ruangan' => 'OTHER', 'nama_ruangan' => 'Ruangan Lain']);

        $response = $this->actingAs($this->makeUser())->get(route('rooms.index', ['q' => $search]));

        $response->assertOk()->assertDontSee('Ruangan Lain');
        $rooms = $response->viewData('rooms');
        $this->assertSame(12, $rooms->total());
        $this->assertCount(10, $rooms->items());
        $this->assertStringContainsString('q='.rawurlencode($search), $rooms->nextPageUrl());
        $response = $this->get(route('rooms.index', ['q' => $search, 'page' => 2]));
        $response->assertOk()->assertSee('LAB-11')->assertSee('LAB-12');
        $this->assertCount(2, $response->viewData('rooms')->items());
    }

    public static function searchTerms(): array
    {
        return ['room code' => ['LAB-'], 'room name' => ['Laboratorium'], 'building code' => ['GKB-1'], 'building name' => ['Kuliah Bersama']];
    }

    public function test_empty_and_no_search_results_states_are_displayed(): void
    {
        $this->actingAs($this->makeUser())->get(route('rooms.index'))
            ->assertOk()->assertSee('Belum ada data ruangan.');
        $this->makeRoom();

        $this->get(route('rooms.index', ['q' => 'tidak-ada']))
            ->assertOk()->assertSee('Tidak ada ruangan yang sesuai dengan pencarian.');
    }

    public function test_search_is_trimmed_and_invalid_search_input_is_rejected(): void
    {
        $this->makeRoom();

        $this->actingAs($this->makeUser())->get(route('rooms.index', ['q' => '  R.101  ']))
            ->assertOk()->assertSee('Ruang Kuliah 101')->assertViewHas('search', 'R.101');
        $this->get(route('rooms.index', ['q' => ['invalid']]))->assertSessionHasErrors('q');
        $this->get(route('rooms.index', ['q' => str_repeat('x', 256)]))->assertSessionHasErrors('q');
    }

    public function test_room_and_building_values_are_escaped_in_list_and_forms(): void
    {
        $building = $this->makeBuilding(['kode_gedung' => '<b>GKB</b>', 'nama_gedung' => '<script>building()</script>']);
        $room = $this->makeRoom($building, ['kode_ruangan' => '<b>101</b>', 'nama_ruangan' => '<script>room()</script>']);
        $this->actingAs($this->makeUser());

        foreach ([route('rooms.index'), route('rooms.edit', $room)] as $url) {
            $response = $this->get($url)->assertOk();
            foreach ([$building->kode_gedung, $building->nama_gedung, $room->kode_ruangan, $room->nama_ruangan] as $value) {
                $response->assertSee($value)->assertDontSee($value, false);
            }
        }
    }

    #[DataProvider('restrictedEndpoints')]
    public function test_non_admins_and_inactive_admin_cannot_access_any_room_action(string $role, bool $active, string $method, string $route): void
    {
        $room = $this->makeRoom();
        $url = route($route, in_array($route, ['rooms.edit', 'rooms.update', 'rooms.destroy']) ? [$room] : []);

        $this->actingAs($this->makeUser($role, $active))->{$method}($url, [
            'id_gedung' => $room->id_gedung, 'kode_ruangan' => 'NEW', 'nama_ruangan' => 'Diubah',
        ])->assertForbidden();

        $this->assertDatabaseCount('rooms', 1);
        $this->assertSame('R.101', $room->fresh()->kode_ruangan);
        $this->assertSame('Ruang Kuliah 101', $room->fresh()->nama_ruangan);
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

    #[DataProvider('restrictedDashboardUsers')]
    public function test_dashboard_hides_room_link_from_unauthorized_users(string $role, bool $active): void
    {
        $this->actingAs($this->makeUser($role, $active))->get(route('dashboard'))
            ->assertOk()->assertDontSee(route('rooms.index'));
    }

    public static function restrictedDashboardUsers(): array
    {
        return ['reporter' => ['Pelapor', true], 'technician' => ['Teknisi', true], 'leader' => ['Pimpinan', true], 'inactive admin' => ['Admin', false]];
    }

    #[DataProvider('endpoints')]
    public function test_guest_is_redirected_to_login_for_every_room_action(string $method, string $route): void
    {
        $room = $this->makeRoom();
        $url = route($route, in_array($route, ['rooms.edit', 'rooms.update', 'rooms.destroy']) ? [$room] : []);

        $this->{$method}($url, ['id_gedung' => $room->id_gedung, 'kode_ruangan' => 'NEW', 'nama_ruangan' => 'Diubah'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('rooms', 1);
        $this->assertSame('R.101', $room->fresh()->kode_ruangan);
    }

    public static function endpoints(): array
    {
        return [
            'index' => ['get', 'rooms.index'],
            'create' => ['get', 'rooms.create'],
            'store' => ['post', 'rooms.store'],
            'edit' => ['get', 'rooms.edit'],
            'update PUT' => ['put', 'rooms.update'],
            'update PATCH' => ['patch', 'rooms.update'],
            'destroy' => ['delete', 'rooms.destroy'],
        ];
    }

    public function test_unknown_room_returns_not_found(): void
    {
        $this->actingAs($this->makeUser())->get(route('rooms.edit', 999))->assertNotFound();
        $this->put(route('rooms.update', 999), [])->assertNotFound();
        $this->delete(route('rooms.destroy', 999))->assertNotFound();
    }

    private function makeBuilding(array $attributes = []): Building
    {
        return Building::create(array_merge(['kode_gedung' => 'GKB-1', 'nama_gedung' => 'Gedung Kuliah Bersama'], $attributes));
    }

    private function makeRoom(?Building $building = null, array $attributes = []): Room
    {
        $building ??= $this->makeBuilding();

        return Room::create(array_merge([
            'id_gedung' => $building->id_gedung, 'kode_ruangan' => 'R.101', 'nama_ruangan' => 'Ruang Kuliah 101',
        ], $attributes));
    }

    private function makeDevice(Room $room): Device
    {
        $category = InfrastructureCategory::create(['nama_kategori' => 'Komputer']);
        $type = DeviceType::create(['id_kategori' => $category->id_kategori, 'nama_jenis' => 'PC']);

        return Device::create([
            'id_jenis' => $type->id_jenis, 'id_ruangan' => $room->id_ruangan,
            'kode_aset' => 'PC-001', 'nama_perangkat' => 'Komputer Uji', 'kondisi' => 'Baik',
        ]);
    }

    private function makeUser(string $roleName = 'Admin', bool $active = true): User
    {
        $role = Role::firstOrCreate(['nama_peran' => $roleName]);

        return User::create([
            'id_peran' => $role->id_peran,
            'nama' => 'Pengguna Uji '.$roleName,
            'email' => strtolower($roleName).'@example.test',
            'kata_sandi_hash' => 'room-test-password',
            'akun_aktif' => $active,
        ]);
    }
}
