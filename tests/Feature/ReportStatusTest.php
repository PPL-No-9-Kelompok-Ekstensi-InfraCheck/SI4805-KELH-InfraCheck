<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seed(ReportDemoSeeder::class);
    }

    private function userByRole(string $role): User
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('nama_peran', $role))
            ->firstOrFail();
    }

    public function test_admin_can_view_monitoring_page(): void
    {
        $response = $this->actingAs($this->userByRole('Admin'))->get('/laporan/status');

        $response->assertOk();
        $response->assertSee('Pemantauan Status Laporan');
        $response->assertSee('LP-2026-');
        $response->assertSee('Menampilkan 1–7 dari 75 laporan');
        $this->assertSame(75, $response->viewData('reports')->total());
    }

    public function test_pimpinan_can_view_monitoring_page(): void
    {
        $response = $this->actingAs($this->userByRole('Pimpinan'))->get('/laporan/status');

        $response->assertOk();
        $response->assertSee('Pemantauan Status Laporan');
        $response->assertDontSee('MASTER DATA');
    }

    public function test_pelapor_and_teknisi_cannot_view_monitoring_page(): void
    {
        $this->actingAs($this->userByRole('Pelapor'))
            ->get('/laporan/status')
            ->assertForbidden();

        $this->actingAs($this->userByRole('Teknisi'))
            ->get('/laporan/status')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/laporan/status')->assertRedirect('/login');
    }

    public function test_status_filter_only_affects_table_not_card_counts(): void
    {
        $selesai = ReportStatus::where('nama_status', 'Selesai')->firstOrFail();
        $diajukan = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?status='.$selesai->id_status);

        $response->assertOk();
        $this->assertSame(37, $response->viewData('reports')->total());
        $this->assertSame(12, (int) $response->viewData('counts')[$diajukan->id_status]);
    }

    public function test_search_by_report_number_returns_single_report(): void
    {
        $report = Report::query()->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?'.http_build_query(['cari' => $report->nomor_laporan]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('reports')->total());
        $this->assertSame($report->id_laporan, $response->viewData('reports')->first()->id_laporan);
    }

    public function test_search_by_device_name_returns_matching_reports(): void
    {
        $device = Device::query()->whereHas('reports')->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?'.http_build_query(['cari' => $device->nama_perangkat]));

        $response->assertOk();
        $this->assertGreaterThan(0, $response->viewData('reports')->total());
    }

    public function test_technician_column_uses_latest_assignment(): void
    {
        $report = Report::query()
            ->whereHas('status', fn ($query) => $query->where('nama_status', 'Selesai'))
            ->whereHas('assignments')
            ->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?'.http_build_query(['cari' => $report->nomor_laporan]));

        $response->assertOk();
        $response->assertSee($report->latestAssignment->technician->short_name);
    }

    public function test_pagination_keeps_active_filters(): void
    {
        $selesai = ReportStatus::where('nama_status', 'Selesai')->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?status='.$selesai->id_status);

        $response->assertOk();
        $response->assertSee('status='.$selesai->id_status, false);
    }

    public function test_period_filter_limits_reports_to_selected_day(): void
    {
        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?periode_mulai=2026-10-01&periode_selesai=2026-10-01');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->viewData('reports')->total());
        $this->assertLessThan(75, $response->viewData('reports')->total());
    }

    public function test_invalid_period_is_rejected(): void
    {
        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/status?periode_mulai=2026-10-13&periode_selesai=2026-10-01');

        $response->assertSessionHasErrors('periode_selesai');
    }

    public function test_cancelled_reports_are_hidden(): void
    {
        $report = Report::query()->firstOrFail();
        $cancelled = ReportStatus::where('nama_status', 'Dibatalkan')->firstOrFail();

        $report->update(['id_status' => $cancelled->id_status]);

        $response = $this->actingAs($this->userByRole('Admin'))->get('/laporan/status');

        $response->assertOk();
        $this->assertSame(74, $response->viewData('reports')->total());
        $response->assertDontSee($report->nomor_laporan);
    }

    public function test_admin_can_view_report_detail(): void
    {
        $report = Report::query()->firstOrFail();

        $response = $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/'.$report->id_laporan);

        $response->assertOk();
        $response->assertSee($report->nomor_laporan);
        $response->assertSee($report->deskripsi_kerusakan);
        $response->assertSee('Riwayat Status');
    }

    public function test_pelapor_cannot_view_report_detail(): void
    {
        $report = Report::query()->firstOrFail();

        $this->actingAs($this->userByRole('Pelapor'))
            ->get('/laporan/'.$report->id_laporan)
            ->assertForbidden();
    }

    public function test_cancelled_report_detail_returns_not_found(): void
    {
        $report = Report::query()->firstOrFail();
        $cancelled = ReportStatus::where('nama_status', 'Dibatalkan')->firstOrFail();

        $report->update(['id_status' => $cancelled->id_status]);

        $this->actingAs($this->userByRole('Admin'))
            ->get('/laporan/'.$report->id_laporan)
            ->assertNotFound();
    }
}
