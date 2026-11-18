<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use App\Models\Role;
use App\Models\Assignment;
use App\Models\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportSeeder;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $technician1;
    protected User $technician2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(ReportSeeder::class);

        $this->admin = User::where('email', 'admin@infracheck.ac.id')->first();
        $this->technician1 = User::where('email', 'agus.setiawan@infracheck.ac.id')->first();
        $this->technician2 = User::where('email', 'dewi.purnama@infracheck.ac.id')->first();
    }

    public function test_guest_cannot_access_assignment_page(): void
    {
        $response = $this->get('/admin/penugasan');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_assignment_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/penugasan');

        $response->assertStatus(200);
        $response->assertSee('Penugasan Laporan kepada Teknisi');
        $response->assertSee('Pilih Teknisi');
        $response->assertSee('Agus Setiawan');
        $response->assertSee('Dewi Purnama');
    }

    public function test_admin_can_assign_technician_to_report(): void
    {
        $report = Report::whereHas('status', function ($q) {
            $q->whereIn('nama_status', ['Diverifikasi', 'Menunggu Verifikasi']);
        })->first();

        $response = $this->actingAs($this->admin)->post("/admin/penugasan/{$report->id_laporan}/assign", [
            'id_teknisi' => $this->technician1->id_pengguna,
            'catatan_penugasan' => 'Mohon dicek segera, ruangan akan dipakai besok pagi.',
        ]);

        $response->assertRedirect("/admin/penugasan?report_id={$report->id_laporan}");
        $response->assertSessionHas('success');

        // Pastikan penugasan aktif terbentuk
        $this->assertDatabaseHas('assignments', [
            'id_laporan' => $report->id_laporan,
            'id_teknisi' => $this->technician1->id_pengguna,
            'id_admin_penugas' => $this->admin->id_pengguna,
            'status_penugasan' => 'aktif',
        ]);

        // Pastikan status laporan berubah menjadi 'Ditugaskan'
        $report->refresh();
        $this->assertEquals('Ditugaskan', $report->status->nama_status);

        // Pastikan riwayat status tercatat di status_histories
        $this->assertDatabaseHas('status_histories', [
            'id_laporan' => $report->id_laporan,
            'id_status' => $report->id_status,
            'id_pengubah' => $this->admin->id_pengguna,
        ]);
    }

    public function test_reassigning_report_deactivates_previous_assignment(): void
    {
        $report = Report::whereHas('status', function ($q) {
            $q->whereIn('nama_status', ['Diverifikasi', 'Menunggu Verifikasi']);
        })->first();

        // 1. Penugasan awal ke Teknisi 1 (Agus)
        $this->actingAs($this->admin)->post("/admin/penugasan/{$report->id_laporan}/assign", [
            'id_teknisi' => $this->technician1->id_pengguna,
            'catatan_penugasan' => 'Penugasan pertama.',
        ]);

        $assignment1 = Assignment::where('id_laporan', $report->id_laporan)
            ->where('id_teknisi', $this->technician1->id_pengguna)
            ->first();

        $this->assertEquals('aktif', $assignment1->status_penugasan);

        // 2. Alihkan penugasan ke Teknisi 2 (Dewi)
        $this->actingAs($this->admin)->post("/admin/penugasan/{$report->id_laporan}/assign", [
            'id_teknisi' => $this->technician2->id_pengguna,
            'catatan_penugasan' => 'Dialihkan karena teknisi pertama sedang berhalangan.',
        ]);

        $assignment1->refresh();
        $assignment2 = Assignment::where('id_laporan', $report->id_laporan)
            ->where('id_teknisi', $this->technician2->id_pengguna)
            ->first();

        // Penugasan lama harus dialihkan, penugasan baru harus aktif
        $this->assertEquals('dialihkan', $assignment1->status_penugasan);
        $this->assertEquals('aktif', $assignment2->status_penugasan);

        // Aturan bisnis: Hanya ada 1 penugasan aktif untuk laporan ini
        $activeAssignmentsCount = Assignment::where('id_laporan', $report->id_laporan)
            ->where('status_penugasan', 'aktif')
            ->count();
        $this->assertEquals(1, $activeAssignmentsCount);
    }

    public function test_cannot_assign_with_invalid_technician(): void
    {
        $report = Report::first();

        // Coba kirim ID teknisi yang tidak ada
        $response = $this->actingAs($this->admin)->post("/admin/penugasan/{$report->id_laporan}/assign", [
            'id_teknisi' => 99999,
        ]);

        $response->assertSessionHasErrors(['id_teknisi']);
    }
}
