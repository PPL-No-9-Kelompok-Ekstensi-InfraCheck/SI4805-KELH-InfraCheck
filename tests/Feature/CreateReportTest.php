<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;

class CreateReportTest extends TestCase
{
    use RefreshDatabase;

    private User $pelapor;

    /**
     * Berkas foto yang dihasilkan selama pengujian untuk dibersihkan.
     *
     * @var list<string>
     */
    private static array $berkasFoto = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->pelapor = User::where('email', 'pelapor@infracheck.ac.id')->first();
    }

    /**
     * Menghapus berkas foto yang dihasilkan selama pengujian.
     */
    protected function tearDown(): void
    {
        foreach (self::$berkasFoto as $lokasi) {
            @unlink(public_path($lokasi));
        }
        self::$berkasFoto = [];

        parent::tearDown();
    }

    public function test_tamu_tidak_dapat_mengakses_formulir_laporan(): void
    {
        $response = $this->get('/laporan/buat');
        $response->assertRedirect('/login');
    }

    public function test_formulir_laporan_dapat_ditampilkan(): void
    {
        $response = $this->actingAs($this->pelapor)->get('/laporan/buat');

        $response->assertStatus(200);
        $response->assertSee('Formulir Pengajuan Laporan Kerusakan');
        $response->assertSee('AST-AP-001');
    }

    public function test_pelapor_dapat_mengajukan_laporan_tanpa_foto(): void
    {
        $device = Device::where('kode_aset', 'AST-AP-001')->first();
        $urgensi = UrgencyLevel::where('nama_urgensi', 'Tinggi')->first();
        $statusAwal = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->first();

        $response = $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Access point tidak mengeluarkan sinyal wifi sejak tadi pagi.',
        ]);

        $response->assertRedirect('/laporan/buat');
        $response->assertSessionHas('status', 'Laporan kerusakan berhasil diajukan.');

        $laporan = Report::where('id_perangkat', $device->id_perangkat)->first();
        $this->assertNotNull($laporan);
        $this->assertSame($this->pelapor->id_pengguna, $laporan->id_pelapor);
        $this->assertSame($device->id_ruangan, $laporan->id_ruangan);
        $this->assertSame($statusAwal->id_status, $laporan->id_status);
        $this->assertNull($laporan->id_prioritas);
        $this->assertNull($laporan->id_admin_verifikator);
    }

    public function test_pelapor_dapat_mengunggah_foto_dokumentasi(): void
    {
        $device = Device::firstOrFail();
        $urgensi = UrgencyLevel::where('nama_urgensi', 'Kritis')->first();

        $response = $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Proyektor tidak menyala sama sekali dan layar mati.',
            'foto' => [
                UploadedFile::fake()->image('bukti1.png'),
                UploadedFile::fake()->image('bukti2.png'),
            ],
        ]);

        $response->assertRedirect();

        $laporan = Report::where('id_perangkat', $device->id_perangkat)->firstOrFail();

        $this->assertSame(2, $laporan->photos()->count());
        $laporan->photos->each(function ($foto) {
            $this->assertStringStartsWith('uploads/laporan/', $foto->lokasi_berkas);
            $this->assertFileExists(public_path($foto->lokasi_berkas));

            // Catat berkas agar dibersihkan setelah pengujian
            self::$berkasFoto[] = $foto->lokasi_berkas;
        });
    }

    public function test_laporan_ganda_pada_perangkat_aktif_ditolak(): void
    {
        $device = Device::firstOrFail();
        $urgensi = UrgencyLevel::where('nama_urgensi', 'Sedang')->first();

        // Buat laporan pertama (masih aktif: Menunggu Verifikasi)
        $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Perangkat mati total dan tidak mau menyala sama sekali.',
        ]);

        // Pengajuan kedua untuk perangkat yang sama harus ditolak
        $response = $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Perangkat masih rusak, melaporkan kembali kerusakan itu.',
        ]);

        $response->assertSessionHasErrors('id_perangkat');

        $this->assertSame(1, Report::where('id_perangkat', $device->id_perangkat)->count());
    }

    public function test_data_laporan_tidak_valid_ditolak_validasi(): void
    {
        $device = Device::firstOrFail();

        $response = $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => '',
            'deskripsi_kerusakan' => 'pendek',
        ]);

        $response->assertSessionHasErrors(['id_urgensi', 'deskripsi_kerusakan']);
        $this->assertSame(0, Report::count());
    }

    public function test_endpoint_cek_duplikat_mengenali_laporan_aktif(): void
    {
        $device = Device::firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $this->actingAs($this->pelapor)->post('/laporan', [
            'id_perangkat' => $device->id_perangkat,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Perangkat error dan layar menampilkan kode kesalahan.',
        ]);

        $response = $this->actingAs($this->pelapor)->getJson(
            '/laporan/cek-duplikat?id_perangkat=' . $device->id_perangkat
        );

        $response->assertStatus(200)
            ->assertJson([
                'duplikat' => true,
                'nama_status' => 'Menunggu Verifikasi',
            ]);
    }

    public function test_endpoint_cek_duplikat_tanpa_laporan_aktif(): void
    {
        $device = Device::whereDoesntHave('reports')->firstOrFail();

        $response = $this->actingAs($this->pelapor)->getJson(
            '/laporan/cek-duplikat?id_perangkat=' . $device->id_perangkat
        );

        $response->assertStatus(200)
            ->assertJson(['duplikat' => false]);
    }
}
