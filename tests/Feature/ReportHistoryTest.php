<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Report;
use App\Models\StatusHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHistoryTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/riwayat-penanganan');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_report_history_index_page(): void
    {
        $admin = $this->userByRole('Admin');
        $response = $this->actingAs($admin)->get('/riwayat-penanganan');

        $response->assertOk();
        $response->assertSee('Riwayat Penanganan');
        $response->assertSee('Per Laporan');
        $response->assertSee('Per Perangkat');
        $response->assertSee('LP-2026-');
        $this->assertGreaterThan(0, $response->viewData('reports')->total());
    }

    public function test_pimpinan_can_view_report_history_index_page(): void
    {
        $pimpinan = $this->userByRole('Pimpinan');
        $response = $this->actingAs($pimpinan)->get('/riwayat-penanganan');

        $response->assertOk();
        $response->assertSee('Riwayat Penanganan');
        $this->assertGreaterThan(0, $response->viewData('reports')->total());
    }

    public function test_teknisi_only_sees_assigned_reports_in_history(): void
    {
        $teknisi = $this->userByRole('Teknisi');
        $response = $this->actingAs($teknisi)->get('/riwayat-penanganan');

        $response->assertOk();
        $reports = $response->viewData('reports');

        foreach ($reports as $report) {
            $this->assertTrue(
                $report->assignments()->where('id_teknisi', $teknisi->id_pengguna)->exists(),
                'Teknisi tidak boleh melihat laporan yang tidak ditugaskan kepadanya.'
            );
        }
    }

    public function test_pelapor_can_only_view_own_report_detail(): void
    {
        $pelapor = $this->userByRole('Pelapor');
        $ownReport = Report::where('id_pelapor', $pelapor->id_pengguna)->firstOrFail();
        $otherReport = Report::where('id_pelapor', '!=', $pelapor->id_pengguna)->firstOrFail();

        // Bisa buka laporannya sendiri
        $responseOwn = $this->actingAs($pelapor)->get('/riwayat-penanganan/' . $ownReport->id_laporan);
        $responseOwn->assertOk();
        $responseOwn->assertSee($ownReport->nomor_laporan);
        $responseOwn->assertSee('Ringkasan Penanganan');

        // Forbidden buka laporan orang lain
        $responseOther = $this->actingAs($pelapor)->get('/riwayat-penanganan/' . $otherReport->id_laporan);
        $responseOther->assertForbidden();
    }

    public function test_admin_can_view_detail_timeline_of_any_report(): void
    {
        $admin = $this->userByRole('Admin');
        $report = Report::firstOrFail();

        $response = $this->actingAs($admin)->get('/riwayat-penanganan/' . $report->id_laporan);

        $response->assertOk();
        $response->assertSee($report->nomor_laporan);
        $response->assertSee('Ringkasan Penanganan');
        $response->assertSee('Riwayat Perangkat Ini');
        $response->assertSee('Durasi penanganan');
    }

    public function test_devices_tab_displays_devices_with_report_count(): void
    {
        $admin = $this->userByRole('Admin');
        $response = $this->actingAs($admin)->get('/riwayat-penanganan?tab=perangkat');

        $response->assertOk();
        $response->assertSee('Kode Aset');
        $response->assertSee('Nama Perangkat');
        $response->assertSee('Kerusakan 12 Bln');
        $this->assertGreaterThan(0, $response->viewData('devices')->total());
    }

    public function test_filter_by_search_query(): void
    {
        $admin = $this->userByRole('Admin');
        $report = Report::with('device')->firstOrFail();

        $response = $this->actingAs($admin)->get('/riwayat-penanganan?cari=' . $report->nomor_laporan);

        $response->assertOk();
        $response->assertSee($report->nomor_laporan);
    }
}
