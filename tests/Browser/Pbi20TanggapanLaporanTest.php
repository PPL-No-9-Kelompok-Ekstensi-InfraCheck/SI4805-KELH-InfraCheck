<?php

namespace Tests\Browser;

use App\Models\Comment;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi20')]
class Pbi20TanggapanLaporanTest extends DuskTestCase
{
    /**
     * PBI 20: Pelapor dapat memberikan tanggapan atas laporan yang sudah selesai melalui browser.
     */
    public function test_pelapor_dapat_memberikan_tanggapan_laporan(): void
    {
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $selesaiStatus = ReportStatus::where('nama_status', 'Selesai')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $selesaiStatus->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'deskripsi_kerusakan' => 'Tanggapan uji Dusk '.time(),
            'waktu_laporan' => now()->subDays(2),
        ]);

        $komentar = 'Perbaikan sangat baik dan cepat (uji Dusk).';

        try {
            $this->browse(function (Browser $browser) use ($pelapor, $report, $komentar) {
                $browser->loginAs($pelapor)
                    ->visit('/laporan/'.$report->id_laporan.'/tanggapan')
                    ->assertSee('Beri Tanggapan atas Penanganan')
                    ->click('#label-sudah-sesuai')
                    ->script("document.getElementById('ratingInput').value = '5';");

                $browser->type('komentar', $komentar)
                    ->press('Kirim Tanggapan')
                    ->waitForLocation('/laporan/'.$report->id_laporan.'/tanggapan')
                    ->assertPathIs('/laporan/'.$report->id_laporan.'/tanggapan')
                    ->assertSee($komentar);
            });

            $this->assertDatabaseHas('comments', [
                'id_laporan' => $report->id_laporan,
                'id_pengguna' => $pelapor->id_pengguna,
            ]);
        } finally {
            Comment::where('id_laporan', $report->id_laporan)->delete();
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
