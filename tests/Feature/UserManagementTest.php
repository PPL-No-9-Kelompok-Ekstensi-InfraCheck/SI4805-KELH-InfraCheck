<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\StatusHistory;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
    }

    private function pelapor(): User
    {
        return User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
    }

    private function role(string $name): Role
    {
        return Role::where('nama_peran', $name)->firstOrFail();
    }

    private function createUser(
        string $roleName,
        string $name,
        string $email,
        string $nimNip,
        bool $akunAktif = true
    ): User {
        return User::create([
            'id_peran' => $this->role($roleName)->id_peran,
            'nama' => $name,
            'email' => $email,
            'kata_sandi_hash' => Hash::make('password'),
            'nim_nip' => $nimNip,
            'unit' => 'Unit Uji',
            'kontak' => '081200000001',
            'akun_aktif' => $akunAktif,
        ]);
    }

    private function createReport(User $reporter, ?User $verifier = null): Report
    {
        $device = Device::query()->firstOrFail();

        return Report::create([
            'id_pelapor' => $reporter->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => ReportStatus::query()->value('id_status'),
            'id_urgensi' => UrgencyLevel::query()->value('id_urgensi'),
            'id_prioritas' => null,
            'id_admin_verifikator' => $verifier?->id_pengguna,
            'deskripsi_kerusakan' => 'Perangkat tidak dapat digunakan.',
            'waktu_laporan' => now(),
            'waktu_verifikasi' => null,
            'catatan_verifikasi' => null,
        ]);
    }

    private function createAssignment(Report $report, User $technician, User $assigningAdmin): Assignment
    {
        return Assignment::create([
            'id_laporan' => $report->id_laporan,
            'id_teknisi' => $technician->id_pengguna,
            'id_admin_penugas' => $assigningAdmin->id_pengguna,
            'waktu_penugasan' => now(),
            'status_penugasan' => 'aktif',
        ]);
    }

    private function createStatusHistory(Report $report, User $changer): StatusHistory
    {
        return StatusHistory::create([
            'id_laporan' => $report->id_laporan,
            'id_status' => ReportStatus::query()->value('id_status'),
            'id_pengubah' => $changer->id_pengguna,
            'waktu_perubahan' => now(),
            'catatan_perubahan' => 'Status laporan diperbarui.',
        ]);
    }

    private function createComment(Report $report, User $user): Comment
    {
        return Comment::create([
            'id_laporan' => $report->id_laporan,
            'id_pengguna' => $user->id_pengguna,
            'isi_tanggapan' => 'Tanggapan untuk laporan ini.',
            'waktu_tanggapan' => now(),
        ]);
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'nama' => $user->nama,
            'email' => $user->email,
            'nim_nip' => $user->nim_nip,
            'id_peran' => $user->id_peran,
            'password' => '',
            'password_confirmation' => '',
            'unit' => $user->unit,
            'kontak' => $user->kontak,
            'akun_aktif' => $user->akun_aktif ? 1 : 0,
        ], $overrides);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Dewi Lestari',
            'email' => 'dewi@infracheck.ac.id',
            'nim_nip' => '1202220999',
            'id_peran' => $this->role('Pelapor')->id_peran,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'unit' => 'Sistem Informasi',
            'kontak' => '081200000000',
            'akun_aktif' => 1,
        ], $overrides);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($pelapor)->get('/users')->assertForbidden();
        $this->actingAs($pelapor)->get('/users/create')->assertForbidden();
        $this->actingAs($pelapor)->post('/users', $this->validPayload())->assertForbidden();
        $this->actingAs($pelapor)
            ->delete('/users/'.$this->admin()->id_pengguna)
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload())
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'dewi@infracheck.ac.id')->first();

        $this->assertNotNull($user);
        $this->assertSame('Dewi Lestari', $user->nama);
        $this->assertSame('1202220999', $user->nim_nip);
        $this->assertSame('Sistem Informasi', $user->unit);
        $this->assertTrue($user->akun_aktif);
        $this->assertNotSame('rahasia123', $user->kata_sandi_hash);
        $this->assertTrue(Hash::check('rahasia123', $user->kata_sandi_hash));
    }

    public function test_email_must_be_unique(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['email' => 'admin@infracheck.ac.id']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'dewi@infracheck.ac.id']);
    }

    public function test_nim_nip_must_be_unique_when_filled(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['nim_nip' => 'ADM001']))
            ->assertSessionHasErrors('nim_nip');
    }

    public function test_nim_nip_can_be_empty(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['nim_nip' => null]))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'dewi@infracheck.ac.id',
            'nim_nip' => null,
        ]);
    }

    public function test_password_is_required_when_creating_user(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['password' => '']))
            ->assertSessionHasErrors('password');
    }

    public function test_role_must_exist(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['id_peran' => 9999]))
            ->assertSessionHasErrors('id_peran');
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $user = $this->pelapor();
        $originalHash = $user->kata_sandi_hash;

        $this->actingAs($this->admin())
            ->put('/users/'.$user->id_pengguna, $this->validPayload([
                'nama' => 'Andi Saputra (Diperbarui)',
                'email' => $user->email,
                'nim_nip' => $user->nim_nip,
                'id_peran' => $user->id_peran,
                'password' => '',
                'unit' => $user->unit,
                'kontak' => $user->kontak,
            ]))
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Andi Saputra (Diperbarui)', $user->nama);
        $this->assertSame($originalHash, $user->kata_sandi_hash);
        $this->assertTrue(Hash::check('password', $user->kata_sandi_hash));
    }

    public function test_admin_can_update_user_password(): void
    {
        $user = $this->pelapor();

        $this->actingAs($this->admin())
            ->put('/users/'.$user->id_pengguna, $this->validPayload([
                'nama' => $user->nama,
                'email' => $user->email,
                'nim_nip' => $user->nim_nip,
                'id_peran' => $user->id_peran,
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ]))
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->kata_sandi_hash));
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete('/users/'.$admin->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_admin_can_hard_delete_user_without_relations(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', [
            'id_pengguna' => $pelapor->id_pengguna,
        ]);
    }

    public function test_hard_deleted_user_record_is_removed_from_database(): void
    {
        $pelapor = $this->pelapor();
        $email = $pelapor->email;

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->assertNull(User::find($pelapor->id_pengguna));
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_hard_delete_does_not_change_other_users(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();

        $this->actingAs($admin)
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $pelapor->id_pengguna]);

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'akun_aktif' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $teknisi->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_last_active_admin_cannot_demote_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/users/'.$admin->id_pengguna.'/edit')
            ->put('/users/'.$admin->id_pengguna, $this->updatePayload($admin, [
                'id_peran' => $this->role('Pelapor')->id_peran,
            ]))
            ->assertRedirect('/users/'.$admin->id_pengguna.'/edit')
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'id_peran' => $this->role('Admin')->id_peran,
            'akun_aktif' => true,
        ]);
    }

    public function test_last_active_admin_cannot_deactivate_self_via_update(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/users/'.$admin->id_pengguna.'/edit')
            ->put('/users/'.$admin->id_pengguna, $this->updatePayload($admin, [
                'akun_aktif' => 0,
            ]))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'id_peran' => $this->role('Admin')->id_peran,
            'akun_aktif' => true,
        ]);
    }

    public function test_admin_cannot_deactivate_self_via_update_even_with_another_admin(): void
    {
        $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/users/'.$admin->id_pengguna.'/edit')
            ->put('/users/'.$admin->id_pengguna, $this->updatePayload($admin, [
                'akun_aktif' => 0,
            ]))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_admin_can_demote_self_when_another_active_admin_exists(): void
    {
        $otherAdmin = $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put('/users/'.$admin->id_pengguna, $this->updatePayload($admin, [
                'id_peran' => $this->role('Pelapor')->id_peran,
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'id_peran' => $this->role('Pelapor')->id_peran,
            'akun_aktif' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $otherAdmin->id_pengguna,
            'id_peran' => $this->role('Admin')->id_peran,
            'akun_aktif' => true,
        ]);
    }

    public function test_active_admin_can_demote_another_admin_when_one_admin_remains(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');

        $this->actingAs($admin)
            ->put('/users/'.$otherAdmin->id_pengguna, $this->updatePayload($otherAdmin, [
                'id_peran' => $this->role('Pelapor')->id_peran,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $otherAdmin->id_pengguna,
            'id_peran' => $this->role('Pelapor')->id_peran,
        ]);

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $admin->id_pengguna,
            'id_peran' => $this->role('Admin')->id_peran,
            'akun_aktif' => true,
        ]);
    }

    public function test_normal_user_data_and_role_can_still_be_updated(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->put('/users/'.$pelapor->id_pengguna, $this->updatePayload($pelapor, [
                'nama' => 'Andi Saputra (Teknisi)',
                'unit' => 'Divisi Infrastruktur & Jaringan',
                'id_peran' => $this->role('Teknisi')->id_peran,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $pelapor->id_pengguna,
            'nama' => 'Andi Saputra (Teknisi)',
            'unit' => 'Divisi Infrastruktur & Jaringan',
            'id_peran' => $this->role('Teknisi')->id_peran,
        ]);
    }

    public function test_inactive_user_can_be_edited_without_reactivation(): void
    {
        $inactive = $this->createUser(
            'Pelapor',
            'Pengguna Nonaktif',
            'nonaktif@infracheck.ac.id',
            'NON001',
            false
        );

        $this->actingAs($this->admin())
            ->put('/users/'.$inactive->id_pengguna, $this->updatePayload($inactive, [
                'nama' => 'Pengguna Nonaktif Diperbarui',
                'akun_aktif' => 0,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $inactive->id_pengguna,
            'nama' => 'Pengguna Nonaktif Diperbarui',
            'akun_aktif' => false,
        ]);
    }

    public function test_inactive_user_can_be_reactivated_via_edit_form(): void
    {
        $inactive = $this->createUser(
            'Pelapor',
            'Pengguna Nonaktif',
            'nonaktif@infracheck.ac.id',
            'NON001',
            false
        );

        $this->actingAs($this->admin())
            ->put('/users/'.$inactive->id_pengguna, $this->updatePayload($inactive, [
                'akun_aktif' => 1,
            ]))
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $inactive->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_deactivated_admin_cannot_access_user_management(): void
    {
        $admin = $this->admin();
        $admin->update(['akun_aktif' => false]);

        $this->actingAs($admin)->get('/users')->assertForbidden();
        $this->actingAs($admin)->get('/users/create')->assertForbidden();
        $this->actingAs($admin)->post('/users', $this->validPayload())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'dewi@infracheck.ac.id']);
    }

    public function test_deactivated_admin_cannot_change_last_active_admin_via_update(): void
    {
        $actingAdmin = $this->admin();
        $actingAdmin->update(['akun_aktif' => false]);

        $targetAdmin = $this->createUser('Admin', 'Admin Aktif', 'adminaktif@infracheck.ac.id', 'ADM009');

        $this->actingAs($actingAdmin)
            ->put('/users/'.$targetAdmin->id_pengguna, $this->updatePayload($targetAdmin, [
                'id_peran' => $this->role('Pelapor')->id_peran,
                'akun_aktif' => 0,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $targetAdmin->id_pengguna,
            'id_peran' => $this->role('Admin')->id_peran,
            'akun_aktif' => true,
        ]);
    }

    public function test_inactive_admin_cannot_delete_last_active_admin(): void
    {
        // Admin yang bertindak sudah nonaktif; F-03 harus menolak aksesnya
        // sehingga Admin aktif terakhir tidak dapat dihapus.
        $actingAdmin = $this->admin();
        $actingAdmin->update(['akun_aktif' => false]);

        $targetAdmin = $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');

        $this->actingAs($actingAdmin)
            ->delete('/users/'.$targetAdmin->id_pengguna)
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $targetAdmin->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_last_active_admin_cannot_be_deleted(): void
    {
        // Simulasikan session lama: guard masih memegang model Admin yang aktif,
        // tetapi record di database sudah nonaktif sehingga target menjadi
        // satu-satunya Admin aktif. Guard Admin aktif terakhir harus menolak.
        $actingAdmin = $this->admin();
        $this->actingAs($actingAdmin);

        User::where('id_pengguna', $actingAdmin->id_pengguna)->update(['akun_aktif' => false]);

        $targetAdmin = $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');

        $this->delete('/users/'.$targetAdmin->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $targetAdmin->id_pengguna,
            'akun_aktif' => true,
        ]);
    }

    public function test_hard_deleted_user_does_not_appear_in_user_list(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $pelapor->id_pengguna]);

        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertDontSee($pelapor->nama)
            ->assertDontSee($pelapor->email);
    }

    public function test_hard_deleted_user_is_excluded_from_search(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->get('/users?q=Andi')
            ->assertOk()
            ->assertDontSee('Andi Saputra (Mahasiswa)');
    }

    public function test_hard_deleted_user_cannot_login(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        // Lepaskan session Admin agar pengujian login benar-benar dimulai sebagai tamu.
        $this->post('/logout');

        $this->post('/login', [
            'login' => 'pelapor@infracheck.ac.id',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_hard_deleted_user_cannot_be_accessed_via_edit_or_update_or_delete_again(): void
    {
        $pelapor = $this->pelapor();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->get('/users/'.$pelapor->id_pengguna.'/edit')
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->put('/users/'.$pelapor->id_pengguna, $this->updatePayload($pelapor))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $pelapor->id_pengguna]);
    }

    public function test_inactive_but_not_deleted_user_is_still_listed(): void
    {
        $inactive = $this->createUser(
            'Pelapor',
            'Pengguna Nonaktif',
            'nonaktif@infracheck.ac.id',
            'NON001',
            false
        );

        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertSee('Pengguna Nonaktif')
            ->assertSee('nonaktif@infracheck.ac.id')
            ->assertSee('Nonaktif');

        $this->assertDatabaseHas('users', [
            'id_pengguna' => $inactive->id_pengguna,
            'akun_aktif' => false,
        ]);
    }

    public function test_user_with_reports_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $report = $this->createReport($pelapor, $admin);

        $this->actingAs($admin)
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $pelapor->id_pengguna]);
        $this->assertDatabaseHas('reports', [
            'id_laporan' => $report->id_laporan,
            'id_pelapor' => $pelapor->id_pengguna,
        ]);
    }

    public function test_user_who_verified_report_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $verifier = $this->createUser('Admin', 'Admin Verifikator', 'verifikator@infracheck.ac.id', 'ADM003');
        $report = $this->createReport($pelapor, $verifier);

        $this->actingAs($admin)
            ->delete('/users/'.$verifier->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $verifier->id_pengguna]);
        $this->assertDatabaseHas('reports', [
            'id_laporan' => $report->id_laporan,
            'id_admin_verifikator' => $verifier->id_pengguna,
        ]);
    }

    public function test_user_with_assignments_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
        $report = $this->createReport($pelapor, $admin);
        $assignment = $this->createAssignment($report, $teknisi, $admin);

        $this->actingAs($admin)
            ->delete('/users/'.$teknisi->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $teknisi->id_pengguna]);
        $this->assertDatabaseHas('assignments', [
            'id_penugasan' => $assignment->id_penugasan,
            'id_teknisi' => $teknisi->id_pengguna,
        ]);
    }

    public function test_admin_who_assigned_technician_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
        $assigningAdmin = $this->createUser('Admin', 'Admin Penugas', 'penugas@infracheck.ac.id', 'ADM004');
        $report = $this->createReport($pelapor, $admin);
        $assignment = $this->createAssignment($report, $teknisi, $assigningAdmin);

        $this->actingAs($admin)
            ->delete('/users/'.$assigningAdmin->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $assigningAdmin->id_pengguna]);
        $this->assertDatabaseHas('assignments', [
            'id_penugasan' => $assignment->id_penugasan,
            'id_admin_penugas' => $assigningAdmin->id_pengguna,
        ]);
    }

    public function test_user_with_comments_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $commenter = $this->createUser('Pelapor', 'Komentator', 'komentator@infracheck.ac.id', 'CMT001');
        $report = $this->createReport($pelapor, $admin);
        $comment = $this->createComment($report, $commenter);

        $this->actingAs($admin)
            ->delete('/users/'.$commenter->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $commenter->id_pengguna]);
        $this->assertDatabaseHas('comments', [
            'id_tanggapan' => $comment->id_tanggapan,
            'id_pengguna' => $commenter->id_pengguna,
        ]);
    }

    public function test_user_with_status_histories_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $changer = $this->createUser('Admin', 'Admin Riwayat', 'riwayat@infracheck.ac.id', 'ADM005');
        $report = $this->createReport($pelapor, $admin);
        $history = $this->createStatusHistory($report, $changer);

        $this->actingAs($admin)
            ->delete('/users/'.$changer->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $changer->id_pengguna]);
        $this->assertDatabaseHas('status_histories', [
            'id_riwayat' => $history->id_riwayat,
            'id_pengubah' => $changer->id_pengguna,
        ]);
    }

    public function test_rejected_deletion_keeps_reports_and_assignments_intact(): void
    {
        $admin = $this->admin();
        $pelapor = $this->pelapor();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
        $report = $this->createReport($pelapor, $admin);
        $assignment = $this->createAssignment($report, $teknisi, $admin);

        $this->actingAs($admin)
            ->delete('/users/'.$pelapor->id_pengguna)
            ->assertSessionHasErrors('user');

        $this->actingAs($admin)
            ->delete('/users/'.$teknisi->id_pengguna)
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id_pengguna' => $pelapor->id_pengguna]);
        $this->assertDatabaseHas('users', ['id_pengguna' => $teknisi->id_pengguna]);
        $this->assertDatabaseHas('reports', ['id_laporan' => $report->id_laporan]);
        $this->assertDatabaseHas('assignments', ['id_penugasan' => $assignment->id_penugasan]);
    }

    public function test_inactive_user_without_relations_can_be_deleted(): void
    {
        $inactive = $this->createUser(
            'Pelapor',
            'Pengguna Nonaktif',
            'nonaktif@infracheck.ac.id',
            'NON001',
            false
        );

        $this->actingAs($this->admin())
            ->delete('/users/'.$inactive->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $inactive->id_pengguna]);
    }

    public function test_non_admin_user_without_relations_can_be_deleted(): void
    {
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete('/users/'.$teknisi->id_pengguna)
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $teknisi->id_pengguna]);
    }

    public function test_other_active_admin_can_still_manage_users_after_deletion(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->createUser('Admin', 'Admin Kedua', 'admin2@infracheck.ac.id', 'ADM002');
        $target = $this->createUser('Pelapor', 'Target Hapus', 'target@infracheck.ac.id', 'TGT001');

        $this->actingAs($otherAdmin)
            ->delete('/users/'.$target->id_pengguna)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id_pengguna' => $target->id_pengguna]);

        $this->actingAs($otherAdmin)
            ->get('/users')
            ->assertOk()
            ->assertSee($admin->nama);

        $this->actingAs($otherAdmin)
            ->get('/users/create')
            ->assertOk();

        $this->actingAs($otherAdmin)
            ->get('/users/'.$admin->id_pengguna.'/edit')
            ->assertOk();
    }

    public function test_admin_can_view_user_index(): void
    {
        $response = $this->actingAs($this->admin())->get('/users');

        $response->assertOk();
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('Administrator Sistem');
        $response->assertSee('Budi Santoso (Teknisi)');
        $response->assertSee('Aktif');
        $response->assertSee('Tambah Pengguna');
        $response->assertSee('Hapus');
        $response->assertSee('Akun Anda');
    }

    public function test_user_index_does_not_expose_password_hash(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertDontSee($admin->kata_sandi_hash, false);
    }

    public function test_user_index_can_be_searched_by_name_email_and_nim(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/users?q=Andi')
            ->assertOk()
            ->assertSee('Andi Saputra (Mahasiswa)')
            ->assertDontSee('Budi Santoso (Teknisi)');

        $this->actingAs($admin)
            ->get('/users?q=teknisi@infracheck.ac.id')
            ->assertOk()
            ->assertSee('Budi Santoso (Teknisi)')
            ->assertDontSee('Andi Saputra (Mahasiswa)');

        $this->actingAs($admin)
            ->get('/users?q=1202220001')
            ->assertOk()
            ->assertSee('Andi Saputra (Mahasiswa)')
            ->assertDontSee('Budi Santoso (Teknisi)');
    }

    public function test_user_index_is_paginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            User::create([
                'id_peran' => $this->role('Pelapor')->id_peran,
                'nama' => sprintf('Pengguna Uji %02d', $i),
                'email' => sprintf('pengguna%02d@infracheck.ac.id', $i),
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => sprintf('UJI%03d', $i),
                'akun_aktif' => true,
            ]);
        }

        $firstPage = $this->actingAs($this->admin())->get('/users');
        $firstPage->assertOk();
        $this->assertSame(10, $firstPage->viewData('users')->count());
        $this->assertSame(16, $firstPage->viewData('users')->total());

        $secondPage = $this->actingAs($this->admin())->get('/users?page=2');
        $secondPage->assertOk();
        $this->assertSame(6, $secondPage->viewData('users')->count());
    }

    public function test_admin_can_view_create_user_form(): void
    {
        $this->actingAs($this->admin())
            ->get('/users/create')
            ->assertOk()
            ->assertSee('Tambah Pengguna')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Pelapor')
            ->assertSee('Admin')
            ->assertSee('Teknisi')
            ->assertSee('Pimpinan');
    }

    public function test_admin_can_view_edit_user_form_without_password_hash(): void
    {
        $user = $this->pelapor();

        $this->actingAs($this->admin())
            ->get('/users/'.$user->id_pengguna.'/edit')
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Password lama akan tetap digunakan.')
            ->assertDontSee($user->kata_sandi_hash, false);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', $this->validPayload(['password_confirmation' => 'berbeda123']))
            ->assertSessionHasErrors('password');
    }

    public function test_validation_errors_and_old_input_are_shown_on_create_form(): void
    {
        $this->actingAs($this->admin())
            ->from('/users/create')
            ->followingRedirects()
            ->post('/users', $this->validPayload([
                'email' => 'admin@infracheck.ac.id',
                'password_confirmation' => 'rahasia123',
            ]))
            ->assertOk()
            ->assertSee('Email sudah digunakan oleh pengguna lain.')
            ->assertSee('value="Dewi Lestari"', false)
            ->assertDontSee('value="rahasia123"', false);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $pelapor = $this->pelapor();
        $pelapor->update(['akun_aktif' => false]);

        $this->post('/login', [
            'login' => 'pelapor@infracheck.ac.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
