<?php

namespace Tests\Browser;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Room;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi12')]
class Pbi12PerbaruiLaporanTest extends DuskTestCase
{
    /**
     * PBI 12: Pelapor dapat memperbarui informasi laporan yang masih berstatus Menunggu Verifikasi.
     */
    public function test_pelapor_dapat_memperbarui_informasi_laporan(): void
    {
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $statusMenunggu = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();
        $urgensiAwal = UrgencyLevel::where('nama_urgensi', 'Rendah')->firstOrFail();
        $urgensiBaru = UrgencyLevel::where('nama_urgensi', 'Tinggi')->firstOrFail();

        $jenis = DeviceType::firstOrFail();
        $ruanganDaftar = Room::orderBy('id_ruangan')->take(2)->get();
        $ruanganAwal = $ruanganDaftar->first();
        $ruanganBaru = $ruanganDaftar->last();

        $cap = time();
        $perangkatAwal = Device::create([
            'id_jenis' => $jenis->id_jenis,
            'id_ruangan' => $ruanganAwal->id_ruangan,
            'kode_aset' => 'PBI12-A-' . $cap,
            'nama_perangkat' => 'Perangkat Awal Dusk ' . $cap,
            'kondisi' => 'Rusak Ringan',
        ]);
        $perangkatBaru = Device::create([
            'id_jenis' => $jenis->id_jenis,
            'id_ruangan' => $ruanganBaru->id_ruangan,
            'kode_aset' => 'PBI12-B-' . $cap,
            'nama_perangkat' => 'Perangkat Baru Dusk ' . $cap,
            'kondisi' => 'Rusak Berat',
        ]);

        $laporan = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $perangkatAwal->id_perangkat,
            'id_ruangan' => $perangkatAwal->id_ruangan,
            'id_status' => $statusMenunggu->id_status,
            'id_urgensi' => $urgensiAwal->id_urgensi,
            'deskripsi_kerusakan' => 'Deskripsi awal laporan PBI 12 Dusk ' . $cap,
            'waktu_laporan' => now(),
        ]);

        $deskripsiBaru = 'Deskripsi laporan setelah diperbarui melalui Dusk ' . $cap;

        try {
            $this->browse(function (Browser $browser) use ($pelapor, $laporan, $perangkatBaru, $urgensiBaru, $deskripsiBaru) {
                $browser->loginAs($pelapor)
                    ->visit('/laporan/' . $laporan->id_laporan)
                    ->assertSee('Edit Laporan')
                    ->clickLink('Edit Laporan')
                    ->waitForLocation('/laporan/' . $laporan->id_laporan . '/edit')
                    ->assertSee('Formulir Pembaruan Informasi Laporan')
                    ->select('id_perangkat', (string) $perangkatBaru->id_perangkat)
                    ->click("label:has(input[name='id_urgensi'][value='{$urgensiBaru->id_urgensi}'])")
                    ->clear('deskripsi_kerusakan')
                    ->type('deskripsi_kerusakan', $deskripsiBaru)
                    ->press('Simpan Perubahan')
                    ->waitForLocation('/laporan/' . $laporan->id_laporan)
                    ->assertPathIs('/laporan/' . $laporan->id_laporan)
                    ->assertSee('Informasi laporan kerusakan berhasil diperbarui.')
                    ->assertSee($perangkatBaru->nama_perangkat)
                    ->assertSee($deskripsiBaru);
            });

            $this->assertDatabaseHas('reports', [
                'id_laporan' => $laporan->id_laporan,
                'id_perangkat' => $perangkatBaru->id_perangkat,
                'id_ruangan' => $perangkatBaru->id_ruangan,
                'id_urgensi' => $urgensiBaru->id_urgensi,
                'deskripsi_kerusakan' => $deskripsiBaru,
            ]);
        } finally {
            $laporan->photos()->delete();
            $laporan->statusHistories()->delete();
            $laporan->delete();
            $perangkatAwal->delete();
            $perangkatBaru->delete();
        }
    }

    /**
     * PBI 12: Laporan yang sudah diverifikasi tidak menampilkan tombol edit dan menolak akses halaman edit.
     */
    public function test_laporan_terverifikasi_tidak_dapat_diedit(): void
    {
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $statusDiverifikasi = ReportStatus::where('nama_status', 'Diverifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();
        $perangkat = Device::firstOrFail();

        $laporan = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $perangkat->id_perangkat,
            'id_ruangan' => $perangkat->id_ruangan,
            'id_status' => $statusDiverifikasi->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Laporan sudah diverifikasi PBI 12 Dusk ' . time(),
            'waktu_laporan' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($pelapor, $laporan) {
                $browser->loginAs($pelapor)
                    ->visit('/laporan/' . $laporan->id_laporan)
                    ->assertSee($laporan->nomor_laporan)
                    ->assertDontSee('Edit Laporan')
                    ->visit('/laporan/' . $laporan->id_laporan . '/edit')
                    ->waitForLocation('/laporan/' . $laporan->id_laporan)
                    ->assertPathIs('/laporan/' . $laporan->id_laporan)
                    ->assertSee('Laporan tidak dapat diperbarui');
            });
        } finally {
            $laporan->statusHistories()->delete();
            $laporan->delete();
        }
    }
}
