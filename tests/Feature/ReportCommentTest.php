<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReportDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seed(ReportDemoSeeder::class);
    }

    private function getFinishedReport(User $reporter): Report
    {
        $selesaiStatus = ReportStatus::where('nama_status', 'Selesai')->firstOrFail();

        return Report::firstOrCreate(
            [
                'id_pelapor' => $reporter->id_pengguna,
                'id_status' => $selesaiStatus->id_status,
            ],
            [
                'id_perangkat' => 1,
                'id_ruangan' => 1,
                'id_urgensi' => 1,
                'deskripsi_kerusakan' => 'Printer tidak mau menarik kertas saat cetak.',
                'waktu_laporan' => now()->subDays(3),
            ]
        );
    }

    private function getPendingReport(User $reporter): Report
    {
        $pendingStatus = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();

        return Report::firstOrCreate(
            [
                'id_pelapor' => $reporter->id_pengguna,
                'id_status' => $pendingStatus->id_status,
            ],
            [
                'id_perangkat' => 1,
                'id_ruangan' => 1,
                'id_urgensi' => 1,
                'deskripsi_kerusakan' => 'Access point mati total.',
                'waktu_laporan' => now()->subDay(),
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $report = Report::firstOrFail();

        $this->get('/laporan/'.$report->id_laporan.'/tanggapan')
            ->assertRedirect('/login');

        $this->post('/laporan/'.$report->id_laporan.'/tanggapan', [])
            ->assertRedirect('/login');
    }

    public function test_pelapor_can_view_feedback_form_for_own_finished_report(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);

        $response = $this->actingAs($reporter)->get('/laporan/'.$report->id_laporan.'/tanggapan');

        $response->assertOk();
        $response->assertSee('Beri Tanggapan atas Penanganan');
        $response->assertSee('Ringkasan Laporan');
        $response->assertSee('Tanggapan Anda');
        $response->assertSee('Apakah perbaikan sudah sesuai?');
        $response->assertSee('Sudah sesuai');
        $response->assertSee('Belum sesuai');
        $response->assertSee('Penilaian layanan');
        $response->assertSee('Kirim Tanggapan');
    }

    public function test_pelapor_cannot_access_feedback_form_for_another_users_report(): void
    {
        $reporterA = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $reporterB = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->where('id_pengguna', '!=', $reporterA->id_pengguna)->first();

        if (! $reporterB) {
            $reporterB = User::create([
                'id_peran' => $reporterA->id_peran,
                'nama' => 'Pelapor Lain',
                'email' => 'pelapor.lain@infracheck.ac.id',
                'kata_sandi_hash' => bcrypt('password'),
                'akun_aktif' => true,
            ]);
        }

        $reportA = $this->getFinishedReport($reporterA);

        $this->actingAs($reporterB)
            ->get('/laporan/'.$reportA->id_laporan.'/tanggapan')
            ->assertForbidden();
    }

    public function test_pelapor_cannot_give_feedback_to_unfinished_report(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $pendingReport = $this->getPendingReport($reporter);

        $response = $this->actingAs($reporter)->get('/laporan/'.$pendingReport->id_laporan.'/tanggapan');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_pelapor_can_submit_satisfactory_feedback(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);
        $initialStatusId = $report->id_status;

        $response = $this->actingAs($reporter)->post('/laporan/'.$report->id_laporan.'/tanggapan', [
            'kesesuaian' => 'sudah_sesuai',
            'rating' => 5,
            'komentar' => 'Perbaikan sangat rapi dan printer sudah berfungsi normal.',
        ]);

        $response->assertRedirect('/laporan/'.$report->id_laporan.'/tanggapan');
        $response->assertSessionHas('success');

        // Pastikan tanggapan tersimpan di basis data
        $comment = Comment::where('id_laporan', $report->id_laporan)
            ->where('id_pengguna', $reporter->id_pengguna)
            ->latest('id_tanggapan')
            ->first();

        $this->assertNotNull($comment);
        $this->assertTrue($comment->is_sesuai);
        $this->assertSame(5, $comment->rating);
        $this->assertSame('Perbaikan sangat rapi dan printer sudah berfungsi normal.', $comment->komentar_bersih);

        // Status laporan tetap Selesai
        $report->refresh();
        $this->assertSame($initialStatusId, $report->id_status);

        // Riwayat status mencatat konfirmasi selesai
        $history = StatusHistory::where('id_laporan', $report->id_laporan)->latest('id_riwayat')->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('sudah sesuai', $history->catatan_perubahan);
        $this->assertStringContainsString('5/5', $history->catatan_perubahan);
    }

    public function test_pelapor_submitting_unsatisfactory_feedback_returns_report_to_admin_verification(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);

        $response = $this->actingAs($reporter)->post('/laporan/'.$report->id_laporan.'/tanggapan', [
            'kesesuaian' => 'belum_sesuai',
            'rating' => 2,
            'komentar' => 'Kertas masih sering macet dan berbunyi bising.',
        ]);

        $response->assertRedirect('/laporan/'.$report->id_laporan.'/tanggapan');
        $response->assertSessionHas('success');

        // Laporan kembali ke antrean verifikasi admin
        $report->refresh();
        $statusVerifikasi = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();
        $this->assertSame($statusVerifikasi->id_status, $report->id_status);

        // Tanggapan tersimpan dengan format sesuai
        $comment = Comment::where('id_laporan', $report->id_laporan)
            ->where('id_pengguna', $reporter->id_pengguna)
            ->latest('id_tanggapan')
            ->first();

        $this->assertNotNull($comment);
        $this->assertFalse($comment->is_sesuai);
        $this->assertSame(2, $comment->rating);

        // Riwayat status mencatat pembukaan kembali laporan
        $history = StatusHistory::where('id_laporan', $report->id_laporan)->latest('id_riwayat')->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('belum sesuai', $history->catatan_perubahan);
        $this->assertStringContainsString('verifikasi ulang admin', $history->catatan_perubahan);
    }

    public function test_validation_errors_when_required_fields_missing(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);

        $response = $this->actingAs($reporter)->post('/laporan/'.$report->id_laporan.'/tanggapan', []);

        $response->assertSessionHasErrors(['kesesuaian', 'rating', 'komentar']);
    }

    public function test_pelapor_can_update_feedback(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);

        $comment = Comment::kirim(
            $reporter,
            $report,
            "[Sudah Sesuai] Penilaian: ★★★★☆ (4/5)\nKomentar awal."
        );

        $response = $this->actingAs($reporter)->put('/laporan/'.$report->id_laporan.'/tanggapan/'.$comment->id_tanggapan, [
            'kesesuaian' => 'sudah_sesuai',
            'rating' => 5,
            'komentar' => 'Komentar yang diperbarui: luar biasa puas.',
        ]);

        $response->assertRedirect('/laporan/'.$report->id_laporan.'/tanggapan');
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertSame(5, $comment->rating);
        $this->assertSame('Komentar yang diperbarui: luar biasa puas.', $comment->komentar_bersih);
    }

    public function test_pelapor_can_delete_feedback(): void
    {
        $reporter = User::whereHas('role', fn ($q) => $q->where('nama_peran', 'Pelapor'))->firstOrFail();
        $report = $this->getFinishedReport($reporter);

        $comment = Comment::kirim(
            $reporter,
            $report,
            "[Sudah Sesuai] Penilaian: ★★★★★ (5/5)\nTanggapan yang akan dihapus."
        );

        $response = $this->actingAs($reporter)->delete('/laporan/'.$report->id_laporan.'/tanggapan/'.$comment->id_tanggapan);

        $response->assertRedirect('/laporan/'.$report->id_laporan.'/tanggapan');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('comments', [
            'id_tanggapan' => $comment->id_tanggapan,
        ]);
    }
}
