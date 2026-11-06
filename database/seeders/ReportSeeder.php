<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Building;
use App\Models\Room;
use App\Models\InfrastructureCategory;
use App\Models\DeviceType;
use App\Models\Device;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\StatusHistory;
use Illuminate\Support\Carbon;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $statusMenunggu = ReportStatus::firstOrCreate(['nama_status' => 'Menunggu Verifikasi']);
        $urgensiSedang = UrgencyLevel::firstOrCreate(['nama_urgensi' => 'Sedang']);
        $urgensiTinggi = UrgencyLevel::firstOrCreate(['nama_urgensi' => 'Tinggi']);
        $rolePelapor = Role::firstOrCreate(['nama_peran' => 'Pelapor']);

        // User Dosen (Fajar Nugroho) sesuai mockup
        $userFajar = User::firstOrCreate(
            ['email' => 'fajar.nugroho@infracheck.ac.id'],
            [
                'id_peran' => $rolePelapor->id_peran,
                'nama' => 'Fajar Nugroho (Dosen)',
                'kata_sandi_hash' => bcrypt('password'),
                'nim_nip' => '19870312',
                'unit' => 'Teknik Komputer',
                'kontak' => '081234567899',
                'akun_aktif' => true,
            ]
        );

        $userRina = User::firstOrCreate(
            ['email' => 'rina.amelia@infracheck.ac.id'],
            [
                'id_peran' => $rolePelapor->id_peran,
                'nama' => 'Rina Amelia (Mahasiswa)',
                'kata_sandi_hash' => bcrypt('password'),
                'nim_nip' => '1202230045',
                'unit' => 'Sistem Informasi',
                'kontak' => '081234587890',
                'akun_aktif' => true,
            ]
        );

        // Pastikan gedung & ruangan sesuai mockup
        $gedungB = Building::firstOrCreate(
            ['kode_gedung' => 'GD-B'],
            ['nama_gedung' => 'Gedung B (Perkuliahan)']
        );
        $ruangB201 = Room::firstOrCreate(
            ['id_gedung' => $gedungB->id_gedung, 'kode_ruangan' => 'B.201'],
            ['nama_ruangan' => 'Gedung B, Lt. 2']
        );

        $gedungC = Building::firstOrCreate(
            ['kode_gedung' => 'GD-C'],
            ['nama_gedung' => 'Gedung C (Laboratorium)']
        );
        $ruangLabC = Room::firstOrCreate(
            ['id_gedung' => $gedungC->id_gedung, 'kode_ruangan' => 'LAB.C1'],
            ['nama_ruangan' => 'Gedung C, Lab 1']
        );

        $gedungRektorat = Building::firstOrCreate(
            ['kode_gedung' => 'GD-R'],
            ['nama_gedung' => 'Gedung Rektorat']
        );
        $ruangRektorat = Room::firstOrCreate(
            ['id_gedung' => $gedungRektorat->id_gedung, 'kode_ruangan' => 'RKT.01'],
            ['nama_ruangan' => 'Gedung Rektorat, Lt. 1']
        );

        $gedungA = Building::firstOrCreate(
            ['kode_gedung' => 'GD-A'],
            ['nama_gedung' => 'Gedung A (Perkuliahan)']
        );
        $ruangA301 = Room::firstOrCreate(
            ['id_gedung' => $gedungA->id_gedung, 'kode_ruangan' => 'A.301'],
            ['nama_ruangan' => 'Gedung A, R. 301']
        );

        // Kategori & Device
        $catJaringan = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Jaringan & Internet']);
        $catKomputer = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Komputer & Periferal']);
        $catMultimedia = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Audio Visual & Multimedia']);

        $typeAP = DeviceType::firstOrCreate(['id_kategori' => $catJaringan->id_kategori, 'nama_jenis' => 'Access Point Wi-Fi']);
        $typePC = DeviceType::firstOrCreate(['id_kategori' => $catKomputer->id_kategori, 'nama_jenis' => 'PC Desktop']);
        $typePrinter = DeviceType::firstOrCreate(['id_kategori' => $catKomputer->id_kategori, 'nama_jenis' => 'Printer']);
        $typeSpeaker = DeviceType::firstOrCreate(['id_kategori' => $catMultimedia->id_kategori, 'nama_jenis' => 'Speaker Ruangan']);

        $deviceAP = Device::firstOrCreate(
            ['kode_aset' => 'AP-B2-03'],
            [
                'id_jenis' => $typeAP->id_jenis,
                'id_ruangan' => $ruangB201->id_ruangan,
                'nama_perangkat' => 'Access Point AP-B2-03',
                'kondisi' => 'Rusak Ringan',
            ]
        );

        $devicePC = Device::firstOrCreate(
            ['kode_aset' => 'LAB-C1-09'],
            [
                'id_jenis' => $typePC->id_jenis,
                'id_ruangan' => $ruangLabC->id_ruangan,
                'nama_perangkat' => 'PC Lab LAB-C1-09',
                'kondisi' => 'Rusak Berat',
            ]
        );

        $devicePrinter = Device::firstOrCreate(
            ['kode_aset' => 'PRN-ADM-01'],
            [
                'id_jenis' => $typePrinter->id_jenis,
                'id_ruangan' => $ruangRektorat->id_ruangan,
                'nama_perangkat' => 'Printer PRN-ADM-01',
                'kondisi' => 'Rusak Ringan',
            ]
        );

        $deviceSpeaker = Device::firstOrCreate(
            ['kode_aset' => 'SPK-A301'],
            [
                'id_jenis' => $typeSpeaker->id_jenis,
                'id_ruangan' => $ruangA301->id_ruangan,
                'nama_perangkat' => 'Speaker SPK-A301',
                'kondisi' => 'Rusak Ringan',
            ]
        );

        // 1. Laporan LP-2026-0148 (Access Point)
        $report1 = Report::create([
            'id_pelapor' => $userFajar->id_pengguna,
            'id_perangkat' => $deviceAP->id_perangkat,
            'id_ruangan' => $ruangB201->id_ruangan,
            'id_status' => $statusMenunggu->id_status,
            'id_urgensi' => $urgensiTinggi->id_urgensi,
            'deskripsi_kerusakan' => 'Wi-Fi di lantai 2 Gedung B tidak bisa terhubung sejak pagi, lampu access point mati.',
            'waktu_laporan' => Carbon::create(2026, 10, 8, 10, 21),
        ]);

        ReportPhoto::create([
            'id_laporan' => $report1->id_laporan,
            'lokasi_berkas' => 'photos/reports/sample_ap_1.jpg',
            'waktu_unggah' => Carbon::create(2026, 10, 8, 10, 21),
        ]);
        ReportPhoto::create([
            'id_laporan' => $report1->id_laporan,
            'lokasi_berkas' => 'photos/reports/sample_ap_2.jpg',
            'waktu_unggah' => Carbon::create(2026, 10, 8, 10, 21),
        ]);

        StatusHistory::create([
            'id_laporan' => $report1->id_laporan,
            'id_status' => $statusMenunggu->id_status,
            'id_pengubah' => $userFajar->id_pengguna,
            'waktu_perubahan' => Carbon::create(2026, 10, 8, 10, 21),
            'catatan_perubahan' => 'Laporan kerusakan diajukan oleh pelapor.',
        ]);

        // 2. Laporan LP-2026-0147 (PC Lab)
        $report2 = Report::create([
            'id_pelapor' => $userRina->id_pengguna,
            'id_perangkat' => $devicePC->id_perangkat,
            'id_ruangan' => $ruangLabC->id_ruangan,
            'id_status' => $statusMenunggu->id_status,
            'id_urgensi' => $urgensiSedang->id_urgensi,
            'deskripsi_kerusakan' => 'PC tidak mau menyala saat tombol power ditekan, tercium bau gosong dari arah power supply.',
            'waktu_laporan' => Carbon::create(2026, 10, 8, 9, 5),
        ]);

        StatusHistory::create([
            'id_laporan' => $report2->id_laporan,
            'id_status' => $statusMenunggu->id_status,
            'id_pengubah' => $userRina->id_pengguna,
            'waktu_perubahan' => Carbon::create(2026, 10, 8, 9, 5),
            'catatan_perubahan' => 'Laporan kerusakan diajukan oleh pelapor.',
        ]);

        // 3. Laporan LP-2026-0145 (Printer)
        $report3 = Report::create([
            'id_pelapor' => $userFajar->id_pengguna,
            'id_perangkat' => $devicePrinter->id_perangkat,
            'id_ruangan' => $ruangRektorat->id_ruangan,
            'id_status' => $statusMenunggu->id_status,
            'id_urgensi' => $urgensiSedang->id_urgensi,
            'deskripsi_kerusakan' => 'Kertas sering tersangkut (paper jam) dan tinta warna hitam bergaris-garis.',
            'waktu_laporan' => Carbon::create(2026, 10, 7, 14, 30),
        ]);

        StatusHistory::create([
            'id_laporan' => $report3->id_laporan,
            'id_status' => $statusMenunggu->id_status,
            'id_pengubah' => $userFajar->id_pengguna,
            'waktu_perubahan' => Carbon::create(2026, 10, 7, 14, 30),
            'catatan_perubahan' => 'Laporan kerusakan diajukan oleh pelapor.',
        ]);

        // 4. Laporan LP-2026-0143 (Speaker)
        $report4 = Report::create([
            'id_pelapor' => $userRina->id_pengguna,
            'id_perangkat' => $deviceSpeaker->id_perangkat,
            'id_ruangan' => $ruangA301->id_ruangan,
            'id_status' => $statusMenunggu->id_status,
            'id_urgensi' => $urgensiSedang->id_urgensi,
            'deskripsi_kerusakan' => 'Speaker mengeluarkan suara mendengung kencang ketika mikrofon dinyalakan.',
            'waktu_laporan' => Carbon::create(2026, 10, 7, 8, 15),
        ]);

        StatusHistory::create([
            'id_laporan' => $report4->id_laporan,
            'id_status' => $statusMenunggu->id_status,
            'id_pengubah' => $userRina->id_pengguna,
            'waktu_perubahan' => Carbon::create(2026, 10, 7, 8, 15),
            'catatan_perubahan' => 'Laporan kerusakan diajukan oleh pelapor.',
        ]);
    }
}
