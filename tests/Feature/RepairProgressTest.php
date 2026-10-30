<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairProgress;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepairProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('progress.index'))->assertRedirect('/login');
    }

    public function test_teknisi_sees_progress_page(): void
    {
        $response = $this->actingAs($this->teknisi())->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee('Progres Perbaikan');
        $response->assertSee('Kelola Progres');
    }

    public function test_teknisi_only_sees_own_assignments(): void
    {
        $teknisi = $this->teknisi();
        $other = $this->makeUser('Teknisi', 'teknisi-lain@infracheck.ac.id');
        $otherAssignment = $this->makeAssignment($this->makeReport($this->pelapor()), $other);

        $response = $this->actingAs($teknisi)->get(route('progress.index'));

        $response->assertOk();
        $response->assertDontSee($otherAssignment->report->nomor_laporan);
    }

    public function test_teknisi_can_add_progress_note(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $response = $this->actingAs($teknisi)->post(route('progress.store', $assignment), [
            'persentase' => 65,
            'catatan_progres' => 'Pengujian ulang selesai, perangkat berfungsi normal.',
        ]);

        $response->assertRedirect(route('progress.show', $assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('repair_progress', [
            'id_penugasan' => $assignment->id_penugasan,
            'persentase' => 65,
            'catatan_progres' => 'Pengujian ulang selesai, perangkat berfungsi normal.',
        ]);
    }

    public function test_progress_time_defaults_to_now_when_left_empty(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $this->actingAs($teknisi)->post(route('progress.store', $assignment), [
            'persentase' => 30,
            'catatan_progres' => 'Pengecekan komponen internal.',
            'waktu_progres' => '',
        ])->assertSessionHasNoErrors();

        $progress = RepairProgress::where('id_penugasan', $assignment->id_penugasan)
            ->where('persentase', 30)
            ->firstOrFail();

        $this->assertNotNull($progress->waktu_progres);
        $this->assertTrue($progress->waktu_progres->diffInMinutes(now()) < 2);
    }

    public function test_percentage_must_be_between_0_and_100(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);
        $before = RepairProgress::count();

        $this->actingAs($teknisi)->post(route('progress.store', $assignment), [
            'persentase' => 150,
            'catatan_progres' => 'Nilai persentase tidak valid.',
        ])->assertSessionHasErrors('persentase');

        $this->assertSame($before, RepairProgress::count());
    }

    public function test_note_is_required(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $this->actingAs($teknisi)->post(route('progress.store', $assignment), [
            'persentase' => 40,
            'catatan_progres' => '',
        ])->assertSessionHasErrors('catatan_progres');
    }

    public function test_teknisi_can_update_own_progress_note(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);
        $progress = $assignment->progressUpdates()->firstOrFail();

        $response = $this->actingAs($teknisi)->put(route('progress.update', [$assignment, $progress]), [
            'persentase' => 90,
            'catatan_progres' => 'Pekerjaan hampir selesai, tinggal finishing.',
            'waktu_progres' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('progress.show', $assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('repair_progress', [
            'id_progres' => $progress->id_progres,
            'persentase' => 90,
            'catatan_progres' => 'Pekerjaan hampir selesai, tinggal finishing.',
        ]);
    }

    public function test_teknisi_cannot_manage_other_technician_assignment(): void
    {
        $teknisi = $this->teknisi();
        $other = $this->makeUser('Teknisi', 'teknisi-lain@infracheck.ac.id');
        $otherAssignment = $this->makeAssignment($this->makeReport($this->pelapor()), $other);
        $otherProgress = $otherAssignment->progressUpdates()->create([
            'persentase' => 15,
            'catatan_progres' => 'Pemeriksaan awal oleh teknisi lain.',
            'waktu_progres' => now(),
        ]);

        $this->actingAs($teknisi)
            ->post(route('progress.store', $otherAssignment), [
                'persentase' => 50,
                'catatan_progres' => 'Tidak boleh ditambahkan.',
            ])
            ->assertForbidden();

        $this->actingAs($teknisi)
            ->get(route('progress.edit', [$otherAssignment, $otherProgress]))
            ->assertForbidden();
    }

    public function test_admin_can_view_progress_but_cannot_manage_it(): void
    {
        $admin = $this->admin();
        $assignment = $this->assignmentFor($this->teknisi());

        $this->actingAs($admin)
            ->get(route('progress.show', $assignment))
            ->assertOk()
            ->assertSee('Riwayat Progres Perbaikan')
            ->assertDontSee('Tambah Catatan Progres');

        $this->actingAs($admin)
            ->post(route('progress.store', $assignment), [
                'persentase' => 70,
                'catatan_progres' => 'Admin tidak boleh menambah progres.',
            ])
            ->assertForbidden();
    }

    public function test_pelapor_can_view_progress_of_own_report_only(): void
    {
        $pelapor = $this->pelapor();
        $ownAssignment = $this->assignmentFor($this->teknisi());

        $this->actingAs($pelapor)
            ->get(route('progress.show', $ownAssignment))
            ->assertOk();

        $otherAssignment = $this->makeAssignment(
            $this->makeReport($this->makeUser('Pelapor', 'pelapor-lain@infracheck.ac.id')),
            $this->teknisi()
        );

        $this->actingAs($pelapor)
            ->get(route('progress.show', $otherAssignment))
            ->assertForbidden();
    }

    public function test_progress_cannot_be_deleted(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);
        $progress = $assignment->progressUpdates()->firstOrFail();

        $this->actingAs($teknisi)
            ->delete(route('progress.update', [$assignment, $progress]))
            ->assertStatus(405);

        $this->assertDatabaseHas('repair_progress', ['id_progres' => $progress->id_progres]);
    }

    private function teknisi(): User
    {
        return User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
    }

    private function pelapor(): User
    {
        return User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
    }

    /**
     * Penugasan milik teknisi tertentu (default: teknisi utama hasil seeder).
     */
    private function assignmentFor(User $teknisi): Assignment
    {
        return Assignment::where('id_teknisi', $teknisi->id_pengguna)
            ->whereHas('report')
            ->orderBy('id_penugasan')
            ->firstOrFail();
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'id_peran' => Role::where('nama_peran', $role)->value('id_peran'),
            'nama' => 'Pengguna '.$role,
            'email' => $email,
            'kata_sandi_hash' => Hash::make('password'),
            'nim_nip' => 'TST'.random_int(1000, 9999),
            'akun_aktif' => true,
        ]);
    }

    private function makeReport(User $reporter): Report
    {
        $device = Device::firstOrFail();

        return Report::create([
            'id_pelapor' => $reporter->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => ReportStatus::where('nama_status', 'Ditugaskan')->value('id_status'),
            'id_urgensi' => UrgencyLevel::where('nama_urgensi', 'Sedang')->value('id_urgensi'),
            'deskripsi_kerusakan' => 'Laporan uji coba untuk pengujian fitur progres.',
            'waktu_laporan' => now(),
        ]);
    }

    private function makeAssignment(Report $report, User $teknisi): Assignment
    {
        return Assignment::create([
            'id_laporan' => $report->id_laporan,
            'id_teknisi' => $teknisi->id_pengguna,
            'id_admin_penugas' => $this->admin()->id_pengguna,
            'waktu_penugasan' => now(),
            'status_penugasan' => 'aktif',
        ]);
    }
}
