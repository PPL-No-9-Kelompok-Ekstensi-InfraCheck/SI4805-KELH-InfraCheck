<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Priority;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pelapor;

    protected User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(ReportSeeder::class);

        $this->admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $this->pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $this->teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
    }

    public function test_guest_cannot_access_priority_page(): void
    {
        $this->get('/admin/prioritas')->assertRedirect('/login');
    }

    public function test_pelapor_and_teknisi_cannot_access_or_update_priority(): void
    {
        $report = Report::whereHas('status', fn ($q) => $q->where('nama_status', 'Diverifikasi'))->firstOrFail();
        $priority = Priority::where('nama_prioritas', 'Tinggi')->firstOrFail();

        $this->actingAs($this->pelapor)->get('/admin/prioritas')->assertForbidden();
        $this->actingAs($this->teknisi)->get('/admin/prioritas')->assertForbidden();

        $this->actingAs($this->pelapor)
            ->post("/admin/prioritas/{$report->id_laporan}", ['id_prioritas' => $priority->id_prioritas])
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->post("/admin/prioritas/{$report->id_laporan}", ['id_prioritas' => $priority->id_prioritas])
            ->assertForbidden();
    }

    public function test_admin_can_view_priority_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/prioritas');

        $response->assertStatus(200);
        $response->assertSee('Penentuan Prioritas Penanganan');
        $response->assertSee('Laporan Terverifikasi Tanpa Prioritas');
        $response->assertSee('Simpan Prioritas');
    }

    public function test_admin_can_set_priority_on_verified_report_without_priority(): void
    {
        $device = Device::firstOrFail();
        $verifiedStatus = ReportStatus::where('nama_status', 'Diverifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::where('nama_urgensi', 'Tinggi')->firstOrFail();
        $priority = Priority::where('nama_prioritas', 'Tinggi')->firstOrFail();

        $report = Report::create([
            'id_pelapor' => $this->pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $verifiedStatus->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'id_prioritas' => null,
            'id_admin_verifikator' => $this->admin->id_pengguna,
            'deskripsi_kerusakan' => 'Wi-Fi lantai 2 mati total saat jam kuliah.',
            'waktu_laporan' => now()->subHour(),
            'waktu_verifikasi' => now(),
        ]);

        $alasan = 'Wi-Fi satu lantai mati, dipakai 6 ruang kelas.';

        $response = $this->actingAs($this->admin)->post("/admin/prioritas/{$report->id_laporan}", [
            'id_prioritas' => $priority->id_prioritas,
            'alasan_prioritas' => $alasan,
        ]);

        $response->assertRedirect(route('admin.priority.index', ['selected' => $report->id_laporan]));
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals($priority->id_prioritas, $report->id_prioritas);

        $this->assertDatabaseHas('status_histories', [
            'id_laporan' => $report->id_laporan,
            'id_status' => $verifiedStatus->id_status,
            'id_pengubah' => $this->admin->id_pengguna,
            'catatan_perubahan' => "Prioritas penanganan ditetapkan menjadi Tinggi. Alasan: {$alasan}",
        ]);
    }

    public function test_admin_can_update_existing_priority(): void
    {
        $report = Report::whereHas('status', fn ($q) => $q->where('nama_status', 'Diverifikasi'))->firstOrFail();
        $priorityRendah = Priority::where('nama_prioritas', 'Rendah')->firstOrFail();

        $response = $this->actingAs($this->admin)->post("/admin/prioritas/{$report->id_laporan}", [
            'id_prioritas' => $priorityRendah->id_prioritas,
            'alasan_prioritas' => 'Masih tersedia perangkat cadangan di ruangan sebelah.',
        ]);

        $response->assertRedirect(route('admin.priority.index', ['selected' => $report->id_laporan]));
        $this->assertEquals($priorityRendah->id_prioritas, $report->fresh()->id_prioritas);
    }

    public function test_validation_rejects_missing_or_invalid_priority(): void
    {
        $report = Report::whereHas('status', fn ($q) => $q->where('nama_status', 'Diverifikasi'))->firstOrFail();

        $this->actingAs($this->admin)
            ->post("/admin/prioritas/{$report->id_laporan}", ['id_prioritas' => ''])
            ->assertSessionHasErrors(['id_prioritas']);

        $this->actingAs($this->admin)
            ->post("/admin/prioritas/{$report->id_laporan}", ['id_prioritas' => 99999])
            ->assertSessionHasErrors(['id_prioritas']);
    }

    public function test_cannot_set_priority_on_unverified_report(): void
    {
        $pendingReport = Report::whereHas('status', fn ($q) => $q->where('nama_status', 'Menunggu Verifikasi'))->firstOrFail();
        $priority = Priority::where('nama_prioritas', 'Sedang')->firstOrFail();

        $response = $this->actingAs($this->admin)->post("/admin/prioritas/{$pendingReport->id_laporan}", [
            'id_prioritas' => $priority->id_prioritas,
        ]);

        $response->assertRedirect(route('admin.priority.index'));
        $response->assertSessionHas('error');
        $this->assertNull($pendingReport->fresh()->id_prioritas);
    }
}
