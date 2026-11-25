<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\ReportStatus;
use App\Models\Role;
use App\Models\UrgencyLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateReportTest extends TestCase
{
    use RefreshDatabase;

    private User $pelapor;

    private User $pelaporLain;

    private User $admin;

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

        $this->pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $this->admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();

        $rolePelapor = Role::where('nama_peran', 'Pelapor')->firstOrFail();
        $this->pelaporLain = User::create([
            'id_peran' => $rolePelapor->id_peran,
            'nama' => 'Pelapor Kedua Uji',
            'email' => 'pelapor2@infracheck.ac.id',
            'kata_sandi_hash' => Hash::make('password'),
            'nim_nip' => '1202229999',
            'unit' => 'Teknik Informatika',
            'kontak' => '081399998888',
            'akun_aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach (self::$berkasFoto as $lokasi) {
            $jalur = public_path($lokasi);
            if (is_file($jalur)) {
                @unlink($jalur);
            }
        }
        self::$berkasFoto = [];

        parent::tearDown();
    }

    /**
     * Membuat laporan uji langsung melalui model.
     */
    private function buatLaporan(
        User $pelapor,
        ?Device $perangkat = null,
        string $namaStatus = 'Menunggu Verifikasi',
        string $namaUrgensi = 'Sedang'
    ): Report {
        $perangkat ??= Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $status = ReportStatus::where('nama_status', $namaStatus)->firstOrFail();
        $urgensi = UrgencyLevel::where('nama_urgensi', $namaUrgensi)->firstOrFail();

        return Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $perangkat->id_perangkat,
            'id_ruangan' => $perangkat->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Deskripsi awal kerusakan perangkat sebelum diperbarui oleh pelapor.',
            'waktu_laporan' => now(),
        ]);
    }

    /**
     * Membuat berkas foto fisik dan catatan ReportPhoto untuk laporan uji.
     */
    private function lampirkanFoto(Report $laporan, string $namaBerkas): ReportPhoto
    {
        $direktori = public_path('uploads/laporan');
        if (! is_dir($direktori)) {
            mkdir($direktori, 0755, true);
        }

        $namaUnik = 'uji_' . time() . '_' . uniqid() . '_' . $namaBerkas;
        $lokasiRelatif = 'uploads/laporan/' . $namaUnik;
        file_put_contents(public_path($lokasiRelatif), 'fake-image-content');
        self::$berkasFoto[] = $lokasiRelatif;

        return ReportPhoto::create([
            'id_laporan' => $laporan->id_laporan,
            'lokasi_berkas' => $lokasiRelatif,
            'waktu_unggah' => now(),
        ]);
    }

    public function test_tamu_tidak_dapat_mengakses_edit_atau_memperbarui_laporan(): void
    {
        $laporan = $this->buatLaporan($this->pelapor);

        $this->get('/laporan/' . $laporan->id_laporan . '/edit')
            ->assertRedirect('/login');

        $this->put('/laporan/' . $laporan->id_laporan, [
            'id_perangkat' => $laporan->id_perangkat,
            'id_urgensi' => $laporan->id_urgensi,
            'deskripsi_kerusakan' => 'Percobaan update dari tamu tidak terautentikasi.',
        ])->assertRedirect('/login');
    }

    public function test_pelapor_pemilik_dapat_membuka_halaman_edit_dan_melihat_tombol_edit(): void
    {
        $laporan = $this->buatLaporan($this->pelapor, null, 'Menunggu Verifikasi');

        $halamanDaftar = $this->actingAs($this->pelapor)->get('/laporan');
        $halamanDaftar->assertStatus(200)
            ->assertSee('Edit Laporan')
            ->assertSee(route('reports.edit', $laporan->id_laporan));

        $halamanDetail = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan);
        $halamanDetail->assertStatus(200)
            ->assertSee('Edit Laporan')
            ->assertSee(route('reports.edit', $laporan->id_laporan));

        $halamanEdit = $this->actingAs($this->pelapor)->get('/laporan/' . $laporan->id_laporan . '/edit');
        $halamanEdit->assertStatus(200)
            ->assertSee('Formulir Pembaruan Informasi Laporan')
            ->assertSee($laporan->nomor_laporan)
            ->assertSee($laporan->deskripsi_kerusakan)
            ->assertSee('Simpan Perubahan');
    }

    public function test_pelapor_pemilik_dapat_memperbarui_perangkat_urgensi_deskripsi_dan_foto(): void
    {
        $perangkatAwal = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $perangkatBaru = Device::where('kode_aset', 'AST-PC-LAB-01')->firstOrFail();
        $urgensiBaru = UrgencyLevel::where('nama_urgensi', 'Kritis')->firstOrFail();

        $this->assertNotSame($perangkatAwal->id_ruangan, $perangkatBaru->id_ruangan);

        $laporan = $this->buatLaporan($this->pelapor, $perangkatAwal, 'Menunggu Verifikasi', 'Rendah');
        $fotoLamaDihapus = $this->lampirkanFoto($laporan, 'lama_hapus.png');
        $fotoLamaDipertahankan = $this->lampirkanFoto($laporan, 'lama_simpan.png');

        $deskripsiBaru = 'PC Lab 01 tidak bisa menyala sama sekali setelah pemadaman listrik mendadak.';

        $response = $this->actingAs($this->pelapor)->put('/laporan/' . $laporan->id_laporan, [
            'id_perangkat' => $perangkatBaru->id_perangkat,
            'id_urgensi' => $urgensiBaru->id_urgensi,
            'deskripsi_kerusakan' => $deskripsiBaru,
            'hapus_foto' => [$fotoLamaDihapus->id_foto],
            'foto' => [
                UploadedFile::fake()->image('bukti_baru_1.png'),
                UploadedFile::fake()->image('bukti_baru_2.jpg'),
            ],
        ]);

        $response->assertRedirect(route('reports.show', $laporan->id_laporan));
        $response->assertSessionHas('success', 'Informasi laporan kerusakan berhasil diperbarui.');

        $laporan->refresh();
        $this->assertSame($perangkatBaru->id_perangkat, $laporan->id_perangkat);
        $this->assertSame($perangkatBaru->id_ruangan, $laporan->id_ruangan);
        $this->assertSame($urgensiBaru->id_urgensi, $laporan->id_urgensi);
        $this->assertSame($deskripsiBaru, $laporan->deskripsi_kerusakan);

        // Foto lama yang ditandai hapus hilang dari DB & disk
        $this->assertDatabaseMissing('report_photos', ['id_foto' => $fotoLamaDihapus->id_foto]);
        $this->assertFileDoesNotExist(public_path($fotoLamaDihapus->lokasi_berkas));

        // Foto lama yang tidak dihapus tetap ada, ditambah 2 foto baru => total 3 foto
        $this->assertDatabaseHas('report_photos', ['id_foto' => $fotoLamaDipertahankan->id_foto]);
        $this->assertSame(3, $laporan->photos()->count());

        $laporan->photos()->get()->each(function (ReportPhoto $foto) {
            $this->assertStringStartsWith('uploads/laporan/', $foto->lokasi_berkas);
            $this->assertFileExists(public_path($foto->lokasi_berkas));
            self::$berkasFoto[] = $foto->lokasi_berkas;
        });
    }

    public function test_pembaruan_tetap_berhasil_jika_perangkat_tidak_diubah(): void
    {
        $perangkat = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $urgensiBaru = UrgencyLevel::where('nama_urgensi', 'Tinggi')->firstOrFail();
        $laporan = $this->buatLaporan($this->pelapor, $perangkat, 'Menunggu Verifikasi', 'Sedang');

        // Endpoint cek duplikat dengan pengecualian laporan ini sendiri tidak menandai duplikat
        $cek = $this->actingAs($this->pelapor)->getJson(
            '/laporan/cek-duplikat?id_perangkat=' . $perangkat->id_perangkat . '&kecualikan_laporan=' . $laporan->id_laporan
        );
        $cek->assertStatus(200)->assertJson(['duplikat' => false]);

        $deskripsiBaru = 'Access point masih menyala namun sinyal Wi-Fi hilang timbul setiap 5 menit.';

        $response = $this->actingAs($this->pelapor)->put('/laporan/' . $laporan->id_laporan, [
            'id_perangkat' => $perangkat->id_perangkat,
            'id_urgensi' => $urgensiBaru->id_urgensi,
            'deskripsi_kerusakan' => $deskripsiBaru,
        ]);

        $response->assertRedirect(route('reports.show', $laporan->id_laporan));
        $response->assertSessionHas('success');

        $laporan->refresh();
        $this->assertSame($perangkat->id_perangkat, $laporan->id_perangkat);
        $this->assertSame($urgensiBaru->id_urgensi, $laporan->id_urgensi);
        $this->assertSame($deskripsiBaru, $laporan->deskripsi_kerusakan);
    }

    public function test_ganti_ke_perangkat_yang_memiliki_laporan_aktif_lain_ditolak(): void
    {
        $perangkatAwal = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $perangkatLain = Device::where('kode_aset', 'AST-PRJ-001')->firstOrFail();

        $laporanSaya = $this->buatLaporan($this->pelapor, $perangkatAwal, 'Menunggu Verifikasi');
        $this->buatLaporan($this->pelaporLain, $perangkatLain, 'Sedang Dikerjakan');

        $response = $this->actingAs($this->pelapor)
            ->from('/laporan/' . $laporanSaya->id_laporan . '/edit')
            ->put('/laporan/' . $laporanSaya->id_laporan, [
                'id_perangkat' => $perangkatLain->id_perangkat,
                'id_urgensi' => $laporanSaya->id_urgensi,
                'deskripsi_kerusakan' => 'Mencoba memindahkan laporan ke perangkat yang sudah dilaporkan aktif.',
            ]);

        $response->assertRedirect('/laporan/' . $laporanSaya->id_laporan . '/edit');
        $response->assertSessionHasErrors('id_perangkat');

        $laporanSaya->refresh();
        $this->assertSame($perangkatAwal->id_perangkat, $laporanSaya->id_perangkat);
    }

    public function test_ganti_ke_perangkat_dengan_laporan_selesai_diizinkan(): void
    {
        $perangkatAwal = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $perangkatLain = Device::where('kode_aset', 'AST-PRJ-001')->firstOrFail();

        $laporanSaya = $this->buatLaporan($this->pelapor, $perangkatAwal, 'Menunggu Verifikasi');
        $this->buatLaporan($this->pelaporLain, $perangkatLain, 'Selesai');

        $response = $this->actingAs($this->pelapor)->put('/laporan/' . $laporanSaya->id_laporan, [
            'id_perangkat' => $perangkatLain->id_perangkat,
            'id_urgensi' => $laporanSaya->id_urgensi,
            'deskripsi_kerusakan' => 'Proyektor di ruang kuliah kembali mati dan perlu pemeriksaan ulang.',
        ]);

        $response->assertRedirect(route('reports.show', $laporanSaya->id_laporan));
        $response->assertSessionHas('success');

        $laporanSaya->refresh();
        $this->assertSame($perangkatLain->id_perangkat, $laporanSaya->id_perangkat);
        $this->assertSame($perangkatLain->id_ruangan, $laporanSaya->id_ruangan);
    }

    public function test_validasi_input_tidak_valid_ditolak(): void
    {
        $laporan = $this->buatLaporan($this->pelapor);

        // Deskripsi terlalu pendek dan format foto bukan gambar
        $response = $this->actingAs($this->pelapor)
            ->from('/laporan/' . $laporan->id_laporan . '/edit')
            ->put('/laporan/' . $laporan->id_laporan, [
                'id_perangkat' => $laporan->id_perangkat,
                'id_urgensi' => $laporan->id_urgensi,
                'deskripsi_kerusakan' => 'pendek',
                'foto' => [
                    UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect('/laporan/' . $laporan->id_laporan . '/edit');
        $response->assertSessionHasErrors(['deskripsi_kerusakan', 'foto.0']);
    }

    public function test_total_foto_melebihi_lima_ditolak_dan_terkendali_setelah_hapus(): void
    {
        $laporan = $this->buatLaporan($this->pelapor);
        $foto1 = $this->lampirkanFoto($laporan, 'f1.png');
        $this->lampirkanFoto($laporan, 'f2.png');
        $this->lampirkanFoto($laporan, 'f3.png');

        // 3 foto lama + 3 foto baru = 6 (> 5) => ditolak
        $gagal = $this->actingAs($this->pelapor)
            ->from('/laporan/' . $laporan->id_laporan . '/edit')
            ->put('/laporan/' . $laporan->id_laporan, [
                'id_perangkat' => $laporan->id_perangkat,
                'id_urgensi' => $laporan->id_urgensi,
                'deskripsi_kerusakan' => 'Mencoba mengunggah foto melebihi batas maksimal lima berkas.',
                'foto' => [
                    UploadedFile::fake()->image('baru1.png'),
                    UploadedFile::fake()->image('baru2.png'),
                    UploadedFile::fake()->image('baru3.png'),
                ],
            ]);

        $gagal->assertRedirect('/laporan/' . $laporan->id_laporan . '/edit');
        $gagal->assertSessionHasErrors('foto');
        $this->assertSame(3, $laporan->photos()->count());

        // Hapus 1 foto lama (tersisa 2) + 3 foto baru = 5 (<= 5) => berhasil
        $berhasil = $this->actingAs($this->pelapor)->put('/laporan/' . $laporan->id_laporan, [
            'id_perangkat' => $laporan->id_perangkat,
            'id_urgensi' => $laporan->id_urgensi,
            'deskripsi_kerusakan' => 'Menghapus satu foto lama lalu menambahkan tiga foto dokumentasi baru.',
            'hapus_foto' => [$foto1->id_foto],
            'foto' => [
                UploadedFile::fake()->image('baru1.png'),
                UploadedFile::fake()->image('baru2.png'),
                UploadedFile::fake()->image('baru3.png'),
            ],
        ]);

        $berhasil->assertRedirect(route('reports.show', $laporan->id_laporan));
        $berhasil->assertSessionHas('success');
        $this->assertSame(5, $laporan->photos()->count());

        $laporan->photos()->get()->each(function (ReportPhoto $foto) {
            self::$berkasFoto[] = $foto->lokasi_berkas;
        });
    }

    public function test_tidak_dapat_menghapus_foto_milik_laporan_lain(): void
    {
        $perangkat1 = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();
        $perangkat2 = Device::where('kode_aset', 'AST-PRJ-001')->firstOrFail();

        $laporanSaya = $this->buatLaporan($this->pelapor, $perangkat1);
        $laporanLain = $this->buatLaporan($this->pelaporLain, $perangkat2);
        $fotoMilikOrangLain = $this->lampirkanFoto($laporanLain, 'milik_lain.png');

        $response = $this->actingAs($this->pelapor)->put('/laporan/' . $laporanSaya->id_laporan, [
            'id_perangkat' => $laporanSaya->id_perangkat,
            'id_urgensi' => $laporanSaya->id_urgensi,
            'deskripsi_kerusakan' => 'Memperbarui deskripsi laporan sambil menyisipkan ID foto laporan lain.',
            'hapus_foto' => [$fotoMilikOrangLain->id_foto],
        ]);

        $response->assertRedirect(route('reports.show', $laporanSaya->id_laporan));
        $this->assertDatabaseHas('report_photos', ['id_foto' => $fotoMilikOrangLain->id_foto]);
        $this->assertFileExists(public_path($fotoMilikOrangLain->lokasi_berkas));
    }

    public function test_pelapor_lain_dan_admin_ditolak_403_saat_mengakses_edit_atau_update(): void
    {
        $laporan = $this->buatLaporan($this->pelapor);
        $deskripsiAsli = $laporan->deskripsi_kerusakan;

        foreach ([$this->pelaporLain, $this->admin] as $penggunaLain) {
            $this->actingAs($penggunaLain)
                ->get('/laporan/' . $laporan->id_laporan . '/edit')
                ->assertStatus(403);

            $this->actingAs($penggunaLain)
                ->put('/laporan/' . $laporan->id_laporan, [
                    'id_perangkat' => $laporan->id_perangkat,
                    'id_urgensi' => $laporan->id_urgensi,
                    'deskripsi_kerusakan' => 'Percobaan ubah oleh pengguna bukan pemilik laporan.',
                ])
                ->assertStatus(403);
        }

        $laporan->refresh();
        $this->assertSame($deskripsiAsli, $laporan->deskripsi_kerusakan);
    }

    public function test_laporan_yang_bukan_menunggu_verifikasi_ditolak_untuk_diedit_atau_diperbarui(): void
    {
        $statusTidakDiizinkan = [
            'Diverifikasi',
            'Ditugaskan',
            'Sedang Dikerjakan',
            'Menunggu Validasi',
            'Selesai',
            'Ditolak',
            'Dibatalkan',
        ];

        $perangkat = Device::where('kode_aset', 'AST-AP-001')->firstOrFail();

        foreach ($statusTidakDiizinkan as $namaStatus) {
            $laporan = $this->buatLaporan($this->pelapor, $perangkat, $namaStatus);
            $deskripsiAsli = $laporan->deskripsi_kerusakan;

            // Tombol Edit Laporan disembunyikan di halaman daftar dan halaman detail
            $this->actingAs($this->pelapor)
                ->get('/laporan?status=' . urlencode($namaStatus))
                ->assertStatus(200)
                ->assertDontSee('Edit Laporan');

            $this->actingAs($this->pelapor)
                ->get('/laporan/' . $laporan->id_laporan)
                ->assertStatus(200)
                ->assertDontSee('Edit Laporan');

            // Akses halaman edit ditolak dengan pesan error
            $this->actingAs($this->pelapor)
                ->get('/laporan/' . $laporan->id_laporan . '/edit')
                ->assertRedirect(route('reports.show', $laporan->id_laporan))
                ->assertSessionHas('error');

            // Aksi PUT update ditolak dan data tidak berubah
            $this->actingAs($this->pelapor)
                ->put('/laporan/' . $laporan->id_laporan, [
                    'id_perangkat' => $laporan->id_perangkat,
                    'id_urgensi' => $laporan->id_urgensi,
                    'deskripsi_kerusakan' => 'Percobaan memperbarui laporan yang sudah berstatus ' . $namaStatus,
                ])
                ->assertRedirect(route('reports.show', $laporan->id_laporan))
                ->assertSessionHas('error');

            $laporan->refresh();
            $this->assertSame($deskripsiAsli, $laporan->deskripsi_kerusakan);
        }
    }
}
