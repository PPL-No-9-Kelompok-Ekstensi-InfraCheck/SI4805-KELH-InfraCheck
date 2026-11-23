<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Report;
use App\Models\ReportStatus;
use App\Models\StatusHistory;

class VerificationController extends Controller
{
    /**
     * Menampilkan antarmuka verifikasi laporan kerusakan (PBI 13).
     */
    public function index(Request $request)
    {
        // Ambil seluruh laporan dengan status Menunggu Verifikasi / Diajukan
        $pendingReports = Report::with(['reporter.role', 'device.deviceType', 'room.building', 'photos', 'status'])
            ->whereHas('status', function ($q) {
                $q->whereIn('nama_status', ['Menunggu Verifikasi', 'Diajukan']);
            })
            ->orderBy('waktu_laporan', 'desc')
            ->get();

        $pendingCount = $pendingReports->count();

        // Tentukan laporan yang aktif dipilih di panel sebelah kanan
        $selectedId = $request->query('selected');
        $selectedReport = null;

        if ($selectedId) {
            $selectedReport = $pendingReports->firstWhere('id_laporan', $selectedId);
            if (!$selectedReport) {
                $selectedReport = Report::with(['reporter.role', 'device.deviceType', 'room.building', 'photos', 'status'])->find($selectedId);
            }
        }

        if (!$selectedReport && $pendingReports->isNotEmpty()) {
            $selectedReport = $pendingReports->first();
        }

        return view('admin.verification.index', compact('pendingReports', 'pendingCount', 'selectedReport'));
    }

    /**
     * Memverifikasi laporan kerusakan dan mengubah status menjadi 'Diverifikasi'.
     */
    public function verify(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $verifiedStatus = ReportStatus::firstOrCreate(['nama_status' => 'Diverifikasi']);

        DB::transaction(function () use ($report, $verifiedStatus, $request) {
            $report->update([
                'id_status' => $verifiedStatus->id_status,
                'id_admin_verifikator' => Auth::id(),
                'waktu_verifikasi' => now(),
                'catatan_verifikasi' => $request->input('catatan_verifikasi'),
            ]);

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $verifiedStatus->id_status,
                'id_pengubah' => Auth::id(),
                'waktu_perubahan' => now(),
                'catatan_perubahan' => $request->filled('catatan_verifikasi')
                    ? 'Laporan diverifikasi oleh admin. Catatan: ' . $request->input('catatan_verifikasi')
                    : 'Laporan telah diverifikasi oleh admin.',
            ]);
        });

        return redirect()->route('admin.verification.index')
            ->with('success', "Laporan {$report->nomor_laporan} ({$report->device->nama_perangkat}) berhasil diverifikasi.");
    }

    /**
     * Menolak laporan kerusakan dan mengubah status menjadi 'Ditolak'.
     * Alasan penolakan bersifat WAJIB diisi.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan_verifikasi' => 'required|string|min:5',
        ], [
            'catatan_verifikasi.required' => 'Catatan verifikasi / alasan penolakan wajib diisi jika laporan ditolak.',
            'catatan_verifikasi.min' => 'Alasan penolakan minimal harus 5 karakter.',
        ]);

        $report = Report::findOrFail($id);
        $rejectedStatus = ReportStatus::firstOrCreate(['nama_status' => 'Ditolak']);

        DB::transaction(function () use ($report, $rejectedStatus, $request) {
            $report->update([
                'id_status' => $rejectedStatus->id_status,
                'id_admin_verifikator' => Auth::id(),
                'waktu_verifikasi' => now(),
                'catatan_verifikasi' => $request->catatan_verifikasi,
            ]);

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $rejectedStatus->id_status,
                'id_pengubah' => Auth::id(),
                'waktu_perubahan' => now(),
                'catatan_perubahan' => 'Laporan ditolak. Alasan: ' . $request->catatan_verifikasi,
            ]);
        });

        return redirect()->route('admin.verification.index')
            ->with('success', "Laporan {$report->nomor_laporan} telah ditolak dengan alasan yang tercatat.");
    }
}
