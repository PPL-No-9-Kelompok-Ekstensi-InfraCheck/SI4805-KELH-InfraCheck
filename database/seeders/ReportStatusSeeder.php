<?php

namespace Database\Seeders;

use App\Models\ReportStatus;
use Illuminate\Database\Seeder;

class ReportStatusSeeder extends Seeder
{
    /**
     * Seed status laporan (Tabel 8).
     */
    public function run(): void
    {
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
    }
}
