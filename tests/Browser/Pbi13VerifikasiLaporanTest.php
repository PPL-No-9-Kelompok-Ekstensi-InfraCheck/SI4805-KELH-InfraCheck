<?php

namespace Tests\Browser;

use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi13')]
class Pbi13VerifikasiLaporanTest extends DuskTestCase
{
    /**
     * PBI 13: Admin dapat memverifikasi laporan kerusakan yang menunggu verifikasi melalui browser.
     */
    public function test_admin_dapat_memverifikasi_laporan(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $pendingStatus = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $pendingStatus->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Verifikasi laporan Dusk '.time(),
            'waktu_laporan' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($admin, $report) {
                $browser->loginAs($admin)
                    ->visit('/admin/verifikasi?id='.$report->id_laporan)
                    ->assertSee('Verifikasi Laporan Kerusakan')
                    ->assertSee($report->deskripsi_kerusakan)
                    ->type('catatan_verifikasi', 'Laporan valid dari pengujian Dusk.')
                    ->press('Verifikasi Laporan')
                    ->waitForLocation('/admin/verifikasi')
                    ->assertPathIs('/admin/verifikasi');
            });

            $this->assertSame('Diverifikasi', $report->fresh()->status->nama_status);
        } finally {
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
