<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairProgress;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\StatusHistory;
use App\Models\UrgencyLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

class MonitorReportTest extends TestCase
{
    use RefreshDatabase;

    private User $pelapor;

    private User $admin;

    private User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $this->admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $this->teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
    }

    /**
     * Membuat laporan kerusakan langsung lewat model untuk kebutuhan pengujian.
     */
    private function buatLaporan(User $pelapor, string $namaStatus = 'Menunggu Verifikasi'): Report
    {
        $perangkat = Device::firstOrFail();
        $status = ReportStatus::where('nama_status', $namaStatus)->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        return Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $perangkat->id_perangkat,
            'id_ruangan' => $perangkat->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Proyektor tidak menyala dan lampu indikator berkedip merah.',
            'waktu_laporan' => now(),
        ]);
    }

    public function test_tamu_tidak_dapat_mengakses_daftar_laporan(): void
    {
        $response = $this->get('/laporan');

        $response->assertRedirect('/login');
    }

    public function test_pelapor_hanya_melihat_laporan_miliknya_sendiri(): void
    {
        $milikSendiri = $this->buatLaporan($this->pelapor, 'Menunggu Verifikasi');
        $milikOrangLain = $this->buatLaporan($this->admin, 'Diverifikasi');

        $response = $this->actingAs($this->pelapor)->get('/laporan');

        $response->assertStatus(200);
        $response->assertSee('Laporan Kerusakan Saya');
        $response->assertSee($milikSendiri->nomor_laporan);
        $response->assertDontSee($milikOrangLain->nomor_laporan);
    }

    public function test_pelapor_dapat_membuka_detail_laporan_miliknya(): void
    {
        $laporan = $this->buatLaporan($this->pelapor);

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(200);
        $response->assertSee($laporan->nomor_laporan);
        $response->assertSee('Detail Laporan');
        $response->assertSee('Progres Penanganan');
        $response->assertSee('Riwayat Status');
        $response->assertSee('Proyektor tidak menyala');
        $response->assertSee($laporan->device->nama_perangkat);
    }

    public function test_pelapor_tidak_dapat_membuka_laporan_milik_pengguna_lain(): void
    {
        $laporan = $this->buatLaporan($this->admin);

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(403);
    }

    public function test_detail_menampilkan_indikator_progres_sesuai_status(): void
    {
        $laporan = $this->buatLaporan($this->pelapor, 'Sedang Dikerjakan');

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(200);
        $response->assertSee('70%');
    }

    public function test_detail_menampilkan_progres_terakhir_dari_teknisi(): void
    {
        $laporan = $this->buatLaporan($this->pelapor, 'Sedang Dikerjakan');

        $penugasan = Assignment::create([
            'id_laporan' => $laporan->id_laporan,
            'id_teknisi' => $this->teknisi->id_pengguna,
            'id_admin_penugas' => $this->admin->id_pengguna,
            'waktu_penugasan' => now(),
            'status_penugasan' => 'aktif',
        ]);

        RepairProgress::create([
            'id_penugasan' => $penugasan->id_penugasan,
            'persentase' => 85,
            'catatan_progres' => 'Penggantian modul lampu proyektor sedang dilakukan.',
            'waktu_progres' => now(),
        ]);

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(200);
        $response->assertSee('85%');
        $response->assertSee($this->teknisi->nama);
        $response->assertSee('Penggantian modul lampu proyektor sedang dilakukan.');

        // Progres pada daftar laporan harus konsisten dengan halaman detail
        $daftar = $this->actingAs($this->pelapor)->get('/laporan');
        $daftar->assertStatus(200);
        $daftar->assertSee('85%');
    }

    public function test_detail_menampilkan_keterangan_laporan_ditolak(): void
    {
        $laporan = $this->buatLaporan($this->pelapor, 'Ditolak');

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(200);
        $response->assertSee('ditolak oleh Admin Unit IT');
        $response->assertSee('0%');
    }

    public function test_detail_menampilkan_riwayat_status_laporan(): void
    {
        $laporan = $this->buatLaporan($this->pelapor, 'Diverifikasi');

        StatusHistory::create([
            'id_laporan' => $laporan->id_laporan,
            'id_status' => $laporan->id_status,
            'id_pengubah' => $this->admin->id_pengguna,
            'waktu_perubahan' => now(),
            'catatan_perubahan' => 'Laporan telah diverifikasi dan akan segera ditugaskan.',
        ]);

        $response = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);

        $response->assertStatus(200);
        $response->assertSee('Laporan telah diverifikasi dan akan segera ditugaskan.');
        $response->assertSee($this->admin->nama);
    }

    public function test_daftar_laporan_dapat_disaring_berdasarkan_status(): void
    {
        $menunggu = $this->buatLaporan($this->pelapor, 'Menunggu Verifikasi');
        $selesai = $this->buatLaporan($this->pelapor, 'Selesai');

        $response = $this->actingAs($this->pelapor)->get('/laporan?status=Selesai');

        $response->assertStatus(200);
        $response->assertSee($selesai->nomor_laporan);
        $response->assertDontSee($menunggu->nomor_laporan);
    }
}
