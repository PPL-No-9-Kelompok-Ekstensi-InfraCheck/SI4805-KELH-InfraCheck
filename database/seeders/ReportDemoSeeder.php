<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Building;
use App\Models\Comment;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use App\Models\Priority;
use App\Models\RepairProgress;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\Room;
use App\Models\Role;
use App\Models\StatusHistory;
use App\Models\UrgencyLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ReportDemoSeeder extends Seeder
{
    /**
     * Membuat data demo untuk halaman pemantauan status laporan.
     */
    public function run(): void
    {
        if (Report::query()->exists()) {
            $this->command?->warn('ReportDemoSeeder dilewati: tabel reports sudah berisi data.');

            return;
        }

        $roles = $this->ensureRoles();
        $statuses = $this->ensureStatuses();
        $urgencies = $this->ensureUrgencies();
        $priorities = $this->ensurePriorities();
        $types = $this->ensureDeviceTypes();
        $rooms = $this->ensureBuildingsAndRooms();

        $admin = $this->ensureAdmin($roles['Admin']);
        $reporters = $this->ensureReporters($roles['Pelapor']);
        $technicians = $this->ensureTechnicians($roles['Teknisi']);

        $devices = $this->ensureDevices($types, $rooms);

        $distribution = [
            'Menunggu Verifikasi' => 12,
            'Diverifikasi' => 5,
            'Ditugaskan' => 8,
            'Sedang Dikerjakan' => 6,
            'Menunggu Validasi' => 4,
            'Selesai' => 37,
            'Ditolak' => 3,
        ];

        $statusPlan = collect($distribution)
            ->flatMap(fn (int $total, string $status) => array_fill(0, $total, $status))
            ->shuffle()
            ->values();

        $descriptions = [
            'Access Point tidak dapat diakses dan lampu indikator mati.',
            'Koneksi jaringan pada ruangan terputus sejak pagi.',
            'Proyektor tidak menampilkan gambar saat perkuliahan.',
            'Printer tidak dapat mencetak dan muncul pesan error.',
            'Komputer laboratorium tidak mau menyala.',
            'Layar Smart TV berkedip dan suara tidak keluar.',
            'Switch jaringan mengeluarkan bunyi tidak normal.',
            'Perangkat sering terputus dari jaringan Wi-Fi.',
            'Kabel jaringan pada meja praktikum rusak.',
            'Perangkat tidak merespons saat dihubungkan ke listrik.',
        ];

        $verificationNotes = [
            'Kerusakan terkonfirmasi dan perlu penanganan teknisi.',
            'Laporan valid, perangkat akan diperiksa lebih lanjut.',
            'Kerusakan sesuai dengan keluhan pelapor.',
        ];

        $rejectionNotes = [
            'Laporan ditolak karena perangkat tidak ditemukan di lokasi.',
            'Laporan duplikat dengan laporan lain yang masih diproses.',
            'Kerusakan bukan termasuk cakupan infrastruktur TI.',
        ];

        $baseTime = Carbon::create(2026, 10, 1, 8, 0, 0, 'Asia/Jakarta');

        foreach ($statusPlan as $index => $statusName) {
            $reportedAt = $baseTime->copy()
                ->addMinutes($index * 240 + random_int(0, 180))
                ->utc();

            $device = $devices[$index % count($devices)];
            $reporter = $reporters[$index % count($reporters)];
            $urgencyName = ['Rendah', 'Sedang', 'Tinggi', 'Kritis'][$index % 4];

            $isVerified = $statusName !== 'Menunggu Verifikasi';
            $verifiedAt = $isVerified ? $reportedAt->copy()->addHours(2) : null;
            $priority = $isVerified && $statusName !== 'Ditolak'
                ? $priorities[['Rendah', 'Sedang', 'Tinggi', 'Mendesak'][$index % 4]]
                : null;

            $report = Report::create([
                'id_pelapor' => $reporter->id_pengguna,
                'id_perangkat' => $device->id_perangkat,
                'id_ruangan' => $device->id_ruangan,
                'id_status' => $statuses[$statusName]->id_status,
                'id_urgensi' => $urgencies[$urgencyName]->id_urgensi,
                'id_prioritas' => $priority?->id_prioritas,
                'id_admin_verifikator' => $isVerified ? $admin->id_pengguna : null,
                'deskripsi_kerusakan' => $descriptions[$index % count($descriptions)],
                'waktu_laporan' => $reportedAt,
                'waktu_verifikasi' => $verifiedAt,
                'catatan_verifikasi' => $isVerified
                    ? ($statusName === 'Ditolak'
                        ? $rejectionNotes[$index % count($rejectionNotes)]
                        : $verificationNotes[$index % count($verificationNotes)])
                    : null,
            ]);

            $this->recordHistory(
                $report,
                $statuses['Menunggu Verifikasi'],
                $reporter,
                $reportedAt,
                'Laporan dibuat oleh pelapor.'
            );

            if ($isVerified) {
                $this->recordHistory(
                    $report,
                    $statuses[$statusName === 'Ditolak' ? 'Ditolak' : 'Diverifikasi'],
                    $admin,
                    $verifiedAt,
                    $statusName === 'Ditolak'
                        ? $rejectionNotes[$index % count($rejectionNotes)]
                        : $verificationNotes[$index % count($verificationNotes)]
                );
            }

            $technician = $technicians[$index % count($technicians)];
            $assignment = null;

            if (in_array($statusName, ['Ditugaskan', 'Sedang Dikerjakan', 'Menunggu Validasi', 'Selesai'], true)) {
                $assignedAt = $verifiedAt->copy()->addHour();

                $this->recordHistory(
                    $report,
                    $statuses['Ditugaskan'],
                    $admin,
                    $assignedAt,
                    'Laporan ditugaskan kepada '.$technician->nama.'.'
                );

                $assignment = Assignment::create([
                    'id_laporan' => $report->id_laporan,
                    'id_teknisi' => $technician->id_pengguna,
                    'id_admin_penugas' => $admin->id_pengguna,
                    'waktu_penugasan' => $assignedAt,
                    'status_penugasan' => $statusName === 'Selesai' ? 'selesai' : 'aktif',
                ]);
            }

            if (in_array($statusName, ['Sedang Dikerjakan', 'Menunggu Validasi', 'Selesai'], true)) {
                $startedAt = $verifiedAt->copy()->addHours(3);

                $this->recordHistory(
                    $report,
                    $statuses['Sedang Dikerjakan'],
                    $technician,
                    $startedAt,
                    'Teknisi mulai mengerjakan perbaikan.'
                );

                $percentage = match ($statusName) {
                    'Sedang Dikerjakan' => 65,
                    'Menunggu Validasi', 'Selesai' => 100,
                    default => 0,
                };

                if ($assignment !== null) {
                    RepairProgress::create([
                        'id_penugasan' => $assignment->id_penugasan,
                        'persentase' => $percentage,
                        'catatan_progres' => $percentage === 100
                            ? 'Perbaikan selesai dan perangkat sudah diuji.'
                            : 'Perbaikan sedang berlangsung, sebagian komponen sudah diganti.',
                        'waktu_progres' => $startedAt->copy()->addHours(2),
                    ]);
                }
            }

            if (in_array($statusName, ['Menunggu Validasi', 'Selesai'], true)) {
                $waitingAt = $verifiedAt->copy()->addHours(8);

                $this->recordHistory(
                    $report,
                    $statuses['Menunggu Validasi'],
                    $technician,
                    $waitingAt,
                    'Perbaikan selesai, menunggu validasi admin.'
                );
            }

            if ($statusName === 'Selesai') {
                $finishedAt = $verifiedAt->copy()->addHours(10);

                $this->recordHistory(
                    $report,
                    $statuses['Selesai'],
                    $admin,
                    $finishedAt,
                    'Hasil perbaikan divalidasi dan laporan dinyatakan selesai.'
                );
            }

            if ($index % 7 === 0) {
                Comment::create([
                    'id_laporan' => $report->id_laporan,
                    'id_pengguna' => $reporter->id_pengguna,
                    'isi_tanggapan' => $statusName === 'Ditolak'
                        ? 'Baik, terima kasih atas penjelasannya.'
                        : 'Terima kasih, mohon segera ditindaklanjuti.',
                    'waktu_tanggapan' => $reportedAt->copy()->addHours(1),
                ]);
            }
        }
    }

    /**
     * @return array<string, Role>
     */
    private function ensureRoles(): array
    {
        foreach (['Pelapor', 'Admin', 'Teknisi', 'Pimpinan'] as $role) {
            Role::firstOrCreate(['nama_peran' => $role]);
        }

        return Role::query()->get()->keyBy('nama_peran')->all();
    }

    /**
     * @return array<string, ReportStatus>
     */
    private function ensureStatuses(): array
    {
        $names = [
            'Menunggu Verifikasi',
            'Diverifikasi',
            'Ditolak',
            'Ditugaskan',
            'Sedang Dikerjakan',
            'Menunggu Validasi',
            'Selesai',
            'Dibatalkan',
        ];

        foreach ($names as $name) {
            ReportStatus::firstOrCreate(['nama_status' => $name]);
        }

        return ReportStatus::query()->get()->keyBy('nama_status')->all();
    }

    /**
     * @return array<string, UrgencyLevel>
     */
    private function ensureUrgencies(): array
    {
        foreach (['Rendah', 'Sedang', 'Tinggi', 'Kritis'] as $name) {
            UrgencyLevel::firstOrCreate(['nama_urgensi' => $name]);
        }

        return UrgencyLevel::query()->get()->keyBy('nama_urgensi')->all();
    }

    /**
     * @return array<string, Priority>
     */
    private function ensurePriorities(): array
    {
        foreach (['Rendah', 'Sedang', 'Tinggi', 'Mendesak'] as $name) {
            Priority::firstOrCreate(['nama_prioritas' => $name]);
        }

        return Priority::query()->get()->keyBy('nama_prioritas')->all();
    }

    /**
     * @return array<string, DeviceType>
     */
    private function ensureDeviceTypes(): array
    {
        $categories = [];
        foreach (['Jaringan & Internet', 'Komputer & Periferal', 'Audio Visual & Multimedia'] as $name) {
            $categories[$name] = InfrastructureCategory::firstOrCreate(['nama_kategori' => $name]);
        }

        $definitions = [
            'Access Point Wi-Fi' => 'Jaringan & Internet',
            'Switch Jaringan' => 'Jaringan & Internet',
            'PC Desktop' => 'Komputer & Periferal',
            'Printer' => 'Komputer & Periferal',
            'Proyektor LCD' => 'Audio Visual & Multimedia',
            'Smart TV' => 'Audio Visual & Multimedia',
        ];

        foreach ($definitions as $type => $category) {
            DeviceType::firstOrCreate([
                'id_kategori' => $categories[$category]->id_kategori,
                'nama_jenis' => $type,
            ]);
        }

        return DeviceType::query()->get()->keyBy('nama_jenis')->all();
    }

    /**
     * @return array<string, Room>
     */
    private function ensureBuildingsAndRooms(): array
    {
        $definitions = [
            'Gedung A' => [
                'kode' => 'GDA',
                'rooms' => [
                    'R.301' => 'Ruang 301',
                    'R.204' => 'Ruang 204',
                ],
            ],
            'Gedung B' => [
                'kode' => 'GDB',
                'rooms' => [
                    'R.201' => 'Ruang 201',
                    'R.202' => 'Ruang 202',
                ],
            ],
            'Gedung C' => [
                'kode' => 'GDC',
                'rooms' => [
                    'LAB.1' => 'Lab 1',
                    'LAB.2' => 'Lab 2',
                ],
            ],
            'Gedung Rektorat' => [
                'kode' => 'REK',
                'rooms' => [
                    'ADM' => 'Ruang Administrasi',
                    'PIM' => 'Ruang Pimpinan',
                ],
            ],
        ];

        $rooms = [];

        foreach ($definitions as $buildingName => $buildingData) {
            $building = Building::firstOrCreate(
                ['kode_gedung' => $buildingData['kode']],
                ['nama_gedung' => $buildingName]
            );

            foreach ($buildingData['rooms'] as $code => $name) {
                $rooms[$buildingName.'|'.$code] = Room::firstOrCreate(
                    ['id_gedung' => $building->id_gedung, 'kode_ruangan' => $code],
                    ['nama_ruangan' => $name]
                );
            }
        }

        return $rooms;
    }

    private function ensureAdmin(Role $role): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@infracheck.ac.id'],
            [
                'id_peran' => $role->id_peran,
                'nama' => 'Budi Hartono',
                'kata_sandi_hash' => Hash::make('password'),
                'nim_nip' => 'ADM001',
                'unit' => 'Biro Sistem Informasi',
                'kontak' => '081234567890',
                'akun_aktif' => true,
            ]
        );
    }

    /**
     * @return list<User>
     */
    private function ensureReporters(Role $role): array
    {
        $definitions = [
            ['email' => 'pelapor@infracheck.ac.id', 'nama' => 'Andi Saputra', 'nip' => '1202220001'],
            ['email' => 'rina@infracheck.ac.id', 'nama' => 'Rina Wulandari', 'nip' => '1202220014'],
            ['email' => 'fajar@infracheck.ac.id', 'nama' => 'Fajar Nugroho', 'nip' => '1202220027'],
        ];

        $reporters = [];

        foreach ($definitions as $definition) {
            $reporters[] = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'id_peran' => $role->id_peran,
                    'nama' => $definition['nama'],
                    'kata_sandi_hash' => Hash::make('password'),
                    'nim_nip' => $definition['nip'],
                    'unit' => 'Sistem Informasi',
                    'kontak' => '08130000000'.count($reporters),
                    'akun_aktif' => true,
                ]
            );
        }

        return $reporters;
    }

    /**
     * @return list<User>
     */
    private function ensureTechnicians(Role $role): array
    {
        $definitions = [
            ['email' => 'teknisi@infracheck.ac.id', 'nama' => 'Budi Santoso', 'nip' => 'TEK001'],
            ['email' => 'agus@infracheck.ac.id', 'nama' => 'Agus Santoso', 'nip' => 'TEK002'],
            ['email' => 'dewi@infracheck.ac.id', 'nama' => 'Dewi Pertiwi', 'nip' => 'TEK003'],
        ];

        $technicians = [];

        foreach ($definitions as $definition) {
            $technicians[] = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'id_peran' => $role->id_peran,
                    'nama' => $definition['nama'],
                    'kata_sandi_hash' => Hash::make('password'),
                    'nim_nip' => $definition['nip'],
                    'unit' => 'Divisi Infrastruktur & Jaringan',
                    'kontak' => '08129876543'.count($technicians),
                    'akun_aktif' => true,
                ]
            );
        }

        return $technicians;
    }

    /**
     * @param  array<string, DeviceType>  $types
     * @param  array<string, Room>  $rooms
     * @return list<Device>
     */
    private function ensureDevices(array $types, array $rooms): array
    {
        $definitions = [
            ['kode_aset' => 'DEMO-AP-B2-03', 'nama' => 'Access Point AP-B2-03', 'type' => 'Access Point Wi-Fi', 'room' => 'Gedung B|R.201'],
            ['kode_aset' => 'DEMO-SW-B2-01', 'nama' => 'Switch SW-B2-01', 'type' => 'Switch Jaringan', 'room' => 'Gedung B|R.202'],
            ['kode_aset' => 'DEMO-PRN-ADM-02', 'nama' => 'Printer PRN-ADM-02', 'type' => 'Printer', 'room' => 'Gedung Rektorat|ADM'],
            ['kode_aset' => 'DEMO-TV-A301', 'nama' => 'Smart TV TV-A301', 'type' => 'Smart TV', 'room' => 'Gedung A|R.301'],
            ['kode_aset' => 'DEMO-PRJ-A204', 'nama' => 'Proyektor PRJ-A204', 'type' => 'Proyektor LCD', 'room' => 'Gedung A|R.204'],
            ['kode_aset' => 'DEMO-PC-C1-17', 'nama' => 'PC Lab LAB-C1-17', 'type' => 'PC Desktop', 'room' => 'Gedung C|LAB.1'],
            ['kode_aset' => 'DEMO-PC-C2-08', 'nama' => 'PC Lab LAB-C2-08', 'type' => 'PC Desktop', 'room' => 'Gedung C|LAB.2'],
            ['kode_aset' => 'DEMO-AP-REK-01', 'nama' => 'Access Point AP-REK-01', 'type' => 'Access Point Wi-Fi', 'room' => 'Gedung Rektorat|PIM'],
        ];

        $devices = [];

        foreach ($definitions as $definition) {
            $devices[] = Device::firstOrCreate(
                ['kode_aset' => $definition['kode_aset']],
                [
                    'id_jenis' => $types[$definition['type']]->id_jenis,
                    'id_ruangan' => $rooms[$definition['room']]->id_ruangan,
                    'nama_perangkat' => $definition['nama'],
                    'kondisi' => 'Rusak',
                ]
            );
        }

        return $devices;
    }

    private function recordHistory(
        Report $report,
        ReportStatus $status,
        User $changer,
        Carbon $at,
        ?string $note = null
    ): void {
        StatusHistory::create([
            'id_laporan' => $report->id_laporan,
            'id_status' => $status->id_status,
            'id_pengubah' => $changer->id_pengguna,
            'waktu_perubahan' => $at,
            'catatan_perubahan' => $note,
        ]);
    }
}
