<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairDocument;
use App\Models\RepairProgress;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RepairProgressSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(RepairProgressSeeder::class);
        Storage::fake(RepairDocument::DISK);
    }

    public function test_guest_is_redirected_to_login_for_document_routes(): void
    {
        $this->get(route('documents.index'))->assertRedirect('/login');

        $assignment = Assignment::firstOrFail();
        $this->get(route('documents.create', $assignment))->assertRedirect('/login');
        $this->post(route('documents.store', $assignment))->assertRedirect('/login');
    }

    public function test_teknisi_can_view_documents_index_and_see_own_assignments(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $response = $this->actingAs($teknisi)->get(route('documents.index'));

        $response->assertOk();
        $response->assertSee('Dokumentasi Hasil Perbaikan');
        $response->assertSee($assignment->report->nomor_laporan);
    }

    public function test_teknisi_can_view_document_create_page_for_own_assignment(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $response = $this->actingAs($teknisi)->get(route('documents.create', $assignment));

        $response->assertOk();
        $response->assertSee('Dokumentasi Hasil Perbaikan');
        $response->assertSee('Foto sebelum perbaikan');
        $response->assertSee('Foto sesudah perbaikan');
        $response->assertSee('Tindakan yang dilakukan');
        $response->assertSee('Suku cadang yang diganti');
        $response->assertSee('Simpan Draf');
        $response->assertSee('Simpan &amp; Tandai Selesai', false);
    }

    public function test_teknisi_cannot_view_or_upload_document_to_other_technician_assignment(): void
    {
        $teknisiA = $this->teknisi();
        $teknisiB = $this->makeUser('Teknisi', 'teknisi-b@infracheck.ac.id');
        $assignmentA = $this->assignmentFor($teknisiA);

        // Teknisi B mencoba akses penugasan Teknisi A
        $this->actingAs($teknisiB)
            ->get(route('documents.create', $assignmentA))
            ->assertForbidden();

        $this->actingAs($teknisiB)
            ->post(route('documents.store', $assignmentA), [
                'foto_sebelum' => [UploadedFile::fake()->image('sebelum.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_pelapor_can_view_document_page_for_own_report_but_cannot_upload(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);
        $pelapor = $assignment->report->reporter;

        $response = $this->actingAs($pelapor)->get(route('documents.create', $assignment));

        $response->assertOk();
        $response->assertSee('Mode Tinjauan');
        $response->assertDontSee('Simpan &amp; Tandai Selesai', false);

        // Pelapor mencoba upload
        $this->actingAs($pelapor)
            ->post(route('documents.store', $assignment), [
                'foto_sebelum' => [UploadedFile::fake()->image('sebelum.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_admin_and_pimpinan_can_view_document_page(): void
    {
        $assignment = Assignment::firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('documents.create', $assignment))
            ->assertOk();

        $this->actingAs($this->pimpinan())
            ->get(route('documents.create', $assignment))
            ->assertOk();
    }

    public function test_teknisi_can_upload_draft_documentation_with_only_foto_sebelum(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $fotoSebelum = UploadedFile::fake()->image('sebelum_rusak.jpg', 600, 400);

        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'tandai_selesai' => 0,
            'tindakan' => 'Pemeriksaan fisik awal dan pembersihan komponen pendingin.',
            'suku_cadang' => 'Belum ada penggantian suku cadang.',
            'foto_sebelum' => [$fotoSebelum],
        ]);

        $response->assertRedirect(route('documents.create', $assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('repair_documents', [
            'jenis_dokumentasi' => 'Sebelum',
        ]);

        $doc = RepairDocument::where('jenis_dokumentasi', 'Sebelum')->latest('id_dokumentasi')->first();
        $this->assertNotNull($doc);
        Storage::disk(RepairDocument::DISK)->assertExists($doc->lokasi_berkas);

        // Status laporan tidak otomatis selesai/menunggu validasi karena hanya draf
        $assignment->report->refresh();
        $this->assertNotSame('Menunggu Validasi', $assignment->report->status->nama_status);
        $this->assertNotSame('Selesai', $assignment->report->status->nama_status);
    }

    public function test_teknisi_can_upload_and_mark_completed_updating_status_to_menunggu_validasi(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        // Set status awal Ditugaskan agar tercatat histori status saat beralih
        $ditugaskanStatus = ReportStatus::where('nama_status', 'Ditugaskan')->value('id_status');
        $assignment->report->update(['id_status' => $ditugaskanStatus]);

        $fotoSebelum = UploadedFile::fake()->image('proyektor_rusak.png', 800, 600);
        $fotoSesudah = UploadedFile::fake()->image('proyektor_nyala.png', 800, 600);

        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'tandai_selesai' => 1,
            'tindakan' => 'Mengganti lampu proyektor dan membersihkan filter udara.',
            'suku_cadang' => 'Lampu proyektor (1 unit)',
            'kondisi_perangkat' => 'Baik',
            'foto_sebelum' => [$fotoSebelum],
            'foto_sesudah' => [$fotoSesudah],
        ]);

        $response->assertRedirect(route('documents.create', $assignment));
        $response->assertSessionHas('success');

        // Memastikan dokumen tersimpan di database dan storage
        $this->assertDatabaseHas('repair_documents', ['jenis_dokumentasi' => 'Sebelum']);
        $this->assertDatabaseHas('repair_documents', ['jenis_dokumentasi' => 'Sesudah']);

        // Memastikan progres baru berbobot 100% dan catatan progres tersimpan gabungan tindakan & suku cadang
        $latestProgress = $assignment->refresh()->latestProgress;
        $this->assertSame(100, $latestProgress->persentase);
        $this->assertStringContainsString('Tindakan: Mengganti lampu proyektor', $latestProgress->catatan_progres);
        $this->assertStringContainsString('Suku cadang: Lampu proyektor (1 unit)', $latestProgress->catatan_progres);

        // Memastikan status laporan menjadi 'Menunggu Validasi' (tidak langsung Selesai, menunggu verifikasi admin)
        $assignment->report->refresh();
        $this->assertSame('Menunggu Validasi', $assignment->report->status->nama_status);

        // Memastikan histori perubahan tercatat
        $this->assertDatabaseHas('status_histories', [
            'id_laporan' => $assignment->report->id_laporan,
            'id_status' => $assignment->report->id_status,
            'id_pengubah' => $teknisi->id_pengguna,
        ]);

        // Memastikan kondisi perangkat terbarui menjadi Baik
        $this->assertSame('Baik', $assignment->report->device->kondisi->value);
    }

    public function test_marking_completed_requires_foto_sebelum_and_sesudah(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        // Hanya upload foto Sebelum saat menandai selesai -> harus gagal validasi foto_sesudah
        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'tandai_selesai' => 1,
            'tindakan' => 'Sudah diperbaiki.',
            'foto_sebelum' => [UploadedFile::fake()->image('sebelum.jpg')],
        ]);

        $response->assertSessionHasErrors('foto_sesudah');

        // Tindakan juga wajib jika ditandai selesai
        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'tandai_selesai' => 1,
            'tindakan' => '',
            'foto_sebelum' => [UploadedFile::fake()->image('sebelum.jpg')],
            'foto_sesudah' => [UploadedFile::fake()->image('sesudah.jpg')],
        ]);

        $response->assertSessionHasErrors('tindakan');
    }

    public function test_teknisi_can_replace_document_file(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        // Buat satu progress & document awal
        $progress = $assignment->progressUpdates()->create([
            'persentase' => 50,
            'catatan_progres' => 'Uji coba progres.',
            'waktu_progres' => now(),
        ]);

        $fileAwal = UploadedFile::fake()->image('awal.jpg')->store('repair-documents', RepairDocument::DISK);
        $document = RepairDocument::create([
            'id_progres' => $progress->id_progres,
            'lokasi_berkas' => $fileAwal,
            'jenis_dokumentasi' => 'Sebelum',
            'waktu_unggah' => now(),
        ]);

        Storage::disk(RepairDocument::DISK)->assertExists($fileAwal);

        // Ganti dengan file baru
        $fileBaru = UploadedFile::fake()->image('perbaikan_revisi.png');

        $response = $this->actingAs($teknisi)->put(route('documents.update', $document), [
            'berkas' => $fileBaru,
        ]);

        $response->assertRedirect(route('documents.create', $assignment));
        $response->assertSessionHas('success');

        // Berkas lama terhapus, berkas baru tersimpan
        Storage::disk(RepairDocument::DISK)->assertMissing($fileAwal);

        $document->refresh();
        $this->assertNotSame($fileAwal, $document->lokasi_berkas);
        Storage::disk(RepairDocument::DISK)->assertExists($document->lokasi_berkas);
    }

    public function test_teknisi_can_delete_document_file(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        $progress = $assignment->progressUpdates()->create([
            'persentase' => 50,
            'catatan_progres' => 'Uji coba progres.',
            'waktu_progres' => now(),
        ]);

        $file = UploadedFile::fake()->image('dihapus.jpg')->store('repair-documents', RepairDocument::DISK);
        $document = RepairDocument::create([
            'id_progres' => $progress->id_progres,
            'lokasi_berkas' => $file,
            'jenis_dokumentasi' => 'Sebelum',
            'waktu_unggah' => now(),
        ]);

        Storage::disk(RepairDocument::DISK)->assertExists($file);

        $response = $this->actingAs($teknisi)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.create', $assignment));
        $response->assertSessionHas('success');

        // File fisik terhapus dan database row hilang
        Storage::disk(RepairDocument::DISK)->assertMissing($file);
        $this->assertDatabaseMissing('repair_documents', [
            'id_dokumentasi' => $document->id_dokumentasi,
        ]);
    }

    public function test_documentation_is_locked_when_report_status_is_selesai(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        // Kunci laporan dengan status Selesai
        $statusSelesai = ReportStatus::firstOrCreate(['nama_status' => 'Selesai']);
        $assignment->report->update(['id_status' => $statusSelesai->id_status]);

        $progress = $assignment->progressUpdates()->create([
            'persentase' => 100,
            'catatan_progres' => 'Selesai.',
            'waktu_progres' => now(),
        ]);

        $document = RepairDocument::create([
            'id_progres' => $progress->id_progres,
            'lokasi_berkas' => 'repair-documents/terkunci.jpg',
            'jenis_dokumentasi' => 'Sesudah',
            'waktu_unggah' => now(),
        ]);

        // Upload baru dilarang (403)
        $this->actingAs($teknisi)
            ->post(route('documents.store', $assignment), [
                'foto_sebelum' => [UploadedFile::fake()->image('baru.jpg')],
            ])
            ->assertForbidden();

        // Ganti file dilarang (403)
        $this->actingAs($teknisi)
            ->put(route('documents.update', $document), [
                'berkas' => UploadedFile::fake()->image('ganti.jpg'),
            ])
            ->assertForbidden();

        // Hapus file dilarang (403)
        $this->actingAs($teknisi)
            ->delete(route('documents.destroy', $document))
            ->assertForbidden();
    }

    public function test_file_validation_rejects_invalid_extension_and_size(): void
    {
        $teknisi = $this->teknisi();
        $assignment = $this->assignmentFor($teknisi);

        // File bukan gambar (txt)
        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'foto_sebelum' => [UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('foto_sebelum.0');

        // File melebihi 2 MB (2500 KB)
        $response = $this->actingAs($teknisi)->post(route('documents.store', $assignment), [
            'foto_sebelum' => [UploadedFile::fake()->create('besar.jpg', 2500, 'image/jpeg')],
        ]);

        $response->assertSessionHasErrors('foto_sebelum.0');
    }

    // ==================== HELPER METHODS ====================

    private function teknisi(): User
    {
        return User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Teknisi'))->firstOrFail();
    }

    private function admin(): User
    {
        return User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Admin'))->firstOrFail();
    }

    private function pimpinan(): User
    {
        return User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pimpinan'))->firstOrFail();
    }

    private function pelapor(): User
    {
        return User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
    }

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
            'nama' => 'Pengguna ' . $role,
            'email' => $email,
            'kata_sandi_hash' => Hash::make('password'),
            'nim_nip' => 'TST' . random_int(1000, 9999),
            'akun_aktif' => true,
        ]);
    }
}
