<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportSeeder;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(ReportSeeder::class);

        $this->admin = User::where('email', 'admin@infracheck.ac.id')->first();
    }

    public function test_guest_cannot_access_verification_page(): void
    {
        $response = $this->get('/admin/verifikasi');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_verification_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/verifikasi');

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Laporan Kerusakan');
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('Access Point AP-B2-03');
    }

    public function test_admin_can_verify_a_pending_report(): void
    {
        $report = Report::whereHas('status', function ($q) {
            $q->where('nama_status', 'Menunggu Verifikasi');
        })->first();

        $response = $this->actingAs($this->admin)->post("/admin/verifikasi/{$report->id_laporan}/verify", [
            'catatan_verifikasi' => 'Laporan valid, teknisi segera dikonfirmasi.',
        ]);

        $response->assertRedirect('/admin/verifikasi');
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals('Diverifikasi', $report->status->nama_status);
        $this->assertEquals($this->admin->id_pengguna, $report->id_admin_verifikator);
        $this->assertNotNull($report->waktu_verifikasi);

        // Pastikan tercatat di status_histories
        $this->assertDatabaseHas('status_histories', [
            'id_laporan' => $report->id_laporan,
            'id_status' => $report->id_status,
            'id_pengubah' => $this->admin->id_pengguna,
        ]);
    }

    public function test_admin_cannot_reject_report_without_reason(): void
    {
        $report = Report::whereHas('status', function ($q) {
            $q->where('nama_status', 'Menunggu Verifikasi');
        })->first();

        $response = $this->actingAs($this->admin)->post("/admin/verifikasi/{$report->id_laporan}/reject", [
            'catatan_verifikasi' => '',
        ]);

        $response->assertSessionHasErrors(['catatan_verifikasi']);

        $report->refresh();
        $this->assertEquals('Menunggu Verifikasi', $report->status->nama_status);
    }

    public function test_admin_can_reject_report_with_reason(): void
    {
        $report = Report::whereHas('status', function ($q) {
            $q->where('nama_status', 'Menunggu Verifikasi');
        })->first();

        $reason = 'Perangkat bukan milik kampus dan tidak terdaftar di sistem.';

        $response = $this->actingAs($this->admin)->post("/admin/verifikasi/{$report->id_laporan}/reject", [
            'catatan_verifikasi' => $reason,
        ]);

        $response->assertRedirect('/admin/verifikasi');
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals('Ditolak', $report->status->nama_status);
        $this->assertEquals($reason, $report->catatan_verifikasi);
        $this->assertEquals($this->admin->id_pengguna, $report->id_admin_verifikator);

        // Pastikan tercatat di status_histories
        $this->assertDatabaseHas('status_histories', [
            'id_laporan' => $report->id_laporan,
            'id_status' => $report->id_status,
            'id_pengubah' => $this->admin->id_pengguna,
        ]);
    }
}
