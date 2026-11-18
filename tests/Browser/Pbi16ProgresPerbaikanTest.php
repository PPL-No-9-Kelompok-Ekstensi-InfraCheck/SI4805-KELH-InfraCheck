<?php

namespace Tests\Browser;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairProgress;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi16')]
class Pbi16ProgresPerbaikanTest extends DuskTestCase
{
    /**
     * PBI 16: Teknisi dapat mencatat progres perbaikan melalui browser.
     */
    public function test_teknisi_dapat_mencatat_progres_perbaikan(): void
    {
        $admin = User::where('email', 'admin@infracheck.ac.id')->firstOrFail();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->firstOrFail();
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->firstOrFail();
        $device = Device::firstOrFail();
        $status = ReportStatus::where('nama_status', 'Ditugaskan')->firstOrFail();
        $urgensi = UrgencyLevel::firstOrFail();

        $report = Report::create([
            'id_pelapor' => $pelapor->id_pengguna,
            'id_perangkat' => $device->id_perangkat,
            'id_ruangan' => $device->id_ruangan,
            'id_status' => $status->id_status,
            'id_urgensi' => $urgensi->id_urgensi,
            'id_admin_verifikator' => $admin->id_pengguna,
            'deskripsi_kerusakan' => 'Progres uji Dusk '.time(),
            'waktu_laporan' => now(),
            'waktu_verifikasi' => now(),
        ]);

        $assignment = Assignment::create([
            'id_laporan' => $report->id_laporan,
            'id_teknisi' => $teknisi->id_pengguna,
            'id_admin_penugas' => $admin->id_pengguna,
            'waktu_penugasan' => now(),
            'status_penugasan' => 'aktif',
        ]);

        $catatan = 'Penggantian komponen uji Dusk '.time();

        try {
            $this->browse(function (Browser $browser) use ($teknisi, $assignment, $catatan) {
                $browser->loginAs($teknisi)
                    ->visit('/progres-perbaikan/'.$assignment->id_penugasan.'/progres/create')
                    ->type('persentase', '50')
                    ->type('catatan_progres', $catatan)
                    ->press('Simpan Catatan')
                    ->waitForLocation('/progres-perbaikan/'.$assignment->id_penugasan)
                    ->assertPathIs('/progres-perbaikan/'.$assignment->id_penugasan)
                    ->assertSee($catatan);
            });
        } finally {
            RepairProgress::where('id_penugasan', $assignment->id_penugasan)->delete();
            $assignment->delete();
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
