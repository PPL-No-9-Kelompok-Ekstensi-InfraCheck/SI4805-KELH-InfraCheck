<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Building;
use App\Models\Room;
use App\Models\InfrastructureCategory;
use App\Models\DeviceType;
use App\Models\Device;
use App\Models\ReportStatus;
use App\Models\UrgencyLevel;
use App\Models\Priority;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial master data.
     */
    public function run(): void
    {
        // 1. Roles (Tabel 1)
        $rolePelapor = Role::firstOrCreate(['nama_peran' => 'Pelapor']);
        $roleAdmin = Role::firstOrCreate(['nama_peran' => 'Admin']);
        $roleTeknisi = Role::firstOrCreate(['nama_peran' => 'Teknisi']);
        $rolePimpinan = Role::firstOrCreate(['nama_peran' => 'Pimpinan']);

        // 2. Report Statuses (Tabel 8)
        $statuses = [
            'Menunggu Verifikasi',
            'Diverifikasi',
            'Ditolak',
            'Ditugaskan',
            'Sedang Dikerjakan',
            'Menunggu Validasi',
            'Selesai',
            'Dibatalkan',
        ];
        foreach ($statuses as $status) {
            ReportStatus::firstOrCreate(['nama_status' => $status]);
        }

        // 3. Urgency Levels (Tabel 9)
        $urgencies = ['Rendah', 'Sedang', 'Tinggi', 'Kritis'];
        foreach ($urgencies as $urgency) {
            UrgencyLevel::firstOrCreate(['nama_urgensi' => $urgency]);
        }

        // 4. Priorities (Tabel 10)
        $priorities = ['Rendah', 'Sedang', 'Tinggi', 'Mendesak'];
        foreach ($priorities as $priority) {
            Priority::firstOrCreate(['nama_prioritas' => $priority]);
        }

        // 5. Buildings & Rooms (Tabel 3 & 4)
        $gedungUtama = Building::firstOrCreate(
            ['kode_gedung' => 'GKB-1'],
            ['nama_gedung' => 'Gedung Kuliah Bersama 1']
        );
        $gedungLab = Building::firstOrCreate(
            ['kode_gedung' => 'LAB-TI'],
            ['nama_gedung' => 'Gedung Laboratorium Teknologi Informasi']
        );

        $ruang101 = Room::firstOrCreate(
            ['id_gedung' => $gedungUtama->id_gedung, 'kode_ruangan' => 'R.101'],
            ['nama_ruangan' => 'Ruang Kuliah 101']
        );
        $ruangLab1 = Room::firstOrCreate(
            ['id_gedung' => $gedungLab->id_gedung, 'kode_ruangan' => 'LAB.01'],
            ['nama_ruangan' => 'Laboratorium Komputer Jaringan']
        );

        // 6. Categories & Device Types & Devices (Tabel 5, 6, 7)
        $catJaringan = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Jaringan & Internet']);
        $catKomputer = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Komputer & Periferal']);
        $catAudioVisual = InfrastructureCategory::firstOrCreate(['nama_kategori' => 'Audio Visual & Multimedia']);

        $typeAccessPoint = DeviceType::firstOrCreate(
            ['id_kategori' => $catJaringan->id_kategori, 'nama_jenis' => 'Access Point Wi-Fi']
        );
        $typePC = DeviceType::firstOrCreate(
            ['id_kategori' => $catKomputer->id_kategori, 'nama_jenis' => 'PC Desktop']
        );
        $typeProyektor = DeviceType::firstOrCreate(
            ['id_kategori' => $catAudioVisual->id_kategori, 'nama_jenis' => 'Proyektor LCD']
        );

        Device::firstOrCreate(
            ['kode_aset' => 'AST-AP-001'],
            [
                'id_jenis' => $typeAccessPoint->id_jenis,
                'id_ruangan' => $ruang101->id_ruangan,
                'nama_perangkat' => 'Access Point Cisco AP-01',
                'kondisi' => 'Baik',
            ]
        );
        Device::firstOrCreate(
            ['kode_aset' => 'AST-PRJ-001'],
            [
                'id_jenis' => $typeProyektor->id_jenis,
                'id_ruangan' => $ruang101->id_ruangan,
                'nama_perangkat' => 'Proyektor Epson EB-X400',
                'kondisi' => 'Baik',
            ]
        );
        Device::firstOrCreate(
            ['kode_aset' => 'AST-PC-LAB-01'],
            [
                'id_jenis' => $typePC->id_jenis,
                'id_ruangan' => $ruangLab1->id_ruangan,
                'nama_perangkat' => 'PC Workstation Lab 01',
                'kondisi' => 'Baik',
            ]
        );

        // 7. Akun Pengguna Default (Tabel 2)
        User::firstOrCreate(
            ['email' => 'admin@infracheck.ac.id'],
            [
                'id_peran' => $roleAdmin->id_peran,
                'nama' => 'Administrator Sistem',
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => 'ADM001',
                'unit' => 'Biro Sistem Informasi',
                'kontak' => '081234567890',
                'akun_aktif' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'teknisi@infracheck.ac.id'],
            [
                'id_peran' => $roleTeknisi->id_peran,
                'nama' => 'Budi Santoso (Teknisi)',
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => 'TEK001',
                'unit' => 'Divisi Infrastruktur & Jaringan',
                'kontak' => '081298765432',
                'akun_aktif' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pelapor@infracheck.ac.id'],
            [
                'id_peran' => $rolePelapor->id_peran,
                'nama' => 'Andi Saputra (Mahasiswa)',
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => '1202220001',
                'unit' => 'Sistem Informasi',
                'kontak' => '081345678901',
                'akun_aktif' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pimpinan@infracheck.ac.id'],
            [
                'id_peran' => $rolePimpinan->id_peran,
                'nama' => 'Prof. Dr. Ir. Pimpinan',
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => 'PMP001',
                'unit' => 'Direktorat Sarana Prasarana',
                'kontak' => '081122334455',
                'akun_aktif' => true,
            ]
        );

        // 8. Data demo laporan untuk lingkungan lokal.
        if (app()->environment('local')) {
            $this->call(ReportDemoSeeder::class);
        }
    }
}
