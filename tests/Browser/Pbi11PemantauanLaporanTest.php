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

#[Group('pbi11')]
class Pbi11PemantauanLaporanTest extends DuskTestCase
{
    /**
     * PBI 11: Pelapor dapat memantau daftar dan detail laporan miliknya melalui browser.
     */
    public function test_pelapor_dapat_memantau_laporan_miliknya(): void
    {
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $status = ReportStatus::where('nama_status', 'Menunggu Verifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Pantau laporan Dusk '.time(),
            'waktu_laporan' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($pelapor, $report) {
                $browser->loginAs($pelapor)
                    ->visit('/laporan')
                    ->assertSee($report->nomor_laporan)
                    ->visit('/laporan/'.$report->id_laporan)
                    ->assertSee($report->nomor_laporan)
                    ->assertSee($report->deskripsi_kerusakan);
            });
        } finally {
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
