<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairSchedule;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepairScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('schedules.index'))->assertRedirect('/login');
    }

    public function test_pelapor_cannot_access_schedules_calendar(): void
    {
        $this->actingAs($this->pelapor())
            ->get(route('schedules.index'))
            ->assertForbidden();
    }

    public function test_admin_and_pimpinan_can_view_schedules(): void
    {
        $this->actingAs($this->admin())
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Penjadwalan Perbaikan')
            ->assertSee('Semua teknisi');

        $this->actingAs($this->pimpinan())
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Penjadwalan Perbaikan');
    }

    public function test_teknisi_can_view_schedules(): void
    {
        $response = $this->actingAs($this->teknisi())->get(route('schedules.index'));

        $response->assertOk();
        $response->assertSee('Penjadwalan Perbaikan');
    }

    public function test_admin_can_schedule_repair_for_any_assignment(): void
    {
        $admin = $this->admin();
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        $startTime = now()->addDays(2)->setHour(9)->setMinute(0);
        $targetTime = (clone $startTime)->addHours(3);

        $response = $this->actingAs($admin)->post(route('schedules.store'), [
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => $startTime->format('Y-m-d H:i'),
            'target_selesai' => $targetTime->format('Y-m-d H:i'),
            'catatan_jadwal' => 'Dikerjakan pagi hari oleh tim teknisi.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('repair_schedules', [
            'id_penugasan' => $assignment->id_penugasan,
            'catatan_jadwal' => 'Dikerjakan pagi hari oleh tim teknisi.',
        ]);
    }

    public function test_teknisi_can_schedule_own_assignment(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        $startTime = now()->addDay()->setHour(10)->setMinute(0);
        $targetTime = (clone $startTime)->addHours(2);

        $response = $this->actingAs($teknisi)->post(route('schedules.store'), [
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => $startTime->format('Y-m-d H:i'),
            'target_selesai' => $targetTime->format('Y-m-d H:i'),
            'catatan_jadwal' => 'Jadwal pengecekan komponen proyektor.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('repair_schedules', [
            'id_penugasan' => $assignment->id_penugasan,
            'catatan_jadwal' => 'Jadwal pengecekan komponen proyektor.',
        ]);
    }

    public function test_teknisi_cannot_schedule_other_technician_assignment(): void
    {
        $teknisiA = $this->teknisi();
        $teknisiB = $this->makeUser('Teknisi', 'teknisi-b@infracheck.ac.id');
        $assignmentB = $this->makeAssignmentWithoutSchedule($teknisiB);

        $this->actingAs($teknisiA)->post(route('schedules.store'), [
            'id_penugasan' => $assignmentB->id_penugasan,
            'waktu_mulai' => now()->addDay()->format('Y-m-d H:i'),
            'target_selesai' => now()->addDays(2)->format('Y-m-d H:i'),
        ])->assertForbidden();

        $this->assertDatabaseMissing('repair_schedules', [
            'id_penugasan' => $assignmentB->id_penugasan,
        ]);
    }

    public function test_validation_target_selesai_must_be_after_or_equal_waktu_mulai(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        $startTime = now()->addDays(2)->setHour(14)->setMinute(0);
        $invalidTarget = (clone $startTime)->subHours(2);

        $response = $this->actingAs($teknisi)->post(route('schedules.store'), [
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => $startTime->format('Y-m-d H:i'),
            'target_selesai' => $invalidTarget->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasErrors('target_selesai');
        $this->assertDatabaseMissing('repair_schedules', [
            'id_penugasan' => $assignment->id_penugasan,
        ]);
    }

    public function test_cannot_create_duplicate_schedule_for_same_assignment(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        RepairSchedule::create([
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => now()->addDay(),
            'target_selesai' => now()->addDays(2),
        ]);

        $response = $this->actingAs($teknisi)->post(route('schedules.store'), [
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i'),
            'target_selesai' => now()->addDays(4)->format('Y-m-d H:i'),
        ]);

        $response->assertSessionHasErrors('id_penugasan');
    }

    public function test_authorized_user_can_update_schedule(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        $schedule = RepairSchedule::create([
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => now()->addDay()->setHour(8)->setMinute(0),
            'target_selesai' => now()->addDay()->setHour(11)->setMinute(0),
            'catatan_jadwal' => 'Jadwal awal',
        ]);

        $newStartTime = now()->addDays(2)->setHour(13)->setMinute(0);
        $newTargetTime = (clone $newStartTime)->addHours(3);

        $response = $this->actingAs($teknisi)->put(route('schedules.update', $schedule), [
            'waktu_mulai' => $newStartTime->format('Y-m-d H:i'),
            'target_selesai' => $newTargetTime->format('Y-m-d H:i'),
            'catatan_jadwal' => 'Jadwal diundur karena ruangan dipakai kuliah pengganti.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $schedule->refresh();
        $this->assertSame('Jadwal diundur karena ruangan dipakai kuliah pengganti.', $schedule->catatan_jadwal);
    }

    public function test_unauthorized_teknisi_cannot_update_other_schedule(): void
    {
        $teknisiA = $this->teknisi();
        $teknisiB = $this->makeUser('Teknisi', 'teknisi-b@infracheck.ac.id');
        $assignmentA = $this->makeAssignmentWithoutSchedule($teknisiA);

        $schedule = RepairSchedule::create([
            'id_penugasan' => $assignmentA->id_penugasan,
            'waktu_mulai' => now()->addDay(),
            'target_selesai' => now()->addDays(2),
        ]);

        $this->actingAs($teknisiB)->put(route('schedules.update', $schedule), [
            'waktu_mulai' => now()->addDays(3)->format('Y-m-d H:i'),
            'target_selesai' => now()->addDays(4)->format('Y-m-d H:i'),
        ])->assertForbidden();
    }

    public function test_authorized_user_can_cancel_schedule(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->makeAssignmentWithoutSchedule($teknisi);

        $schedule = RepairSchedule::create([
            'id_penugasan' => $assignment->id_penugasan,
            'waktu_mulai' => now()->addDay(),
            'target_selesai' => now()->addDays(2),
        ]);

        $response = $this->actingAs($teknisi)->delete(route('schedules.destroy', $schedule));

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('repair_schedules', [
            'id_jadwal' => $schedule->id_jadwal,
        ]);

        // Penugasan tetap ada
        $this->assertDatabaseHas('assignments', [
            'id_penugasan' => $assignment->id_penugasan,
        ]);
    }

    public function test_unauthorized_teknisi_cannot_cancel_other_schedule(): void
    {
        $teknisiA = $this->teknisi();
        $teknisiB = $this->makeUser('Teknisi', 'teknisi-b@infracheck.ac.id');
        $assignmentA = $this->makeAssignmentWithoutSchedule($teknisiA);

        $schedule = RepairSchedule::create([
            'id_penugasan' => $assignmentA->id_penugasan,
            'waktu_mulai' => now()->addDay(),
            'target_selesai' => now()->addDays(2),
        ]);

        $this->actingAs($teknisiB)
            ->delete(route('schedules.destroy', $schedule))
            ->assertForbidden();

        $this->assertDatabaseHas('repair_schedules', [
            'id_jadwal' => $schedule->id_jadwal,
        ]);
    }

    // ==================== HELPER METHODS ====================

    private function admin(): User
    {
        return User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
    }

    private function teknisi(): User
    {
        return User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
    }

    private function pelapor(): User
    {
        return User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
    }

    private function pimpinan(): User
    {
        return User::where('email', 'pimpinan@infracheck.ac.id')->firstOrFail();
    }

    private function makeUser(string $roleName, string $email): User
    {
        $role = Role::where('nama_peran', $roleName)->firstOrFail();

        return User::create([
            'id_peran' => $role->id_peran,
            'nama' => 'User ' . $roleName,
            'email' => $email,
            'kata_sandi_hash' => Hash::make('password'),
            'akun_aktif' => true,
        ]);
    }

    private function makeAssignmentWithoutSchedule(User $teknisi): Assignment
    {
        $pelapor = $this->pelapor();
        $admin = $this->admin();
        $device = Device::firstOrFail();
        $status = ReportStatus::where('nama_status', 'Ditugaskan')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Perangkat butuh penjadwalan perbaikan segera.',
            'waktu_laporan' => now(),
        ]);

        return Assignment::create([
            'id_laporan' => $report->id_laporan,
            'id_teknisi' => $teknisi->id_pengguna,
            'id_admin_penugas' => $admin->id_pengguna,
            'waktu_penugasan' => now(),
            'status_penugasan' => 'aktif',
        ]);
    }
}
