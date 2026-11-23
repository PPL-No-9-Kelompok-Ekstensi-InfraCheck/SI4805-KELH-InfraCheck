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

#[Group('pbi19')]
class Pbi19PemantauanStatusLaporanTest extends DuskTestCase
{
    /**
     * PBI 19: Admin dan Pimpinan dapat memantau daftar status dan detail laporan melalui browser.
     */
    public function test_admin_dan_pimpinan_dapat_memantau_status_laporan(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pimpinan = User::where('email', 'pimpinan@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $status = ReportStatus::where('nama_status', 'Diverifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Pantau status Dusk '.time(),
            'waktu_laporan' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($admin, $pimpinan, $report) {
                $browser->loginAs($admin)
                    ->visit('/laporan/status')
                    ->assertSee('Pemantauan Status Laporan')
                    ->visit('/laporan/'.$report->id_laporan)
                    ->assertSee('Detail Laporan')
                    ->assertSee($report->nomor_laporan);

                $browser->loginAs($pimpinan)
                    ->visit('/laporan/status')
                    ->assertSee('Pemantauan Status Laporan');
            });
        } finally {
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
