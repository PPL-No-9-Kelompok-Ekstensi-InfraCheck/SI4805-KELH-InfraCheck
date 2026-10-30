<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Device;
use App\Models\Priority;
use App\Models\RepairProgress;
use App\Models\RepairSchedule;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairProgressSeeder extends Seeder
{
    /**
     * Data contoh laporan, penugasan, dan progres perbaikan untuk PBI 16.
     */
    public function run(): void
    {
        $teknisi = User::where('email', 'teknisi@infracheck.ac.id')->first();
        $admin = User::where('email', 'admin@infracheck.ac.id')->first();
        $pelapor = User::where('email', 'pelapor@infracheck.ac.id')->first();

        if (! $teknisi || ! $admin || ! $pelapor) {
            return;
        }

        $status = fn (string $nama) => ReportStatus::where('nama_status', $nama)->value('id_status');
        $urgensi = fn (string $nama) => UrgencyLevel::where('nama_urgensi', $nama)->value('id_urgensi');
        $prioritas = fn (string $nama) => Priority::where('nama_prioritas', $nama)->value('id_prioritas');
        $perangkat = fn (string $kode) => Device::where('kode_aset', $kode)->first();

        $proyektor = $perangkat('AST-PRJ-001');
        $pcLab = $perangkat('AST-PC-LAB-01');
        $accessPoint = $perangkat('AST-AP-001');

        if (! $proyektor || ! $pcLab || ! $accessPoint) {
            return;
        }

        // 1. Laporan proyektor yang sedang dikerjakan teknisi.
        $laporanProyektor = Report::firstOrCreate(
            [
                'id_perangkat' => $proyektor->id_perangkat,
                'deskripsi_kerusakan' => 'Proyektor di Ruang Kuliah 101 tidak menampilkan gambar. Lampu indikator berkedip merah dan kipas berbunyi kasar.',
            ],
            [
                'id_pelapor' => $pelapor->id_pengguna,
                'id_ruangan' => $proyektor->id_ruangan,
                'id_status' => $status('Sedang Dikerjakan'),
                'id_urgensi' => $urgensi('Sedang'),
                'id_prioritas' => $prioritas('Sedang'),
                'id_admin_verifikator' => $admin->id_pengguna,
                'waktu_laporan' => now()->subDays(3),
                'waktu_verifikasi' => now()->subDays(2),
                'catatan_verifikasi' => 'Kerusakan terkonfirmasi, segera ditugaskan ke teknisi.',
            ]
        );

        // 2. Laporan PC laboratorium dengan progres awal.
        $laporanPc = Report::firstOrCreate(
            [
                'id_perangkat' => $pcLab->id_perangkat,
                'deskripsi_kerusakan' => 'PC Workstation Lab 01 tidak dapat booting. Muncul blue screen setelah pembaruan driver grafis.',
            ],
            [
                'id_pelapor' => $pelapor->id_pengguna,
                'id_ruangan' => $pcLab->id_ruangan,
                'id_status' => $status('Ditugaskan'),
                'id_urgensi' => $urgensi('Tinggi'),
                'id_prioritas' => $prioritas('Tinggi'),
                'id_admin_verifikator' => $admin->id_pengguna,
                'waktu_laporan' => now()->subDay(),
                'waktu_verifikasi' => now()->subHours(20),
                'catatan_verifikasi' => 'Prioritas tinggi karena digunakan untuk praktikum.',
            ]
        );

        // 3. Laporan yang belum diverifikasi (tidak memiliki penugasan).
        Report::firstOrCreate(
            [
                'id_perangkat' => $accessPoint->id_perangkat,
                'deskripsi_kerusakan' => 'Koneksi Wi-Fi di Ruang Kuliah 101 sering terputus saat digunakan untuk kelas besar.',
            ],
            [
                'id_pelapor' => $pelapor->id_pengguna,
                'id_ruangan' => $accessPoint->id_ruangan,
                'id_status' => $status('Menunggu Verifikasi'),
                'id_urgensi' => $urgensi('Rendah'),
                'waktu_laporan' => now()->subHours(5),
            ]
        );

        $penugasanProyektor = Assignment::firstOrCreate(
            [
                'id_laporan' => $laporanProyektor->id_laporan,
                'id_teknisi' => $teknisi->id_pengguna,
            ],
            [
                'id_admin_penugas' => $admin->id_pengguna,
                'waktu_penugasan' => now()->subDays(2),
                'status_penugasan' => 'aktif',
            ]
        );

        $penugasanPc = Assignment::firstOrCreate(
            [
                'id_laporan' => $laporanPc->id_laporan,
                'id_teknisi' => $teknisi->id_pengguna,
            ],
            [
                'id_admin_penugas' => $admin->id_pengguna,
                'waktu_penugasan' => now()->subHours(18),
                'status_penugasan' => 'aktif',
            ]
        );

        RepairSchedule::firstOrCreate(
            ['id_penugasan' => $penugasanProyektor->id_penugasan],
            [
                'waktu_mulai' => now()->subDay(),
                'target_selesai' => now()->addDay(),
                'catatan_jadwal' => 'Pengerjaan di ruang kuliah setelah jam perkuliahan.',
            ]
        );

        RepairSchedule::firstOrCreate(
            ['id_penugasan' => $penugasanPc->id_penugasan],
            [
                'waktu_mulai' => now()->subHours(12),
                'target_selesai' => now()->addDays(2),
                'catatan_jadwal' => 'Instalasi ulang sistem operasi bila diperlukan.',
            ]
        );

        $progresProyektor = [
            [
                'persentase' => 20,
                'catatan_progres' => 'Pemeriksaan awal selesai. Kabel HDMI dan sumber daya sudah dicek, proyektor masih tidak menampilkan gambar.',
                'waktu_progres' => now()->subHours(20),
            ],
            [
                'persentase' => 50,
                'catatan_progres' => 'Lampu proyektor sudah diganti. Perangkat menyala, tetapi warna tampilan masih pudar.',
                'waktu_progres' => now()->subHours(8),
            ],
            [
                'persentase' => 80,
                'catatan_progres' => 'Kalibrasi warna dan pengujian ulang sedang dilakukan. Kendala: kipas proyektor masih berisik dan perlu dibersihkan.',
                'waktu_progres' => now()->subHours(2),
            ],
        ];

        foreach ($progresProyektor as $progres) {
            RepairProgress::firstOrCreate(
                [
                    'id_penugasan' => $penugasanProyektor->id_penugasan,
                    'persentase' => $progres['persentase'],
                    'catatan_progres' => $progres['catatan_progres'],
                ],
                ['waktu_progres' => $progres['waktu_progres']]
            );
        }

        RepairProgress::firstOrCreate(
            [
                'id_penugasan' => $penugasanPc->id_penugasan,
                'persentase' => 10,
                'catatan_progres' => 'Diagnosis awal: dugaan kerusakan pada RAM atau sistem operasi. Sedang dilakukan pengujian memori.',
            ],
            ['waktu_progres' => now()->subHours(3)]
        );
    }
}
