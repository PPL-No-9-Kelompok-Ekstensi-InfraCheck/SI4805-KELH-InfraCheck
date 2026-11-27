<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Priority;
use App\Models\Report;
use App\Models\StatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PriorityController extends Controller
{
    /**
     * Menampilkan halaman penentuan prioritas penanganan laporan (PBI 14).
     */
    public function index(Request $request)
    {
        $verifiedReports = Report::with(['reporter.role', 'device.deviceType', 'room.building', 'urgency', 'priority', 'status'])
            ->whereHas('status', function ($q) {
                $q->whereIn('nama_status', ['Diverifikasi', 'Ditugaskan', 'Sedang Dikerjakan']);
            })
            ->orderByRaw('CASE WHEN id_prioritas IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('waktu_verifikasi')
            ->orderByDesc('waktu_laporan')
            ->get();

        $withoutPriorityCount = $verifiedReports->whereNull('id_prioritas')->count();

        $selectedId = $request->query('selected', $request->query('id'));
        $selectedReport = null;

        if ($selectedId) {
            $selectedReport = $verifiedReports->firstWhere('id_laporan', (int) $selectedId);
        }

        if (! $selectedReport && $verifiedReports->isNotEmpty()) {
            $selectedReport = $verifiedReports->first();
        }

        // Urutkan pilihan prioritas dari dampak tertinggi ke terendah
        $urutanPrioritas = ['Mendesak' => 1, 'Tinggi' => 2, 'Sedang' => 3, 'Rendah' => 4];
        $priorities = Priority::all()
            ->sortBy(fn (Priority $p) => $urutanPrioritas[$p->nama_prioritas] ?? 99)
            ->values();

        return view('admin.priority.index', compact(
            'verifiedReports',
            'withoutPriorityCount',
            'selectedReport',
            'priorities'
        ));
    }

    /**
     * Menyimpan atau memperbarui prioritas penanganan laporan yang sudah diverifikasi.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_prioritas' => ['required', 'integer', 'exists:priorities,id_prioritas'],
            'alasan_prioritas' => ['nullable', 'string', 'max:1000'],
        ], [
            'id_prioritas.required' => 'Silakan pilih tingkat prioritas penanganan.',
            'id_prioritas.exists' => 'Tingkat prioritas yang dipilih tidak valid.',
            'alasan_prioritas.max' => 'Alasan penentuan prioritas maksimal 1000 karakter.',
        ]);

        $report = Report::with(['status', 'device'])->findOrFail($id);
        $allowedStatuses = ['Diverifikasi', 'Ditugaskan', 'Sedang Dikerjakan'];

        if (! $report->status || ! in_array($report->status->nama_status, $allowedStatuses, true)) {
            return redirect()->route('admin.priority.index')
                ->with('error', 'Prioritas hanya dapat ditetapkan untuk laporan yang sudah diverifikasi dan masih aktif.');
        }

        $priority = Priority::findOrFail($validated['id_prioritas']);
        $alasan = trim((string) ($validated['alasan_prioritas'] ?? ''));

        DB::transaction(function () use ($report, $priority, $alasan) {
            $report->update([
                'id_prioritas' => $priority->id_prioritas,
            ]);

            $catatan = "Prioritas penanganan ditetapkan menjadi {$priority->nama_prioritas}.";
            if ($alasan !== '') {
                $catatan .= " Alasan: {$alasan}";
            }

            StatusHistory::create([
                'id_laporan' => $report->id_laporan,
                'id_status' => $report->id_status,
                'id_pengubah' => Auth::id(),
                'waktu_perubahan' => now(),
                'catatan_perubahan' => $catatan,
            ]);
        });

        $namaPerangkat = $report->device->nama_perangkat ?? 'Perangkat';

        return redirect()->route('admin.priority.index', ['selected' => $report->id_laporan])
            ->with('success', "Prioritas laporan {$report->nomor_laporan} ({$namaPerangkat}) berhasil ditetapkan menjadi {$priority->nama_prioritas}.");
    }
}
