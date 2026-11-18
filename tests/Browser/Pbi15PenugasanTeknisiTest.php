<?php

namespace Tests\Browser;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi15')]
class Pbi15PenugasanTeknisiTest extends DuskTestCase
{
    /**
     * PBI 15: Admin dapat menugaskan laporan yang sudah diverifikasi kepada teknisi melalui browser.
     */
    public function test_admin_dapat_menugaskan_laporan_ke_teknisi(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $verifiedStatus = ReportStatus::where('nama_status', 'Diverifikasi')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $verifiedStatus->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'id_admin_verifikator' => $admin->id_pengguna,
            'deskripsi_kerusakan' => 'Penugasan uji Dusk '.time(),
            'waktu_laporan' => now(),
            'waktu_verifikasi' => now(),
        ]);

        try {
            $this->browse(function (Browser $browser) use ($admin, $teknisi, $report) {
                $browser->loginAs($admin)
                    ->visit('/admin/penugasan?id='.$report->id_laporan)
                    ->assertSee($report->deskripsi_kerusakan)
                    ->click("label:has(input[name='id_teknisi'][value='{$teknisi->id_pengguna}'])")
                    ->type('catatan_penugasan', 'Segera ditangani oleh teknisi via Dusk.')
                    ->press('Tugaskan')
                    ->waitForLocation('/admin/penugasan')
                    ->assertPathIs('/admin/penugasan');
            });

            $this->assertDatabaseHas('assignments', [
                'id_laporan' => $report->id_laporan,
                'id_teknisi' => $teknisi->id_pengguna,
            ]);
        } finally {
            Assignment::where('id_laporan', $report->id_laporan)->delete();
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
