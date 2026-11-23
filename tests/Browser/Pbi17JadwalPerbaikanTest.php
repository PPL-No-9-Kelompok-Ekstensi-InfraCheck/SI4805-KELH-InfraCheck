<?php

namespace Tests\Browser;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\RepairSchedule;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

#[Group('pbi17')]
class Pbi17JadwalPerbaikanTest extends DuskTestCase
{
    /**
     * PBI 17: Admin dapat menjadwalkan perbaikan untuk penugasan aktif melalui browser.
     */
    public function test_admin_dapat_membuat_jadwal_perbaikan(): void
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
            'deskripsi_kerusakan' => 'Jadwal uji Dusk '.time(),
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

        $catatan = 'Jadwal perbaikan Dusk '.time();
        $mulai = now()->addDay()->format('Y-m-d\TH:i');
        $selesai = now()->addDays(2)->format('Y-m-d\TH:i');

        try {
            $this->browse(function (Browser $browser) use ($admin, $assignment, $mulai, $selesai, $catatan) {
                $browser->loginAs($admin)
                    ->visit('/jadwal-perbaikan/create')
                    ->assertSee('Penjadwalan Perbaikan')
                    ->select('id_penugasan', (string) $assignment->id_penugasan)
                    ->script([
                        "document.querySelector(\"input[name='waktu_mulai']\").value = '{$mulai}';",
                        "document.querySelector(\"input[name='target_selesai']\").value = '{$selesai}';",
                    ]);

                $browser->type('catatan_jadwal', $catatan)
                    ->press('Simpan Jadwal')
                    ->waitForLocation('/jadwal-perbaikan')
                    ->assertPathIs('/jadwal-perbaikan');
            });

            $this->assertDatabaseHas('repair_schedules', [
                'id_penugasan' => $assignment->id_penugasan,
                'catatan_jadwal' => $catatan,
            ]);
        } finally {
            RepairSchedule::where('id_penugasan', $assignment->id_penugasan)->delete();
            $assignment->delete();
            $report->statusHistories()->delete();
            $report->delete();
        }
    }
}
