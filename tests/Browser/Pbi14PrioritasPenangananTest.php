<?php

namespace Tests\Browser;

use App\Models\Device;
use App\Models\Priority;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi14')]
class Pbi14PrioritasPenangananTest extends DuskTestCase
{
    /**
     * PBI 14: Admin dapat menentukan prioritas penanganan untuk laporan yang sudah diverifikasi melalui browser.
     */
    public function test_admin_dapat_menentukan_prioritas_penanganan(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $verifiedStatus = ReportStatus::where('nama_status', 'Diverifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();
        $priorityTinggi = Priority::where('nama_prioritas', 'Tinggi')->firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $verifiedStatus->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'id_prioritas' => null,
            'id_admin_verifikator' => $admin->id_pengguna,
            'deskripsi_kerusakan' => 'Prioritas uji Dusk '.time(),
            'waktu_laporan' => now()->subMinutes(30),
            'waktu_verifikasi' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($admin, $report, $priorityTinggi) {
                $browser->loginAs($admin)
                    ->visit('/admin/prioritas?selected='.$report->id_laporan)
                    ->assertSee('Penentuan Prioritas Penanganan')
                    ->assertSee($report->nomor_laporan)
                    ->click("label:has(input[name='id_prioritas'][value='{$priorityTinggi->id_prioritas}'])")
                    ->type('alasan_prioritas', 'Digunakan oleh ruang kuliah aktif sepanjang hari.')
                    ->press('Simpan Prioritas')
                    ->waitForLocation('/admin/prioritas')
                    ->assertPathIs('/admin/prioritas')
                    ->assertSee('berhasil ditetapkan menjadi Tinggi');
            });

            $this->assertEquals($priorityTinggi->id_prioritas, $report->fresh()->id_prioritas);
        } finally {
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
