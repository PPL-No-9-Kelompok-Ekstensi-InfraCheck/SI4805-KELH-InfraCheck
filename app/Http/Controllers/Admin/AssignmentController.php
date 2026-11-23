<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\User;
use App\Models\Assignment;
use App\Models\StatusHistory;

class AssignmentController extends Controller
{
    /**
     * Menampilkan antarmuka penugasan laporan kepada teknisi (PBI 15).
     */
    public function create(Request $request)
    {
        // Ambil daftar laporan yang berstatus Diverifikasi atau Ditugaskan (untuk kemungkinan pengalihan tugas)
        $verifiedReports = Report::with(['device.deviceType.category', 'room.building', 'priority', 'status', 'reporter.role', 'activeAssignment.technician'])
            ->whereHas('status', function ($q) {
                $q->whereIn('nama_status', ['Diverifikasi', 'Ditugaskan']);
            })
            ->orderBy('waktu_verifikasi', 'desc')
            ->get();

        // Tentukan laporan yang sedang dipilih
        $reportId = $request->query('report_id');
        $selectedReport = null;

        if ($reportId) {
            $selectedReport = $verifiedReports->firstWhere('id_laporan', $reportId)
                ?? Report::with(['device.deviceType.category', 'room.building', 'priority', 'status', 'reporter.role', 'activeAssignment.technician'])->find($reportId);
        }

        if (!$selectedReport && $verifiedReports->isNotEmpty()) {
            $selectedReport = $verifiedReports->first();
        }

        // Ambil seluruh teknisi aktif beserta jumlah tugas aktif
        $technicians = User::whereHas('role', function ($q) {
            $q->where('nama_peran', 'Teknisi');
        })
        ->where('akun_aktif', true)
        ->withCount(['activeAssignments as active_tasks_count'])
        ->get();

        // Urutkan teknisi: Prioritaskan yang keahliannya sesuai kategori perangkat, lalu beban tugas terendah
        if ($selectedReport && $selectedReport->device && $selectedReport->device->deviceType && $selectedReport->device->deviceType->category) {
            $categoryName = strtolower($selectedReport->device->deviceType->category->nama_kategori);

            $technicians = $technicians->sortBy(function ($tech) use ($categoryName) {
                $unit = strtolower($tech->unit ?? '');
                $match = 1; // Default: tidak cocok spesifik (skor lebih besar)

                if (str_contains($categoryName, 'jaringan') && str_contains($unit, 'jaringan')) {
                    $match = 0;
                } elseif (str_contains($categoryName, 'komputer') && str_contains($unit, 'komputer')) {
                    $match = 0;
                } elseif (str_contains($categoryName, 'audio') || str_contains($categoryName, 'multimedia')) {
                    if (str_contains($unit, 'audio') || str_contains($unit, 'presentasi')) {
                        $match = 0;
                    }
                }

                return ($match * 1000) + $tech->active_tasks_count;
            })->values();
        }

        return view('admin.assignment.create', compact('selectedReport', 'technicians', 'verifiedReports'));
    }

    /**
     * Memproses penugasan laporan kepada teknisi terpilih.
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'id_teknisi' => 'required|exists:users,id_pengguna',
            'catatan_penugasan' => 'nullable|string|max:1000',
        ], [
            'id_teknisi.required' => 'Silakan pilih teknisi yang akan ditugaskan.',
            'id_teknisi.exists' => 'Teknisi yang dipilih tidak valid atau tidak terdaftar.',
        ]);

        $report = Report::findOrFail($id);

        // Pastikan teknisi yang dipilih ber-peran Teknisi dan berstatus aktif
        $technician = User::where('id_pengguna', $request->id_teknisi)
            ->whereHas('role', function ($q) {
                $q->where('nama_peran', 'Teknisi');
            })
            ->where('akun_aktif', true)
            ->firstOrFail();

        DB::transaction(function () use ($report, $technician, $request) {
            $assignedStatus = ReportStatus::firstOrCreate(['nama_status' => 'Ditugaskan']);

            // Aturan Bisnis: Hanya 1 penugasan aktif per laporan.
            // Jika ada penugasan aktif sebelumnya, tandai sebagai 'dialihkan'
            Assignment::where('id_laporan', $report->id_laporan)
                ->where('status_penugasan', 'aktif')
                ->update(['status_penugasan' => 'dialihkan']);

            // Buat penugasan baru berstatus aktif
            Assignment::create([
                'id_laporan' => $report->id_laporan,
                'id_teknisi' => $technician->id_pengguna,
                'id_admin_penugas' => Auth::id(),
                'waktu_penugasan' => now(),
                'status_penugasan' => 'aktif',
            ]);

            // Perbarui status laporan menjadi 'Ditugaskan'
            $report->update([
                'id_status' => $assignedStatus->id_status,
            ]);

            // Buat catatan audit trail di status_histories
            $historyNote = "Ditugaskan ke {$technician->nama}";
            if ($request->filled('catatan_penugasan')) {
                $historyNote .= ", jadwal perbaikan: " . $request->input('catatan_penugasan');
            }

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $assignedStatus->id_status,
                'id_pengubah' => Auth::id(),
                'waktu_perubahan' => now(),
                'catatan_perubahan' => $historyNote,
            ]);
        });

        return redirect()->route('admin.assignment.create', ['report_id' => $report->id_laporan])
            ->with('success', "Laporan {$report->nomor_laporan} berhasil ditugaskan kepada {$technician->nama}.");
    }
}
