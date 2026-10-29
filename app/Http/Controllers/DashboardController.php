<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama sistem InfraCheck.
     */
    public function index()
    {
        $user = Auth::user();

        // Data metrik statistik (sesuai UI desain)
        $todayReportsCount = 4;
        $waitingVerificationCount = 12;
        $inRepairCount = 6;
        $doneThisMonthCount = 41;

        // Data laporan terbaru
        $recentReports = [
            [
                'nomor' => 'LP-2026-0148',
                'perangkat' => 'Access Point AP-B2-03',
                'lokasi' => 'Gedung B, Lt. 2',
                'status' => 'Diajukan',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
            ],
            [
                'nomor' => 'LP-2026-0146',
                'perangkat' => 'PC Lab LAB-C1-17',
                'lokasi' => 'Gedung C, Lab 1',
                'status' => 'Diverifikasi',
                'badge_class' => 'bg-blue-100 text-blue-700 border border-blue-200',
            ],
            [
                'nomor' => 'LP-2026-0144',
                'perangkat' => 'Proyektor PRJ-A301',
                'lokasi' => 'Gedung A, R. 301',
                'status' => 'Ditugaskan',
                'badge_class' => 'bg-purple-100 text-purple-700 border border-purple-200',
            ],
            [
                'nomor' => 'LP-2026-0142',
                'perangkat' => 'Proyektor PRJ-A204',
                'lokasi' => 'Gedung A, R. 204',
                'status' => 'Dalam Perbaikan',
                'badge_class' => 'bg-amber-100 text-amber-700 border border-amber-200',
            ],
            [
                'nomor' => 'LP-2026-0135',
                'perangkat' => 'Printer PRN-ADM-02',
                'lokasi' => 'Gedung Rektorat',
                'status' => 'Selesai',
                'badge_class' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            ],
        ];

        // Laporan per Gedung (Okt 2026)
        $buildingStats = [
            ['nama' => 'Gedung A', 'jumlah' => 32, 'width' => '85%'],
            ['nama' => 'Gedung B', 'jumlah' => 21, 'width' => '56%'],
            ['nama' => 'Gedung C (Lab)', 'jumlah' => 27, 'width' => '72%'],
            ['nama' => 'Gedung Rektorat', 'jumlah' => 9, 'width' => '24%'],
            ['nama' => 'Perpustakaan', 'jumlah' => 6, 'width' => '16%'],
        ];

        // Perangkat Sering Rusak
        $frequentDamagedDevices = [
            ['nama' => 'Proyektor PRJ-A204', 'jumlah' => '3×'],
            ['nama' => 'Access Point AP-B2-03', 'jumlah' => '3×'],
            ['nama' => 'PC Lab LAB-C1-17', 'jumlah' => '2×'],
        ];

        return view('dashboard', compact(
            'user',
            'todayReportsCount',
            'waitingVerificationCount',
            'inRepairCount',
            'doneThisMonthCount',
            'recentReports',
            'buildingStats',
            'frequentDamagedDevices'
        ));
    }
}
